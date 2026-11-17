<x-guest-layout title="Password Baru - DuitMoo">
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3 mb-6 group">
            <x-brand-logo size="lg" />
            <div>
                <h1 class="text-3xl font-black tracking-tight text-[#263238]">Duit<span class="text-[#245AE0]">Moo</span></h1>
                <p class="text-xs font-bold text-[#526066]">Your Money, Your Rules!</p>
            </div>
        </a>

        <div class="brick-card-pop w-full max-w-md p-6 sm:p-8 bg-white">
            <div class="mb-6 text-center">
                <h2 class="text-2xl font-black text-[#263238]">Buat Password Baru</h2>
                <p class="text-sm text-[#526066] mt-1 font-medium">Masukkan password baru untuk akun DuitMoo milikmu.</p>
            </div>

            <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-bold text-[#263238] mb-1.5">
                        Alamat email
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required
                           class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm">
                    @error('email')
                        <p class="text-xs font-bold text-[#C93535] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-[#263238] mb-1.5">
                        Password baru (minimal 8 karakter)
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm">
                    @error('password')
                        <p class="text-xs font-bold text-[#C93535] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-[#263238] mb-1.5">
                        Konfirmasi password baru
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm">
                </div>

                <button type="submit" class="w-full py-3 brick-btn brick-btn-yellow text-sm font-black">
                    Simpan Password Baru
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
