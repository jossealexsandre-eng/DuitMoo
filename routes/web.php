<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\GoogleSheetsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingsAndBillsController;
use App\Http\Controllers\TransactionController;
use App\Livewire\OnboardingWizard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:login');

    // Google OAuth Login Redirect
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

// Shared Google OAuth Callback (Supports both Guest Login & Authenticated Google Sheets Connect)
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Protected with Anti-IDOR Policies & Active check)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    // Onboarding
    Route::get('/onboarding', OnboardingWizard::class)->name('onboarding');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Transactions CRUD
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    // Celengan Impian (Savings Goals)
    Route::get('/celengan', [SavingsAndBillsController::class, 'indexGoals'])->name('goals.index');
    Route::post('/celengan/goals', [SavingsAndBillsController::class, 'storeGoal'])->name('goals.store');
    Route::post('/celengan/goals/{goal}/deposit', [SavingsAndBillsController::class, 'depositGoal'])->name('goals.deposit');
    Route::delete('/celengan/goals/{goal}', [SavingsAndBillsController::class, 'destroyGoal'])->name('goals.destroy');

    // Pengingat Tagihan Rutin (Recurring Bills)
    Route::get('/tagihan', [SavingsAndBillsController::class, 'indexBills'])->name('bills.index');
    Route::post('/tagihan/bills', [SavingsAndBillsController::class, 'storeBill'])->name('bills.store');
    Route::post('/tagihan/bills/{bill}/pay', [SavingsAndBillsController::class, 'payBill'])->name('bills.pay');
    Route::delete('/tagihan/bills/{bill}', [SavingsAndBillsController::class, 'destroyBill'])->name('bills.destroy');

    // Reports & Analytics
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');

    // Budget Settings & Snapshots
    Route::get('/budget/settings', [BudgetSettingController::class, 'index'])->name('budget.settings');
    Route::post('/budget/settings/preferences', [BudgetSettingController::class, 'updatePreferences'])->name('budget.preferences.update');
    Route::post('/budget/settings/snapshot', [BudgetSettingController::class, 'updateMonthlySnapshot'])->name('budget.snapshot.update');
    Route::post('/budget/category', [BudgetSettingController::class, 'storeCategory'])->name('budget.category.store');
    Route::put('/budget/category/{category}', [BudgetSettingController::class, 'updateCategory'])->name('budget.category.update');
    Route::delete('/budget/category/{category}', [BudgetSettingController::class, 'destroyCategory'])->name('budget.category.destroy');

    // Profile, Preferences & Security
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/settings/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Google Sheets Optional Integration
    Route::get('/settings/google-sheets', [GoogleSheetsController::class, 'index'])->name('settings.google-sheets');
    Route::post('/settings/google-sheets/config', [GoogleSheetsController::class, 'saveConfig'])->name('settings.google-sheets.save');
    Route::post('/settings/google-sheets/sync-now', [GoogleSheetsController::class, 'syncNow'])->name('settings.google-sheets.sync-now');
    Route::get('/settings/google-sheets/connect', [GoogleSheetsController::class, 'redirectToGoogle'])->name('settings.google-sheets.connect');
    Route::get('/settings/google-sheets/callback', [GoogleSheetsController::class, 'handleCallback'])->name('settings.google-sheets.callback');
    Route::post('/settings/google-sheets/disconnect', [GoogleSheetsController::class, 'disconnect'])->name('settings.google-sheets.disconnect');
    Route::post('/settings/google-sheets/retry/{syncLog}', [GoogleSheetsController::class, 'retrySync'])->name('settings.google-sheets.retry');
});

/*
|--------------------------------------------------------------------------
| Administrator Routes (Strictly Protected by AdminMiddleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('user.show');
    Route::patch('/users/{user}/toggle', [AdminController::class, 'toggleUserStatus'])->name('user.toggle');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
});
