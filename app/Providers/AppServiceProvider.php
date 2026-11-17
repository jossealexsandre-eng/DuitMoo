<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\MonthlyBudget;
use App\Models\RecurringBill;
use App\Models\Transaction;
use App\Policies\CategoryPolicy;
use App\Policies\MonthlyBudgetPolicy;
use App\Policies\TransactionPolicy;
use App\Services\BudgetCalculatorService;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production') || str_starts_with((string) config('app.url'), 'https://') || !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            URL::forceScheme('https');
        }

        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(MonthlyBudget::class, MonthlyBudgetPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(10)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Share categories and real-time budget notifications with authenticated layout
        View::composer('layouts.app', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();

                // Auto-heal: Ensure user has default categories (both expense and income) if missing
                if ($user->categories()->where('transaction_type', 'income')->count() === 0 || $user->categories()->where('transaction_type', 'expense')->count() === 0) {
                    app(\App\Services\UserInitializationService::class)->initialize($user);
                }

                $categories = Category::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                    ->groupBy(fn($cat) => $cat->transaction_type === 'income' ? 'income' : $cat->bucket);

                $selectedMonth = request()->query('month', Carbon::now()->format('Y-m'));
                try {
                    $monthDate = Carbon::parse($selectedMonth . '-01');
                } catch (\Throwable $e) {
                    $monthDate = Carbon::now()->startOfMonth();
                    $selectedMonth = $monthDate->format('Y-m');
                }

                $firstDay = $monthDate->format('Y-m-d');
                $monthlyBudget = MonthlyBudget::where('user_id', $user->id)
                    ->whereDate('budget_month', $firstDay)
                    ->first();

                $navbarInsights = [];
                $alertCount = 0;

                // 1. Check Unpaid Recurring Bills for this month
                $unpaidBills = RecurringBill::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->where(function ($q) use ($selectedMonth) {
                        $q->whereNull('last_paid_month')
                          ->orWhere('last_paid_month', '!=', $selectedMonth);
                    })
                    ->orderBy('due_day')
                    ->take(3)
                    ->get();

                foreach ($unpaidBills as $bill) {
                    $status = $bill->getStatusBadgeForMonth($selectedMonth);
                    $navbarInsights[] = [
                        'type' => 'notice',
                        'icon' => 'bell',
                        'text' => "Tagihan {$bill->name} (Rp " . number_format($bill->amount, 0, ',', '.') . ") belum dibayar · {$status['label']}.",
                    ];
                    $alertCount++;
                }

                // 2. Check Budget Summary Insights
                if ($monthlyBudget) {
                    $calculator = app(BudgetCalculatorService::class);
                    $summary = $calculator->getMonthlySummary($monthlyBudget);

                    if ($summary['total_allocatable'] === 0) {
                        $navbarInsights[] = [
                            'type' => 'info',
                            'icon' => 'lightbulb',
                            'text' => 'Kamu belum mengisi saldo awal atau pemasukan di bulan ini. Yuk, atur saldo awal atau catat pemasukan pertamamu.',
                        ];
                    } else {
                        foreach ($summary['buckets'] as $bucket) {
                            if ($bucket['is_overspent']) {
                                $overAmount = abs($bucket['remaining']);
                                $navbarInsights[] = [
                                    'type' => 'warning',
                                    'icon' => 'alert-triangle',
                                    'text' => "Peringatan: Pos {$bucket['label']} melebihi alokasi sebesar Rp " . number_format($overAmount, 0, ',', '.') . '.',
                                ];
                                $alertCount++;
                            }
                        }

                        if ($summary['buckets']['needs']['progress'] >= 80 && !$summary['buckets']['needs']['is_overspent']) {
                            $navbarInsights[] = [
                                'type' => 'notice',
                                'icon' => 'gauge',
                                'text' => "Pengeluaran Kebutuhanmu sudah mencapai {$summary['buckets']['needs']['progress']}% dari anggaran bulan ini.",
                            ];
                            $alertCount++;
                        }

                        if ($summary['buckets']['wants']['remaining'] > 0) {
                            $navbarInsights[] = [
                                'type' => 'success',
                                'icon' => 'sparkles',
                                'text' => 'Masih tersedia Rp ' . number_format($summary['buckets']['wants']['remaining'], 0, ',', '.') . ' untuk pos Keinginan.',
                            ];
                        }

                        if ($summary['buckets']['emergency']['spent'] === 0 && $summary['buckets']['emergency']['allocated'] > 0) {
                            $navbarInsights[] = [
                                'type' => 'success',
                                'icon' => 'shield-check',
                                'text' => 'Alokasi Dana Darurat sebesar Rp ' . number_format($summary['buckets']['emergency']['allocated'], 0, ',', '.') . ' masih utuh.',
                            ];
                        }
                    }
                } else {
                    $navbarInsights[] = [
                        'type' => 'info',
                        'icon' => 'lightbulb',
                        'text' => 'Selamat datang di DuitMoo! Catat transaksi pertamamu untuk melihat catatan anggaran otomatis.',
                    ];
                }

                $view->with([
                    'globalUserCategories' => $categories,
                    'navbarInsights' => array_slice($navbarInsights, 0, 5),
                    'navbarAlertCount' => $alertCount > 0 ? $alertCount : count($navbarInsights),
                    'navbarMonthLabel' => $monthDate->translatedFormat('F Y'),
                ]);
            }
        });
    }
}
