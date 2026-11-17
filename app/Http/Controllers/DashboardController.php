<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MonthlyBudget;
use App\Models\RecurringBill;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Services\BudgetCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, BudgetCalculatorService $calculator): View
    {
        $user = Auth::user();

        $selectedMonth = $request->query('month', Carbon::now()->format('Y-m'));

        try {
            $monthDate = Carbon::parse($selectedMonth . '-01');
        } catch (\Exception $e) {
            $monthDate = Carbon::now()->startOfMonth();
            $selectedMonth = $monthDate->format('Y-m');
        }

        $firstDay = $monthDate->format('Y-m-d');

        $monthlyBudget = MonthlyBudget::where('user_id', $user->id)
            ->whereDate('budget_month', $firstDay)
            ->first();

        if (!$monthlyBudget) {
            $prefs = $user->preferences;
            $monthlyBudget = MonthlyBudget::create([
                'user_id' => $user->id,
                'budget_month' => $firstDay,
                'opening_balance' => 0,
                'needs_percent' => $prefs ? $prefs->default_needs_percent : 60,
                'wants_percent' => $prefs ? $prefs->default_wants_percent : 30,
                'emergency_percent' => $prefs ? $prefs->default_emergency_percent : 10,
            ]);
        }

        $summary = $calculator->getMonthlySummary($monthlyBudget);

        $recentTransactions = Transaction::where('monthly_budget_id', $monthlyBudget->id)
            ->with('category')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Historical 6-month trend (Income, Expense, Net Ending Cash)
        $historicalLabels = [];
        $historicalIncome = [];
        $historicalExpense = [];
        $historicalRemainingCash = [];

        for ($i = 5; $i >= 0; $i--) {
            $cursorMonth = $monthDate->copy()->subMonths($i);
            $cursorFirstDay = $cursorMonth->format('Y-m-d');
            $label = $cursorMonth->translatedFormat('M Y');

            $histBudget = MonthlyBudget::where('user_id', $user->id)
                ->whereDate('budget_month', $cursorFirstDay)
                ->first();

            $inc = 0;
            $exp = 0;
            $opening = 0;

            if ($histBudget) {
                $opening = (int) $histBudget->opening_balance;
                $inc = (int) Transaction::where('monthly_budget_id', $histBudget->id)
                    ->where('type', 'income')
                    ->sum('amount');

                $exp = (int) Transaction::where('monthly_budget_id', $histBudget->id)
                    ->where('type', 'expense')
                    ->sum('amount');
            }

            $historicalLabels[] = $label;
            $historicalIncome[] = $inc;
            $historicalExpense[] = $exp;
            $historicalRemainingCash[] = ($opening + $inc) - $exp;
        }

        // Donut chart: expense distribution across buckets
        $donutLabels = ['Kebutuhan', 'Keinginan', 'Dana Darurat'];
        $donutData = [
            $summary['buckets']['needs']['spent'],
            $summary['buckets']['wants']['spent'],
            $summary['buckets']['emergency']['spent'],
        ];
        $donutColors = ['#45C486', '#FFD54F', '#9B7EDE'];

        // Category breakdown bar chart for current month
        $categoryTotals = Transaction::where('monthly_budget_id', $monthlyBudget->id)
            ->where('type', 'expense')
            ->with('category')
            ->selectRaw('category_id, bucket, SUM(amount) as total_amount')
            ->groupBy('category_id', 'bucket')
            ->orderByDesc('total_amount')
            ->take(6)
            ->get();

        $bucketPalette = [
            'needs' => '#45C486',
            'wants' => '#FFD54F',
            'emergency' => '#9B7EDE',
        ];

        $categoryBarLabels = [];
        $categoryBarData = [];
        $categoryBarColors = [];

        foreach ($categoryTotals as $ct) {
            $categoryBarLabels[] = $ct->category ? $ct->category->name : ucfirst((string) $ct->bucket);
            $categoryBarData[] = (int) $ct->total_amount;
            $categoryBarColors[] = $bucketPalette[$ct->bucket] ?? '#407BFF';
        }

        // Dynamic Real-Data Insights
        $insights = [];

        if ($summary['total_allocatable'] === 0) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'lightbulb',
                'text' => 'Kamu belum mengisi saldo awal atau pemasukan di bulan ini. Yuk, atur saldo awal atau catat pemasukan pertamamu.',
            ];
        } else {
            foreach ($summary['buckets'] as $bucket) {
                if ($bucket['is_overspent']) {
                    $overAmount = abs($bucket['remaining']);
                    $insights[] = [
                        'type' => 'warning',
                        'icon' => 'alert-triangle',
                        'text' => "Peringatan: Pos {$bucket['label']} melebihi alokasi sebesar Rp " . number_format($overAmount, 0, ',', '.') . '.',
                    ];
                }
            }

            if ($categoryTotals->isNotEmpty()) {
                $topCat = $categoryTotals->first();
                $topName = $topCat->category ? $topCat->category->name : ucfirst((string) $topCat->bucket);
                $insights[] = [
                    'type' => 'info',
                    'icon' => 'trending-up',
                    'text' => "Pengeluaran terbesar bulan ini ada di kategori {$topName} sebesar Rp " . number_format((int) $topCat->total_amount, 0, ',', '.') . '.',
                ];
            }

            if ($summary['buckets']['needs']['progress'] >= 80 && !$summary['buckets']['needs']['is_overspent']) {
                $insights[] = [
                    'type' => 'notice',
                    'icon' => 'gauge',
                    'text' => "Pengeluaran Kebutuhanmu sudah mencapai {$summary['buckets']['needs']['progress']}% dari anggaran bulan ini.",
                ];
            }

            if ($summary['buckets']['wants']['remaining'] > 0) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'sparkles',
                    'text' => 'Masih tersedia Rp ' . number_format($summary['buckets']['wants']['remaining'], 0, ',', '.') . ' untuk pos Keinginan.',
                ];
            }

            if ($summary['buckets']['emergency']['spent'] === 0 && $summary['buckets']['emergency']['allocated'] > 0) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'shield-check',
                    'text' => 'Alokasi Dana Darurat sebesar Rp ' . number_format($summary['buckets']['emergency']['allocated'], 0, ',', '.') . ' masih utuh.',
                ];
            }
        }

        // Previous month comparison insight (safe against zero division)
        $prevExpense = $historicalExpense[4] ?? 0;
        if ($summary['total_expenses'] > 0 && $prevExpense > 0) {
            $diffPct = round((($summary['total_expenses'] - $prevExpense) / $prevExpense) * 100, 1);
            if ($diffPct > 0) {
                $insights[] = [
                    'type' => 'notice',
                    'icon' => 'arrow-up-right',
                    'text' => "Pengeluaran bulan ini naik {$diffPct}% dibanding bulan sebelumnya.",
                ];
            } elseif ($diffPct < 0) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'arrow-down-right',
                    'text' => 'Bagus! Pengeluaran bulan ini turun ' . abs($diffPct) . '% dibanding bulan sebelumnya.',
                ];
            }
        }

        $userCategories = Category::where('user_id', $user->id)
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

        $savingsGoals = SavingsGoal::where('user_id', $user->id)
            ->orderByRaw('CASE WHEN current_amount >= target_amount THEN 1 ELSE 0 END ASC')
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        $recurringBills = RecurringBill::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('due_day')
            ->take(5)
            ->get();

        return view('dashboard', [
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $monthDate->translatedFormat('F Y'),
            'availableMonths' => $availableMonths,
            'monthlyBudget' => $monthlyBudget,
            'summary' => $summary,
            'recentTransactions' => $recentTransactions,
            'historicalLabels' => $historicalLabels,
            'historicalIncome' => $historicalIncome,
            'historicalExpense' => $historicalExpense,
            'historicalRemainingCash' => $historicalRemainingCash,
            'donutLabels' => $donutLabels,
            'donutData' => $donutData,
            'donutColors' => $donutColors,
            'categoryBarLabels' => $categoryBarLabels,
            'categoryBarData' => $categoryBarData,
            'categoryBarColors' => $categoryBarColors,
            'insights' => array_slice($insights, 0, 4),
            'userCategories' => $userCategories,
            'savingsGoals' => $savingsGoals,
            'recurringBills' => $recurringBills,
        ]);
    }
}
