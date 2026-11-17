<x-app-layout title="Kelola Transaksi">
    @php
        $needsCats = ($categories->get('needs') ?? collect())->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
        $wantsCats = ($categories->get('wants') ?? collect())->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
        $emergCats = ($categories->get('emergency') ?? collect())->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
        $incomeCats = ($categories->get('income') ?? collect())->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
    @endphp

    <div x-data="{
        editModalOpen: false,
        deleteFormId: null,
        cats: {
            needs: @js($needsCats),
            wants: @js($wantsCats),
            emergency: @js($emergCats),
            income: @js($incomeCats)
        },
        editTx: { id: null, type: 'expense', amount: 0, transaction_date: '', bucket: 'needs', category_id: '', description: '', income_source: '', notes: '' },
        get editCategoryList() {
            if (this.editTx.type === 'income') return this.cats.income;
            return this.cats[this.editTx.bucket] || [];
        },
        openEdit(tx) {
            this.editTx = Object.assign({}, tx);
            this.editModalOpen = true;
        }
    }"
    @if($openCreateModal) x-init="$nextTick(() => $dispatch('open-quick-add'))" @endif>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#263238]">Catatan Transaksi</h1>
                <p class="text-xs sm:text-sm text-[#526066] font-medium mt-0.5">
                    Cari, filter, ubah, atau hapus catatan pemasukan dan pengeluaranmu.
                </p>
            </div>

            <button type="button" @click="$dispatch('open-quick-add')"
                    class="brick-btn brick-btn-yellow px-4 py-2 text-xs sm:text-sm font-black flex items-center gap-2 self-start sm:self-auto">
                <x-icon name="plus" class="w-4 h-4" />
                <span>Catat Transaksi Baru</span>
            </button>
        </div>

        <!-- Summary Strip (Periode, Pemasukan, Pengeluaran, Profit / Net Income) -->
        @php
            $netProfit = $totalIncome - $totalExpense;
            $profitMarginPct = $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100, 1) : 0;
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="brick-card p-4 bg-white">
                <span class="text-xs font-bold text-[#526066] block">Periode aktif</span>
                <span class="text-sm sm:text-base font-black text-[#263238]">{{ $periodLabel }}</span>
            </div>
            <div class="brick-card p-4 bg-[#F2FBF6]">
                <span class="text-xs font-bold text-[#114A2D] block">Total Pemasukan</span>
                <span class="text-sm sm:text-base font-black text-[#1C7C49]">
                    <span x-show="!hideBalance">+Rp {{ number_format($totalIncome, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>+Rp ••••••••</span>
                </span>
            </div>
            <div class="brick-card p-4 bg-[#FFF5F4]">
                <span class="text-xs font-bold text-[#8C1D1D] block">Total Pengeluaran</span>
                <span class="text-sm sm:text-base font-black text-[#C93535]">
                    <span x-show="!hideBalance">-Rp {{ number_format($totalExpense, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>-Rp ••••••••</span>
                </span>
            </div>
            <div class="brick-card p-4 {{ $netProfit >= 0 ? 'bg-[#FFF9E6]' : 'bg-[#FFE2DE]' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#263238]">Profit / Net Income</span>
                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded border border-[#263238] {{ $netProfit >= 0 ? 'bg-[#D8F5E5] text-[#114A2D]' : 'bg-white text-[#8C1D1D]' }}">
                        {{ $profitMarginPct }}%
                    </span>
                </div>
                <span class="text-sm sm:text-base font-black {{ $netProfit >= 0 ? 'text-[#1C7C49]' : 'text-[#C93535]' }}">
                    <span x-show="!hideBalance">{{ $netProfit >= 0 ? '+' : '-' }}Rp {{ number_format(abs($netProfit), 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>{{ $netProfit >= 0 ? '+' : '-' }}Rp ••••••••</span>
                </span>
            </div>
        </div>

        <!-- Period Tabs & Filters -->
        <div class="brick-card p-4 sm:p-5 bg-white mb-6 space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-[#526066] mr-1">Rentang:</span>
                @foreach(['month' => 'Bulan Ini', 'today' => 'Hari Ini', 'week' => 'Minggu Ini', 'custom' => 'Tanggal Kustom'] as $pKey => $pLabel)
                    <a href="{{ route('transactions.index', array_merge(request()->query(), ['period' => $pKey])) }}"
                       class="min-h-[40px] px-3.5 py-1.5 rounded-xl border-[1.5px] text-xs font-bold inline-flex items-center transition {{ $period === $pKey ? 'bg-[#FFEAA0] text-[#263238] border-[#263238]' : 'bg-[#FCF6EB] text-[#526066] border-[#263238]/20 hover:border-[#263238]' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <input type="hidden" name="period" value="{{ $period }}">

                @if($period === 'custom')
                    <div>
                        <label for="f-from" class="block text-xs font-bold text-[#263238] mb-1">Dari tanggal</label>
                        <input id="f-from" type="date" name="date_from" value="{{ $dateFrom }}"
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/30 bg-[#FCF6EB] font-bold text-xs">
                    </div>
                    <div>
                        <label for="f-to" class="block text-xs font-bold text-[#263238] mb-1">Sampai tanggal</label>
                        <input id="f-to" type="date" name="date_to" value="{{ $dateTo }}"
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/30 bg-[#FCF6EB] font-bold text-xs">
                    </div>
                @else
                    <div>
                        <label for="f-month" class="block text-xs font-bold text-[#263238] mb-1">Bulan</label>
                        <select id="f-month" name="month" onchange="this.form.submit()"
                                class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/30 bg-[#FCF6EB] font-bold text-xs">
                            @foreach($availableMonths as $val => $lbl)
                                <option value="{{ $val }}" {{ $selectedMonth === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="f-type" class="block text-xs font-bold text-[#263238] mb-1">Tipe</label>
                    <select id="f-type" name="type" onchange="this.form.submit()"
                            class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/30 bg-[#FCF6EB] font-bold text-xs">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Semua Tipe</option>
                        <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                        <option value="income" {{ $type === 'income' ? 'selected' : '' }}>Pemasukan</option>
                    </select>
                </div>

                <div>
                    <label for="f-bucket" class="block text-xs font-bold text-[#263238] mb-1">Pos anggaran</label>
                    <select id="f-bucket" name="bucket" onchange="this.form.submit()"
                            class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/30 bg-[#FCF6EB] font-bold text-xs">
                        <option value="all" {{ $bucket === 'all' ? 'selected' : '' }}>Semua Pos</option>
                        <option value="needs" {{ $bucket === 'needs' ? 'selected' : '' }}>Kebutuhan</option>
                        <option value="wants" {{ $bucket === 'wants' ? 'selected' : '' }}>Keinginan</option>
                        <option value="emergency" {{ $bucket === 'emergency' ? 'selected' : '' }}>Dana Darurat</option>
                    </select>
                </div>

                <div>
                    <label for="f-category" class="block text-xs font-bold text-[#263238] mb-1">Kategori</label>
                    <select id="f-category" name="category_id" onchange="this.form.submit()"
                            class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/30 bg-[#FCF6EB] font-bold text-xs">
                        <option value="all">Semua Kategori</option>
                        @foreach($categories as $b => $cats)
                            <optgroup label="{{ strtoupper($b) }}">
                                @foreach($cats as $c)
                                    <option value="{{ $c->id }}" {{ (string) $categoryId === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="{{ $period === 'custom' ? 'sm:col-span-2 lg:col-span-1' : 'sm:col-span-2' }} flex items-end gap-2">
                    <div class="flex-1">
                        <label for="f-search" class="block text-xs font-bold text-[#263238] mb-1">Cari keterangan</label>
                        <input id="f-search" type="text" name="search" value="{{ $search }}" placeholder="Ketik kata kunci..."
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#263238]/30 bg-[#FCF6EB] font-medium text-xs focus:bg-white focus:outline-none">
                    </div>
                    <button type="submit" class="brick-btn brick-btn-blue px-4 py-2 text-xs font-bold shrink-0">Cari</button>
                    @if($type !== 'all' || $bucket !== 'all' || $categoryId !== 'all' || !empty($search) || $period !== 'month')
                        <a href="{{ route('transactions.index', ['month' => $selectedMonth]) }}"
                           class="brick-btn brick-btn-white px-3 py-2 text-xs font-bold shrink-0">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Ledger List (Clean Pastel Illustrated Cards inspired by Reference 2 & 4) -->
        <div class="brick-card p-5 sm:p-6 bg-white">
            @if($transactions->count() > 0)
                <div class="space-y-3">
                    @foreach($transactions as $tx)
                        @php
                            $rowTint = match($tx->bucket) {
                                'wants' => 'bg-[#FFF9E6]',
                                'emergency' => 'bg-[#F5F0FF]',
                                default => $tx->type === 'income' ? 'bg-[#EEFAF3]' : 'bg-[#F7FAFC]',
                            };
                            $bBadge = match($tx->bucket) {
                                'needs' => 'brick-badge-needs',
                                'wants' => 'brick-badge-wants',
                                'emergency' => 'brick-badge-emergency',
                                default => 'brick-badge-needs',
                            };
                            $bLabel = match($tx->bucket) {
                                'needs' => 'Kebutuhan',
                                'wants' => 'Keinginan',
                                'emergency' => 'Darurat',
                                default => 'Pemasukan',
                            };
                            $catTitle = $tx->category?->name ?? ($tx->type === 'income' ? 'Pemasukan' : $bLabel);
                        @endphp
                        <div class="p-3.5 sm:p-4 rounded-2xl {{ $rowTint }} border border-[#263238]/20 hover:border-[#263238] transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                                <x-category-badge-icon :bucket="$tx->type === 'income' ? 'income' : ($tx->bucket ?? 'needs')" :categoryName="$catTitle" />
                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-[#263238] break-words">
                                        {{ $tx->description ?: ($tx->type === 'income' ? ($tx->income_source ?: 'Pemasukan') : $catTitle) }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-[#526066]">
                                        <span>{{ \Carbon\Carbon::parse($tx->transaction_date)->translatedFormat('d M Y') }}</span>
                                        <span class="brick-badge {{ $bBadge }} text-[10px]">{{ $bLabel }}</span>
                                        @if($tx->category)
                                            <span class="font-semibold text-[#263238]">{{ $tx->category->name }}</span>
                                        @endif
                                    </div>
                                    @if($tx->notes)
                                        <p class="text-xs text-[#526066] mt-1">{{ $tx->notes }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-[#263238]/10 shrink-0">
                                <p class="font-black text-base {{ $tx->type === 'income' ? 'text-[#1C7C49]' : 'text-[#C93535]' }}">
                                    <span x-show="!hideBalance">{{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                    <span x-show="hideBalance" x-cloak>{{ $tx->type === 'income' ? '+' : '-' }}Rp ••••••••</span>
                                </p>

                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openEdit({
                                                id: {{ $tx->id }},
                                                type: '{{ $tx->type }}',
                                                amount: {{ $tx->amount }},
                                                transaction_date: '{{ $tx->transaction_date->format('Y-m-d') }}',
                                                bucket: '{{ $tx->bucket ?? 'needs' }}',
                                                category_id: '{{ $tx->category_id ?? '' }}',
                                                description: @js($tx->description ?? ''),
                                                income_source: @js($tx->income_source ?? ''),
                                                notes: @js($tx->notes ?? '')
                                            })"
                                            aria-label="Edit transaksi"
                                            class="min-w-[40px] min-h-[40px] bg-white hover:bg-[#FFD54F] text-[#263238] rounded-xl border border-[#263238]/30 hover:border-[#263238] flex items-center justify-center cursor-pointer transition">
                                        <x-icon name="edit" class="w-4 h-4" />
                                    </button>
                                    <form id="del-tx-{{ $tx->id }}" action="{{ route('transactions.destroy', $tx) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" @click="deleteFormId = 'del-tx-{{ $tx->id }}'" aria-label="Hapus transaksi"
                                                class="min-w-[40px] min-h-[40px] bg-white hover:bg-[#FFE2DE] text-[#8C1D1D] rounded-xl border border-[#263238]/30 hover:border-[#263238] flex items-center justify-center cursor-pointer transition">
                                            <x-icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-4 border-t border-[#263238]/10">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-12 px-4">
                    <img src="{{ asset('images/mascot/moowie-sad.png') }}" alt="Moowie" class="w-16 h-16 mx-auto mb-2 drop-shadow-[0_4px_8px_rgba(0,0,0,0.08)]">
                    <h3 class="font-black text-base text-[#263238]">Tidak ada transaksi yang cocok dengan filter</h3>
                    <p class="text-xs text-[#526066] mt-1 max-w-sm mx-auto">
                        Ubah rentang tanggal atau kata kunci, atau catat transaksi baru sekarang.
                    </p>
                    <button type="button" @click="$dispatch('open-quick-add')" class="mt-4 px-5 py-2.5 brick-btn brick-btn-yellow text-xs font-black">
                        Catat Transaksi Baru
                    </button>
                </div>
            @endif
        </div>

        <!-- Edit Modal -->
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60 backdrop-blur-xs" @click="editModalOpen = false"></div>
            <div class="min-h-screen px-4 flex items-center justify-center">
                <div class="brick-card-pop w-full max-w-lg p-6 sm:p-8 my-8 bg-white relative z-10" @click.stop>
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-xl font-black text-[#263238]">Edit Transaksi</h3>
                        <button type="button" @click="editModalOpen = false" aria-label="Tutup modal" class="p-1.5 cursor-pointer">
                            <x-icon name="x" class="w-5 h-5" />
                        </button>
                    </div>

                    <form :action="'/transactions/' + editTx.id" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="type" :value="editTx.type">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="edit-amount" class="block text-xs font-bold text-[#263238] mb-1">Nominal (Rp)</label>
                                <input id="edit-amount" type="number" inputmode="numeric" name="amount" required min="1" x-model="editTx.amount"
                                       class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-black text-sm focus:bg-white focus:outline-none">
                            </div>
                            <div>
                                <label for="edit-date" class="block text-xs font-bold text-[#263238] mb-1">Tanggal</label>
                                <input id="edit-date" type="date" name="transaction_date" required x-model="editTx.transaction_date"
                                       class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:outline-none">
                            </div>
                        </div>

                        <div x-show="editTx.type === 'expense'">
                            <label for="edit-bucket" class="block text-xs font-bold text-[#263238] mb-1">Pos anggaran</label>
                            <select id="edit-bucket" name="bucket" x-model="editTx.bucket" @change="editTx.category_id = ''"
                                    class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-bold text-sm">
                                <option value="needs">Kebutuhan</option>
                                <option value="wants">Keinginan</option>
                                <option value="emergency">Dana Darurat</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit-category" class="block text-xs font-bold text-[#263238] mb-1">Kategori</label>
                            <select id="edit-category" name="category_id" x-model="editTx.category_id"
                                    class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-bold text-sm">
                                <option value="">Pilih kategori</option>
                                <template x-for="cat in editCategoryList" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="editTx.type === 'expense'">
                            <label for="edit-desc" class="block text-xs font-bold text-[#263238] mb-1">Keterangan</label>
                            <input id="edit-desc" type="text" name="description" x-model="editTx.description"
                                   class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-semibold text-sm focus:bg-white focus:outline-none">
                        </div>

                        <div x-show="editTx.type === 'income'">
                            <label for="edit-income-src" class="block text-xs font-bold text-[#263238] mb-1">Sumber pemasukan</label>
                            <input id="edit-income-src" type="text" name="income_source" x-model="editTx.income_source"
                                   class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-semibold text-sm focus:bg-white focus:outline-none">
                        </div>

                        <div>
                            <label for="edit-notes" class="block text-xs font-bold text-[#263238] mb-1">Catatan tambahan</label>
                            <input id="edit-notes" type="text" name="notes" x-model="editTx.notes"
                                   class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-medium text-sm focus:bg-white focus:outline-none">
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="editModalOpen = false" class="brick-btn brick-btn-white px-4 py-2 text-xs font-bold">Batal</button>
                            <button type="submit" class="brick-btn brick-btn-blue px-6 py-2 text-xs font-black">Perbarui Transaksi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div x-show="deleteFormId !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60" @click="deleteFormId = null"></div>
            <div class="brick-card-pop max-w-sm w-full p-6 bg-white relative z-10 text-center">
                <h3 class="text-lg font-black text-[#263238] mb-1">Hapus Catatan Transaksi?</h3>
                <p class="text-xs text-[#526066] font-medium mb-5">Perhitungan saldo dan sisa anggaran akan langsung diperbarui.</p>
                <div class="flex items-center gap-3">
                    <button type="button" @click="deleteFormId = null" class="brick-btn brick-btn-white flex-1 py-2 text-xs font-bold">Batal</button>
                    <button type="button" @click="document.getElementById(deleteFormId).submit()" class="brick-btn brick-btn-red flex-1 py-2 text-xs font-black">Ya, Hapus</button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
