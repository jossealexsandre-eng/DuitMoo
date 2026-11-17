<?php

namespace App\Services;

use App\Models\GoogleSheetConnection;
use App\Models\SyncLog;
use App\Models\Transaction;
use App\Models\TransactionExternalMapping;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleSheetsSyncService
{
    public function isOAuthConfigured(): bool
    {
        $clientId = config('services.google_sheets.client_id') ?? config('services.google.client_id');
        $clientSecret = config('services.google_sheets.client_secret') ?? config('services.google.client_secret');

        return !empty($clientId) && !empty($clientSecret);
    }

    public function getRedirectUri(): string
    {
        return config('services.google_sheets.redirect')
            ?? config('services.google.redirect')
            ?? url('/auth/google/callback');
    }

    /**
     * Extract Spreadsheet ID from either a raw ID or full Google Sheets URL.
     */
    public static function extractSpreadsheetId(string $input): string
    {
        $trimmed = trim($input);
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $trimmed, $matches)) {
            return $matches[1];
        }

        return $trimmed;
    }

    /**
     * Build Google OAuth consent URL with spreadsheets scope.
     */
    public function buildAuthorizationUrl(string $state): string
    {
        $clientId = config('services.google_sheets.client_id') ?? config('services.google.client_id');

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    /**
     * Exchange OAuth authorization code for access and refresh tokens.
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $clientId = config('services.google_sheets.client_id') ?? config('services.google.client_id');
        $clientSecret = config('services.google_sheets.client_secret') ?? config('services.google.client_secret');

        $response = Http::asForm()->timeout(12)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->getRedirectUri(),
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        if (!$response->successful()) {
            $errBody = $response->json('error_description') ?? $response->json('error') ?? ('HTTP ' . $response->status());
            throw new \RuntimeException('Gagal menukar kode OAuth Google: ' . $errBody);
        }

        return $response->json();
    }

    /**
     * Refresh expired OAuth access token when refresh_token exists.
     */
    public function ensureFreshAccessToken(GoogleSheetConnection $connection): string
    {
        if ($connection->access_token && (!$connection->token_expires_at || $connection->token_expires_at->isFuture())) {
            return $connection->access_token;
        }

        if (!$connection->refresh_token) {
            throw new \RuntimeException('Token akses Google Sheets belum ada atau sudah kedaluwarsa. Silakan klik Hubungkan Akun Google.');
        }

        $clientId = config('services.google_sheets.client_id') ?? config('services.google.client_id');
        $clientSecret = config('services.google_sheets.client_secret') ?? config('services.google.client_secret');

        $response = Http::asForm()->timeout(12)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gagal memperbarui token Google OAuth. Silakan hubungkan ulang akun Google Anda.');
        }

        $data = $response->json();
        $connection->update([
            'access_token' => $data['access_token'],
            'token_expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 3500)),
        ]);

        return $data['access_token'];
    }

    /**
     * Ensure target tabs exist in the Google Spreadsheet and return metadata (titles, sheetIds, tables, locale).
     */
    public function ensureTabsAndHeaders(GoogleSheetConnection $connection, string $token): array
    {
        $metaResp = Http::withToken($token)
            ->timeout(12)
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$connection->spreadsheet_id}?fields=properties.locale,sheets(properties(sheetId,title),tables)");

        if (!$metaResp->successful()) {
            $apiMsg = $metaResp->json('error.message') ?? ('HTTP ' . $metaResp->status());
            throw new \RuntimeException('Google Sheets API menolak akses: ' . $apiMsg);
        }

        $locale = (string) $metaResp->json('properties.locale', 'en_US');
        $sheetsMeta = collect($metaResp->json('sheets', []));

        $existingTitles = $sheetsMeta
            ->map(fn ($s) => $s['properties']['title'] ?? '')
            ->filter()
            ->values()
            ->all();

        $sheetIdByTitle = [];
        $tablesByTitle = [];
        foreach ($sheetsMeta as $s) {
            $t = $s['properties']['title'] ?? null;
            if ($t !== null) {
                $sheetIdByTitle[$t] = (int) ($s['properties']['sheetId'] ?? 0);
                $tablesByTitle[$t] = $s['tables'] ?? [];
            }
        }

        $incomeTab = $connection->income_sheet_name ?: 'Pemasukan';
        $expenseTab = $connection->expense_sheet_name ?: 'Pengeluaran';

        $requests = [];

        foreach ([$incomeTab, $expenseTab] as $tabName) {
            if (!in_array($tabName, $existingTitles, true)) {
                $requests[] = [
                    'addSheet' => [
                        'properties' => [
                            'title' => $tabName,
                        ],
                    ],
                ];
                $existingTitles[] = $tabName;
            }
        }

        if (!empty($requests)) {
            $batchResp = Http::withToken($token)
                ->timeout(12)
                ->post("https://sheets.googleapis.com/v4/spreadsheets/{$connection->spreadsheet_id}:batchUpdate", [
                    'requests' => $requests,
                ]);

            if ($batchResp->successful()) {
                foreach ($batchResp->json('replies', []) as $reply) {
                    $added = $reply['addSheet']['properties'] ?? null;
                    if ($added && isset($added['title'])) {
                        $sheetIdByTitle[$added['title']] = (int) ($added['sheetId'] ?? 0);
                    }
                }
            }
        }

        return [
            'titles' => $existingTitles,
            'sheetIds' => $sheetIdByTitle,
            'tables' => $tablesByTitle,
            'locale' => $locale,
        ];
    }

    /**
     * Full Live Recalculation Sync:
     * - Orders transactions chronologically ASCENDING (oldest at top -> newest/current at bottom)
     * - Assigns sequential ID Sistem starting from duitmoo-1, duitmoo-2, ..., duitmoo-N
     * - Adjusts any Google Sheets Table boundary so summary rows at the bottom NEVER trigger DATE or DROPDOWN validation errors.
     */
    public function syncAllUserTransactions(int $userId): array
    {
        $connection = GoogleSheetConnection::where('user_id', $userId)->first();
        if (!$connection || !$connection->spreadsheet_id) {
            return ['synced' => 0, 'error' => 'Konfigurasi Spreadsheet belum disimpan.'];
        }

        if (!$this->isOAuthConfigured() || !$connection->access_token) {
            return ['synced' => 0, 'error' => 'OAuth Google belum terhubung.'];
        }

        try {
            $token = $this->ensureFreshAccessToken($connection);
            $meta = $this->ensureTabsAndHeaders($connection, $token);
            $existingTitles = $meta['titles'];
            $sheetIds = $meta['sheetIds'];
            $tablesByTitle = $meta['tables'] ?? [];
            $locale = strtolower($meta['locale'] ?? 'en_us');

            // Indonesian & European locales in Google Sheets use semicolon (;) as formula separator
            $sep = (str_starts_with($locale, 'in') || str_starts_with($locale, 'id') || str_starts_with($locale, 'de') || str_starts_with($locale, 'fr'))
                ? ';'
                : ',';

            $user = User::find($userId);

            // Chronological ASCENDING order: oldest first (duitmoo-1 at top), newest/current at the bottom!
            $transactions = Transaction::where('user_id', $userId)
                ->with('category')
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->values();

            $totalIncome = (int) $transactions->where('type', 'income')->sum('amount');
            $totalExpense = (int) $transactions->where('type', 'expense')->sum('amount');
            $profitIncome = $totalIncome - $totalExpense;
            $profitMargin = $totalIncome > 0 ? round(($profitIncome / $totalIncome) * 100, 1) : 0.0;
            $isProfit = $profitIncome >= 0;

            $firstSheet = $existingTitles[0] ?? 'Sheet1';
            $incomeTab = $connection->income_sheet_name ?: 'Pemasukan';
            $expenseTab = $connection->expense_sheet_name ?: 'Pengeluaran';

            // Data rows start at Row 10 (1-based) = index 9 (0-based)
            $startDataRow = 10;
            $txCount = max(1, $transactions->count());
            $lastDataRow = $startDataRow + $txCount - 1; // 1-based last transaction row (also 0-based exclusive endRowIndex for Table!)

            // Before writing values, if the first sheet has a Google Sheets Table (e.g. "DANA"),
            // resize the Table range so it strictly ends at $lastDataRow (excluding the bottom summary rows!)
            // and clean its column validation rules so no "Tidak valid" triangles ever appear.
            if (isset($sheetIds[$firstSheet])) {
                $this->sanitizeSheetTableAndValidations(
                    $connection->spreadsheet_id,
                    $token,
                    $sheetIds[$firstSheet],
                    $tablesByTitle[$firstSheet] ?? [],
                    $lastDataRow
                );
            }

            $fTotalIncome = "=SUMIF(B{$startDataRow}:B{$lastDataRow}{$sep}\"Pemasukan\"{$sep}E{$startDataRow}:E{$lastDataRow})";
            $fTotalExpense = "=SUMIF(B{$startDataRow}:B{$lastDataRow}{$sep}\"Pengeluaran\"{$sep}E{$startDataRow}:E{$lastDataRow})";
            $fCountIncome = "=COUNTIF(B{$startDataRow}:B{$lastDataRow}{$sep}\"Pemasukan\")&\" Transaksi\"";
            $fCountExpense = "=COUNTIF(B{$startDataRow}:B{$lastDataRow}{$sep}\"Pengeluaran\")&\" Transaksi\"";
            $fCountTotal = "=COUNTA(B{$startDataRow}:B{$lastDataRow})&\" Transaksi\"";
            $fNeeds = "=SUMIFS(E{$startDataRow}:E{$lastDataRow}{$sep}B{$startDataRow}:B{$lastDataRow}{$sep}\"Pengeluaran\"{$sep}C{$startDataRow}:C{$lastDataRow}{$sep}\"Kebutuhan\")";
            $fWants = "=SUMIFS(E{$startDataRow}:E{$lastDataRow}{$sep}B{$startDataRow}:B{$lastDataRow}{$sep}\"Pengeluaran\"{$sep}C{$startDataRow}:C{$lastDataRow}{$sep}\"Keinginan\")";
            $fEmerg = "=SUMIFS(E{$startDataRow}:E{$lastDataRow}{$sep}B{$startDataRow}:B{$lastDataRow}{$sep}\"Pengeluaran\"{$sep}C{$startDataRow}:C{$lastDataRow}{$sep}\"Dana Darurat\")";

            // 1. Build Main Executive Dashboard & Ledger on Sheet1
            $mainRows = [
                ['LAPORAN EKSEKUTIF KEUANGAN & PROFITABILITAS DUITMOO', '', '', '', 'Diperbarui Otomatis:', now()->format('Y-m-d H:i:s'), ''],
                ['Pemilik Akun:', $user ? $user->name : 'Pengguna DuitMoo', '', '', '', '', ''],
                ['', '', '', '', '', '', ''],
                ['TOTAL PEMASUKAN (INCOME)', $fTotalIncome, '', 'Pos Kebutuhan (Needs)', $fNeeds, '', ''],
                ['TOTAL PENGELUARAN (EXPENSE)', $fTotalExpense, '', 'Pos Keinginan (Wants)', $fWants, '', ''],
                ['PROFIT / NET INCOME (LABA BERSIH)', '=B4-B5', '', 'Pos Dana Darurat', $fEmerg, '', ''],
                ['STATUS PROFITABILITAS', $isProfit ? 'SURPLUS / PROFIT (+)' : 'DEFISIT (-)', '', 'Rasio Profit / Saving Rate', $profitMargin . '%', '', ''],
                ['', '', '', '', '', '', ''],
                ['Tanggal', 'Jenis', 'Pos Anggaran', 'Kategori', 'Nominal (Rp)', 'Keterangan', 'ID Sistem'],
            ];

            foreach ($transactions as $idx => $tx) {
                $seqCode = 'duitmoo-' . ($idx + 1);
                $mainRows[] = $this->buildUnifiedRowPayload($tx, $seqCode);
            }

            if ($transactions->isEmpty()) {
                $mainRows[] = [now()->format('Y-m-d'), 'Pemasukan', 'Pemasukan', 'Belum ada transaksi', 0, 'Catat transaksi di aplikasi DuitMoo agar otomatis terhitung di sini', 'duitmoo-1'];
            }

            // Bottom Footer Summary Rows right below the transaction table
            $footerIncRowIdx = count($mainRows) + 2; // 1-based row number after 1 blank separator row
            $footerExpRowIdx = $footerIncRowIdx + 1;

            $mainRows[] = ['', '', '', '', '', '', ''];
            $mainRows[] = [
                'TOTAL PEMASUKAN (INCOME)',
                'Pemasukan',
                'Pemasukan',
                'Total Seluruh Pemasukan',
                $fTotalIncome,
                'Total dana masuk terhitung otomatis',
                $fCountIncome,
            ];
            $mainRows[] = [
                'TOTAL PENGELUARAN (EXPENSE)',
                'Pengeluaran',
                'Kebutuhan',
                'Total Seluruh Pengeluaran',
                $fTotalExpense,
                'Total dana keluar terhitung otomatis',
                $fCountExpense,
            ];
            $mainRows[] = [
                'PROFIT / NET INCOME (LABA BERSIH)',
                $isProfit ? 'SURPLUS / PROFIT (+)' : 'DEFISIT (-)',
                'Rasio Profit: ' . $profitMargin . '%',
                'Selisih Pemasukan - Pengeluaran',
                "=E{$footerIncRowIdx}-E{$footerExpRowIdx}",
                'Laba bersih / sisa arus kas periode berjalan',
                $fCountTotal,
            ];

            // Clear and write Main Sheet
            $clearMainRange = urlencode($firstSheet . '!A1:G900');
            Http::withToken($token)->timeout(12)->post(
                "https://sheets.googleapis.com/v4/spreadsheets/{$connection->spreadsheet_id}/values/{$clearMainRange}:clear"
            );

            $writeMainRange = urlencode($firstSheet . '!A1:G' . count($mainRows));
            $mainResp = Http::withToken($token)->timeout(15)->put(
                "https://sheets.googleapis.com/v4/spreadsheets/{$connection->spreadsheet_id}/values/{$writeMainRange}?valueInputOption=USER_ENTERED",
                ['values' => $mainRows]
            );

            if (!$mainResp->successful()) {
                throw new \RuntimeException('Gagal menulis ke sheet utama: ' . ($mainResp->json('error.message') ?? $mainResp->status()));
            }

            // Apply visual formatting & clear any stray validations on the footer rows
            if (isset($sheetIds[$firstSheet])) {
                $this->applyMainSheetFormatting(
                    $connection->spreadsheet_id,
                    $token,
                    $sheetIds[$firstSheet],
                    count($mainRows),
                    $footerIncRowIdx,
                    $lastDataRow
                );
            }

            // 2. Rebuild Pemasukan Tab (Chronological ASC + sequential ID duitmoo-1..N)
            if ($incomeTab !== $firstSheet) {
                $incList = $transactions->where('type', 'income')->values();
                $incStartRow = 5;
                $incLastRow = max($incStartRow, $incStartRow + $incList->count() - 1);

                $incSheetRows = [
                    ['REKAPITULASI PEMASUKAN DUITMOO', '', 'TOTAL PEMASUKAN (Rp):', "=SUM(D{$incStartRow}:D{$incLastRow})", 'Diperbarui:', now()->format('Y-m-d H:i')],
                    ['Total Pengeluaran:', $totalExpense, 'PROFIT / NET INCOME:', $profitIncome, 'Rasio Profit:', $profitMargin . '%'],
                    ['', '', '', '', '', ''],
                    ['Tanggal', 'Jenis', 'Kategori / Sumber', 'Nominal (Rp)', 'Keterangan', 'ID Sistem'],
                ];
                foreach ($incList as $idx => $tx) {
                    $incSheetRows[] = $this->buildRowPayload($tx, 'duitmoo-' . ($idx + 1));
                }
                if ($incList->isEmpty()) {
                    $incSheetRows[] = [now()->format('Y-m-d'), 'Pemasukan', '-', 0, 'Belum ada pemasukan', 'duitmoo-1'];
                }
                $incSheetRows[] = ['', '', '', '', '', ''];
                $incSheetRows[] = ['TOTAL SELURUH PEMASUKAN', 'Pemasukan', 'Semua Kategori', "=SUM(D{$incStartRow}:D{$incLastRow})", 'Total Pemasukan Otomatis', $incList->count() . ' Transaksi'];
                $incSheetRows[] = ['TOTAL SELURUH PENGELUARAN', 'Pengeluaran', 'Semua Pos', $totalExpense, 'Total Pengeluaran', $transactions->where('type', 'expense')->count() . ' Transaksi'];
                $incSheetRows[] = ['PROFIT / NET INCOME (LABA BERSIH)', $isProfit ? 'SURPLUS (+)' : 'DEFISIT (-)', 'Rasio: ' . $profitMargin . '%', $profitIncome, 'Pemasukan - Pengeluaran', $transactions->count() . ' Transaksi'];

                $clearInc = urlencode($incomeTab . '!A1:F900');
                Http::withToken($token)->timeout(10)->post("https://sheets.googleapis.com/v4/spreadsheets/{$connection->spreadsheet_id}/values/{$clearInc}:clear");

                $writeInc = urlencode($incomeTab . '!A1:F' . count($incSheetRows));
                Http::withToken($token)->timeout(15)->put(
                    "https://sheets.googleapis.com/v4/spreadsheets/{$connection->spreadsheet_id}/values/{$writeInc}?valueInputOption=USER_ENTERED",
                    ['values' => $incSheetRows]
                );

                if (isset($sheetIds[$incomeTab])) {
                    $this->applySplitTabFormatting(
                        $connection->spreadsheet_id,
                        $token,
                        $sheetIds[$incomeTab],
                        count($incSheetRows),
                        3,
                        $incLastRow,
                        $incLastRow + 1
                    );
                }
            }

            // 3. Rebuild Pengeluaran Tab (Chronological ASC + sequential ID duitmoo-1..N)
            if ($expenseTab !== $firstSheet) {
                $expList = $transactions->where('type', 'expense')->values();
                $expStartRow = 5;
                $expLastRow = max($expStartRow, $expStartRow + $expList->count() - 1);

                $expSheetRows = [
                    ['REKAPITULASI PENGELUARAN DUITMOO', '', '', 'TOTAL PENGELUARAN (Rp):', "=SUM(E{$expStartRow}:E{$expLastRow})", 'Diperbarui:', now()->format('Y-m-d H:i')],
                    ['Total Pemasukan:', $totalIncome, '', 'PROFIT / NET INCOME:', $profitIncome, 'Rasio Profit:', $profitMargin . '%'],
                    ['', '', '', '', '', '', ''],
                    ['Tanggal', 'Jenis', 'Pos Anggaran', 'Kategori', 'Nominal (Rp)', 'Keterangan', 'ID Sistem'],
                ];
                foreach ($expList as $idx => $tx) {
                    $expSheetRows[] = $this->buildRowPayload($tx, 'duitmoo-' . ($idx + 1));
                }
                if ($expList->isEmpty()) {
                    $expSheetRows[] = [now()->format('Y-m-d'), 'Pengeluaran', 'Kebutuhan', '-', 0, 'Belum ada pengeluaran', 'duitmoo-1'];
                }
                $expSheetRows[] = ['', '', '', '', '', '', ''];
                $expSheetRows[] = ['TOTAL SELURUH PENGELUARAN', 'Pengeluaran', 'Semua Pos', 'Semua Kategori', "=SUM(E{$expStartRow}:E{$expLastRow})", 'Total Pengeluaran Otomatis', $expList->count() . ' Transaksi'];
                $expSheetRows[] = ['TOTAL SELURUH PEMASUKAN', 'Pemasukan', '-', 'Semua Sumber', $totalIncome, 'Total Pemasukan', $transactions->where('type', 'income')->count() . ' Transaksi'];
                $expSheetRows[] = ['PROFIT / NET INCOME (LABA BERSIH)', $isProfit ? 'SURPLUS (+)' : 'DEFISIT (-)', 'Rasio: ' . $profitMargin . '%', 'Pemasukan - Pengeluaran', $profitIncome, 'Selisih Bersih', $transactions->count() . ' Transaksi'];

                $clearExp = urlencode($expenseTab . '!A1:G900');
                Http::withToken($token)->timeout(10)->post("https://sheets.googleapis.com/v4/spreadsheets/{$connection->spreadsheet_id}/values/{$clearExp}:clear");

                $writeExp = urlencode($expenseTab . '!A1:G' . count($expSheetRows));
                Http::withToken($token)->timeout(15)->put(
                    "https://sheets.googleapis.com/v4/spreadsheets/{$connection->spreadsheet_id}/values/{$writeExp}?valueInputOption=USER_ENTERED",
                    ['values' => $expSheetRows]
                );

                if (isset($sheetIds[$expenseTab])) {
                    $this->applySplitTabFormatting(
                        $connection->spreadsheet_id,
                        $token,
                        $sheetIds[$expenseTab],
                        count($expSheetRows),
                        3,
                        $expLastRow,
                        $expLastRow + 1
                    );
                }
            }

            foreach ($transactions as $idx => $tx) {
                $tabTarget = $tx->type === 'income' ? $incomeTab : $expenseTab;
                $seqCode = 'duitmoo-' . ($idx + 1);
                $this->recordSuccessfulSync($connection, $tx, $tabTarget, $this->buildRowPayload($tx, $seqCode));
            }

            $connection->update([
                'is_active' => true,
                'last_synced_at' => now(),
                'last_error' => null,
            ]);

            return ['synced' => $transactions->count(), 'total' => $transactions->count(), 'error' => null];
        } catch (\Throwable $e) {
            $connection->update([
                'last_error' => Str::limit($e->getMessage(), 250),
            ]);

            return ['synced' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Adjust Google Sheets structured Table range so it ONLY covers the transaction rows (startRowIndex 8 .. endRowIndex $lastDataRow)
     * and never traps the bottom summary rows inside DATE / DROPDOWN column validation.
     */
    protected function sanitizeSheetTableAndValidations(
        string $spreadsheetId,
        string $token,
        int $sheetId,
        array $tables,
        int $lastDataRow
    ): void {
        $requests = [];

        foreach ($tables as $table) {
            $tableId = $table['tableId'] ?? null;
            if (!$tableId) {
                continue;
            }

            $requests[] = [
                'updateTable' => [
                    'table' => [
                        'tableId' => $tableId,
                        'name' => $table['name'] ?? 'DANA',
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => 8,
                            'endRowIndex' => $lastDataRow,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 7,
                        ],
                        'columnProperties' => [
                            [
                                'columnIndex' => 0,
                                'columnName' => 'Tanggal',
                                'columnType' => 'DATE',
                            ],
                            [
                                'columnIndex' => 1,
                                'columnName' => 'Jenis',
                                'columnType' => 'DROPDOWN',
                                'dataValidationRule' => [
                                    'condition' => [
                                        'type' => 'ONE_OF_LIST',
                                        'values' => [
                                            ['userEnteredValue' => 'Pemasukan'],
                                            ['userEnteredValue' => 'Pengeluaran'],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'columnIndex' => 2,
                                'columnName' => 'Pos Anggaran',
                                'columnType' => 'DROPDOWN',
                                'dataValidationRule' => [
                                    'condition' => [
                                        'type' => 'ONE_OF_LIST',
                                        'values' => [
                                            ['userEnteredValue' => 'Kebutuhan'],
                                            ['userEnteredValue' => 'Keinginan'],
                                            ['userEnteredValue' => 'Dana Darurat'],
                                            ['userEnteredValue' => 'Pemasukan'],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'columnIndex' => 3,
                                'columnName' => 'Kategori',
                            ],
                            [
                                'columnIndex' => 4,
                                'columnName' => 'Nominal (Rp)',
                                'columnType' => 'CURRENCY',
                            ],
                            [
                                'columnIndex' => 5,
                                'columnName' => 'Keterangan',
                            ],
                            [
                                'columnIndex' => 6,
                                'columnName' => 'ID Sistem',
                            ],
                        ],
                    ],
                    'fields' => 'range,columnProperties',
                ],
            ];
        }

        // Clear any lingering data validation rules below the transaction table
        $requests[] = [
            'setDataValidation' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $lastDataRow,
                    'endRowIndex' => $lastDataRow + 30,
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 7,
                ],
            ],
        ];

        if (!empty($requests)) {
            Http::withToken($token)->timeout(12)->post(
                "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate",
                ['requests' => $requests]
            );
        }
    }

    /**
     * Format the main sheet (bold headers, Rupiah number formatting, colored summary & footer total rows,
     * and remove any data validation on the footer rows).
     */
    protected function applyMainSheetFormatting(
        string $spreadsheetId,
        string $token,
        int $sheetId,
        int $totalRows,
        int $footerIncRow1Based,
        int $lastDataRow
    ): void {
        $footerStart0 = $footerIncRow1Based - 1; // 0-indexed row for TOTAL PEMASUKAN at bottom

        $requests = [
            // 1. Top Executive Title (Row 1, index 0): Always BOLD
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 0,
                        'endRowIndex' => 1,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'textFormat' => [
                                'bold' => true,
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.textFormat.bold',
                ],
            ],
            // 2. Top Summary Cards (Rows 4..7, index 3..7, cols A..E): Always BOLD
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 3,
                        'endRowIndex' => 7,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 5,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'textFormat' => [
                                'bold' => true,
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.textFormat.bold',
                ],
            ],
            // 3. Table Header Row (Row 9, index 8): Always BOLD
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 8,
                        'endRowIndex' => 9,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'textFormat' => [
                                'bold' => true,
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.textFormat.bold',
                ],
            ],
            // 4. All transaction data rows: Always REGULAR (bold: false) and dark charcoal text
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 9,
                        'endRowIndex' => $lastDataRow,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'textFormat' => [
                                'bold' => false,
                                'foregroundColor' => [
                                    'red' => 0.15,
                                    'green' => 0.20,
                                    'blue' => 0.22,
                                ],
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.textFormat(bold,foregroundColor)',
                ],
            ],
            // 5. Clear lingering custom background colors on data rows so table zebra banding displays cleanly
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 9,
                        'endRowIndex' => $lastDataRow,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 6,
                    ],
                    'cell' => (object)[],
                    'fields' => 'userEnteredFormat.backgroundColor',
                ],
            ],
            // Ensure zero data validation rules on footer summary rows so no "Tidak valid" warning ever appears
            [
                'setDataValidation' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $lastDataRow,
                        'endRowIndex' => $totalRows + 10,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                ],
            ],
            // Reset Column A on footer rows to plain text format (not DATE)
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $lastDataRow,
                        'endRowIndex' => $totalRows + 5,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 4,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'numberFormat' => [
                                'type' => 'TEXT',
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.numberFormat',
                ],
            ],
            // Format Column B & Column E in Top Summary (rows 3..6, 0-indexed) as Currency Rp
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 3,
                        'endRowIndex' => 6,
                        'startColumnIndex' => 1,
                        'endColumnIndex' => 5,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'numberFormat' => [
                                'type' => 'CURRENCY',
                                'pattern' => '"Rp "#,##0',
                            ],
                            'textFormat' => ['bold' => true],
                        ],
                    ],
                    'fields' => 'userEnteredFormat(numberFormat,textFormat.bold)',
                ],
            ],
            // Format Column E in Data + Bottom Totals (row 9..totalRows) as Currency Rp
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 9,
                        'endRowIndex' => $totalRows,
                        'startColumnIndex' => 4,
                        'endColumnIndex' => 5,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'numberFormat' => [
                                'type' => 'CURRENCY',
                                'pattern' => '"Rp "#,##0',
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.numberFormat',
                ],
            ],
            // Bottom Footer Row 1: TOTAL PEMASUKAN (Soft Green #D8F5E5, Bold)
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $footerStart0,
                        'endRowIndex' => $footerStart0 + 1,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColor' => ['red' => 0.85, 'green' => 0.96, 'blue' => 0.90],
                            'textFormat' => ['bold' => true],
                        ],
                    ],
                    'fields' => 'userEnteredFormat(backgroundColor,textFormat.bold)',
                ],
            ],
            // Bottom Footer Row 2: TOTAL PENGELUARAN (Soft Red #FFE2DE, Bold)
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $footerStart0 + 1,
                        'endRowIndex' => $footerStart0 + 2,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColor' => ['red' => 1.0, 'green' => 0.89, 'blue' => 0.87],
                            'textFormat' => ['bold' => true],
                        ],
                    ],
                    'fields' => 'userEnteredFormat(backgroundColor,textFormat.bold)',
                ],
            ],
            // Bottom Footer Row 3: PROFIT / NET INCOME (Gold #FFEAA0, Bold)
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $footerStart0 + 2,
                        'endRowIndex' => $footerStart0 + 3,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColor' => ['red' => 1.0, 'green' => 0.92, 'blue' => 0.63],
                            'textFormat' => ['bold' => true],
                        ],
                    ],
                    'fields' => 'userEnteredFormat(backgroundColor,textFormat.bold)',
                ],
            ],
            // Auto-resize columns A to G
            [
                'autoResizeDimensions' => [
                    'dimensions' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'COLUMNS',
                        'startIndex' => 0,
                        'endIndex' => 7,
                    ],
                ],
            ],
        ];

        Http::withToken($token)->timeout(12)->post(
            "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate",
            ['requests' => $requests]
        );
    }

    /**
     * Standardize typography and formatting on separate Pemasukan & Pengeluaran tabs
     * (Bold headers, clean regular text on all transaction rows, bold footer summary).
     */
    protected function applySplitTabFormatting(
        string $spreadsheetId,
        string $token,
        int $sheetId,
        int $totalRows,
        int $headerRowIndex,
        int $lastDataRow,
        int $footerStartRowIndex
    ): void {
        $requests = [
            // 1. Table Header Row (index 3): Bold
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $headerRowIndex,
                        'endRowIndex' => $headerRowIndex + 1,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'textFormat' => ['bold' => true],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.textFormat.bold',
                ],
            ],
            // 2. All Data Rows: Always REGULAR (bold: false) and dark charcoal text
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $headerRowIndex + 1,
                        'endRowIndex' => $lastDataRow,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'textFormat' => [
                                'bold' => false,
                                'foregroundColor' => [
                                    'red' => 0.15,
                                    'green' => 0.20,
                                    'blue' => 0.22,
                                ],
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.textFormat(bold,foregroundColor)',
                ],
            ],
            // 3. Footer Summary Rows: Always BOLD
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $footerStartRowIndex,
                        'endRowIndex' => $totalRows,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'textFormat' => ['bold' => true],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.textFormat.bold',
                ],
            ],
            // 4. Auto-resize columns
            [
                'autoResizeDimensions' => [
                    'dimensions' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'COLUMNS',
                        'startIndex' => 0,
                        'endIndex' => 7,
                    ],
                ],
            ],
        ];

        Http::withToken($token)->timeout(12)->post(
            "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate",
            ['requests' => $requests]
        );
    }

    protected function recordSuccessfulSync(GoogleSheetConnection $connection, Transaction $tx, string $sheetName, array $rowValues): void
    {
        $externalKey = 'dm-' . $tx->id . '-' . Str::random(8);
        TransactionExternalMapping::updateOrCreate(
            [
                'google_sheet_connection_id' => $connection->id,
                'transaction_id' => $tx->id,
            ],
            [
                'user_id' => $tx->user_id,
                'external_row_key' => $externalKey,
                'sheet_name' => $sheetName,
                'last_synced_hash' => hash('sha256', json_encode($rowValues)),
            ]
        );

        $idempotencyKey = sprintf('tx-%d-conn-%d', $tx->id, $connection->id);
        SyncLog::updateOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'user_id' => $tx->user_id,
                'google_sheet_connection_id' => $connection->id,
                'transaction_id' => $tx->id,
                'direction' => 'push',
                'status' => 'success',
                'error_message' => null,
                'attempts' => 1,
                'synced_at' => now(),
            ]
        );
    }

    /**
     * Trigger full spreadsheet recalculation whenever a transaction is created, updated, or deleted.
     */
    public function syncNewTransaction(Transaction $transaction): void
    {
        $this->syncUserSpreadsheetIfConnected($transaction->user_id, $transaction);
    }

    public function syncUserSpreadsheetIfConnected(int $userId, ?Transaction $contextTx = null): void
    {
        $connection = GoogleSheetConnection::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$connection || !$connection->spreadsheet_id) {
            return;
        }

        if (!$this->isOAuthConfigured() || !$connection->access_token) {
            if ($contextTx) {
                $idempotencyKey = sprintf('tx-%d-conn-%d', $contextTx->id, $connection->id);
                SyncLog::updateOrCreate(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'user_id' => $userId,
                        'google_sheet_connection_id' => $connection->id,
                        'transaction_id' => $contextTx->id,
                        'direction' => 'push',
                        'status' => 'failed',
                        'error_message' => 'OAuth Google belum dihubungkan.',
                        'attempts' => 1,
                    ]
                );
            }
            return;
        }

        $this->syncAllUserTransactions($userId);
    }

    protected function buildUnifiedRowPayload(Transaction $transaction, ?string $seqId = null): array
    {
        $bucketLabel = match ($transaction->bucket) {
            'needs' => 'Kebutuhan',
            'wants' => 'Keinginan',
            'emergency' => 'Dana Darurat',
            default => 'Pemasukan',
        };

        return [
            $transaction->transaction_date->format('Y-m-d'),
            $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            $bucketLabel,
            $transaction->category?->name ?? ($transaction->income_source ?: 'Lainnya'),
            (int) $transaction->amount,
            $transaction->description ?? ($transaction->notes ?? ''),
            $seqId ?? ('duitmoo-' . $transaction->id),
        ];
    }

    protected function buildRowPayload(Transaction $transaction, ?string $seqId = null): array
    {
        $bucketLabel = match ($transaction->bucket) {
            'needs' => 'Kebutuhan',
            'wants' => 'Keinginan',
            'emergency' => 'Dana Darurat',
            default => '-',
        };

        if ($transaction->type === 'income') {
            return [
                $transaction->transaction_date->format('Y-m-d'),
                'Pemasukan',
                $transaction->category?->name ?? ($transaction->income_source ?: 'Pemasukan'),
                (int) $transaction->amount,
                $transaction->description ?? ($transaction->notes ?? ''),
                $seqId ?? ('duitmoo-' . $transaction->id),
            ];
        }

        return [
            $transaction->transaction_date->format('Y-m-d'),
            'Pengeluaran',
            $bucketLabel,
            $transaction->category?->name ?? 'Lainnya',
            (int) $transaction->amount,
            $transaction->description ?? ($transaction->notes ?? ''),
            $seqId ?? ('duitmoo-' . $transaction->id),
        ];
    }
}
