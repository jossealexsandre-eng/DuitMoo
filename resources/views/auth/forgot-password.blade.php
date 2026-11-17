<x-guest-layout title="Lupa Password - DuitMoo">
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
                <h2 class="text-2xl font-black text-[#263238]">Atur Ulang Password</h2>
                <p class="text-sm text-[#526066] mt-1 font-medium">Masukkan email akunmu dan kami akan mengirimkan tautan reset password.</p>
            </div>

            @if (session('status'))
                <div class="mb-4 p-3.5 rounded-xl bg-[#D8F5E5] border border-[#263238] text-[#114A2D] text-xs font-bold">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-[#263238] mb-1.5">
                        Alamat email
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="email@example.com"
                           class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] focus:bg-white focus:outline-none font-medium text-sm">
                    @error('email')
                        <p class="text-xs font-bold text-[#C93535] mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full py-3 brick-btn brick-btn-blue text-sm font-black">
                    Kirim Tautan Reset Password
                </button>
            </form>

            <div class="mt-6 pt-5 border-t-2 border-[#263238] text-center">
                <a href="{{ route('login') }}" class="text-xs font-black text-[#2862EB] hover:underline">
                    Kembali ke Halaman Login
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
