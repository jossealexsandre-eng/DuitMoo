<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\UserInitializationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google OAuth authentication page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google Login belum aktif karena GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di file .env masih kosong.',
            ]);
        }

        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Obtain the user information from Google and authenticate.
     */
    public function handleGoogleCallback(Request $request, UserInitializationService $initService): RedirectResponse
    {
        // Route Google Sheets OAuth connection callbacks that share the /auth/google/callback URI
        $stateParam = (string) $request->query('state', '');
        if (str_starts_with($stateParam, 'sheets_')) {
            return app(GoogleSheetsController::class)->handleCallback(
                $request,
                app(\App\Services\GoogleSheetsSyncService::class)
            );
        }

        if ($request->has('error')) {
            return redirect()->route('login')->withErrors([
                'google' => 'Login dengan Google dibatalkan atau ditolak.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google OAuth error: ' . $e->getMessage(), ['exception' => $e]);

            return redirect()->route('login')->withErrors([
                'google' => 'Gagal mengautentikasi dengan akun Google: ' . ($e->getMessage() ?: 'Sesi autentikasi kedaluwarsa. Silakan coba klik tombol Google lagi.'),
            ]);
        }

        $email = $googleUser->getEmail();
        if (empty($email)) {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun Google tidak menyediakan alamat email yang valid.',
            ]);
        }

        // 1. Check if user already exists with this google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        // 2. If not found by google_id, check if user exists by email (link accounts)
        if (!$user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // Link Google account to existing user
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $user->avatar ?? $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }
        }

        // 3. If user doesn't exist at all, register new user
        if (!$user) {
            if (AppSetting::getValue('registration_enabled', '1') !== '1') {
                return redirect()->route('login')->withErrors([
                    'google' => 'Pendaftaran akun baru sedang ditutup sementara oleh administrator.',
                ]);
            }

            $user = User::create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Pengguna DuitMoo'),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null,
                'email_verified_at' => now(),
                'role' => 'user',
                'is_active' => true,
            ]);

            $initService->initialize($user);
            AuditLog::record('auth.register.google', $user->id, $user->id, User::class, $user->id, [], $request->ip());
        } else {
            // Keep avatar up-to-date if empty
            if (empty($user->avatar) && $googleUser->getAvatar()) {
                $user->update(['avatar' => $googleUser->getAvatar()]);
            }
        }

        // 4. Verify account is active
        if (!$user->is_active) {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi admin.',
            ]);
        }

        // 5. Log user in
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        AuditLog::record('auth.login.google', $user->id, $user->id, User::class, $user->id, [], $request->ip());

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user->monthlyBudgets()->count() === 0) {
            return redirect()->route('onboarding')->with('success', 'Selamat datang di DuitMoo! Yuk, siapkan alokasi anggaran pertamamu.');
        }

        return redirect()->intended(route('dashboard'));
    }
}
