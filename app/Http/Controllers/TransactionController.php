<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\MonthlyBudget;
use App\Models\Transaction;
use App\Services\BudgetCalculatorService;
use App\Services\GoogleSheetsSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $period = $request->query('period', 'month'); // today, week, month, custom
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $type = $request->query('type', 'all');
        $bucket = $request->query('bucket', 'all');
        $categoryId = $request->query('category_id', 'all');
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        try {
            $monthObj = Carbon::parse($month . '-01');
        } catch (\Throwable $e) {
            $monthObj = Carbon::now()->startOfMonth();
            $month = $monthObj->format('Y-m');
        }

        [$rangeStart, $rangeEnd, $periodLabel] = match ($period) {
            'today' => [
                Carbon::today()->format('Y-m-d'),
                Carbon::today()->format('Y-m-d'),
                'Hari Ini (' . Carbon::today()->translatedFormat('d M Y') . ')',
            ],
            'week' => [
                Carbon::now()->startOfWeek()->format('Y-m-d'),
                Carbon::now()->endOfWeek()->format('Y-m-d'),
                'Minggu Ini',
            ],
            'custom' => [
                $dateFrom ?: $monthObj->format('Y-m-d'),
                $dateTo ?: $monthObj->copy()->endOfMonth()->format('Y-m-d'),
                'Rentang Kustom',
            ],
            default => [
                $monthObj->format('Y-m-d'),
                $monthObj->copy()->endOfMonth()->format('Y-m-d'),
                $monthObj->translatedFormat('F Y'),
            ],
        };

        $baseQuery = Transaction::where('user_id', $user->id)
            ->whereDate('transaction_date', '>=', $rangeStart)
            ->whereDate('transaction_date', '<=', $rangeEnd);

        $filteredQuery = (clone $baseQuery)->with('category');

        if ($type !== 'all' && in_array($type, ['income', 'expense'], true)) {
            $filteredQuery->where('type', $type);
        }

        if ($bucket !== 'all' && in_array($bucket, ['needs', 'wants', 'emergency'], true)) {
            $filteredQuery->where('bucket', $bucket);
        }

        if ($categoryId !== 'all' && is_numeric($categoryId)) {
            $filteredQuery->where('category_id', (int) $categoryId);
        }

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $filteredQuery->where(function ($q) use ($pattern) {
                $q->whereRaw("LOWER(COALESCE(description, '')) LIKE ?", [$pattern])
                  ->orWhereRaw("LOWER(COALESCE(income_source, '')) LIKE ?", [$pattern])
                  ->orWhereRaw("LOWER(COALESCE(notes, '')) LIKE ?", [$pattern]);
            });
        }

        $transactions = $filteredQuery->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $totalIncome = (int) (clone $baseQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (int) (clone $baseQuery)->where('type', 'expense')->sum('amount');

        $categories = Category::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy(fn($cat) => $cat->transaction_type === 'income' ? 'income' : $cat->bucket);

        $availableMonths = [];
        $today = Carbon::now();
        for ($m = -5; $m <= 6; $m++) {
            $opt = $today->copy()->addMonths($m);
            $availableMonths[$opt->format('Y-m')] = $opt->translatedFormat('F Y');
        }

        return view('transactions.index', [
            'transactions' => $transactions,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'selectedMonth' => $month,
            'monthLabel' => $monthObj->translatedFormat('F Y'),
            'dateFrom' => $rangeStart,
            'dateTo' => $rangeEnd,
            'type' => $type,
            'bucket' => $bucket,
            'categoryId' => $categoryId,
            'search' => $search,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'categories' => $categories,
            'availableMonths' => $availableMonths,
            'openCreateModal' => $request->boolean('create'),
        ]);
    }

    public function store(
        StoreTransactionRequest $request,
        BudgetCalculatorService $calculator,
        GoogleSheetsSyncService $sheetsService
    ): RedirectResponse {
        $user = Auth::user();
        $validated = $request->validated();

        $txDate = Carbon::parse($validated['transaction_date']);
        $firstDayOfMonth = $txDate->copy()->startOfMonth()->format('Y-m-d');

        $transaction = DB::transaction(function () use ($user, $validated, $firstDayOfMonth, $request) {
            $budget = MonthlyBudget::where('user_id', $user->id)
                ->whereDate('budget_month', $firstDayOfMonth)
                ->first();

            if (!$budget) {
                $prefs = $user->preferences;
                $budget = MonthlyBudget::create([
                    'user_id' => $user->id,
                    'budget_month' => $firstDayOfMonth,
                    'opening_balance' => 0,
                    'needs_percent' => $prefs ? $prefs->default_needs_percent : 60,
                    'wants_percent' => $prefs ? $prefs->default_wants_percent : 30,
                    'emergency_percent' => $prefs ? $prefs->default_emergency_percent : 10,
                ]);
            }

            $catId = $validated['category_id'] ?? null;
            $cat = $catId ? Category::find($catId) : null;
            $desc = $validated['description'] ?? ($request->input('income_description') ?: null);
            if (!$desc && $cat) {
                $desc = $cat->name;
            }

            $incomeSource = null;
            if ($validated['type'] === 'income') {
                $incomeSource = !empty($validated['income_source'])
                    ? $validated['income_source']
                    : ($cat ? $cat->name : 'Pemasukan');
            }

            return Transaction::create([
                'user_id' => $user->id,
                'monthly_budget_id' => $budget->id,
                'type' => $validated['type'],
                'bucket' => $validated['type'] === 'expense' ? $validated['bucket'] : null,
                'category_id' => $catId,
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'description' => $desc,
                'income_source' => $incomeSource,
                'notes' => $validated['notes'] ?? null,
                'source' => $validated['source'] ?? 'web',
            ]);
        });

        // Optional non-blocking Google Sheets sync
        $sheetsService->syncNewTransaction($transaction);

        $redirectMonth = $request->input('return_month', $txDate->format('Y-m'));
        $redirect = redirect()->route('dashboard', ['month' => $redirectMonth])
            ->with('success', 'Transaksi berhasil disimpan!');

        // Non-blocking overspend warning check
        if ($transaction->type === 'expense' && $transaction->bucket) {
            $summary = $calculator->getMonthlySummary($transaction->monthlyBudget);
            $bucketStats = $summary['buckets'][$transaction->bucket] ?? null;
            if ($bucketStats && $bucketStats['is_overspent']) {
                $overAmount = number_format(abs($bucketStats['remaining']), 0, ',', '.');
                $redirect->with('warning', "Perhatian: Pengeluaran pos {$bucketStats['label']} kini melewati batas alokasi sebesar Rp {$overAmount}.");
            }
        }

        return $redirect;
    }

    public function update(
        UpdateTransactionRequest $request,
        Transaction $transaction,
        BudgetCalculatorService $calculator,
        GoogleSheetsSyncService $sheetsService
    ): RedirectResponse {
        $this->authorize('update', $transaction);

        $user = Auth::user();
        $validated = $request->validated();

        $newTxDate = Carbon::parse($validated['transaction_date']);
        $newFirstDay = $newTxDate->copy()->startOfMonth()->format('Y-m-d');

        DB::transaction(function () use ($transaction, $user, $validated, $newFirstDay) {
            if ($transaction->monthlyBudget->budget_month->format('Y-m-d') !== $newFirstDay) {
                $budget = MonthlyBudget::where('user_id', $user->id)
                    ->whereDate('budget_month', $newFirstDay)
                    ->first();

                if (!$budget) {
                    $prefs = $user->preferences;
                    $budget = MonthlyBudget::create([
                        'user_id' => $user->id,
                        'budget_month' => $newFirstDay,
                        'opening_balance' => 0,
                        'needs_percent' => $prefs ? $prefs->default_needs_percent : 60,
                        'wants_percent' => $prefs ? $prefs->default_wants_percent : 30,
                        'emergency_percent' => $prefs ? $prefs->default_emergency_percent : 10,
                    ]);
                }
                $transaction->monthly_budget_id = $budget->id;
            }

            $catId = $validated['category_id'] ?? null;
            $cat = $catId ? Category::find($catId) : null;
            $incomeSource = null;
            if ($validated['type'] === 'income') {
                $incomeSource = !empty($validated['income_source'])
                    ? $validated['income_source']
                    : ($cat ? $cat->name : 'Pemasukan');
            }

            $transaction->update([
                'monthly_budget_id' => $transaction->monthly_budget_id,
                'type' => $validated['type'],
                'bucket' => $validated['type'] === 'expense' ? $validated['bucket'] : null,
                'category_id' => $catId,
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? ($cat ? $cat->name : null),
                'income_source' => $incomeSource,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        // Auto-recalculate connected Google Sheet when transaction is modified
        $sheetsService->syncUserSpreadsheetIfConnected($user->id, $transaction);

        $redirect = back()->with('success', 'Transaksi berhasil diperbarui!');

        if ($transaction->type === 'expense' && $transaction->bucket) {
            $summary = $calculator->getMonthlySummary($transaction->monthlyBudget->fresh());
            $bucketStats = $summary['buckets'][$transaction->bucket] ?? null;
            if ($bucketStats && $bucketStats['is_overspent']) {
                $overAmount = number_format(abs($bucketStats['remaining']), 0, ',', '.');
                $redirect->with('warning', "Pos {$bucketStats['label']} melewati alokasi sebesar Rp {$overAmount}.");
            }
        }

        return $redirect;
    }

    public function destroy(Transaction $transaction, GoogleSheetsSyncService $sheetsService): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $userId = $transaction->user_id;
        $transaction->delete();

        // Auto-recalculate connected Google Sheet when transaction is deleted
        $sheetsService->syncUserSpreadsheetIfConnected($userId);

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }
}
