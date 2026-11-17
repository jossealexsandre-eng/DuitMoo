<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = Auth::user();
        $prefs = $user->preferences ?? UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'default_needs_percent' => 60,
                'default_wants_percent' => 30,
                'default_emergency_percent' => 10,
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd M Y',
            ]
        );

        return view('profile.edit', [
            'user' => $user,
            'prefs' => $prefs,
            'hasGoogleConnection' => $user->googleSheetConnections()->where('is_active', true)->exists(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'timezone' => ['nullable', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura'],
            'date_format' => ['nullable', 'in:d M Y,d/m/Y,Y-m-d'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['timezone']) || !empty($validated['date_format'])) {
            UserPreference::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'timezone' => $validated['timezone'] ?? 'Asia/Jakarta',
                    'date_format' => $validated['date_format'] ?? 'd M Y',
                ]
            );
        }

        return back()->with('success', 'Profil dan preferensi berhasil diperbarui!');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password berhasil diubah!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Akun DuitMoo dan seluruh data pribadimu telah dihapus.');
    }
}
