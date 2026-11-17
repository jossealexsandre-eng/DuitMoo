<?php

namespace App\Http\Controllers;

use App\Models\MonthlyBudget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $selectedYear = (int) $request->query('year', Carbon::now()->year);
        $selectedMonth = $request->query('month', 'all');
        $period = $request->query('period', $selectedMonth === 'all' ? 'year' : 'month'); // today, week, month, last_month, year, custom
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Resolve filter date window for period analytics
        [$rangeStart, $rangeEnd, $periodTitle] = $this->resolvePeriodRange(
            $period,
            $selectedYear,
            $selectedMonth,
            $dateFrom,
            $dateTo
        );

        // 1. Annual 12-month data for comparative charts & table
        $monthLabels = [];
        $monthlyIncomes = [];
        $monthlyExpenses = [];
        $monthlyNeeds = [];
        $monthlyWants = [];
        $monthlyEmergency = [];
        $monthlySummaries = [];

        $totalYearIncome = 0;
        $totalYearExpense = 0;

        for ($m = 1; $m <= 12; $m++) {
            $monthObj = Carbon::createFromDate($selectedYear, $m, 1);
            $monthStr = $monthObj->format('Y-m');
            $firstDay = $monthObj->format('Y-m-d');
            $lastDay = $monthObj->copy()->endOfMonth()->format('Y-m-d');
            $label = $monthObj->translatedFormat('F');

            $monthLabels[] = $label;

            $budget = MonthlyBudget::where('user_id', $user->id)
                ->whereDate('budget_month', $firstDay)
                ->first();

            $opening = $budget ? (int) $budget->opening_balance : 0;

            $inc = (int) Transaction::where('user_id', $user->id)
                ->whereDate('transaction_date', '>=', $firstDay)
                ->whereDate('transaction_date', '<=', $lastDay)
                ->where('type', 'income')
                ->sum('amount');

            $expNeeds = (int) Transaction::where('user_id', $user->id)
                ->whereDate('transaction_date', '>=', $firstDay)
                ->whereDate('transaction_date', '<=', $lastDay)
                ->where('type', 'expense')
                ->where('bucket', 'needs')
                ->sum('amount');

            $expWants = (int) Transaction::where('user_id', $user->id)
                ->whereDate('transaction_date', '>=', $firstDay)
                ->whereDate('transaction_date', '<=', $lastDay)
                ->where('type', 'expense')
                ->where('bucket', 'wants')
                ->sum('amount');

            $expEmerg = (int) Transaction::where('user_id', $user->id)
                ->whereDate('transaction_date', '>=', $firstDay)
                ->whereDate('transaction_date', '<=', $lastDay)
                ->where('type', 'expense')
                ->where('bucket', 'emergency')
                ->sum('amount');

            $expTotal = $expNeeds + $expWants + $expEmerg;
            $netSavings = $inc - $expTotal;

            $monthlyIncomes[] = $inc;
            $monthlyExpenses[] = $expTotal;
            $monthlyNeeds[] = $expNeeds;
            $monthlyWants[] = $expWants;
            $monthlyEmergency[] = $expEmerg;

            $totalYearIncome += $inc;
            $totalYearExpense += $expTotal;

            if ($budget || $inc > 0 || $expTotal > 0) {
                $monthlySummaries[] = [
                    'month_str' => $monthStr,
                    'label' => $label,
                    'opening' => $opening,
                    'income' => $inc,
                    'expense' => $expTotal,
                    'needs' => $expNeeds,
                    'wants' => $expWants,
                    'emergency' => $expEmerg,
                    'net' => $netSavings,
                ];
            }
        }

        // 2. Period-specific KPIs (respects today, week, month, last_month, year, custom)
        $periodBase = Transaction::where('user_id', $user->id)
            ->whereDate('transaction_date', '>=', $rangeStart)
            ->whereDate('transaction_date', '<=', $rangeEnd);

        $periodIncome = (int) (clone $periodBase)->where('type', 'income')->sum('amount');
        $periodExpense = (int) (clone $periodBase)->where('type', 'expense')->sum('amount');
        $largestExpenseTx = (clone $periodBase)->where('type', 'expense')->with('category')->orderByDesc('amount')->first();

        $daysInSpan = max(1, Carbon::parse($rangeStart)->diffInDays(Carbon::parse($rangeEnd)) + 1);
        $dailyAverageExpense = (int) round($periodExpense / $daysInSpan);

        // Daily trend series (capped sensibly up to 31 days or grouped by day within window)
        $dailyRows = (clone $periodBase)
            ->where('type', 'expense')
            ->selectRaw('transaction_date, SUM(amount) as total_amount')
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->transaction_date)->format('Y-m-d'));

        $dailyLabels = [];
        $dailyExpenses = [];
        $cursor = Carbon::parse($rangeStart);
        $endCursor = Carbon::parse($rangeEnd);
        if ($daysInSpan <= 35) {
            while ($cursor->lte($endCursor)) {
                $key = $cursor->format('Y-m-d');
                $dailyLabels[] = $cursor->format('d M');
                $dailyExpenses[] = isset($dailyRows[$key]) ? (int) $dailyRows[$key]->total_amount : 0;
                $cursor->addDay();
            }
        }

        // Safe previous-period comparison (zero-division protected)
        $prevSpanStart = Carbon::parse($rangeStart)->subDays($daysInSpan)->format('Y-m-d');
        $prevSpanEnd = Carbon::parse($rangeStart)->subDay()->format('Y-m-d');
        $prevPeriodExpense = (int) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereDate('transaction_date', '>=', $prevSpanStart)
            ->whereDate('transaction_date', '<=', $prevSpanEnd)
            ->sum('amount');

        $expenseChangePercent = $prevPeriodExpense > 0
            ? round((($periodExpense - $prevPeriodExpense) / $prevPeriodExpense) * 100, 1)
            : null;

        // 3. Category spending distribution for selected period
        $topCategories = (clone $periodBase)
            ->where('type', 'expense')
            ->with('category')
            ->selectRaw('category_id, bucket, SUM(amount) as total_amount, COUNT(id) as total_count')
            ->groupBy('category_id', 'bucket')
            ->orderByDesc('total_amount')
            ->get();

        $catChartLabels = [];
        $catChartData = [];
        $catChartColors = [];

        $bucketPalette = [
            'needs' => '#45C486',
            'wants' => '#FFD54F',
            'emergency' => '#9B7EDE',
        ];

        foreach ($topCategories as $tc) {
            $catChartLabels[] = $tc->category ? $tc->category->name : ($tc->bucket ? ucfirst($tc->bucket) : 'Lainnya');
            $catChartData[] = (int) $tc->total_amount;
            $catChartColors[] = $bucketPalette[$tc->bucket] ?? '#407BFF';
        }

        $availableYears = [Carbon::now()->year - 1, Carbon::now()->year, Carbon::now()->year + 1];

        return view('reports.index', [
            'period' => $period,
            'periodTitle' => $periodTitle,
            'dateFrom' => $rangeStart,
            'dateTo' => $rangeEnd,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'availableYears' => $availableYears,
            'totalYearIncome' => $totalYearIncome,
            'totalYearExpense' => $totalYearExpense,
            'periodIncome' => $periodIncome,
            'periodExpense' => $periodExpense,
            'prevPeriodExpense' => $prevPeriodExpense,
            'expenseChangePercent' => $expenseChangePercent,
            'largestExpenseTx' => $largestExpenseTx,
            'dailyAverageExpense' => $dailyAverageExpense,
            'dailyLabels' => $dailyLabels,
            'dailyExpenses' => $dailyExpenses,
            'monthLabels' => $monthLabels,
            'monthlyIncomes' => $monthlyIncomes,
            'monthlyExpenses' => $monthlyExpenses,
            'monthlyNeeds' => $monthlyNeeds,
            'monthlyWants' => $monthlyWants,
            'monthlyEmergency' => $monthlyEmergency,
            'monthlySummaries' => $monthlySummaries,
            'topCategories' => $topCategories,
            'catChartLabels' => $catChartLabels,
            'catChartData' => $catChartData,
            'catChartColors' => $catChartColors,
        ]);
    }

    private function resolvePeriodRange(
        string $period,
        int $selectedYear,
        string $selectedMonth,
        ?string $dateFrom,
        ?string $dateTo
    ): array {
        if ($period === 'today') {
            $today = Carbon::today()->format('Y-m-d');
            return [$today, $today, 'Hari Ini'];
        }

        if ($period === 'week') {
            return [
                Carbon::now()->startOfWeek()->format('Y-m-d'),
                Carbon::now()->endOfWeek()->format('Y-m-d'),
                'Minggu Ini',
            ];
        }

        if ($period === 'last_month') {
            $lastMonth = Carbon::now()->subMonth();
            return [
                $lastMonth->copy()->startOfMonth()->format('Y-m-d'),
                $lastMonth->copy()->endOfMonth()->format('Y-m-d'),
                'Bulan Lalu (' . $lastMonth->translatedFormat('F Y') . ')',
            ];
        }

        if ($period === 'custom' && $dateFrom && $dateTo) {
            return [$dateFrom, $dateTo, 'Rentang Kustom'];
        }

        if ($selectedMonth !== 'all' && is_numeric($selectedMonth)) {
            $mObj = Carbon::createFromDate($selectedYear, (int) $selectedMonth, 1);
            return [
                $mObj->copy()->startOfMonth()->format('Y-m-d'),
                $mObj->copy()->endOfMonth()->format('Y-m-d'),
                $mObj->translatedFormat('F Y'),
            ];
        }

        if ($period === 'month') {
            $now = Carbon::now();
            return [
                $now->copy()->startOfMonth()->format('Y-m-d'),
                $now->copy()->endOfMonth()->format('Y-m-d'),
                $now->translatedFormat('F Y'),
            ];
        }

        return [
            Carbon::createFromDate($selectedYear, 1, 1)->format('Y-m-d'),
            Carbon::createFromDate($selectedYear, 12, 31)->format('Y-m-d'),
            'Tahun ' . $selectedYear,
        ];
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $year = (int) $request->query('year', Carbon::now()->year);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"duitmoo_transaksi_{$year}.csv\"",
        ];

        return response()->stream(function () use ($user, $year) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Tanggal', 'Jenis', 'Pos Anggaran', 'Kategori', 'Nominal (Rp)', 'Keterangan', 'Sumber Pemasukan', 'Catatan']);

            Transaction::where('user_id', $user->id)
                ->whereYear('transaction_date', $year)
                ->with('category')
                ->orderBy('transaction_date', 'asc')
                ->chunk(100, function ($transactions) use ($handle) {
                    foreach ($transactions as $tx) {
                        fputcsv($handle, [
                            $tx->transaction_date->format('Y-m-d'),
                            $tx->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                            $tx->bucket ? ucfirst($tx->bucket) : '-',
                            $tx->category ? $tx->category->name : '-',
                            $tx->amount,
                            $tx->description ?? '',
                            $tx->income_source ?? '',
                            $tx->notes ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
