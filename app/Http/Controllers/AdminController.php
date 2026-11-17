<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\MonthlyBudget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        // Operational non-sensitive aggregate metrics only
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $newUsersLast30Days = User::where('created_at', '>=', now()->subDays(30))->count();
        $adminCount = User::where('role', 'admin')->count();
        $totalBudgets = MonthlyBudget::count();
        $totalTransactionsCount = Transaction::count();
        $dbDriver = DB::connection()->getDriverName();

        $usersQuery = User::orderBy('created_at', 'desc');

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $usersQuery->where(function ($q) use ($pattern) {
                $q->whereRaw('LOWER(name) LIKE ?', [$pattern])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$pattern]);
            });
        }

        $users = $usersQuery->paginate(15)->withQueryString();

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'newUsersLast30Days' => $newUsersLast30Days,
            'adminCount' => $adminCount,
            'totalBudgets' => $totalBudgets,
            'totalTransactionsCount' => $totalTransactionsCount,
            'dbDriver' => $dbDriver,
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show strictly non-financial account metadata for a specific user.
     */
    public function showUser(User $user): View
    {
        $user->loadCount(['monthlyBudgets', 'transactions', 'categories']);
        $recentLogs = AuditLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('admin.user-detail', [
            'targetUser' => $user,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function toggleUserStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['admin' => 'Anda tidak dapat menonaktifkan akun administrator Anda sendiri.']);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AuditLog::record(
            'admin.user_status_toggled',
            $user->id,
            Auth::id(),
            User::class,
            $user->id,
            ['is_active' => $user->is_active],
            $request->ip()
        );

        return back()->with('success', "Status akun '{$user->name}' berhasil {$statusText}.");
    }

    public function settings(): View
    {
        return view('admin.settings', [
            'registrationEnabled' => AppSetting::getValue('registration_enabled', '1') === '1',
            'appAnnouncement' => AppSetting::getValue('app_announcement', ''),
            'supportEmail' => AppSetting::getValue('support_email', 'support@duitmoo.local'),
            'dbDriver' => DB::connection()->getDriverName(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_enabled' => ['nullable', 'boolean'],
            'app_announcement' => ['nullable', 'string', 'max:500'],
            'support_email' => ['nullable', 'email', 'max:150'],
        ]);

        AppSetting::setValue('registration_enabled', $request->boolean('registration_enabled') ? '1' : '0');
        AppSetting::setValue('app_announcement', $validated['app_announcement'] ?? '');
        AppSetting::setValue('support_email', $validated['support_email'] ?? 'support@duitmoo.local');

        AuditLog::record(
            'admin.settings_updated',
            Auth::id(),
            Auth::id(),
            AppSetting::class,
            null,
            ['registration_enabled' => $request->boolean('registration_enabled')],
            $request->ip()
        );

        return back()->with('success', 'Konfigurasi aplikasi berhasil diperbarui.');
    }
}
