<x-guest-layout title="Daftar Akun - DuitMoo">
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3 mb-6 group">
            <x-brand-logo size="lg" class="group-hover:scale-105 transition-transform" />
            <div>
                <h1 class="text-3xl font-black tracking-tight text-[#263238]">Duit<span class="text-[#245AE0]">Moo</span></h1>
            </div>
        </a>

        <div class="brick-card-pop w-full max-w-md p-6 sm:p-8 bg-white">
            <div class="mb-6 text-center">
                <h2 class="text-2xl font-black text-[#263238]">Mulai Atur Uangmu</h2>
                <p class="text-sm text-[#526066] mt-1 font-medium">Daftar akun gratis dan bangun alokasi keuanganmu.</p>
            </div>

            @if(isset($registrationEnabled) && !$registrationEnabled)
                <div class="p-4 rounded-2xl bg-[#FFE2DE] border border-[#263238] text-[#8C1D1D] text-xs font-bold text-center mb-4">
                    Pendaftaran akun baru sedang ditutup sementara oleh administrator. Silakan hubungi tim admin atau masuk dengan akun yang sudah terdaftar.
                </div>
            @else
                @if ($errors->has('google'))
                    <div class="mb-4 p-3.5 rounded-xl bg-[#FFE2DE] border-2 border-[#263238] text-[#8C1D1D] text-xs font-bold flex items-start gap-2 shadow-[0_2px_0_0_#263238]">
                        <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-[#C93535] mt-0.5" />
                        <span>{{ $errors->first('google') }}</span>
                    </div>
                @endif

                <!-- Tombol Daftar Cepat dengan Google -->
                <a href="{{ route('auth.google') }}"
                   class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-2xl border-2 border-[#263238] bg-white hover:bg-[#FCF6EB] text-sm font-black text-[#263238] shadow-[0_4px_0_0_#263238] active:translate-y-1 active:shadow-none transition-all cursor-pointer mb-5">
                    <x-icon name="google" class="w-5 h-5 shrink-0" />
                    <span>Daftar Cepat dengan Google</span>
                </a>

                <!-- Pemisah -->
                <div class="relative flex items-center justify-center mb-5">
                    <div class="border-t-2 border-dashed border-[#263238]/20 w-full"></div>
                    <span class="bg-white px-3 text-[11px] font-black uppercase tracking-wider text-[#526066] absolute">atau daftar manual</span>
                </div>

                <form action="{{ route('register') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs font-bold text-[#263238] mb-1.5">
                            Nama lengkap
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                               placeholder="Nama Anda"
                               class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm transition">
                        @error('name')
                            <p class="text-xs font-bold text-[#C93535] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold text-[#263238] mb-1.5">
                            Alamat email
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                               placeholder="email@example.com"
                               class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm transition">
                        @error('email')
                            <p class="text-xs font-bold text-[#C93535] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold text-[#263238] mb-1.5">
                            Password (minimal 8 karakter)
                        </label>
                        <input type="password" id="password" name="password" required autocomplete="new-password"
                               placeholder="Minimal 8 karakter"
                               class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm transition">
                        @error('password')
                            <p class="text-xs font-bold text-[#C93535] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-[#263238] mb-1.5">
                            Konfirmasi password
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                               placeholder="Ulangi password"
                               class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm transition">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3 brick-btn brick-btn-blue text-sm font-black">
                            Buat Akun &amp; Mulai
                        </button>
                    </div>
                </form>
            @endif

            <div class="mt-6 pt-5 border-t border-[#263238]/15 text-center">
                <p class="text-xs text-[#526066] font-medium">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-black text-[#245AE0] hover:underline">
                        Masuk di Sini
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
