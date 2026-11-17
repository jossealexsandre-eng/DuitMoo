<?php

namespace App\Http\Controllers;

use App\Models\MonthlyBudget;
use App\Models\RecurringBill;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Services\GoogleSheetsSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SavingsAndBillsController extends Controller
{
    public function index(Request $request): View
    {
        return $this->indexGoals($request);
    }

    public function indexGoals(Request $request): View
    {
        $user = Auth::user();

        $savingsGoals = SavingsGoal::where('user_id', $user->id)
            ->orderByRaw('CASE WHEN current_amount >= target_amount THEN 1 ELSE 0 END ASC')
            ->orderByDesc('created_at')
            ->get();

        $totalGoalTarget = (int) $savingsGoals->sum('target_amount');
        $totalGoalSaved = (int) $savingsGoals->sum('current_amount');
        $achievedCount = $savingsGoals->filter(fn (SavingsGoal $g) => $g->is_achieved)->count();

        return view('goals.index', [
            'savingsGoals' => $savingsGoals,
            'totalGoalTarget' => $totalGoalTarget,
            'totalGoalSaved' => $totalGoalSaved,
            'achievedCount' => $achievedCount,
        ]);
    }

    public function indexBills(Request $request): View
    {
        $user = Auth::user();
        $selectedMonth = $request->query('month', Carbon::now()->format('Y-m'));

        try {
            $monthDate = Carbon::parse($selectedMonth . '-01');
        } catch (\Throwable $e) {
            $monthDate = Carbon::now()->startOfMonth();
            $selectedMonth = $monthDate->format('Y-m');
        }

        $recurringBills = RecurringBill::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('due_day')
            ->get();

        $totalMonthlyBills = (int) $recurringBills->sum('amount');
        $paidBillsAmount = (int) $recurringBills
            ->filter(fn (RecurringBill $b) => $b->isPaidForMonth($selectedMonth))
            ->sum('amount');

        $availableMonths = [];
        $today = Carbon::now();
        for ($m = -5; $m <= 6; $m++) {
            $opt = $today->copy()->addMonths($m);
            $availableMonths[$opt->format('Y-m')] = $opt->translatedFormat('F Y');
        }

        return view('bills.index', [
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $monthDate->translatedFormat('F Y'),
            'availableMonths' => $availableMonths,
            'recurringBills' => $recurringBills,
            'totalMonthlyBills' => $totalMonthlyBills,
            'paidBillsAmount' => $paidBillsAmount,
            'unpaidBillsAmount' => max(0, $totalMonthlyBills - $paidBillsAmount),
        ]);
    }

    public function storeGoal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'target_amount' => ['required', 'integer', 'min:1000', 'max:999999999999'],
            'current_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'icon' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'in:yellow,green,purple,blue'],
            'target_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        SavingsGoal::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'current_amount' => $validated['current_amount'] ?? 0,
            'icon' => $validated['icon'] ?: '🐮',
            'color' => $validated['color'] ?? 'yellow',
            'target_date' => $validated['target_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Celengan Impian '{$validated['name']}' berhasil dibuat!");
    }

    public function depositGoal(Request $request, SavingsGoal $goal, GoogleSheetsSyncService $sheetsService): RedirectResponse
    {
        abort_if($goal->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:999999999999'],
            'record_transaction' => ['nullable', 'boolean'],
        ]);

        $depositAmount = (int) $validated['amount'];

        DB::transaction(function () use ($goal, $depositAmount, $request) {
            $goal->update([
                'current_amount' => $goal->current_amount + $depositAmount,
            ]);

            if ($request->boolean('record_transaction')) {
                $user = Auth::user();
                $today = Carbon::now();
                $firstDay = $today->copy()->startOfMonth()->format('Y-m-d');

                $budget = MonthlyBudget::firstOrCreate(
                    ['user_id' => $user->id, 'budget_month' => $firstDay],
                    [
                        'opening_balance' => 0,
                        'needs_percent' => $user->preferences?->default_needs_percent ?? 60,
                        'wants_percent' => $user->preferences?->default_wants_percent ?? 30,
                        'emergency_percent' => $user->preferences?->default_emergency_percent ?? 10,
                    ]
                );

                Transaction::create([
                    'user_id' => $user->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => 'emergency',
                    'amount' => $depositAmount,
                    'transaction_date' => $today->format('Y-m-d'),
                    'description' => "Tabungan Celengan: {$goal->name}",
                    'notes' => 'Alokasi ke Celengan Impian',
                    'source' => 'celengan_deposit',
                ]);
            }
        });

        if ($request->boolean('record_transaction')) {
            $sheetsService->syncUserSpreadsheetIfConnected(Auth::id());
        }

        $formatted = number_format($depositAmount, 0, ',', '.');

        return back()->with('success', "Berhasil menabung Rp {$formatted} ke celengan '{$goal->name}'!");
    }

    public function destroyGoal(SavingsGoal $goal): RedirectResponse
    {
        abort_if($goal->user_id !== Auth::id(), 403);

        $name = $goal->name;
        $goal->delete();

        return back()->with('success', "Celengan '{$name}' berhasil dihapus.");
    }

    public function storeBill(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'min:1000', 'max:999999999999'],
            'due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'bucket' => ['required', 'string', 'in:needs,wants,emergency'],
            'icon' => ['nullable', 'string', 'max:20'],
        ]);

        RecurringBill::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'due_day' => $validated['due_day'],
            'bucket' => $validated['bucket'],
            'icon' => $validated['icon'] ?: '⚡',
            'is_active' => true,
        ]);

        return back()->with('success', "Pengingat tagihan '{$validated['name']}' berhasil ditambahkan!");
    }

    public function payBill(Request $request, RecurringBill $bill, GoogleSheetsSyncService $sheetsService): RedirectResponse
    {
        abort_if($bill->user_id !== Auth::id(), 403);

        $targetMonth = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $monthDate = Carbon::parse($targetMonth . '-01');
        } catch (\Throwable $e) {
            $monthDate = Carbon::now()->startOfMonth();
            $targetMonth = $monthDate->format('Y-m');
        }

        if ($bill->isPaidForMonth($targetMonth)) {
            return back()->with('warning', "Tagihan '{$bill->name}' sudah ditandai lunas untuk periode ini.");
        }

        DB::transaction(function () use ($bill, $targetMonth, $monthDate, $request) {
            $bill->update([
                'last_paid_month' => $targetMonth,
            ]);

            if ($request->boolean('record_transaction', true)) {
                $user = Auth::user();
                $firstDay = $monthDate->format('Y-m-d');

                $budget = MonthlyBudget::firstOrCreate(
                    ['user_id' => $user->id, 'budget_month' => $firstDay],
                    [
                        'opening_balance' => 0,
                        'needs_percent' => $user->preferences?->default_needs_percent ?? 60,
                        'wants_percent' => $user->preferences?->default_wants_percent ?? 30,
                        'emergency_percent' => $user->preferences?->default_emergency_percent ?? 10,
                    ]
                );

                $txDate = ($targetMonth === Carbon::now()->format('Y-m'))
                    ? Carbon::now()->format('Y-m-d')
                    : $monthDate->copy()->day(min($bill->due_day, $monthDate->daysInMonth))->format('Y-m-d');

                Transaction::create([
                    'user_id' => $user->id,
                    'monthly_budget_id' => $budget->id,
                    'type' => 'expense',
                    'bucket' => $bill->bucket,
                    'amount' => $bill->amount,
                    'transaction_date' => $txDate,
                    'description' => "Tagihan Rutin: {$bill->name}",
                    'notes' => "Pembayaran tagihan jatuh tempo tgl {$bill->due_day}",
                    'source' => 'recurring_bill',
                ]);
            }
        });

        $sheetsService->syncUserSpreadsheetIfConnected(Auth::id());

        return back()->with('success', "Tagihan '{$bill->name}' berhasil ditandai lunas & dicatat ke transaksi!");
    }

    public function destroyBill(RecurringBill $bill): RedirectResponse
    {
        abort_if($bill->user_id !== Auth::id(), 403);

        $name = $bill->name;
        $bill->delete();

        return back()->with('success', "Pengingat tagihan '{$name}' berhasil dihapus.");
    }
}
