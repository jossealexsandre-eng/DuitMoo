<?php

namespace App\Http\Controllers;

use App\Models\GoogleSheetConnection;
use App\Models\SyncLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GoogleSheetsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GoogleSheetsController extends Controller
{
    public function index(GoogleSheetsSyncService $sheetsService): View
    {
        $user = Auth::user();
        $connection = GoogleSheetConnection::where('user_id', $user->id)->first();
        $recentLogs = SyncLog::where('user_id', $user->id)
            ->with('transaction')
            ->orderByDesc('created_at')
            ->take(15)
            ->get();

        $transactions = Transaction::where('user_id', $user->id)
            ->with('category')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->values();

        $totalIncome = (int) $transactions->where('type', 'income')->sum('amount');
        $totalExpense = (int) $transactions->where('type', 'expense')->sum('amount');
        $profitIncome = $totalIncome - $totalExpense;
        $profitMarginPercent = $totalIncome > 0 ? round(($profitIncome / $totalIncome) * 100, 1) : 0.0;

        $needsSpent = (int) $transactions->where('type', 'expense')->where('bucket', 'needs')->sum('amount');
        $wantsSpent = (int) $transactions->where('type', 'expense')->where('bucket', 'wants')->sum('amount');
        $emergencySpent = (int) $transactions->where('type', 'expense')->where('bucket', 'emergency')->sum('amount');

        $lastDataRow = max(10, 10 + $transactions->count() - 1);
        $footerIncRow = $lastDataRow + 2;
        $footerExpRow = $footerIncRow + 1;

        // Build Executive Report TSV with Live Google Sheets Formulas (Indonesian locale ';' separator)
        $tsvRows = [
            ['LAPORAN EKSEKUTIF KEUANGAN & PROFITABILITAS DUITMOO', '', '', '', 'Diperbarui:', now()->format('Y-m-d H:i'), ''],
            ['Pemilik Akun:', $user->name, '', '', 'Total Transaksi:', $transactions->count(), ''],
            ['', '', '', '', '', '', ''],
            ['TOTAL PEMASUKAN (INCOME)', "=SUMIF(B10:B{$lastDataRow}; \"Pemasukan\"; E10:E{$lastDataRow})", '', 'Pos Kebutuhan (Needs)', "=SUMIFS(E10:E{$lastDataRow}; B10:B{$lastDataRow}; \"Pengeluaran\"; C10:C{$lastDataRow}; \"Kebutuhan\")", '', ''],
            ['TOTAL PENGELUARAN (EXPENSE)', "=SUMIF(B10:B{$lastDataRow}; \"Pengeluaran\"; E10:E{$lastDataRow})", '', 'Pos Keinginan (Wants)', "=SUMIFS(E10:E{$lastDataRow}; B10:B{$lastDataRow}; \"Pengeluaran\"; C10:C{$lastDataRow}; \"Keinginan\")", '', ''],
            ['PROFIT / NET INCOME (LABA BERSIH)', '=B4-B5', '', 'Pos Dana Darurat', "=SUMIFS(E10:E{$lastDataRow}; B10:B{$lastDataRow}; \"Pengeluaran\"; C10:C{$lastDataRow}; \"Dana Darurat\")", '', ''],
            ['STATUS PROFITABILITAS', $profitIncome >= 0 ? 'SURPLUS / PROFIT (+)' : 'DEFISIT (-)', '', 'Rasio Profit / Saving Rate', $profitMarginPercent . '%', '', ''],
            ['', '', '', '', '', '', ''],
            ['Tanggal', 'Jenis', 'Pos Anggaran', 'Kategori', 'Nominal (Rp)', 'Keterangan', 'ID Sistem'],
        ];

        foreach ($transactions as $idx => $tx) {
            $pos = match ($tx->bucket) {
                'needs' => 'Kebutuhan',
                'wants' => 'Keinginan',
                'emergency' => 'Dana Darurat',
                default => 'Pemasukan',
            };
            $tsvRows[] = [
                $tx->transaction_date->format('Y-m-d'),
                $tx->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                $pos,
                $tx->category?->name ?? ($tx->income_source ?: 'Lainnya'),
                (int) $tx->amount,
                str_replace(["\t", "\r", "\n"], ' ', (string) ($tx->description ?? $tx->notes ?? '-')),
                'duitmoo-' . ($idx + 1),
            ];
        }

        $incCount = $transactions->where('type', 'income')->count();
        $expCount = $transactions->where('type', 'expense')->count();

        $tsvRows[] = ['', '', '', '', '', '', ''];
        $tsvRows[] = ['TOTAL PEMASUKAN (INCOME)', 'Pemasukan', 'Semua Sumber', 'Total Dana Masuk', "=SUMIF(B10:B{$lastDataRow}; \"Pemasukan\"; E10:E{$lastDataRow})", 'Total Pemasukan Otomatis', $incCount . ' Transaksi'];
        $tsvRows[] = ['TOTAL PENGELUARAN (EXPENSE)', 'Pengeluaran', 'Semua Pos', 'Total Dana Keluar', "=SUMIF(B10:B{$lastDataRow}; \"Pengeluaran\"; E10:E{$lastDataRow})", 'Total Pengeluaran Otomatis', $expCount . ' Transaksi'];
        $tsvRows[] = ['PROFIT / NET INCOME (LABA BERSIH)', $profitIncome >= 0 ? 'SURPLUS / PROFIT (+)' : 'DEFISIT (-)', 'Rasio Profit: ' . $profitMarginPercent . '%', 'Pemasukan - Pengeluaran', "=E{$footerIncRow}-E{$footerExpRow}", 'Selisih Bersih', $transactions->count() . ' Transaksi'];

        $tsvLines = array_map(fn (array $r) => implode("\t", $r), $tsvRows);

        // Extract Google Cloud API activation link if service disabled error occurred
        $apiEnableUrl = null;
        if ($connection && $connection->last_error && str_contains($connection->last_error, 'sheets.googleapis.com')) {
            if (preg_match('#https://console\.developers\.google\.com/apis/api/sheets\.googleapis\.com/overview\?project=[0-9]+#', $connection->last_error, $m)) {
                $apiEnableUrl = $m[0];
            } else {
                $apiEnableUrl = 'https://console.developers.google.com/apis/library/sheets.googleapis.com';
            }
        }

        return view('settings.google-sheets', [
            'connection' => $connection,
            'recentLogs' => $recentLogs,
            'oauthConfigured' => $sheetsService->isOAuthConfigured(),
            'tsvClipboard' => implode("\n", $tsvLines),
            'transactions' => $transactions,
            'totalUserTransactions' => $transactions->count(),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'profitIncome' => $profitIncome,
            'profitMarginPercent' => $profitMarginPercent,
            'needsSpent' => $needsSpent,
            'wantsSpent' => $wantsSpent,
            'emergencySpent' => $emergencySpent,
            'apiEnableUrl' => $apiEnableUrl,
        ]);
    }

    public function saveConfig(Request $request, GoogleSheetsSyncService $sheetsService): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'spreadsheet_input' => ['required', 'string', 'max:500'],
            'spreadsheet_name' => ['nullable', 'string', 'max:150'],
            'income_sheet_name' => ['required', 'string', 'max:80'],
            'expense_sheet_name' => ['required', 'string', 'max:80'],
            'income_columns' => ['nullable', 'string', 'max:255'],
            'expense_columns' => ['nullable', 'string', 'max:255'],
        ]);

        $spreadsheetId = GoogleSheetsSyncService::extractSpreadsheetId($validated['spreadsheet_input']);
        if (strlen($spreadsheetId) < 10) {
            return back()->withErrors(['spreadsheet_input' => 'Spreadsheet ID atau URL Google Sheets tidak valid.'])->withInput();
        }

        $connection = GoogleSheetConnection::firstOrNew(['user_id' => $user->id]);
        $connection->fill([
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_name' => $validated['spreadsheet_name'] ?: 'Keuangan Pribadi',
            'income_sheet_name' => $validated['income_sheet_name'],
            'expense_sheet_name' => $validated['expense_sheet_name'],
            'income_mapping' => ['header_preview' => $validated['income_columns'] ?? 'Tanggal, Jenis, Kategori, Nominal, Catatan, ID'],
            'expense_mapping' => ['header_preview' => $validated['expense_columns'] ?? 'Tanggal, Jenis, Pos, Kategori, Nominal, Catatan, ID'],
            'is_active' => true,
        ]);
        $connection->save();

        if (!empty($connection->access_token)) {
            $result = $sheetsService->syncAllUserTransactions($user->id);
            if (empty($result['error'])) {
                return back()->with('success', "Konfigurasi disimpan & seluruh laporan ({$result['total']} transaksi + kalkulasi Profit Income) otomatis diperbarui di Google Sheets!");
            }

            return back()->with('warning', 'Konfigurasi Spreadsheet berhasil disimpan. Aktifkan Google Sheets API pada panel petunjuk di bawah agar sinkronisasi berjalan otomatis.');
        }

        return back()->with('success', 'Konfigurasi Spreadsheet berhasil disimpan!');
    }

    public function syncNow(Request $request, GoogleSheetsSyncService $sheetsService): RedirectResponse
    {
        $user = Auth::user();
        $connection = GoogleSheetConnection::where('user_id', $user->id)->first();

        if (!$connection || !$connection->spreadsheet_id) {
            return back()->withErrors([
                'spreadsheet_input' => 'Simpan ID / URL Spreadsheet tujuan terlebih dahulu pada Langkah 1.',
            ]);
        }

        if (empty($connection->access_token)) {
            return $this->redirectToGoogle($request, $sheetsService);
        }

        $result = $sheetsService->syncAllUserTransactions($user->id);
        if (!empty($result['error'])) {
            return back()->with('warning', 'Sinkronisasi tertunda karena Google Sheets API di Google Cloud belum di-Enable. Klik tombol "Aktifkan Google Sheets API" di halaman ini lalu coba lagi.');
        }

        return back()->with('success', "Sinkronisasi Otomatis Berhasil! Total Pemasukan, Pengeluaran, Profit Income, dan {$result['total']} transaksi telah terhitung di Google Sheets.");
    }

    public function redirectToGoogle(Request $request, GoogleSheetsSyncService $sheetsService): RedirectResponse
    {
        if (!$sheetsService->isOAuthConfigured()) {
            return back()->withErrors([
                'oauth' => 'Kredensial Google OAuth (GOOGLE_CLIENT_ID & GOOGLE_CLIENT_SECRET) belum diatur pada environment server.',
            ]);
        }

        $connection = GoogleSheetConnection::where('user_id', Auth::id())->first();
        if (!$connection) {
            return back()->withErrors([
                'spreadsheet_input' => 'Simpan ID / URL Spreadsheet tujuan terlebih dahulu sebelum menghubungkan akun Google.',
            ]);
        }

        $state = 'sheets_' . encrypt([
            'user_id' => Auth::id(),
            'ts' => now()->timestamp,
        ]);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($sheetsService->buildAuthorizationUrl($state));
    }

    public function handleCallback(Request $request, GoogleSheetsSyncService $sheetsService): RedirectResponse
    {
        $rawState = (string) $request->query('state', '');
        $userId = Auth::id();

        if (str_starts_with($rawState, 'sheets_')) {
            try {
                $payload = decrypt(substr($rawState, 7));
                if (is_array($payload) && !empty($payload['user_id'])) {
                    $userId = (int) $payload['user_id'];
                    if (!Auth::check()) {
                        $user = User::find($userId);
                        if ($user && $user->is_active) {
                            Auth::login($user, remember: true);
                        }
                    }
                }
            } catch (\Throwable $e) {
                return redirect()->route('settings.google-sheets')->withErrors([
                    'oauth' => 'Sesi OAuth tidak valid atau kedaluwarsa. Silakan coba lagi.',
                ]);
            }
        } else {
            $expectedState = $request->session()->pull('google_oauth_state');
            if (!$expectedState || $expectedState !== $rawState) {
                return redirect()->route('settings.google-sheets')->withErrors([
                    'oauth' => 'Sesi OAuth tidak valid atau kedaluwarsa. Silakan coba lagi.',
                ]);
            }
        }

        if ($request->has('error')) {
            return redirect()->route('settings.google-sheets')->withErrors([
                'oauth' => 'Izin akses Google Sheets dibatalkan oleh pengguna.',
            ]);
        }

        $code = (string) $request->query('code', '');
        if ($code === '' || !$userId) {
            return redirect()->route('settings.google-sheets')->withErrors([
                'oauth' => 'Kode otorisasi Google tidak ditemukan.',
            ]);
        }

        try {
            $tokens = $sheetsService->exchangeCodeForTokens($code);
            $connection = GoogleSheetConnection::where('user_id', $userId)->firstOrFail();

            $connection->update([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? $connection->refresh_token,
                'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3500)),
                'is_active' => true,
                'last_error' => null,
            ]);

            $syncResult = $sheetsService->syncAllUserTransactions($userId);

            if (!empty($syncResult['error'])) {
                return redirect()->route('settings.google-sheets')->with(
                    'warning',
                    'Akun Google berhasil terhubung! Tinggal 1 langkah lagi: klik tombol "Aktifkan Google Sheets API" di bawah agar Google mengizinkan pengiriman data otomatis.'
                );
            }

            return redirect()->route('settings.google-sheets')->with(
                'success',
                "Auto-Sync Aktif! Seluruh {$syncResult['total']} transaksi beserta rumus Total Pemasukan, Pengeluaran, dan Profit Income telah dihitung di Google Sheets."
            );
        } catch (\Throwable $e) {
            return redirect()->route('settings.google-sheets')->withErrors([
                'oauth' => 'Gagal menyelesaikan koneksi OAuth Google: ' . $e->getMessage(),
            ]);
        }
    }

    public function disconnect(): RedirectResponse
    {
        $connection = GoogleSheetConnection::where('user_id', Auth::id())->first();
        if ($connection) {
            $connection->update([
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
                'is_active' => false,
            ]);
        }

        return back()->with('success', 'Koneksi Google Sheets diputuskan dengan aman.');
    }

    public function retrySync(SyncLog $syncLog, GoogleSheetsSyncService $sheetsService): RedirectResponse
    {
        if ($syncLog->user_id !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        if ($syncLog->transaction) {
            $sheetsService->syncNewTransaction($syncLog->transaction);
        }

        return back()->with('success', 'Sinkronisasi ulang telah dijalankan.');
    }
}
