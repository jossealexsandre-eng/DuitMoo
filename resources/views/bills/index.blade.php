<x-app-layout title="Tagihan Rutin">
    <div x-data="{
        showBillModal: false,
        billAmountRaw: '',
        selectedBillIcon: '⚡',
        selectedBillBucket: 'needs',
        formatPreview(raw) {
            const num = parseInt(String(raw).replace(/[^0-9]/g, ''), 10);
            return isNaN(num) || num <= 0 ? 'Rp 0' : 'Rp ' + num.toLocaleString('id-ID');
        }
    }"
    @keydown.escape.window="showBillModal = false">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#263238]">
                    Pengingat Tagihan Rutin
                </h1>
                <p class="text-xs sm:text-sm text-[#526066] font-medium mt-0.5">
                    Pantau jadwal jatuh tempo tagihan bulanan (WiFi, Listrik, Kosan, Langganan) periode {{ $monthLabel }}.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <form method="GET" action="{{ route('bills.index') }}" class="flex items-center">
                    <label for="bill-month-select" class="sr-only">Pilih Bulan</label>
                    <select id="bill-month-select" name="month" onchange="this.form.submit()"
                            class="min-h-[42px] px-3.5 py-2 rounded-xl border-2 border-[#263238] bg-white font-bold text-xs sm:text-sm focus:outline-none cursor-pointer">
                        @foreach($availableMonths as $val => $label)
                            <option value="{{ $val }}" {{ $selectedMonth === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <button type="button" @click="showBillModal = true; triggerFeedback('tap')"
                        class="brick-btn brick-btn-yellow px-4 py-2 text-xs sm:text-sm font-black flex items-center gap-2 cursor-pointer">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Tambah Tagihan</span>
                </button>
            </div>
        </div>

        <!-- Summary Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-white shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#526066]">Total Tagihan Bulanan</span>
                    <span class="w-8 h-8 rounded-xl bg-[#FFF9E6] border border-[#263238] flex items-center justify-center text-sm">📅</span>
                </div>
                <p class="text-xl sm:text-2xl font-black text-[#263238] mt-2">
                    <span x-show="!hideBalance">Rp {{ number_format($totalMonthlyBills, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                </p>
                <span class="text-xs font-bold text-[#526066] mt-1">{{ $recurringBills->count() }} tagihan aktif</span>
            </div>

            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-[#EDFAF3] shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#114A2D]">Sudah Lunas ({{ $monthLabel }})</span>
                    <span class="w-8 h-8 rounded-xl bg-[#D8F5E5] border border-[#263238] flex items-center justify-center text-sm">✓</span>
                </div>
                <p class="text-xl sm:text-2xl font-black text-[#1C7C49] mt-2">
                    <span x-show="!hideBalance">Rp {{ number_format($paidBillsAmount, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                </p>
                <span class="text-xs font-bold text-[#526066] mt-1">Tercatat di transaksi</span>
            </div>

            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-[#FFF0ED] shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#8C1D1D]">Belum Dibayar ({{ $monthLabel }})</span>
                    <span class="w-8 h-8 rounded-xl bg-[#FFE2DE] border border-[#263238] flex items-center justify-center text-sm">⚡</span>
                </div>
                <p class="text-xl sm:text-2xl font-black text-[#C93535] mt-2">
                    <span x-show="!hideBalance">Rp {{ number_format($unpaidBillsAmount, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                </p>
                <span class="text-xs font-bold text-[#526066] mt-1">Menunggu pelunasan</span>
            </div>
        </div>

        <!-- RECURRING BILLS LIST -->
        <div class="mb-8">
            @if($recurringBills->count() > 0)
                <div class="rounded-3xl border-2 border-[#263238] bg-white p-4 sm:p-6 shadow-[3px_3px_0_#263238] space-y-3">
                    @foreach($recurringBills as $bill)
                        @php
                            $status = $bill->getStatusBadgeForMonth($selectedMonth);
                            $bucketLabel = match($bill->bucket) {
                                'wants' => 'Keinginan',
                                'emergency' => 'Dana Darurat',
                                default => 'Kebutuhan',
                            };
                        @endphp
                        <div class="p-4 rounded-2xl {{ $status['is_paid'] ? 'bg-[#EDFAF3]' : 'bg-[#FCF6EB]' }} border-2 border-[#263238] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-11 h-11 rounded-2xl bg-white border-2 border-[#263238] shadow-[2px_2px_0_#263238] flex items-center justify-center text-xl shrink-0">
                                    {{ $bill->icon }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-black text-sm sm:text-base text-[#263238] truncate">{{ $bill->name }}</h2>
                                        <span class="px-2 py-0.5 rounded-lg border border-[#263238] {{ $status['bg'] }} {{ $status['text'] }} font-black text-[10px]">
                                            {{ $status['label'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#526066] font-medium mt-0.5">
                                        Jatuh tempo setiap tanggal <strong>{{ $bill->due_day }}</strong> · Pos {{ $bucketLabel }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-[#263238]/10">
                                <span class="font-black text-sm sm:text-base text-[#263238]">
                                    <span x-show="!hideBalance">Rp {{ number_format($bill->amount, 0, ',', '.') }}</span>
                                    <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                                </span>

                                <div class="flex items-center gap-2">
                                    @if(!$status['is_paid'])
                                        <form action="{{ route('bills.pay', $bill) }}" method="POST" @submit="triggerFeedback('coin')">
                                            @csrf
                                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                            <input type="hidden" name="record_transaction" value="1">
                                            <button type="submit"
                                                    class="brick-btn brick-btn-green px-3.5 py-2 text-xs font-black flex items-center gap-1.5 cursor-pointer">
                                                <x-icon name="check" class="w-3.5 h-3.5" />
                                                <span>Tandai Lunas</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-3 py-2 rounded-xl bg-[#D8F5E5] text-[#114A2D] border border-[#263238] text-xs font-black flex items-center gap-1">
                                            <x-icon name="check" class="w-3.5 h-3.5" />
                                            <span>Sudah Lunas</span>
                                        </span>
                                    @endif

                                    <form action="{{ route('bills.destroy', $bill) }}" method="POST" onsubmit="return confirm('Hapus pengingat tagihan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus tagihan {{ $bill->name }}"
                                                class="p-2 rounded-xl bg-white hover:bg-[#FFE2DE] border border-[#263238]/40 text-[#526066] hover:text-[#C93535] transition cursor-pointer">
                                            <x-icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-10 rounded-3xl bg-white border-2 border-dashed border-[#263238]/35 text-center flex flex-col items-center justify-center gap-2">
                    <div class="w-14 h-14 bg-[#FFF0ED] rounded-2xl border-2 border-[#263238] shadow-[2px_2px_0_#263238] flex items-center justify-center text-2xl mb-1">
                        ⚡
                    </div>
                    <p class="font-black text-base text-[#263238]">Belum ada pengingat tagihan rutin</p>
                    <p class="text-xs text-[#526066] max-w-sm mb-3">Tambahkan tagihan bulanan seperti Listrik, WiFi, Kosan, atau langganan streaming agar tidak lupa bayar.</p>
                    <button type="button" @click="showBillModal = true; triggerFeedback('tap')"
                            class="brick-btn brick-btn-yellow px-5 py-2.5 text-xs font-black cursor-pointer">
                        + Tambah Tagihan Pertama
                    </button>
                </div>
            @endif
        </div>

        <!-- MODAL: Tambah Tagihan Rutin -->
        <div x-show="showBillModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/65 backdrop-blur-xs" @click="showBillModal = false"></div>
            <div class="min-h-screen flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-white border-2 border-[#263238] rounded-3xl shadow-[5px_5px_0_#263238] p-6 relative z-10" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-black text-[#263238]">⚡ Tambah Tagihan Rutin</h3>
                        <button type="button" @click="showBillModal = false" aria-label="Tutup" class="p-1.5 rounded-xl hover:bg-[#FCF6EB] cursor-pointer">
                            <x-icon name="x" class="w-5 h-5" />
                        </button>
                    </div>

                    <form action="{{ route('bills.store') }}" method="POST" @submit="triggerFeedback('coin')" class="space-y-4">
                        @csrf
                        <input type="hidden" name="icon" :value="selectedBillIcon">
                        <input type="hidden" name="bucket" :value="selectedBillBucket">

                        <div>
                            <label for="bill-name" class="block text-xs font-bold text-[#263238] mb-1">Nama Tagihan / Langganan</label>
                            <input id="bill-name" type="text" name="name" required placeholder="Contoh: WiFi Indihome, Listrik PLN, Netflix"
                                   class="w-full min-h-[44px] px-3.5 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="bill-amount" class="block text-xs font-bold text-[#263238] mb-1">Nominal (Rp)</label>
                                <input id="bill-amount" type="text" inputmode="numeric" name="amount" x-model="billAmountRaw"
                                       @input="billAmountRaw = billAmountRaw.replace(/[^0-9]/g, '')" required placeholder="350000"
                                       class="w-full min-h-[44px] px-3.5 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-black text-sm focus:bg-white focus:outline-none">
                                <span class="text-[11px] font-bold text-[#245AE0] mt-0.5 block" x-text="formatPreview(billAmountRaw)"></span>
                            </div>

                            <div>
                                <label for="bill-due" class="block text-xs font-bold text-[#263238] mb-1">Tanggal Jatuh Tempo</label>
                                <select id="bill-due" name="due_day" required
                                        class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-black text-sm focus:bg-white focus:outline-none">
                                    @for($d = 1; $d <= 31; $d++)
                                        <option value="{{ $d }}">Setiap Tanggal {{ $d }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-[#263238] mb-1.5">Pos Anggaran</span>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="selectedBillBucket = 'needs'; triggerFeedback('tap')"
                                        :class="selectedBillBucket === 'needs' ? 'bg-[#D8F5E5] text-[#114A2D] border-2 border-[#263238]' : 'bg-[#FCF6EB] text-[#526066] border border-[#263238]/25'"
                                        class="py-2 rounded-xl font-black text-xs cursor-pointer">
                                    Kebutuhan
                                </button>
                                <button type="button" @click="selectedBillBucket = 'wants'; triggerFeedback('tap')"
                                        :class="selectedBillBucket === 'wants' ? 'bg-[#FFEAA0] text-[#263238] border-2 border-[#263238]' : 'bg-[#FCF6EB] text-[#526066] border border-[#263238]/25'"
                                        class="py-2 rounded-xl font-black text-xs cursor-pointer">
                                    Keinginan
                                </button>
                                <button type="button" @click="selectedBillBucket = 'emergency'; triggerFeedback('tap')"
                                        :class="selectedBillBucket === 'emergency' ? 'bg-[#EAE2FF] text-[#3A1D75] border-2 border-[#263238]' : 'bg-[#FCF6EB] text-[#526066] border border-[#263238]/25'"
                                        class="py-2 rounded-xl font-black text-xs cursor-pointer">
                                    Darurat
                                </button>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-[#263238] mb-1.5">Pilih Ikon Tagihan</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(['⚡', '📶', '🎬', '🏠', '💧', '📱', '🛡️', '🎵'] as $ic)
                                    <button type="button" @click="selectedBillIcon = '{{ $ic }}'; triggerFeedback('tap')"
                                            :class="selectedBillIcon === '{{ $ic }}' ? 'bg-[#FFD54F] border-2 border-[#263238] shadow-[2px_2px_0_#263238]' : 'bg-[#FCF6EB] border border-[#263238]/25'"
                                            class="w-10 h-10 rounded-xl flex items-center justify-center text-lg cursor-pointer transition">
                                        {{ $ic }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end gap-2.5">
                            <button type="button" @click="showBillModal = false" class="brick-btn brick-btn-white px-4 py-2.5 text-xs font-bold cursor-pointer">Batal</button>
                            <button type="submit" class="brick-btn brick-btn-yellow px-5 py-2.5 text-xs font-black cursor-pointer">Simpan Tagihan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
