<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#FF6B6B">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">

    <title>{{ $title ?? 'Admin Portal' }} - DuitMoo</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#FCF6EB] text-[#263238] min-h-screen font-sans antialiased selection:bg-[#FF6B6B] selection:text-white">

    <div class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="pointer-events-auto bg-[#1C7C49] text-white p-4 rounded-2xl border-2 border-[#263238] shadow-[3px_3px_0_#263238] flex items-center justify-between gap-3">
                <span class="font-bold text-sm">{{ session('success') }}</span>
                <button type="button" @click="show = false" aria-label="Tutup" class="p-1 hover:bg-white/20 rounded-lg cursor-pointer">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="pointer-events-auto bg-[#C93535] text-white p-4 rounded-2xl border-2 border-[#263238] shadow-[3px_3px_0_#263238] flex items-center justify-between gap-3">
                <span class="font-bold text-sm">{{ session('error') }}</span>
                <button type="button" @click="show = false" aria-label="Tutup" class="p-1 hover:bg-white/20 rounded-lg cursor-pointer">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif
    </div>

    <header class="bg-white border-b border-[#263238]/15 px-4 sm:px-6 py-4 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#FFE2DE] border-2 border-[#263238] shadow-[2px_2px_0_#263238] flex items-center justify-center text-[#8C1D1D]">
                    <x-icon name="shield" class="w-5 h-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.dashboard') }}" class="text-xl font-black text-[#263238]">Duit<span class="text-[#C93535]">Moo</span></a>
                        <span class="px-2 py-0.5 rounded-lg text-[11px] font-black bg-[#FFE2DE] text-[#8C1D1D] border border-[#263238]">Admin</span>
                    </div>
                    <p class="text-xs text-[#526066] font-semibold">Pusat Operasional &amp; Manajemen Akun</p>
                </div>
            </div>

            <nav class="flex flex-wrap items-center gap-2.5" aria-label="Navigasi Admin">
                <a href="{{ route('admin.dashboard') }}"
                   class="brick-btn {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.user.*') ? 'brick-btn-yellow' : 'brick-btn-white' }} px-3.5 py-2 text-xs font-bold">
                    Daftar Pengguna
                </a>
                <a href="{{ route('admin.settings') }}"
                   class="brick-btn {{ request()->routeIs('admin.settings') ? 'brick-btn-yellow' : 'brick-btn-white' }} px-3.5 py-2 text-xs font-bold">
                    Pengaturan Aplikasi
                </a>
                <a href="{{ route('dashboard') }}" class="brick-btn brick-btn-white px-3.5 py-2 text-xs font-bold">
                    Dashboard User
                </a>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="brick-btn brick-btn-red px-3.5 py-2 text-xs font-bold">
                        Logout
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
