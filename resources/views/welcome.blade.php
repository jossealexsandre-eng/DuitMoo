<x-guest-layout title="DuitMoo: Your Money, Your Rules!">
    <div class="min-h-screen flex flex-col justify-between bg-aesthetic-grid relative overflow-x-clip"
         x-data="{
            landingAmount: 5000000,
            landingNeeds: 60,
            landingWants: 30,
            landingEmerg: 10,
            isCustomLocked: false,
            privacyModal: false,
            termsModal: false,
            get landingNeedsAlloc() { return Math.floor(((this.landingAmount || 0) * this.landingNeeds) / 100); },
            get landingWantsAlloc() { return Math.floor(((this.landingAmount || 0) * this.landingWants) / 100); },
            get landingEmergAlloc() { return Math.max(0, (this.landingAmount || 0) - this.landingNeedsAlloc - this.landingWantsAlloc); },
            setAmount(val) {
                this.landingAmount = val;
                this.isCustomLocked = (val !== 5000000);
            },
            resetAmount() {
                this.landingAmount = 5000000;
                this.isCustomLocked = false;
            }
         }">

        <!-- Floating Animated Cute Cows in the Background -->
        <x-floating-cows-background />

        <!-- 1. Sticky Floating Navbar (Always sticks when scrolling down) -->
        <header id="beranda" class="sticky top-0 z-50 bg-[#FCF6EB]/90 backdrop-blur-md border-b border-[#263238]/12 px-4 sm:px-8 py-3.5">
            <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group shrink-0">
                    <x-brand-logo size="md" class="group-hover:rotate-6 transition-transform" />
                    <div>
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-[#263238] block leading-none">Duit<span class="text-[#245AE0]">Moo</span></span>
                    </div>
                </a>

                <nav class="hidden md:flex items-center gap-8 font-bold text-sm" aria-label="Navigasi Utama">
                    <a href="#playground" class="text-[#263238] hover:text-[#245AE0] transition">Playground</a>
                    <a href="#cara-kerja" class="text-[#546E7A] hover:text-[#245AE0] transition">Cara Kerja</a>
                    <a href="#komitmen" class="text-[#546E7A] hover:text-[#245AE0] transition">Privasi Aman</a>
                </nav>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="brick-btn brick-btn-yellow px-4 py-2 text-xs sm:text-sm font-black hover:scale-105 transition-transform">
                            Buka Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="min-h-[44px] px-3.5 py-2 text-xs sm:text-sm font-bold text-[#263238] hover:text-[#245AE0] inline-flex items-center transition">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="brick-btn brick-btn-blue px-4 py-2 text-xs sm:text-sm font-black hover:scale-105 transition-transform shadow-[2px_2px_0_#263238]">
                            Daftar Gratis
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- 2. Clean Centerpiece Hero: Playful, Alive & Focused -->
        <main class="relative z-10">
            <section id="playground" class="py-10 sm:py-16 px-4 sm:px-6 max-w-5xl mx-auto w-full text-center">
                
                <!-- Center Mascot & Greeting Bubble -->
                <div class="flex flex-col items-center justify-center mb-6">
                    <div class="relative inline-block anim-cow-bob">
                        <x-duitmoo-mascot variant="hero" class="w-24 h-24 sm:w-28 sm:h-28 mx-auto filter drop-shadow-[0_8px_16px_rgba(0,0,0,0.08)]" />
                        <span class="absolute -bottom-2 -right-2 px-2.5 py-0.5 rounded-full bg-[#FFD54F] border border-[#263238] text-[10px] font-black shadow-[1px_1px_0_#263238]">
                            Moo!
                        </span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-[#263238] tracking-tight mt-4 leading-tight">
                        Your Money, <span class="text-[#245AE0]">Your Rules!</span>
                    </h1>

                    <p class="text-sm sm:text-base text-[#546E7A] font-medium max-w-md mx-auto mt-2 leading-relaxed">
                        Bagi otomatis saldo ke 3 pos balok keuangan dan catat belanja harian langsung dari HP.
                    </p>
                </div>

                <!-- Interactive 3D Perspective Phone Mockup (Inspired by Editorial UI & 3D Smartphone Device) -->
                <div class="max-w-[430px] mx-auto perspective-[1200px] text-left">
                    <div class="relative transition-all duration-500 ease-out transform md:rotate-y-[-6deg] md:rotate-x-[4deg] md:hover:rotate-0 md:hover:scale-[1.01] filter drop-shadow-[0_20px_40px_rgba(38,50,56,0.18)]">
                        
                        <!-- Physical Smartphone Outer Frame (Dark Metallic Titanium Finish) -->
                        <div class="bg-[#1C2024] p-[10px] sm:p-[12px] rounded-[48px] sm:rounded-[54px] border-4 border-[#263238] shadow-[inset_0_0_0_2px_rgba(255,255,255,0.15),0_15px_30px_rgba(0,0,0,0.25)] relative">
                            
                            <!-- Hardware Side Buttons Accent -->
                            <div class="hidden sm:block absolute -left-[14px] top-28 w-[6px] h-10 bg-[#263238] rounded-l-md"></div>
                            <div class="hidden sm:block absolute -left-[14px] top-42 w-[6px] h-10 bg-[#263238] rounded-l-md"></div>
                            <div class="hidden sm:block absolute -right-[14px] top-32 w-[6px] h-14 bg-[#263238] rounded-r-md"></div>

                            <!-- Screen Canvas Container -->
                            <div class="bg-[#FFFDF9] rounded-[38px] sm:rounded-[44px] overflow-hidden border-2 border-[#263238] p-4 sm:p-5 relative select-none">
                                
                                <!-- Realistic Dynamic Island & Status Bar Header -->
                                <div class="flex items-center justify-between px-1 pt-0.5 pb-3 text-[10px] font-black text-[#263238]">
                                    <span>9:41</span>
                                    <!-- Dynamic Island Pill -->
                                    <div class="w-24 h-5 bg-[#1C2024] rounded-full flex items-center justify-between px-2.5 shadow-inner">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#111] border border-white/20"></span>
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#245AE0] animate-pulse"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-[#263238]">
                                        <span class="text-[9px] font-black">5G</span>
                                        <div class="w-4 h-2 border border-[#263238] rounded-xs p-0.5 flex items-center">
                                            <div class="h-full w-full bg-[#263238] rounded-2xs"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- App Header Bar: Avatar Cow Mascot & Live Status Pill -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-2xl bg-[#FFD54F] border-2 border-[#263238] flex items-center justify-center text-base shadow-[1.5px_1.5px_0_#263238]">
                                            🐮
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-black uppercase tracking-wider text-[#526066] block leading-tight">Halo, Juragan!</span>
                                            <h2 class="text-sm font-black text-[#263238] leading-tight">Papan Alokasi Pos</h2>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full bg-[#D8F5E5] text-[#114A2D] border border-[#263238] text-[10px] font-black flex items-center gap-1 shadow-[1px_1px_0_#263238]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#22C55E] animate-pulse"></span>
                                        Demo Interaktif
                                    </span>
                                </div>

                                <!-- Hero Card: Saldo Awal (Warm Editorial Style with Segmented Bar) -->
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-[#FFF9E6] border-2 border-[#263238] mb-3 space-y-2 shadow-[2px_2px_0_#263238]">
                                    <div class="flex justify-between items-center text-xs font-bold text-[#526066]">
                                        <span class="text-[11px] font-black uppercase tracking-wider text-[#263238] flex items-center gap-1">
                                            <span>💰</span> Saldo Tersedia
                                        </span>
                                        <span class="text-[10px] font-black text-[#245AE0] bg-white px-2 py-0.5 rounded-lg border border-[#263238]/20">Geser Nilai &darr;</span>
                                    </div>
                                    <p class="text-2xl sm:text-3xl font-black text-[#263238] tracking-tight tabular-nums" x-text="'Rp ' + (landingAmount || 0).toLocaleString('id-ID')"></p>

                                    <!-- Quick Preset Buttons & Interactive Slider -->
                                    <div class="pt-0.5 flex items-center gap-1.5">
                                        <button type="button" @click="setAmount(3000000)"
                                                :class="landingAmount === 3000000 ? 'bg-[#FFD54F] border-[#263238] shadow-[1px_1px_0_#263238]' : 'bg-white border-[#263238]/25 text-[#526066]'"
                                                class="px-2.5 py-1 rounded-xl border-2 text-[11px] font-black transition-all cursor-pointer">
                                            3 Jt
                                        </button>
                                        <button type="button" @click="setAmount(5000000)"
                                                :class="landingAmount === 5000000 ? 'bg-[#FFD54F] border-[#263238] shadow-[1px_1px_0_#263238]' : 'bg-white border-[#263238]/25 text-[#526066]'"
                                                class="px-2.5 py-1 rounded-xl border-2 text-[11px] font-black transition-all cursor-pointer">
                                            5 Jt
                                        </button>
                                        <button type="button" @click="setAmount(10000000)"
                                                :class="landingAmount === 10000000 ? 'bg-[#FFD54F] border-[#263238] shadow-[1px_1px_0_#263238]' : 'bg-white border-[#263238]/25 text-[#526066]'"
                                                class="px-2.5 py-1 rounded-xl border-2 text-[11px] font-black transition-all cursor-pointer">
                                            10 Jt
                                        </button>
                                        <input type="range" min="1000000" max="15000000" step="500000" x-model.number="landingAmount"
                                               @input="isCustomLocked = (landingAmount != 5000000)"
                                               aria-label="Ubah Nominal Saldo" class="ml-1 flex-1 accent-[#245AE0] cursor-pointer h-2 bg-white rounded-lg border border-[#263238]/30">
                                    </div>

                                    <!-- Segmented Color Budget Bar (Inspired by Editorial Layout in Gambar 1) -->
                                    <div class="pt-1.5 border-t border-[#263238]/10">
                                        <div class="h-2.5 w-full bg-white rounded-full border border-[#263238] flex overflow-hidden p-0.5 gap-0.5">
                                            <div class="h-full bg-[#45C486] rounded-full transition-all duration-300" style="width: 60%;" title="Kebutuhan 60%"></div>
                                            <div class="h-full bg-[#FFD54F] rounded-full transition-all duration-300" style="width: 30%;" title="Keinginan 30%"></div>
                                            <div class="h-full bg-[#9B7EDE] rounded-full transition-all duration-300" style="width: 10%;" title="Tabungan / Darurat 10%"></div>
                                        </div>
                                        <div class="flex items-center justify-between text-[9px] font-black text-[#526066] mt-1 px-1">
                                            <span class="flex items-center gap-1 text-[#114A2D]"><span class="w-1.5 h-1.5 rounded-full bg-[#45C486]"></span> 60% Pokok</span>
                                            <span class="flex items-center gap-1 text-[#634800]"><span class="w-1.5 h-1.5 rounded-full bg-[#FFD54F]"></span> 30% Keinginan</span>
                                            <span class="flex items-center gap-1 text-[#3A1D75]"><span class="w-1.5 h-1.5 rounded-full bg-[#9B7EDE]"></span> 10% Tabungan</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3 Expressive Budget Blocks (Vibrant & Tactile) -->
                                <div class="grid grid-cols-3 gap-2 mb-3">
                                    <!-- Kebutuhan Block -->
                                    <div class="p-2 sm:p-2.5 rounded-2xl bg-[#E8F8F0] border-2 border-[#263238] flex flex-col justify-between shadow-[1.5px_1.5px_0_#263238] hover:-translate-y-0.5 transition-transform">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[9px] font-black uppercase text-[#124D30] tracking-wider">Kebutuhan</span>
                                            <template x-if="!isCustomLocked"><x-duitmoo-mascot variant="eyes" class="w-4 h-2" /></template>
                                            <template x-if="isCustomLocked"><x-duitmoo-mascot variant="locked-eyes" class="w-4 h-2" /></template>
                                        </div>
                                        <div>
                                            <template x-if="!isCustomLocked">
                                                <p class="text-xs font-black text-[#263238] tabular-nums" x-text="'Rp ' + landingNeedsAlloc.toLocaleString('id-ID')"></p>
                                            </template>
                                            <template x-if="isCustomLocked">
                                                <p class="text-[10px] font-black text-[#8F1D1D]">🔒 Kustom</p>
                                            </template>
                                            <div class="flex items-center justify-between text-[8px] font-bold text-[#124D30] mt-0.5">
                                                <span>Pos 1</span>
                                                <span class="font-black bg-white/70 px-1 rounded">60%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Keinginan Block -->
                                    <div class="p-2 sm:p-2.5 rounded-2xl bg-[#FFF2CC] border-2 border-[#263238] flex flex-col justify-between shadow-[1.5px_1.5px_0_#263238] hover:-translate-y-0.5 transition-transform">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[9px] font-black uppercase text-[#263238] tracking-wider">Keinginan</span>
                                            <template x-if="!isCustomLocked"><x-duitmoo-mascot variant="eyes" class="w-4 h-2" /></template>
                                            <template x-if="isCustomLocked"><x-duitmoo-mascot variant="locked-eyes" class="w-4 h-2" /></template>
                                        </div>
                                        <div>
                                            <template x-if="!isCustomLocked">
                                                <p class="text-xs font-black text-[#263238] tabular-nums" x-text="'Rp ' + landingWantsAlloc.toLocaleString('id-ID')"></p>
                                            </template>
                                            <template x-if="isCustomLocked">
                                                <p class="text-[10px] font-black text-[#8F1D1D]">🔒 Kustom</p>
                                            </template>
                                            <div class="flex items-center justify-between text-[8px] font-bold text-[#263238] mt-0.5">
                                                <span>Pos 2</span>
                                                <span class="font-black bg-white/70 px-1 rounded">30%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabungan / Darurat Block -->
                                    <div class="p-2 sm:p-2.5 rounded-2xl bg-[#F1EBFF] border-2 border-[#263238] flex flex-col justify-between shadow-[1.5px_1.5px_0_#263238] hover:-translate-y-0.5 transition-transform">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[9px] font-black uppercase text-[#3C1F7A] tracking-wider">Tabungan</span>
                                            <template x-if="!isCustomLocked"><x-duitmoo-mascot variant="eyes" class="w-4 h-2" /></template>
                                            <template x-if="isCustomLocked"><x-duitmoo-mascot variant="locked-eyes" class="w-4 h-2" /></template>
                                        </div>
                                        <div>
                                            <template x-if="!isCustomLocked">
                                                <p class="text-xs font-black text-[#263238] tabular-nums" x-text="'Rp ' + landingEmergAlloc.toLocaleString('id-ID')"></p>
                                            </template>
                                            <template x-if="isCustomLocked">
                                                <p class="text-[10px] font-black text-[#8F1D1D]">🔒 Kustom</p>
                                            </template>
                                            <div class="flex items-center justify-between text-[8px] font-bold text-[#3C1F7A] mt-0.5">
                                                <span>Pos 3</span>
                                                <span class="font-black bg-white/70 px-1 rounded">10%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mini Locked Notice (Muncul Saat Slider Digeser) -->
                                <div x-show="isCustomLocked" x-cloak class="mb-3 p-2.5 rounded-2xl bg-[#FFF2CC] border-2 border-[#263238] text-center space-y-1 shadow-[2px_2px_0_#263238] transition-all">
                                    <div class="flex items-center justify-center gap-1.5 text-xs font-black text-[#263238]">
                                        <x-duitmoo-mascot variant="locked-eyes" class="w-5 h-2.5" />
                                        <span>Mata Terkunci! Daftar untuk Melihat Lengkap</span>
                                    </div>
                                    <p class="text-[10px] text-[#546E7A] font-medium leading-tight">
                                        Daftar akun gratis sekarang untuk membuka simulasi nominal kustom &amp; menyimpan anggaranmu.
                                    </p>
                                    <div class="pt-0.5 flex items-center justify-center gap-2">
                                        <a href="{{ route('register') }}" class="brick-btn brick-btn-blue px-3 py-1 text-[11px] font-black shadow-[1px_1px_0_#263238]">
                                            Daftar Gratis
                                        </a>
                                        <button type="button" @click="resetAmount()" class="px-2.5 py-0.5 rounded-lg border border-[#263238]/30 bg-white text-[10px] font-bold text-[#263238] cursor-pointer">
                                            Kembalikan
                                        </button>
                                    </div>
                                </div>

                                <!-- Realistic Daily Expense Feed Preview with Interactive Lock Overlay -->
                                <div class="rounded-2xl border-2 border-[#263238] bg-[#FCF6EB] p-2.5 relative overflow-hidden shadow-[2px_2px_0_#263238]">
                                    <!-- Sample Mini Transactions (Gives Depth & Realism like Gambar 1) -->
                                    <div class="space-y-1.5 opacity-40 filter blur-[0.8px] select-none pointer-events-none">
                                        <div class="flex items-center justify-between p-1.5 rounded-xl bg-white border border-[#263238]/20 text-[10px]">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-6 h-6 rounded-lg bg-[#FFEAA0] border border-[#263238]/20 flex items-center justify-center text-xs">☕</span>
                                                <div>
                                                    <span class="font-black text-[#263238] block leading-tight">Kopi Sore &amp; Cemilan</span>
                                                    <span class="text-[8px] text-[#526066]">Keinginan</span>
                                                </div>
                                            </div>
                                            <span class="font-black text-[#C93535]">-Rp 28.000</span>
                                        </div>
                                        <div class="flex items-center justify-between p-1.5 rounded-xl bg-white border border-[#263238]/20 text-[10px]">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-6 h-6 rounded-lg bg-[#D8F5E5] border border-[#263238]/20 flex items-center justify-center text-xs">🛒</span>
                                                <div>
                                                    <span class="font-black text-[#263238] block leading-tight">Belanja Supermarket</span>
                                                    <span class="text-[8px] text-[#526066]">Kebutuhan</span>
                                                </div>
                                            </div>
                                            <span class="font-black text-[#C93535]">-Rp 175.000</span>
                                        </div>
                                    </div>

                                    <!-- Frosted Neo-Brutalism Action Overlay -->
                                    <div class="absolute inset-0 bg-[#FFFDF9]/85 backdrop-blur-[2px] flex flex-col items-center justify-center p-3 text-center">
                                        <div class="flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#FFD54F] border border-[#263238] text-[10px] font-black shadow-[1px_1px_0_#263238] mb-1">
                                            <x-icon name="lock" class="w-3 h-3" />
                                            <span>Catatan &amp; Analisis Lengkap</span>
                                        </div>
                                        <p class="text-[10px] text-[#526066] font-medium leading-tight mb-2 max-w-[210px]">
                                            Masuk untuk mencatat pengeluaran harian dan sinkronisasi ke Google Sheets otomatis.
                                        </p>
                                        <div class="flex items-center gap-2 w-full max-w-[240px]">
                                            <a href="{{ route('login') }}" class="brick-btn brick-btn-blue flex-1 py-1.5 text-[11px] font-black shadow-[1.5px_1.5px_0_#263238]">
                                                Masuk
                                            </a>
                                            <a href="{{ route('register') }}" class="brick-btn brick-btn-yellow flex-1 py-1.5 text-[11px] font-black shadow-[1.5px_1.5px_0_#263238]">
                                                Daftar Gratis
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Floating Ergonomic Bottom Nav Pill (Like Gambar 1 & Gambar 2) -->
                                <div class="mt-3 p-1.5 rounded-2xl bg-[#FFFDF9] border-2 border-[#263238] flex items-center justify-between px-2.5 shadow-[1.5px_1.5px_0_#263238]">
                                    <div class="flex items-center gap-1">
                                        <span class="px-2 py-1 rounded-xl bg-[#FFEAA0] text-[#263238] border border-[#263238] text-[10px] font-black flex items-center gap-1 shadow-[1px_1px_0_#263238]">
                                            <x-icon name="dashboard" class="w-3.5 h-3.5" />
                                            <span>Beranda</span>
                                        </span>
                                        <span class="p-1 text-[#526066]">
                                            <x-icon name="credit-card" class="w-3.5 h-3.5" />
                                        </span>
                                    </div>
                                    <a href="{{ route('login') }}"
                                       class="w-7 h-7 -my-1 rounded-xl bg-[#FFD54F] border-2 border-[#263238] text-[#263238] flex items-center justify-center shadow-[1.5px_1.5px_0_#263238] hover:scale-110 active:scale-95 transition-transform"
                                       aria-label="Catat">
                                        <x-icon name="plus" class="w-3.5 h-3.5 stroke-[3]" />
                                    </a>
                                    <div class="flex items-center gap-1.5 text-[#526066]">
                                        <span class="p-1">
                                            <x-icon name="piggy-bank" class="w-3.5 h-3.5" />
                                        </span>
                                        <span class="p-1">
                                            <x-icon name="user" class="w-3.5 h-3.5" />
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Callout Strip Below Device -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-xs font-bold text-[#546E7A]">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#45C486]"></span>
                        Tanpa Akses Rekening Bank
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#407BFF]"></span>
                        100% Catatan Mandiri Privat
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#FFD54F]"></span>
                        PWA Siap Pasang di Layar HP
                    </span>
                </div>

            </section>

            <!-- 3. Section: Cara Kerja Simple (3 Clean Floating Tiles) -->
            <section id="cara-kerja" class="py-12 sm:py-16 px-4 sm:px-8 max-w-5xl mx-auto w-full">
                <div class="text-center max-w-xl mx-auto mb-10">
                    <span class="px-3 py-1 rounded-full bg-[#E8F8F0] border border-[#263238]/20 text-xs font-bold text-[#124D30] inline-block mb-2 shadow-[1px_1px_0_#263238]">
                        Mudah &amp; Nyaman
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-black text-[#263238] tracking-tight">Tiga Langkah Mengatur Uangmu</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Step 1 -->
                    <div class="p-6 rounded-3xl bg-white border-2 border-[#263238] shadow-[4px_4px_0_#263238] hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 rounded-2xl bg-[#FFF2CC] border-2 border-[#263238] flex items-center justify-center text-[#263238] font-black text-lg mb-4 shadow-[2px_2px_0_#263238]">
                            1
                        </div>
                        <h3 class="text-base font-black text-[#263238] mb-1.5">Tentukan Saldo &amp; Rasio</h3>
                        <p class="text-xs text-[#546E7A] leading-relaxed">
                            Bagi uangmu ke Kebutuhan, Keinginan, dan Darurat. DuitMoo otomatis menghitung alokasi presisi tanpa selisih Rupiah.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="p-6 rounded-3xl bg-white border-2 border-[#263238] shadow-[4px_4px_0_#263238] hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 rounded-2xl bg-[#E8F8F0] border-2 border-[#263238] flex items-center justify-center text-[#124D30] font-black text-lg mb-4 shadow-[2px_2px_0_#263238]">
                            2
                        </div>
                        <h3 class="text-base font-black text-[#263238] mb-1.5">Catat Sambil Jalan</h3>
                        <p class="text-xs text-[#546E7A] leading-relaxed">
                            Buka langsung dari HP tanpa repot buka laptop. Masukkan pengeluaran kecil harian dalam hitungan detik.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="p-6 rounded-3xl bg-white border-2 border-[#263238] shadow-[4px_4px_0_#263238] hover:-translate-y-1 transition-transform">
                        <div class="w-12 h-12 rounded-2xl bg-[#F1EBFF] border-2 border-[#263238] flex items-center justify-center text-[#3C1F7A] font-black text-lg mb-4 shadow-[2px_2px_0_#263238]">
                            3
                        </div>
                        <h3 class="text-base font-black text-[#263238] mb-1.5">Ketahui Sisa Nyata</h3>
                        <p class="text-xs text-[#546E7A] leading-relaxed">
                            Lihat sisa uang per pos di dashboard interaktif. Tahu kapan harus berhemat dan kapan aman berbelanja santai.
                        </p>
                    </div>
                </div>
            </section>

            <!-- 4. Section: Privacy & Guarantee -->
            <section id="komitmen" class="py-10 sm:py-14 px-4 sm:px-8 max-w-5xl mx-auto w-full">
                <div class="p-6 sm:p-9 rounded-[32px] bg-white border-2 border-[#263238] shadow-[4px_4px_0_#263238] flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="space-y-2 max-w-lg">
                        <span class="px-2.5 py-0.5 rounded-full bg-[#E5F2FF] border border-[#263238]/20 text-[11px] font-bold text-[#163C94]">
                            Komitmen Privasi
                        </span>
                        <h3 class="text-xl sm:text-2xl font-black text-[#263238]">Catatan Finansialmu Aman &amp; Privat</h3>
                        <p class="text-xs text-[#546E7A] leading-relaxed">
                            Data catatan keuangan terisolasi penuh di akun pribadimu. Tidak ada transaksi perbankan nyata dan admin sistem tidak dapat melihat rincian catatan pengeluaranmu.
                        </p>
                    </div>
                    <div class="shrink-0 flex items-center gap-3">
                        <a href="{{ route('register') }}" class="brick-btn brick-btn-blue px-6 py-3 text-xs sm:text-sm font-black shadow-[2px_2px_0_#263238] hover:scale-105 transition-transform">
                            Mulai Sekarang
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <!-- 5. Calm Clean Footer -->
        <footer class="py-8 px-4 sm:px-8 max-w-6xl mx-auto w-full border-t border-[#263238]/10 relative z-10">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#546E7A]">
                <div class="flex items-center gap-3">
                    <x-brand-logo size="sm" />
                    <div>
                        <span class="font-black text-sm text-[#263238]">Duit<span class="text-[#245AE0]">Moo</span></span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-5 font-bold">
                    <button type="button" @click="privacyModal = true" class="hover:text-[#245AE0] underline cursor-pointer">Kebijakan Privasi</button>
                    <button type="button" @click="termsModal = true" class="hover:text-[#245AE0] underline cursor-pointer">Ketentuan Penggunaan</button>
                    <span>&copy; {{ date('Y') }} DuitMoo</span>
                </div>
            </div>
        </footer>

        <!-- Privacy Modal -->
        <div x-show="privacyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60 backdrop-blur-xs" @click="privacyModal = false"></div>
            <div class="brick-card-pop max-w-md w-full p-6 bg-white relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-black text-[#263238]">Kebijakan Privasi DuitMoo</h3>
                    <button type="button" @click="privacyModal = false" aria-label="Tutup dialog privasi" class="p-1.5 cursor-pointer"><x-icon name="x" class="w-5 h-5" /></button>
                </div>
                <p class="text-xs text-[#546E7A] leading-relaxed mb-5">
                    Seluruh catatan saldo, anggaran, dan transaksi tersimpan terpisah berdasarkan identitas akunmu. Administrator sistem hanya melihat statistik operasional agregat dan tidak mengakses catatan nominal keuangan pribadimu.
                </p>
                <button type="button" @click="privacyModal = false" class="brick-btn brick-btn-blue w-full py-2.5 text-xs font-black">Tutup</button>
            </div>
        </div>

        <!-- Terms Modal -->
        <div x-show="termsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60 backdrop-blur-xs" @click="termsModal = false"></div>
            <div class="brick-card-pop max-w-md w-full p-6 bg-white relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-black text-[#263238]">Ketentuan Penggunaan</h3>
                    <button type="button" @click="termsModal = false" aria-label="Tutup dialog ketentuan" class="p-1.5 cursor-pointer"><x-icon name="x" class="w-5 h-5" /></button>
                </div>
                <p class="text-xs text-[#546E7A] leading-relaxed mb-5">
                    DuitMoo berfungsi sebagai alat bantu pencatatan dan simulasi anggaran pribadi. Seluruh nilai berasal dari input manual pengguna dan pembagian anggaran bersifat virtual.
                </p>
                <button type="button" @click="termsModal = false" class="brick-btn brick-btn-yellow w-full py-2.5 text-xs font-black">Saya Mengerti</button>
            </div>
        </div>

    </div>
</x-guest-layout>