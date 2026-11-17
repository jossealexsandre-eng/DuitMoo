<div class="min-h-screen py-8 px-4 max-w-4xl mx-auto flex flex-col justify-center">
    <div class="text-center mb-7">
        <div class="flex justify-center mb-2">
            <x-duitmoo-mascot variant="peek" class="w-24 h-12" />
        </div>
        <h1 class="text-3xl sm:text-4xl font-black text-[#263238] tracking-tight">Bangun Balok Anggaranmu</h1>
        <p class="text-sm sm:text-base text-[#546E7A] max-w-xl mx-auto mt-2">
            Masukkan uang yang tersedia saat ini dan tentukan pembagiannya ke dalam 3 pos anggaran.
        </p>
    </div>

    <div class="brick-card-pop p-6 sm:p-10 bg-white space-y-8">
        <!-- Step 1 -->
        <div>
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 rounded-xl bg-[#245AE0] text-white font-black text-sm flex items-center justify-center border-2 border-[#263238]">1</span>
                <h2 class="text-xl font-black text-[#263238]">Saldo Awal &amp; Periode Bulan</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="ob-month" class="block text-xs font-bold text-[#37474F] mb-1.5">
                        Bulan anggaran
                    </label>
                    <input id="ob-month" type="month" wire:model.live="budgetMonth"
                           class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                </div>

                <div>
                    <label for="ob-balance" class="block text-xs font-bold text-[#37474F] mb-1.5">
                        Saldo awal saat ini (Rp)
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-gray-500 text-sm">Rp</span>
                        <input id="ob-balance" type="text" inputmode="numeric" wire:model.live.debounce.300ms="openingBalance"
                               placeholder="5000000"
                               class="w-full min-h-[46px] pl-12 pr-4 py-2.5 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-black text-base focus:bg-white focus:border-[#245AE0] focus:outline-none">
                    </div>
                    <p class="text-[11px] text-[#546E7A] mt-1 font-medium">
                        Saldo awal hanya uang awal saat mulai mencatat, tidak dihitung sebagai pemasukan ganda.
                    </p>
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="pt-6 border-t border-dashed border-[#263238]/15">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-[#FFD54F] text-[#263238] font-black text-sm flex items-center justify-center border-2 border-[#263238]">2</span>
                    <h2 class="text-xl font-black text-[#263238]">Atur Persentase Alokasi</h2>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="setPreset(60, 30, 10)"
                            class="min-h-[40px] px-3.5 py-1.5 text-xs font-bold rounded-xl border-2 border-[#263238] cursor-pointer transition {{ $needsPercent == 60 && $wantsPercent == 30 && $emergencyPercent == 10 ? 'bg-[#FFD54F] shadow-[2px_2px_0_#263238]' : 'bg-white hover:bg-[#FCF6EB]' }}">
                        Default 60/30/10
                    </button>
                    <button type="button" wire:click="setPreset(50, 30, 20)"
                            class="min-h-[40px] px-3.5 py-1.5 text-xs font-bold rounded-xl border-2 border-[#263238] cursor-pointer transition {{ $needsPercent == 50 && $wantsPercent == 30 && $emergencyPercent == 20 ? 'bg-[#FFD54F] shadow-[2px_2px_0_#263238]' : 'bg-white hover:bg-[#FCF6EB]' }}">
                        Seimbang 50/30/20
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl border-2 border-[#263238] bg-[#E8F8F0]">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-[#124D30] flex items-center gap-1.5">
                            <x-duitmoo-mascot variant="eyes" class="w-6 h-3" />
                            Kebutuhan
                        </span>
                        <span class="text-lg font-black text-[#263238]">{{ $needsPercent }}%</span>
                    </div>
                    <input type="range" min="0" max="100" wire:model.live="needsPercent" aria-label="Persentase Kebutuhan" class="w-full accent-[#1E824C] cursor-pointer">
                    <p class="text-[11px] text-[#124D30] mt-1 font-medium">Makan, transport, tempat tinggal, tagihan.</p>
                </div>

                <div class="p-4 rounded-2xl border-2 border-[#263238] bg-[#FFF2CC]">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-[#263238] flex items-center gap-1.5">
                            <x-duitmoo-mascot variant="eyes" class="w-6 h-3" />
                            Keinginan
                        </span>
                        <span class="text-lg font-black text-[#263238]">{{ $wantsPercent }}%</span>
                    </div>
                    <input type="range" min="0" max="100" wire:model.live="wantsPercent" aria-label="Persentase Keinginan" class="w-full accent-[#263238] cursor-pointer">
                    <p class="text-[11px] text-[#263238] mt-1 font-medium">Hiburan, nongkrong, belanja, hobi.</p>
                </div>

                <div class="p-4 rounded-2xl border-2 border-[#263238] bg-[#F1EBFF]">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-[#3C1F7A] flex items-center gap-1.5">
                            <x-duitmoo-mascot variant="eyes" class="w-6 h-3" />
                            Dana Darurat
                        </span>
                        <span class="text-lg font-black text-[#263238]">{{ $emergencyPercent }}%</span>
                    </div>
                    <input type="range" min="0" max="100" wire:model.live="emergencyPercent" aria-label="Persentase Dana Darurat" class="w-full accent-[#6B47B8] cursor-pointer">
                    <p class="text-[11px] text-[#3C1F7A] mt-1 font-medium">Keperluan mendesak &amp; kesehatan.</p>
                </div>
            </div>

            @if($errorMessage)
                <div class="mt-4 p-3.5 bg-[#FFE8E8] text-[#8F1D1D] rounded-xl border-2 border-[#263238] text-xs font-bold flex items-center gap-2">
                    <x-icon name="alert-triangle" class="w-5 h-5 shrink-0" />
                    <span>{{ $errorMessage }}</span>
                </div>
            @endif
        </div>

        <!-- Step 3 -->
        <div class="pt-6 border-t border-dashed border-[#263238]/15">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 rounded-xl bg-[#45C486] text-[#113D26] font-black text-sm flex items-center justify-center border-2 border-[#263238]">3</span>
                <h2 class="text-xl font-black text-[#263238]">Hasil Pembagian Otomatis</h2>
            </div>

            @if($allocation)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-5 rounded-2xl bg-[#E8F8F0] border-2 border-[#263238]">
                        <span class="brick-badge bg-white text-[#124D30] text-[11px]">Kebutuhan ({{ $needsPercent }}%)</span>
                        <p class="text-xl sm:text-2xl font-black text-[#263238] mt-3 tabular-nums">
                            Rp {{ number_format($allocation['needs'], 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-[#FFF2CC] border-2 border-[#263238]">
                        <span class="brick-badge bg-white text-[#263238] text-[11px]">Keinginan ({{ $wantsPercent }}%)</span>
                        <p class="text-xl sm:text-2xl font-black text-[#263238] mt-3 tabular-nums">
                            Rp {{ number_format($allocation['wants'], 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-[#F1EBFF] border-2 border-[#263238]">
                        <span class="brick-badge bg-white text-[#3C1F7A] text-[11px]">Dana Darurat ({{ $emergencyPercent }}%)</span>
                        <p class="text-xl sm:text-2xl font-black text-[#263238] mt-3 tabular-nums">
                            Rp {{ number_format($allocation['emergency'], 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 p-4 rounded-2xl bg-[#FCF6EB] border border-[#263238]/15 flex items-center justify-between font-bold text-sm">
                    <span class="text-[#37474F]">Total uang dialokasikan:</span>
                    <span class="text-lg font-black text-[#263238] tabular-nums">
                        Rp {{ number_format($allocation['total'], 0, ',', '.') }}
                    </span>
                </div>
            @endif
        </div>

        <div class="pt-6 border-t border-[#263238]/15">
            <button type="button" wire:click="saveBudget"
                    class="w-full py-3.5 brick-btn brick-btn-blue text-base font-black">
                Simpan Anggaran &amp; Masuk Dashboard
            </button>
        </div>
    </div>
</div>
