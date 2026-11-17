@props(['categories', 'selectedMonth' => null])

@php
    $needsCats = ($categories->get('needs') ?? collect())->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
    $wantsCats = ($categories->get('wants') ?? collect())->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
    $emergCats = ($categories->get('emergency') ?? collect())->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
    $incomeCats = ($categories->get('income') ?? collect())->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
@endphp

<div
    x-data="{
        isOpen: false,
        txType: 'expense',
        bucket: 'needs',
        rawAmount: '',
        categoryId: '',
        incomeSource: '',
        submitting: false,
        isOnline: navigator.onLine,
        cats: {
            needs: @js($needsCats),
            wants: @js($wantsCats),
            emergency: @js($emergCats),
            income: @js($incomeCats)
        },
        get formattedPreview() {
            const num = parseInt(String(this.rawAmount).replace(/[^0-9]/g, ''), 10);
            return isNaN(num) || num <= 0 ? 'Rp 0' : 'Rp ' + num.toLocaleString('id-ID');
        },
        get activeCategoryList() {
            if (this.txType === 'income') return this.cats.income;
            return this.cats[this.bucket] || [];
        },
        addChip(val) {
            const current = parseInt(String(this.rawAmount).replace(/[^0-9]/g, ''), 10) || 0;
            this.rawAmount = String(current + val);
            if (window.duitmooFeedback) window.duitmooFeedback('tap');
        },
        openModal(detail = {}) {
            this.txType = detail.type || 'expense';
            this.bucket = detail.bucket || 'needs';
            this.categoryId = '';
            this.incomeSource = '';
            this.submitting = false;
            this.isOpen = true;
            if (window.duitmooFeedback) window.duitmooFeedback('tap');
            this.$nextTick(() => {
                if (this.$refs.amountInput) this.$refs.amountInput.focus();
            });
        }
    }"
    x-init="
        window.addEventListener('online', () => isOnline = true);
        window.addEventListener('offline', () => isOnline = false);
    "
    @open-quick-add.window="openModal($event.detail || {})"
    @keydown.escape.window="isOpen = false"
