<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#FFD54F">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="DuitMoo">

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <title>{{ $title ?? 'Dashboard' }} - DuitMoo</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#FCF6EB] text-[#263238] min-h-screen font-sans antialiased pb-32 md:pb-8 selection:bg-[#FFD54F] selection:text-[#263238]"
      x-data="{
          isOnline: navigator.onLine,
          canInstallPwa: !!window.duitmooInstallPrompt,
          hideBalance: localStorage.getItem('duitmoo_hide_balance') === '1',
          soundEnabled: localStorage.getItem('duitmoo_sound') !== 'off',
          togglePrivacy() {
              this.hideBalance = !this.hideBalance;
              localStorage.setItem('duitmoo_hide_balance', this.hideBalance ? '1' : '0');
              this.triggerFeedback('tap');
          },
          toggleSound() {
              this.soundEnabled = !this.soundEnabled;
              localStorage.setItem('duitmoo_sound', this.soundEnabled ? 'on' : 'off');
              if (this.soundEnabled) {
                  this.triggerFeedback('coin');
              }
          },
          triggerFeedback(type = 'tap') {
              if (window.duitmooFeedback) {
                  window.duitmooFeedback(type);
              }
          },
          async triggerInstall() {
              if (!window.duitmooInstallPrompt) return;
              window.duitmooInstallPrompt.prompt();
              await window.duitmooInstallPrompt.userChoice;
              window.duitmooInstallPrompt = null;
              this.canInstallPwa = false;
          }
      }"
      x-init="
          window.addEventListener('online', () => isOnline = true);
          window.addEventListener('offline', () => isOnline = false);
          window.addEventListener('duitmoo-pwa-installable', () => canInstallPwa = true);
      ">

    <!-- Offline Status Top Strip -->
    <div x-show="!isOnline" x-cloak class="bg-[#FFE2DE] border-b-2 border-[#263238] text-[#8C1D1D] px-4 py-2 text-xs font-bold flex items-center justify-center gap-2 sticky top-0 z-50" style="display: none;">
        <x-icon name="wifi-off" class="w-4 h-4 shrink-0" />
        <span>Mode Offline: Pencatatan transaksi membutuhkan koneksi internet aktif.</span>
    </div>

    <!-- Top Alert Toast Stack -->
    <div class="fixed top-20 right-4 left-4 sm:left-auto z-50 flex flex-col gap-2 sm:max-w-sm w-auto pointer-events-none">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show"
                 x-init="setTimeout(() => triggerFeedback('coin'), 120); setTimeout(() => show = false, 4500)"
                 class="pointer-events-auto bg-[#1C7C49] text-white p-4 rounded-2xl border-2 border-[#263238] shadow-[3px_3px_0_#263238] flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <x-icon name="check" class="w-5 h-5 shrink-0" />
                    <span class="font-bold text-sm">{{ session('success') }}</span>
                </div>
                <button type="button" @click="show = false" aria-label="Tutup notifikasi" class="p-1 rounded-lg hover:bg-white/20 cursor-pointer">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif

        @if (session('warning'))
            <div x-data="{ show: true }" x-show="show"
                 class="pointer-events-auto bg-[#FFEAA0] text-[#263238] p-4 rounded-2xl border-2 border-[#263238] shadow-[3px_3px_0_#263238] flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <x-icon name="alert-triangle" class="w-5 h-5 shrink-0" />
                    <span class="font-bold text-sm">{{ session('warning') }}</span>
                </div>
                <button type="button" @click="show = false" aria-label="Tutup peringatan" class="p-1 rounded-lg hover:bg-black/10 cursor-pointer">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5500)"
                 class="pointer-events-auto bg-[#C93535] text-white p-4 rounded-2xl border-2 border-[#263238] shadow-[3px_3px_0_#263238] flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <x-icon name="alert-triangle" class="w-5 h-5 shrink-0" />
                    <span class="font-bold text-sm">{{ session('error') }}</span>
                </div>
                <button type="button" @click="show = false" aria-label="Tutup pesan error" class="p-1 rounded-lg hover:bg-white/20 cursor-pointer">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        @endif
    </div>

    <div class="min-h-screen flex">
        <!-- Desktop Sidebar (Permanently Fixed on Screen - Never Scrolls Away) -->
        <aside class="hidden md:flex flex-col w-64 bg-[#FFFDF9] border-r-2 border-[#263238]/15 p-5 shrink-0 fixed inset-y-0 left-0 overflow-y-auto z-40">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 mb-7 p-1.5 rounded-2xl hover:bg-[#FCF6EB] transition">
                <x-brand-logo size="md" />
                <div>
                    <span class="text-xl font-black tracking-tight text-[#263238] block leading-none">Duit<span class="text-[#245AE0]">Moo</span></span>
                </div>
            </a>

            <button type="button" @click="triggerFeedback('tap'); $dispatch('open-quick-add')"
                    class="brick-btn brick-btn-yellow w-full px-4 py-2.5 text-sm font-black mb-6 flex items-center justify-center gap-2 shadow-[2px_2px_0_#263238]">
                <x-icon name="plus" class="w-4 h-4" />
                <span>Catat Transaksi</span>
            </button>

            <nav class="flex-1 space-y-1" aria-label="Menu Utama">
                <p class="text-[11px] font-bold text-[#526066] px-3 pb-1">Keuangan</p>
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm transition {{ request()->routeIs('dashboard') ? 'bg-[#FFEAA0] border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-[#263238]' : 'hover:bg-[#FCF6EB] text-[#263238]' }}">
                    <x-icon name="dashboard" class="w-5 h-5 shrink-0" />
                    <span>Beranda</span>
                </a>

                <a href="{{ route('transactions.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm transition {{ request()->routeIs('transactions.*') ? 'bg-[#E0EAFF] border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-[#163C94]' : 'hover:bg-[#FCF6EB] text-[#263238]' }}">
                    <x-icon name="credit-card" class="w-5 h-5 shrink-0" />
                    <span>Transaksi</span>
                </a>

                <a href="{{ route('goals.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm transition {{ request()->routeIs('goals.*') ? 'bg-[#FFEAA0] border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-[#263238]' : 'hover:bg-[#FCF6EB] text-[#263238]' }}">
                    <x-icon name="piggy-bank" class="w-5 h-5 shrink-0" />
                    <span>Celengan Impian</span>
                </a>

                <a href="{{ route('bills.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm transition {{ request()->routeIs('bills.*') ? 'bg-[#FFE2DE] border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-[#8C1D1D]' : 'hover:bg-[#FCF6EB] text-[#263238]' }}">
                    <x-icon name="bell" class="w-5 h-5 shrink-0" />
                    <span>Tagihan Rutin</span>
                </a>

                <a href="{{ route('budget.settings') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm transition {{ request()->routeIs('budget.settings') ? 'bg-[#EAE2FF] border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-[#3A1D75]' : 'hover:bg-[#FCF6EB] text-[#263238]' }}">
                    <x-icon name="sliders" class="w-5 h-5 shrink-0" />
                    <span>Atur Anggaran</span>
                </a>

                <a href="{{ route('reports.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm transition {{ request()->routeIs('reports.*') ? 'bg-[#D8F5E5] border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-[#114A2D]' : 'hover:bg-[#FCF6EB] text-[#263238]' }}">
                    <x-icon name="trending-up" class="w-5 h-5 shrink-0" />
                    <span>Laporan</span>
                </a>

                <p class="text-[11px] font-bold text-[#526066] px-3 pt-4 pb-1">Akun &amp; Koneksi</p>
                <a href="{{ route('settings.google-sheets') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm transition {{ request()->routeIs('settings.google-sheets*') ? 'bg-[#D8F5E5] border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-[#114A2D]' : 'hover:bg-[#FCF6EB] text-[#263238]' }}">
                    <x-icon name="table" class="w-5 h-5 shrink-0" />
                    <span>Google Sheets</span>
                </a>

                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm transition {{ request()->routeIs('profile.*') ? 'bg-[#FFEAA0] border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-[#263238]' : 'hover:bg-[#FCF6EB] text-[#263238]' }}">
                    <x-icon name="user" class="w-5 h-5 shrink-0" />
                    <span>Profil &amp; PWA</span>
                </a>

                @if(auth()->user()->isAdmin())
                    <div class="pt-4 mt-4 border-t border-[#263238]/15">
                        <p class="text-[11px] font-bold text-[#526066] px-3 pb-1">Admin Area</p>
                        <a href="{{ route('admin.dashboard') }}"
                           class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-2xl font-bold text-sm bg-[#FFE2DE] text-[#8C1D1D] border-2 border-[#263238] shadow-[2px_2px_0_#263238] transition">
                            <x-icon name="shield" class="w-5 h-5 shrink-0" />
                            <span>Portal Admin</span>
                        </a>
                    </div>
                @endif
            </nav>

            <!-- Subtle Bottom Stamp -->
            <div class="mt-auto pt-4 border-t border-[#263238]/10 text-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#FCF6EB] border border-[#263238]/20 rounded-xl text-[10px] font-black text-[#526066]">
                    <span>🐮</span> DuitMoo 2.0
                </span>
            </div>
        </aside>

        <!-- Main Content Area (Offset by Fixed Sidebar on Desktop & Fixed Top Navbar) -->
        <div class="flex-1 flex flex-col min-w-0 md:pl-64 pt-16">
            <!-- Top Navbar with Profile Dropdown (Permanently Fixed on Top - Never Disappears on Scroll) -->
            <header class="fixed top-0 left-0 md:left-64 right-0 h-16 z-40 bg-[#FFFDF9]/95 backdrop-blur-md border-b-2 border-[#263238]/15 px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-[0_2px_0_rgba(38,50,56,0.05)]">
                <!-- Left: Mobile Brand or Desktop Breadcrumb -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="md:hidden flex items-center gap-2">
                        <x-brand-logo size="sm" />
                        <span class="font-black text-xl text-[#263238]">Duit<span class="text-[#245AE0]">Moo</span></span>
                    </a>

                    <div class="hidden md:flex items-center gap-2">
                        <span class="px-2.5 py-1 bg-[#FFEAA0] text-[#263238] border-2 border-[#263238] rounded-xl text-xs font-black shadow-[1.5px_1.5px_0_#263238]">
                            🐮 DuitMoo
                        </span>
                        <span class="text-[#526066] font-bold text-xs">/</span>
                        <span class="text-xs font-black text-[#263238]">{{ $title ?? 'Ringkasan' }}</span>
                    </div>
                </div>

                <!-- Right: Icon-Only Privacy Eye + Notification Bell (Catatan Anggaran) + Profile Dropdown -->
                <div class="flex items-center gap-2 sm:gap-2.5">
                    <!-- 1. Icon-Only Privacy Eye Toggle (No Text Label) -->
                    <button type="button"
                            @click="togglePrivacy()"
                            :aria-label="hideBalance ? 'Tampilkan nominal saldo' : 'Sembunyikan nominal saldo'"
                            :title="hideBalance ? 'Tampilkan Saldo' : 'Sembunyikan Saldo'"
                            class="w-10 h-10 rounded-2xl border-2 border-[#263238] flex items-center justify-center shadow-[2px_2px_0_#263238] active:translate-y-0.5 active:shadow-none transition cursor-pointer"
                            :class="hideBalance ? 'bg-[#FFEAA0] text-[#263238]' : 'bg-[#FCF6EB] hover:bg-white text-[#263238]'">
                        <template x-if="!hideBalance">
                            <x-icon name="eye" class="w-4 h-4" />
                        </template>
                        <template x-if="hideBalance">
                            <x-icon name="eye-off" class="w-4 h-4" />
                        </template>
                    </button>

                    <!-- 2. Notification Bell Dropdown (Catatan Anggaranmu) -->
                    <div class="relative" x-data="{ notifOpen: false }" @click.outside="notifOpen = false">
                        <button type="button"
                                @click="triggerFeedback('tap'); notifOpen = !notifOpen"
                                aria-label="Notifikasi Catatan Anggaran"
                                title="Catatan Anggaranmu"
                                class="relative w-10 h-10 rounded-2xl border-2 border-[#263238] bg-[#FCF6EB] hover:bg-white text-[#263238] flex items-center justify-center shadow-[2px_2px_0_#263238] active:translate-y-0.5 active:shadow-none transition cursor-pointer">
                            <x-icon name="bell" class="w-4 h-4" />
                            @if(!empty($navbarInsights) && count($navbarInsights) > 0)
                                <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-[#FF6B6B] text-white border-[1.5px] border-[#263238] text-[10px] font-black flex items-center justify-center leading-none">
                                    {{ count($navbarInsights) }}
                                </span>
                            @endif
                        </button>

                        <!-- Catatan Anggaranmu Dropdown Panel -->
                        <div x-show="notifOpen" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                             class="absolute right-0 mt-2.5 w-80 sm:w-96 bg-white border-2 border-[#263238] rounded-3xl p-4 shadow-[4px_6px_0_#263238] z-50">
                            <div class="flex items-center justify-between pb-3 mb-3 border-b border-dashed border-[#263238]/20">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl bg-[#FFEAA0] border border-[#263238] flex items-center justify-center text-sm">🔔</span>
                                    <div>
                                        <h3 class="text-sm font-black text-[#263238] leading-tight">Catatan Anggaranmu</h3>
                                        <p class="text-[11px] font-bold text-[#526066]">Periode {{ $navbarMonthLabel ?? now()->translatedFormat('F Y') }}</p>
                                    </div>
                                </div>
                                <button type="button" @click="notifOpen = false" aria-label="Tutup notifikasi" class="p-1 rounded-lg hover:bg-[#FCF6EB] cursor-pointer">
                                    <x-icon name="x" class="w-4 h-4 text-[#526066]" />
                                </button>
                            </div>

                            <div class="space-y-2.5 max-h-80 overflow-y-auto pr-0.5">
                                @forelse($navbarInsights ?? [] as $ins)
                                    @php
                                        $rowStyle = match($ins['type']) {
                                            'warning' => 'bg-[#FFE2DE] text-[#8C1D1D] border-[#263238]',
                                            'success' => 'bg-[#EDFAF3] text-[#114A2D] border-[#263238]',
                                            'notice' => 'bg-[#FFF9E6] text-[#263238] border-[#263238]',
                                            default => 'bg-[#FCF6EB] text-[#263238] border-[#263238]/30',
                                        };
                                    @endphp
                                    <div class="p-3 rounded-2xl border-2 {{ $rowStyle }} flex items-start gap-2.5 shadow-[1.5px_1.5px_0_#263238]">
                                        <x-icon :name="$ins['icon']" class="w-4 h-4 mt-0.5 shrink-0" />
                                        <p class="text-xs font-bold leading-relaxed">{{ $ins['text'] }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs text-[#526066] text-center py-4 font-bold">Belum ada catatan anggaran baru.</p>
                                @endforelse
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-[#263238]/15 flex items-center justify-between text-xs font-black">
                                <a href="{{ route('bills.index') }}" class="text-[#245AE0] hover:underline">Lihat Tagihan Rutin &rarr;</a>
                                <a href="{{ route('budget.settings') }}" class="text-[#526066] hover:text-[#263238]">Atur Anggaran</a>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Profile Button -->
                    <div class="relative" x-data="{ profileOpen: false }" @click.outside="profileOpen = false">
                        <button type="button" @click="triggerFeedback('tap'); profileOpen = !profileOpen"
                                aria-label="Menu Akun Profil"
                                class="flex items-center gap-2 sm:gap-2.5 p-1 sm:px-3 sm:py-1.5 bg-[#FCF6EB] hover:bg-white border-2 border-[#263238] rounded-2xl shadow-[2px_2px_0_#263238] active:translate-y-0.5 active:shadow-none transition cursor-pointer">
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" width="32" height="32" class="w-8 h-8 rounded-full border border-[#263238] object-cover shrink-0" referrerpolicy="no-referrer">
                            @else
                                <div class="w-8 h-8 rounded-full bg-[#FFD54F] border border-[#263238] flex items-center justify-center font-black text-xs text-[#263238] shrink-0">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="hidden sm:block text-left mr-1">
                                <p class="font-black text-xs text-[#263238] leading-tight truncate max-w-[120px]">{{ auth()->user()->name }}</p>
                                <span class="text-[9px] font-bold text-[#526066] uppercase tracking-wider block">{{ auth()->user()->role === 'admin' ? 'Admin' : 'Akun Aktif' }}</span>
                            </div>
                            <x-icon name="chevron-down" class="w-4 h-4 text-[#263238] shrink-0 transition-transform duration-200" ::class="profileOpen ? 'rotate-180' : ''" />
                        </button>

                        <!-- Postcase Dropdown Card -->
                        <div x-show="profileOpen" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                             class="absolute right-0 mt-2.5 w-72 sm:w-80 bg-white border-2 border-[#263238] rounded-3xl p-4 shadow-[4px_6px_0_#263238] z-50">
                            
                            <!-- Postcase Stamp Card Header -->
                            <div class="p-3.5 bg-[#FCF6EB] rounded-2xl border border-dashed border-[#263238]/40 mb-3 relative overflow-hidden">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        @if(auth()->user()->avatar)
                                            <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" width="48" height="48" class="w-12 h-12 rounded-full border-2 border-[#263238] object-cover shadow-[2px_2px_0_#263238]" referrerpolicy="no-referrer">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-[#FFD54F] border-2 border-[#263238] flex items-center justify-center font-black text-lg text-[#263238] shadow-[2px_2px_0_#263238]">
                                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <span class="absolute -bottom-1 -right-1 bg-[#45C486] w-3.5 h-3.5 rounded-full border-2 border-[#263238]"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="font-black text-sm text-[#263238] truncate">{{ auth()->user()->name }}</p>
                                            <span class="px-1.5 py-0.5 bg-[#FFEAA0] text-[#263238] border border-[#263238] rounded-md text-[9px] font-black uppercase">{{ auth()->user()->role }}</span>
                                        </div>
                                        <p class="text-xs text-[#526066] font-medium truncate mt-0.5">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Interactive Toggles: Sound & Privacy -->
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <button type="button" @click="toggleSound()"
                                        class="px-2.5 py-2 rounded-xl border border-[#263238] text-[11px] font-black flex items-center justify-center gap-1.5 transition cursor-pointer"
                                        :class="soundEnabled ? 'bg-[#EDFAF3] text-[#114A2D]' : 'bg-[#FCF6EB] text-[#526066]'">
                                    <template x-if="soundEnabled">
                                        <x-icon name="volume-2" class="w-3.5 h-3.5 shrink-0" />
                                    </template>
                                    <template x-if="!soundEnabled">
                                        <x-icon name="volume-x" class="w-3.5 h-3.5 shrink-0" />
                                    </template>
                                    <span x-text="soundEnabled ? 'Suara: ON' : 'Suara: OFF'"></span>
                                </button>

                                <button type="button" @click="togglePrivacy()"
                                        class="px-2.5 py-2 rounded-xl border border-[#263238] text-[11px] font-black flex items-center justify-center gap-1.5 transition cursor-pointer"
                                        :class="hideBalance ? 'bg-[#FFEAA0] text-[#263238]' : 'bg-[#FCF6EB] text-[#263238]'">
                                    <template x-if="hideBalance">
                                        <x-icon name="eye-off" class="w-3.5 h-3.5 shrink-0" />
                                    </template>
                                    <template x-if="!hideBalance">
                                        <x-icon name="eye" class="w-3.5 h-3.5 shrink-0" />
                                    </template>
                                    <span x-text="hideBalance ? 'Saldo: Privat' : 'Saldo: Tampil'"></span>
                                </button>
                            </div>

                            <!-- PWA Install Button inside Profile Dropdown -->
                            <div x-show="canInstallPwa" x-cloak class="mb-3">
                                <button type="button" @click="triggerInstall(); profileOpen = false;"
                                        class="w-full brick-btn brick-btn-blue px-3 py-2 text-xs font-black flex items-center justify-center gap-2 cursor-pointer shadow-[2px_2px_0_#263238]">
                                    <x-icon name="smartphone" class="w-4 h-4" />
                                    <span>Pasang Aplikasi ke Layar Utama</span>
                                </button>
                            </div>

                            <!-- Links -->
                            <div class="space-y-1 mb-3">
                                <a href="{{ route('goals.index') }}"
                                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-[#263238] hover:bg-[#FCF6EB] transition">
                                    <x-icon name="piggy-bank" class="w-4 h-4 text-[#FFB703]" />
                                    <span>Celengan Impian</span>
                                </a>

                                <a href="{{ route('bills.index') }}"
                                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-[#263238] hover:bg-[#FCF6EB] transition">
                                    <x-icon name="bell" class="w-4 h-4 text-[#C93535]" />
                                    <span>Pengingat Tagihan Rutin</span>
                                </a>

                                <a href="{{ route('budget.settings') }}"
                                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-[#263238] hover:bg-[#FCF6EB] transition">
                                    <x-icon name="sliders" class="w-4 h-4 text-[#3A1D75]" />
                                    <span>Atur Persentase Anggaran</span>
                                </a>

                                <a href="{{ route('reports.index') }}"
                                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-[#263238] hover:bg-[#FCF6EB] transition">
                                    <x-icon name="trending-up" class="w-4 h-4 text-[#1C7C49]" />
                                    <span>Laporan &amp; Ekspor CSV</span>
                                </a>

                                <a href="{{ route('profile.edit') }}"
                                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-[#263238] hover:bg-[#FCF6EB] transition">
                                    <x-icon name="user" class="w-4 h-4 text-[#245AE0]" />
                                    <span>Pengaturan Profil &amp; Password</span>
                                </a>

                                <a href="{{ route('settings.google-sheets') }}"
                                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-[#263238] hover:bg-[#FCF6EB] transition">
                                    <x-icon name="table" class="w-4 h-4 text-[#1C7C49]" />
                                    <span>Koneksi Google Sheets</span>
                                </a>

                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}"
                                       class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-[#8C1D1D] bg-[#FFE2DE] border border-[#263238]/30 hover:bg-[#FFD1CB] transition">
                                        <x-icon name="shield" class="w-4 h-4 text-[#C93535]" />
                                        <span>Portal Administrator</span>
                                    </a>
                                @endif
                            </div>

                            <!-- Logout Action -->
                            <div class="pt-2 border-t border-[#263238]/15">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-white hover:bg-[#FFE2DE] border-2 border-[#263238] text-[#8C1D1D] text-xs font-black shadow-[2px_2px_0_#263238] active:translate-y-0.5 active:shadow-none transition cursor-pointer">
                                        <x-icon name="logout" class="w-4 h-4" />
                                        <span>Keluar dari Akun</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            @php $announcement = \App\Models\AppSetting::getValue('app_announcement'); @endphp
            @if(!empty($announcement))
                <div class="bg-[#FFEAA0] border-b border-[#263238]/20 px-4 py-2 text-xs font-bold text-[#263238] text-center">
                    {{ $announcement }}
                </div>
            @endif

            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Floating Pill Mobile Bottom Navigation (Postcase Image 3 Ergonomic Layout: Beranda, Transaksi, [+], Celengan, Profil) -->
    <nav class="md:hidden fixed bottom-3 left-3 right-3 bg-[#FFFDF9] border-2 border-[#263238] rounded-[24px] px-2 py-1.5 z-40 grid grid-cols-5 items-center shadow-[0_4px_0_#263238]" aria-label="Navigasi Mobile">
        <a href="{{ route('dashboard') }}"
           @click="triggerFeedback('tap')"
           class="min-h-[48px] flex flex-col items-center justify-center gap-0.5 text-[11px] font-black rounded-2xl transition {{ request()->routeIs('dashboard') ? 'text-[#263238] bg-[#FFEAA0] border border-[#263238]' : 'text-[#526066]' }}">
            <x-icon name="dashboard" class="w-5 h-5" />
            <span>Beranda</span>
        </a>

        <a href="{{ route('transactions.index') }}"
           @click="triggerFeedback('tap')"
           class="min-h-[48px] flex flex-col items-center justify-center gap-0.5 text-[11px] font-black rounded-2xl transition {{ request()->routeIs('transactions.*') ? 'text-[#163C94] bg-[#E0EAFF] border border-[#263238]' : 'text-[#526066]' }}">
            <x-icon name="credit-card" class="w-5 h-5" />
            <span>Transaksi</span>
        </a>

        <!-- Center Raised Action Button [+] -->
        <div class="flex items-center justify-center">
            <button type="button"
                    @click="triggerFeedback('tap'); $dispatch('open-quick-add')"
                    aria-label="Tambah Transaksi Cepat"
                    class="-mt-5 w-14 h-14 bg-[#FFD54F] hover:bg-[#FFC107] text-[#263238] rounded-2xl border-2 border-[#263238] shadow-[0_4px_0_#263238] active:translate-y-0.5 active:shadow-[0_1px_0_#263238] flex flex-col items-center justify-center transition cursor-pointer">
                <x-icon name="plus" class="w-6 h-6 stroke-[2.8]" />
                <span class="text-[9px] font-black uppercase leading-none mt-0.5">Catat</span>
            </button>
        </div>

        <a href="{{ route('goals.index') }}"
           @click="triggerFeedback('tap')"
           class="min-h-[48px] flex flex-col items-center justify-center gap-0.5 text-[11px] font-black rounded-2xl transition {{ request()->routeIs('goals.*') || request()->routeIs('bills.*') ? 'text-[#263238] bg-[#FFEAA0] border border-[#263238]' : 'text-[#526066]' }}">
            <x-icon name="piggy-bank" class="w-5 h-5" />
            <span>Celengan</span>
        </a>

        <a href="{{ route('profile.edit') }}"
           @click="triggerFeedback('tap')"
           class="min-h-[48px] flex flex-col items-center justify-center gap-0.5 text-[11px] font-black rounded-2xl transition {{ request()->routeIs('profile.*') || request()->routeIs('settings.google-sheets*') ? 'text-[#263238] bg-[#FFEAA0] border border-[#263238]' : 'text-[#526066]' }}">
            <x-icon name="user" class="w-5 h-5" />
            <span>Profil</span>
        </a>
    </nav>

    @if(isset($globalUserCategories))
        <x-quick-add-modal :categories="$globalUserCategories" :selectedMonth="request()->query('month')" />
    @endif

    @livewireScripts
</body>
</html>
