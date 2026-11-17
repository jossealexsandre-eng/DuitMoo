<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\UserInitializationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi admin.',
                ]);
            }

            AuditLog::record('auth.login', $user->id, $user->id, User::class, $user->id, [], $request->ip());

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'));
            }

            if ($user->monthlyBudgets()->count() === 0) {
                return redirect()->route('onboarding');
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Kombinasi email atau password salah.',
        ])->onlyInput('email');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $registrationEnabled = AppSetting::getValue('registration_enabled', '1') === '1';

        return view('auth.register', [
            'registrationEnabled' => $registrationEnabled,
        ]);
    }

    public function register(Request $request, UserInitializationService $initService): RedirectResponse
    {
        if (AppSetting::getValue('registration_enabled', '1') !== '1') {
            return back()->withErrors(['email' => 'Pendaftaran pengguna baru sedang ditutup sementara oleh administrator.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Security invariant: Public registration strictly assigns 'user' role
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'is_active' => true,
        ]);

        $initService->initialize($user);
        AuditLog::record('auth.register', $user->id, $user->id, User::class, $user->id, [], $request->ip());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('onboarding')->with('success', 'Selamat datang di DuitMoo! Yuk, siapkan anggaran pertamamu.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = PasswordBroker::sendResetLink(
            $request->only('email')
        );

        // Always return generic helpful confirmation to prevent email enumeration
        return back()->with('status', __($status));
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $status = PasswordBroker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                AuditLog::record('auth.password_reset', $user->id, $user->id, User::class, $user->id, [], $request->ip());
            }
        );

        return $status === PasswordBroker::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password berhasil direset. Silakan masuk dengan password barumu.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Kamu telah berhasil logout.');
    }
}