>
    <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="quick-add-title" style="display: none;">
        <div class="fixed inset-0 bg-[#263238]/65 backdrop-blur-xs transition-opacity" @click="isOpen = false"></div>

        <!-- Mobile bottom-sheet / Desktop centered dialog -->
        <div class="min-h-screen flex items-end sm:items-center justify-center sm:p-4">
            <div class="w-full sm:max-w-lg bg-white border-t-2 sm:border-2 border-[#263238] rounded-t-3xl sm:rounded-3xl shadow-[0_-4px_0_#263238] sm:shadow-[4px_4px_0_#263238] p-5 sm:p-7 relative z-10 max-h-[92vh] overflow-y-auto"
                 @click.stop>

                <!-- Drag handle on mobile -->
                <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto mb-4 sm:hidden"></div>

                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2.5">
                        <span class="w-10 h-10 rounded-xl bg-[#FFD54F] border-2 border-[#263238] flex items-center justify-center text-[#263238]">
                            <x-icon name="plus" class="w-5 h-5" />
                        </span>
                        <div>
                            <h2 id="quick-add-title" class="text-lg font-black text-[#263238]">Catat Transaksi Cepat</h2>
                            <p class="text-xs font-medium text-[#546E7A]">Simpan pemasukan atau pengeluaran dalam hitungan detik</p>
                        </div>
                    </div>
                    <button type="button" @click="isOpen = false" aria-label="Tutup modal"
                            class="min-w-[44px] min-h-[44px] rounded-xl border-2 border-transparent hover:border-[#263238] hover:bg-[#FCF6EB] flex items-center justify-center text-[#263238] cursor-pointer">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Offline Guard Banner -->
                <div x-show="!isOnline" class="mb-4 p-3 rounded-xl bg-[#FFE8E8] border-2 border-[#263238] text-[#8F1D1D] text-xs font-bold flex items-center gap-2">
                    <x-icon name="wifi-off" class="w-4 h-4 shrink-0" />
                    <span>Kamu sedang offline. Transaksi membutuhkan koneksi internet agar tersimpan aman di server.</span>
                </div>

                <form action="{{ route('transactions.store') }}" method="POST" @submit="submitting = true; if (window.duitmooFeedback) window.duitmooFeedback('coin')" class="space-y-4">
                    @csrf
                    @if($selectedMonth)
                        <input type="hidden" name="return_month" value="{{ $selectedMonth }}">
                    @endif
                    <input type="hidden" name="source" value="quick_add">
                    <input type="hidden" name="type" :value="txType">

                    <!-- Toggle Expense vs Income -->
                    <div class="grid grid-cols-2 gap-2 p-1.5 bg-[#FCF6EB] rounded-2xl border-2 border-[#263238]">
                        <button type="button" @click="txType = 'expense'; bucket = 'needs'; categoryId = ''; incomeSource = ''; if (window.duitmooFeedback) window.duitmooFeedback('tap');"
                                :class="txType === 'expense' ? 'bg-[#D93838] text-white border-2 border-[#263238] shadow-[0_2px_0_#263238]' : 'text-[#263238] hover:bg-white'"
                                class="min-h-[44px] rounded-xl text-sm font-black transition flex items-center justify-center gap-2 cursor-pointer">
                            <x-icon name="arrow-up-right" class="w-4 h-4" />
                            <span>Pengeluaran</span>
                        </button>
                        <button type="button" @click="txType = 'income'; categoryId = ''; incomeSource = ''; if (window.duitmooFeedback) window.duitmooFeedback('tap');"
                                :class="txType === 'income' ? 'bg-[#1E824C] text-white border-2 border-[#263238] shadow-[0_2px_0_#263238]' : 'text-[#263238] hover:bg-white'"
                                class="min-h-[44px] rounded-xl text-sm font-black transition flex items-center justify-center gap-2 cursor-pointer">
                            <x-icon name="arrow-down-left" class="w-4 h-4" />
                            <span>Pemasukan</span>
                        </button>
                    </div>

                    <!-- Nominal (Mobile Numeric Keyboard + Live Rupiah Preview + Quick Chips) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="qa-amount" class="text-xs font-bold text-[#263238]">Nominal</label>
                            <span class="text-sm font-black text-[#245AE0]" x-text="formattedPreview"></span>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-black text-[#526066] text-base">Rp</span>
                            <input id="qa-amount" x-ref="amountInput" type="text" inputmode="numeric" pattern="[0-9]*"
                                   name="amount" x-model="rawAmount" required placeholder="25000"
                                   @input="rawAmount = rawAmount.replace(/[^0-9]/g, '')"
                                   class="w-full min-h-[48px] pl-11 pr-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-black text-lg text-[#263238] focus:bg-white focus:outline-none">
                        </div>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <button type="button" @click="addChip(10000)" class="px-2.5 py-1 rounded-lg bg-[#FCF6EB] hover:bg-[#FFD54F] border border-[#263238]/30 text-xs font-bold cursor-pointer">+10rb</button>
                            <button type="button" @click="addChip(25000)" class="px-2.5 py-1 rounded-lg bg-[#FCF6EB] hover:bg-[#FFD54F] border border-[#263238]/30 text-xs font-bold cursor-pointer">+25rb</button>
                            <button type="button" @click="addChip(50000)" class="px-2.5 py-1 rounded-lg bg-[#FCF6EB] hover:bg-[#FFD54F] border border-[#263238]/30 text-xs font-bold cursor-pointer">+50rb</button>
                            <button type="button" @click="addChip(100000)" class="px-2.5 py-1 rounded-lg bg-[#FCF6EB] hover:bg-[#FFD54F] border border-[#263238]/30 text-xs font-bold cursor-pointer">+100rb</button>
                            <button type="button" @click="rawAmount = ''" class="px-2.5 py-1 rounded-lg bg-white hover:bg-gray-100 border border-gray-300 text-xs font-semibold text-[#526066] cursor-pointer ml-auto">Reset</button>
                        </div>
                    </div>

                    <!-- Bucket Selector (Only for Expense) -->
                    <div x-show="txType === 'expense'">
                        <span class="block text-xs font-bold text-[#263238] mb-1.5">Pos anggaran</span>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="bucket" value="needs" x-model="bucket" @change="categoryId = ''" class="sr-only">
                                <div :class="bucket === 'needs' ? 'bg-[#D8F5E5] text-[#114A2D] border-2 border-[#263238]' : 'bg-white border border-[#263238]/25 text-[#526066]'"
                                     class="min-h-[44px] px-2 py-2 rounded-xl flex items-center justify-center font-black text-xs transition">
                                    Kebutuhan
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="bucket" value="wants" x-model="bucket" @change="categoryId = ''" class="sr-only">
                                <div :class="bucket === 'wants' ? 'bg-[#FFEAA0] text-[#263238] border-2 border-[#263238]' : 'bg-white border border-[#263238]/25 text-[#526066]'"
                                     class="min-h-[44px] px-2 py-2 rounded-xl flex items-center justify-center font-black text-xs transition">
                                    Keinginan
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="bucket" value="emergency" x-model="bucket" @change="categoryId = ''" class="sr-only">
                                <div :class="bucket === 'emergency' ? 'bg-[#EAE2FF] text-[#3A1D75] border-2 border-[#263238]' : 'bg-white border border-[#263238]/25 text-[#526066]'"
                                     class="min-h-[44px] px-2 py-2 rounded-xl flex items-center justify-center font-black text-xs transition">
                                    Darurat
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Category & Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="qa-category" class="block text-xs font-bold text-[#263238] mb-1">Kategori</label>
                            <select id="qa-category" name="category_id" x-model="categoryId"
                                    @change="if (txType === 'income') {
                                        const c = cats.income.find(item => String(item.id) === String(categoryId));
                                        if (c && (!incomeSource || cats.income.some(item => item.name === incomeSource))) {
                                            incomeSource = c.name;
                                        }
                                    }"
                                    class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/35 bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:outline-none">
                                <option value="">Pilih kategori</option>
                                <template x-for="cat in activeCategoryList" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label for="qa-date" class="block text-xs font-bold text-[#263238] mb-1">Tanggal</label>
                            <input id="qa-date" type="date" name="transaction_date" required value="{{ date('Y-m-d') }}"
                                   class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/35 bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:outline-none">
                        </div>
                    </div>

                    <!-- Description / Income Source -->
                    <div x-show="txType === 'expense'">
                        <label for="qa-desc" class="block text-xs font-bold text-[#263238] mb-1">Nama pengeluaran</label>
                        <input id="qa-desc" type="text" name="description" placeholder="Contoh: Makan siang / Transport"
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/35 bg-[#FCF6EB] font-semibold text-sm focus:bg-white focus:outline-none">
                    </div>

                    <div x-show="txType === 'income'">
                        <label for="qa-income-source" class="block text-xs font-bold text-[#263238] mb-1">Sumber pemasukan (opsional)</label>
                        <input id="qa-income-source" type="text" name="income_source" x-model="incomeSource" placeholder="Contoh: Gaji, Freelance"
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/35 bg-[#FCF6EB] font-semibold text-sm focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label for="qa-notes" class="block text-xs font-bold text-[#263238] mb-1">Catatan (opsional)</label>
                        <input id="qa-notes" type="text" name="notes" placeholder="Catatan tambahan singkat"
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/35 bg-[#FCF6EB] font-medium text-sm focus:bg-white focus:outline-none">
                    </div>

                    <!-- Submit Buttons -->
                    <div class="pt-3 border-t-2 border-gray-100 flex items-center justify-end gap-3">
                        <button type="button" @click="isOpen = false" class="brick-btn brick-btn-white px-5 py-2.5 text-sm font-bold">
                            Batal
                        </button>
                        <button type="submit" :disabled="submitting || !isOnline" class="brick-btn brick-btn-blue px-6 py-2.5 text-sm font-black flex-1 sm:flex-none">
                            <span x-show="!submitting">Simpan Transaksi</span>
                            <span x-show="submitting">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
