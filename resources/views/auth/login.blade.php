<x-guest-layout title="Masuk - DuitMoo">
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3 mb-6 group">
            <x-brand-logo size="lg" class="group-hover:scale-105 transition-transform" />
            <div>
                <h1 class="text-3xl font-black tracking-tight text-[#263238]">Duit<span class="text-[#245AE0]">Moo</span></h1>
            </div>
        </a>

        <div class="brick-card-pop w-full max-w-md p-6 sm:p-8 bg-white">
            <div class="mb-6 text-center">
                <img src="{{ asset('images/mascot/moowie-wave.png') }}" alt="Moowie" class="w-16 h-16 mx-auto mb-2 drop-shadow-[0_4px_8px_rgba(0,0,0,0.1)] hover:scale-110 transition-transform">
                <h2 class="text-2xl font-black text-[#263238]">Selamat Datang Kembali</h2>
                <p class="text-sm text-[#526066] mt-1 font-medium">Masuk untuk melihat &amp; mengatur anggaranmu.</p>
            </div>

            @if (session('success'))
                <div class="mb-4 p-3.5 rounded-xl bg-[#D8F5E5] border-2 border-[#263238] text-[#114A2D] text-xs font-bold flex items-center gap-2 shadow-[0_2px_0_0_#263238]">
                    <x-icon name="check" class="w-4 h-4 shrink-0 text-[#114A2D]" />
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->has('google'))
                <div class="mb-4 p-3.5 rounded-xl bg-[#FFE2DE] border-2 border-[#263238] text-[#8C1D1D] text-xs font-bold flex items-start gap-2 shadow-[0_2px_0_0_#263238]">
                    <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-[#C93535] mt-0.5" />
                    <span>{{ $errors->first('google') }}</span>
                </div>
            @endif

            <!-- Tombol Masuk dengan Google -->
            <a href="{{ route('auth.google') }}"
               class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-2xl border-2 border-[#263238] bg-white hover:bg-[#FCF6EB] text-sm font-black text-[#263238] shadow-[0_4px_0_0_#263238] active:translate-y-1 active:shadow-none transition-all cursor-pointer mb-5">
                <x-icon name="google" class="w-5 h-5 shrink-0" />
                <span>Masuk dengan Google</span>
            </a>

            <!-- Pemisah -->
            <div class="relative flex items-center justify-center mb-5">
                <div class="border-t-2 border-dashed border-[#263238]/20 w-full"></div>
                <span class="bg-white px-3 text-[11px] font-black uppercase tracking-wider text-[#526066] absolute">atau dengan email</span>
            </div>

            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-[#263238] mb-1.5">
                        Alamat email
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                           class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm transition">
                    @error('email')
                        <p class="text-xs font-bold text-[#C93535] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold text-[#263238]">
                            Password
                        </label>
                        <a href="{{ route('password.request') }}" class="text-xs font-bold text-[#245AE0] hover:underline">
                            Lupa password?
                        </a>
                    </div>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm transition">
                    @error('password')
                        <p class="text-xs font-bold text-[#C93535] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2.5 cursor-pointer min-h-[44px]">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-2 border-[#263238] text-[#245AE0] focus:ring-0">
                        <span class="text-xs font-bold text-[#526066]">Ingat Saya</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 brick-btn brick-btn-yellow text-sm font-black">
                    Masuk ke Akun
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-[#263238]/15 text-center">
                <p class="text-xs text-[#526066] font-medium">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="font-black text-[#245AE0] hover:underline">
                        Daftar Gratis
                    </a>
                </p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="text-xs font-bold text-[#526066] hover:text-[#263238] transition">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</x-guest-layout>
