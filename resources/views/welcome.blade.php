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

                <!-- Interactive Phone Playground Card (Centered, Clean, Animated) -->
                <div class="max-w-[420px] mx-auto">
                    <div class="bg-[#FFFDF9] rounded-[38px] border-3 border-[#263238] p-5 sm:p-6 shadow-[6px_6px_0_#263238] hover:shadow-[8px_8px_0_#263238] transition-all relative overflow-hidden text-left">
                        
                        <!-- Phone Speaker Notch -->
                        <div class="w-20 h-1.5 bg-[#263238]/20 rounded-full mx-auto mb-4"></div>

                        <!-- Header App Strip (Gambar Pertama Hilang sesuai permintaan) -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-[11px] font-bold text-[#546E7A] block">Simulasi Anggaran</span>
                                <h2 class="text-xl font-black text-[#263238]">Papan Keuangan</h2>
                            </div>
                        </div>

                        <!-- Interactive Balance Setter Box -->
                        <div class="p-4 rounded-2xl bg-[#FCF6EB] border-2 border-[#263238]/15 mb-4 space-y-2">
                            <div class="flex justify-between items-center text-xs font-bold text-[#546E7A]">
                                <span>Saldo Awal yang Dimiliki</span>
                                <span class="text-[#245AE0]">Geser &darr;</span>
                            </div>
                            <p class="text-2xl sm:text-3xl font-black text-[#263238] tabular-nums" x-text="'Rp ' + (landingAmount || 0).toLocaleString('id-ID')"></p>

                            <!-- Interactive Nominal Presets & Slider -->
                            <div class="pt-1 flex items-center gap-2">
                                <button type="button" @click="setAmount(3000000)"
                                        :class="landingAmount === 3000000 ? 'bg-[#FFD54F] border-[#263238] scale-105' : 'bg-white border-[#263238]/20'"
                                        class="px-2.5 py-1 rounded-xl border-2 text-[11px] font-black text-[#263238] transition-transform cursor-pointer">
                                    3 Jt
                                </button>
                                <button type="button" @click="setAmount(5000000)"
                                        :class="landingAmount === 5000000 ? 'bg-[#FFD54F] border-[#263238] scale-105' : 'bg-white border-[#263238]/20'"
                                        class="px-2.5 py-1 rounded-xl border-2 text-[11px] font-black text-[#263238] transition-transform cursor-pointer">
                                    5 Jt
                                </button>
                                <button type="button" @click="setAmount(10000000)"
                                        :class="landingAmount === 10000000 ? 'bg-[#FFD54F] border-[#263238] scale-105' : 'bg-white border-[#263238]/20'"
                                        class="px-2.5 py-1 rounded-xl border-2 text-[11px] font-black text-[#263238] transition-transform cursor-pointer">
                                    10 Jt
                                </button>
                                <input type="range" min="1000000" max="15000000" step="500000" x-model.number="landingAmount"
                                       @input="isCustomLocked = (landingAmount != 5000000)"
                                       aria-label="Ubah Nominal Saldo" class="ml-1 w-full accent-[#245AE0] cursor-pointer">
                            </div>
                        </div>

                        <!-- 3 Expressive Budget Bricks with Dynamic Locked Eyes on Slider Move -->
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            <!-- Needs Block -->
                            <div class="p-2.5 sm:p-3 rounded-2xl bg-[#E8F8F0] border-2 border-[#263238] flex flex-col justify-between hover:-translate-y-1 transition-transform">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-black text-[#124D30]">Kebutuhan</span>
                                    <template x-if="!isCustomLocked">
                                        <x-duitmoo-mascot variant="eyes" class="w-5 h-2.5" />
                                    </template>
                                    <template x-if="isCustomLocked">
                                        <x-duitmoo-mascot variant="locked-eyes" class="w-5 h-2.5" />
                                    </template>
                                </div>
                                <div>
                                    <template x-if="!isCustomLocked">
                                        <p class="text-xs font-black text-[#263238] tabular-nums" x-text="'Rp ' + landingNeedsAlloc.toLocaleString('id-ID')"></p>
                                    </template>
                                    <template x-if="isCustomLocked">
                                        <p class="text-[11px] font-black text-[#8F1D1D] flex items-center gap-0.5">🔒 Terkunci</p>
                                    </template>
                                    <span class="text-[9px] font-bold text-[#124D30]">60%</span>
                                </div>
                            </div>

                            <!-- Wants Block -->
                            <div class="p-2.5 sm:p-3 rounded-2xl bg-[#FFF2CC] border-2 border-[#263238] flex flex-col justify-between hover:-translate-y-1 transition-transform">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-black text-[#263238]">Keinginan</span>
                                    <template x-if="!isCustomLocked">
                                        <x-duitmoo-mascot variant="eyes" class="w-5 h-2.5" />
                                    </template>
                                    <template x-if="isCustomLocked">
                                        <x-duitmoo-mascot variant="locked-eyes" class="w-5 h-2.5" />
                                    </template>
                                </div>
                                <div>
                                    <template x-if="!isCustomLocked">
                                        <p class="text-xs font-black text-[#263238] tabular-nums" x-text="'Rp ' + landingWantsAlloc.toLocaleString('id-ID')"></p>
                                    </template>
                                    <template x-if="isCustomLocked">
                                        <p class="text-[11px] font-black text-[#8F1D1D] flex items-center gap-0.5">🔒 Terkunci</p>
                                    </template>
                                    <span class="text-[9px] font-bold text-[#263238]">30%</span>
                                </div>
                            </div>

                            <!-- Emergency Block -->
                            <div class="p-2.5 sm:p-3 rounded-2xl bg-[#F1EBFF] border-2 border-[#263238] flex flex-col justify-between hover:-translate-y-1 transition-transform">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-black text-[#3C1F7A]">Darurat</span>
                                    <template x-if="!isCustomLocked">
                                        <x-duitmoo-mascot variant="eyes" class="w-5 h-2.5" />
                                    </template>
                                    <template x-if="isCustomLocked">
                                        <x-duitmoo-mascot variant="locked-eyes" class="w-5 h-2.5" />
                                    </template>
                                </div>
                                <div>
                                    <template x-if="!isCustomLocked">
                                        <p class="text-xs font-black text-[#263238] tabular-nums" x-text="'Rp ' + landingEmergAlloc.toLocaleString('id-ID')"></p>
                                    </template>
                                    <template x-if="isCustomLocked">
                                        <p class="text-[11px] font-black text-[#8F1D1D] flex items-center gap-0.5">🔒 Terkunci</p>
                                    </template>
                                    <span class="text-[9px] font-bold text-[#3C1F7A]">10%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Locked Notice (Muncul Saat Slider Digeser) -->
                        <div x-show="isCustomLocked" x-cloak class="mb-4 p-3 rounded-2xl bg-[#FFF2CC] border-2 border-[#263238] text-center space-y-1.5 shadow-[2px_2px_0_#263238] transition-all">
                            <div class="flex items-center justify-center gap-1.5 text-xs font-black text-[#263238]">
                                <x-duitmoo-mascot variant="locked-eyes" class="w-6 h-3" />
                                <span>Mata Terkunci! Daftar untuk Melihat Lengkap</span>
                            </div>
                            <p class="text-[11px] text-[#546E7A] font-medium leading-tight">
                                Daftar akun gratis sekarang untuk membuka simulasi nominal kustom &amp; menyimpan anggaranmu.
                            </p>
                            <div class="pt-1 flex items-center justify-center gap-2">
                                <a href="{{ route('register') }}" class="brick-btn brick-btn-blue px-3.5 py-1.5 text-xs font-black shadow-[1px_1px_0_#263238]">
                                    Daftar Gratis
                                </a>
                                <button type="button" @click="resetAmount()" class="px-3 py-1 rounded-xl border border-[#263238]/30 bg-white text-[11px] font-bold text-[#263238] cursor-pointer">
                                    Kembalikan
                                </button>
                            </div>
                        </div>

                        <!-- Private Locked State for Financial Transactions (HANYA BISA DILIHAT KALAU LOGIN DULU) -->
                        <div class="p-5 rounded-3xl bg-[#FCF6EB] border-2 border-[#263238] text-center space-y-3 relative overflow-hidden shadow-[2px_2px_0_#263238]">
                            <div class="w-11 h-11 rounded-2xl bg-[#FFF2CC] border-2 border-[#263238] mx-auto flex items-center justify-center text-[#263238] shadow-[2px_2px_0_#263238] anim-cow-bob">
                                <x-icon name="lock" class="w-5 h-5 text-[#263238]" />
                            </div>

                            <div>
                                <h3 class="text-sm font-black text-[#263238]">Catatan Belanja &amp; Sisa Keuangan</h3>
                                <p class="text-[11px] text-[#546E7A] mt-1 font-medium leading-relaxed max-w-[270px] mx-auto">
                                    Catatan transaksi harian dan grafik analisis hanya bisa diakses setelah kamu masuk ke akunmu.
                                </p>
                            </div>

                            <div class="pt-1 flex flex-col sm:flex-row items-center gap-2">
                                <a href="{{ route('login') }}" class="brick-btn brick-btn-blue flex-1 w-full py-2.5 text-xs font-black flex items-center justify-center gap-1.5 shadow-[2px_2px_0_#263238]">
                                    <x-icon name="log-in" class="w-4 h-4" />
                                    <span>Masuk ke Akun</span>
                                </a>
                                <a href="{{ route('register') }}" class="brick-btn brick-btn-yellow flex-1 w-full py-2.5 text-xs font-black flex items-center justify-center gap-1.5 shadow-[2px_2px_0_#263238]">
                                    <span>Buat Akun Baru</span>
                                </a>
                            </div>
                        </div>

                        <!-- Bottom Floating Pill Bar -->
                        <div class="mt-4 p-2 rounded-2xl bg-[#FCF6EB] border border-[#263238]/20 flex items-center justify-between px-4">
                            <div class="flex items-center gap-5 text-[#263238]">
                                <span class="p-1.5 rounded-xl bg-[#263238] text-white">
                                    <x-icon name="layout-dashboard" class="w-4 h-4" />
                                </span>
                                <span class="text-gray-400">
                                    <x-icon name="receipt" class="w-4 h-4" />
                                </span>
                                <span class="text-gray-400">
                                    <x-icon name="pie-chart" class="w-4 h-4" />
                                </span>
                            </div>
                            <a href="{{ route('login') }}" class="w-8 h-8 rounded-xl bg-[#263238] text-white flex items-center justify-center hover:scale-105 transition-transform" aria-label="Masuk">
                                <x-icon name="plus" class="w-4 h-4" />
                            </a>
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