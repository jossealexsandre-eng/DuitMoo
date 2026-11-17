<x-app-layout title="Atur Anggaran">
    <div x-data="{
        snapOpening: {{ $monthlyBudget->opening_balance }},
        snapNeeds: {{ $monthlyBudget->needs_percent }},
        snapWants: {{ $monthlyBudget->wants_percent }},
        snapEmerg: {{ $monthlyBudget->emergency_percent }},
        get snapTotal() { return parseInt(this.snapNeeds || 0) + parseInt(this.snapWants || 0) + parseInt(this.snapEmerg || 0); },
        get snapNeedsAlloc() { return Math.floor((this.snapOpening * this.snapNeeds) / 100); },
        get snapWantsAlloc() { return Math.floor((this.snapOpening * this.snapWants) / 100); },
        get snapEmergAlloc() { return this.snapOpening - this.snapNeedsAlloc - this.snapWantsAlloc; },

        addCatModal: false,
        editCatModal: false,
        editCatId: null,
        editCatName: '',
        deleteCatFormId: null
    }">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#263238]">Pengaturan Anggaran &amp; Kategori</h1>
                <p class="text-xs sm:text-sm text-gray-600 font-medium mt-1">
                    Sesuaikan saldo awal, rasio pembagian 100%, dan kelola kategori pemasukan maupun pengeluaranmu.
                </p>
            </div>

            <form method="GET" action="{{ route('budget.settings') }}" class="flex items-center gap-2">
                <label for="bs-month" class="text-xs font-bold text-[#546E7A]">Pilih Bulan:</label>
                <select id="bs-month" name="month" onchange="this.form.submit()"
                        class="min-h-[42px] px-3.5 py-2 rounded-xl border-2 border-[#263238] bg-white font-bold text-xs sm:text-sm shadow-[2px_2px_0_#263238]">
                    @foreach($availableMonths as $val => $lbl)
                        <option value="{{ $val }}" {{ $selectedMonth === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-[#FFE8E8] text-[#8F1D1D] rounded-2xl border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-xs font-bold space-y-1">
                @foreach($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif

        <!-- 1. Snapshot Bulanan (Full-Width Clean Card without redundant Preferensi Rasio Default card) -->
        <div class="brick-card-pop p-6 sm:p-7 bg-white mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-dashed border-[#263238]/15">
                <div>
                    <span class="brick-badge brick-badge-blue text-[11px]">Konfigurasi Periode</span>
                    <h2 class="text-lg sm:text-xl font-black text-[#263238] mt-1.5">Anggaran Bulan {{ $monthLabel }}</h2>
                    <p class="text-xs text-[#546E7A] mt-0.5">
                        Perubahan rasio di bulan ini tidak akan mengubah konfigurasi bulan sebelumnya.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="snapNeeds = 60; snapWants = 30; snapEmerg = 10;"
                            class="min-h-[38px] px-3 py-1.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] hover:bg-[#FFD54F] text-xs font-black transition cursor-pointer">
                        Preset 60/30/10
                    </button>
                    <button type="button" @click="snapNeeds = 50; snapWants = 30; snapEmerg = 20;"
                            class="min-h-[38px] px-3 py-1.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] hover:bg-[#FFD54F] text-xs font-black transition cursor-pointer">
                        Preset 50/30/20
                    </button>
                </div>
            </div>

            <form action="{{ route('budget.snapshot.update') }}" method="POST">
                @csrf
                <input type="hidden" name="budget_id" value="{{ $monthlyBudget->id }}">

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <div class="lg:col-span-5 space-y-4">
                        <div>
                            <label for="snap-opening" class="block text-xs font-bold text-[#37474F] mb-1">
                                Saldo awal bulan ini (Rp)
                            </label>
                            <input id="snap-opening" type="number" inputmode="numeric" name="opening_balance" min="0" required x-model.number="snapOpening"
                                   class="w-full min-h-[46px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-black text-base focus:bg-white focus:border-[#245AE0] focus:outline-none">
                            <p class="text-[11px] text-[#546E7A] mt-1.5">Uang yang sudah kamu miliki di awal periode (tidak dihitung sebagai pemasukan baru).</p>
                        </div>

                        <div class="p-4 rounded-2xl bg-[#FCF6EB] border-2 border-[#263238]/20">
                            <span class="text-xs font-bold text-[#546E7A] block mb-2.5">Simulasi alokasi saldo awal:</span>
                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="p-2.5 rounded-xl bg-[#E8F8F0] text-[#124D30] border border-[#1E824C]/30">
                                    <span class="block text-[10px] font-bold">Kebutuhan</span>
                                    <span class="font-black tabular-nums" x-text="'Rp ' + snapNeedsAlloc.toLocaleString('id-ID')"></span>
                                </div>
                                <div class="p-2.5 rounded-xl bg-[#FFF2CC] text-[#263238] border border-[#263238]/20">
                                    <span class="block text-[10px] font-bold">Keinginan</span>
                                    <span class="font-black tabular-nums" x-text="'Rp ' + snapWantsAlloc.toLocaleString('id-ID')"></span>
                                </div>
                                <div class="p-2.5 rounded-xl bg-[#F1EBFF] text-[#3C1F7A] border border-[#6B47B8]/30">
                                    <span class="block text-[10px] font-bold">Darurat</span>
                                    <span class="font-black tabular-nums" x-text="'Rp ' + snapEmergAlloc.toLocaleString('id-ID')"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-7 space-y-3">
                        <div class="p-3.5 bg-[#E8F8F0] rounded-xl border-2 border-[#263238]">
                            <div class="flex justify-between items-center mb-1 text-xs font-black text-[#124D30]">
                                <label for="snap-needs" class="flex items-center gap-1.5">
                                    <x-duitmoo-mascot variant="eyes" class="w-5 h-2.5" />
                                    Kebutuhan
                                </label>
                                <span x-text="snapNeeds + '%'"></span>
                            </div>
                            <input id="snap-needs" type="range" name="needs_percent" min="0" max="100" x-model.number="snapNeeds" class="w-full accent-[#1E824C] cursor-pointer">
                        </div>

                        <div class="p-3.5 bg-[#FFF2CC] rounded-xl border-2 border-[#263238]">
                            <div class="flex justify-between items-center mb-1 text-xs font-black text-[#263238]">
                                <label for="snap-wants" class="flex items-center gap-1.5">
                                    <x-duitmoo-mascot variant="eyes" class="w-5 h-2.5" />
                                    Keinginan
                                </label>
                                <span x-text="snapWants + '%'"></span>
                            </div>
                            <input id="snap-wants" type="range" name="wants_percent" min="0" max="100" x-model.number="snapWants" class="w-full accent-[#263238] cursor-pointer">
                        </div>

                        <div class="p-3.5 bg-[#F1EBFF] rounded-xl border-2 border-[#263238]">
                            <div class="flex justify-between items-center mb-1 text-xs font-black text-[#3C1F7A]">
                                <label for="snap-emerg" class="flex items-center gap-1.5">
                                    <x-duitmoo-mascot variant="eyes" class="w-5 h-2.5" />
                                    Dana Darurat
                                </label>
                                <span x-text="snapEmerg + '%'"></span>
                            </div>
                            <input id="snap-emerg" type="range" name="emergency_percent" min="0" max="100" x-model.number="snapEmerg" class="w-full accent-[#6B47B8] cursor-pointer">
                        </div>

                        <div class="p-3 rounded-xl border-2 border-[#263238] flex items-center justify-between text-xs font-black"
                             :class="snapTotal === 100 ? 'bg-[#E8F8F0] text-[#124D30]' : 'bg-[#FFE8E8] text-[#8F1D1D]'">
                            <span>Total Persentase: <span x-text="snapTotal + '%'"></span></span>
                            <span x-text="snapTotal === 100 ? 'Tepat 100%' : 'Total wajib tepat 100%'"></span>
                        </div>

                        <button type="submit" :disabled="snapTotal !== 100" class="w-full py-3 brick-btn brick-btn-blue text-xs font-black cursor-pointer">
                            Simpan Anggaran Bulan Ini
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 3. Category Management (Kebutuhan, Keinginan, Darurat, Pemasukan) -->
        <div class="brick-card p-6 bg-white mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-dashed border-[#263238]/15">
                <div>
                    <h2 class="text-xl font-black text-[#263238]">Kelola Kategori Transaksi Pribadi</h2>
                    <p class="text-xs text-[#546E7A] font-medium mt-1">
                        Atur kategori pengeluaran maupun sumber pemasukan milik akunmu sendiri.
                    </p>
                </div>

                <button type="button" @click="addCatModal = true" class="brick-btn brick-btn-green px-4 py-2 text-xs font-black flex items-center gap-1.5 self-start sm:self-auto">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Tambah Kategori Baru</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                @php
                    $catColumns = [
                        'needs' => ['title' => 'Kebutuhan', 'bg' => 'bg-[#E8F8F0]', 'dot' => 'bg-[#1E824C]'],
                        'wants' => ['title' => 'Keinginan', 'bg' => 'bg-[#FFF2CC]', 'dot' => 'bg-[#F5B800]'],
                        'emergency' => ['title' => 'Dana Darurat', 'bg' => 'bg-[#F1EBFF]', 'dot' => 'bg-[#6B47B8]'],
                        'income' => ['title' => 'Pemasukan', 'bg' => 'bg-[#E9F0FF]', 'dot' => 'bg-[#245AE0]'],
                    ];
                @endphp

                @foreach($catColumns as $bKey => $bMeta)
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-3 h-3 rounded-full {{ $bMeta['dot'] }} border border-[#263238]"></span>
                            <h3 class="text-sm font-black text-[#263238]">{{ $bMeta['title'] }}</h3>
                        </div>

                        <div class="space-y-2">
                            @forelse($categories->get($bKey, collect()) as $cat)
                                <div class="p-2.5 {{ $bMeta['bg'] }} rounded-2xl border border-[#263238]/15 flex items-center justify-between text-xs font-bold text-[#263238]">
                                    <div class="flex items-center gap-2.5 min-w-0 mr-2">
                                        <x-category-badge-icon :bucket="$bKey" :categoryName="$cat->name" class="w-7 h-7" />
                                        <span class="truncate">{{ $cat->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="editCatId = {{ $cat->id }}; editCatName = @js($cat->name); editCatModal = true;"
                                                class="p-1.5 rounded-lg hover:bg-white transition cursor-pointer" aria-label="Edit kategori {{ $cat->name }}">
                                            <x-icon name="edit" class="w-3.5 h-3.5" />
                                        </button>
                                        @if($cat->transactions()->count() === 0)
                                            <form id="del-cat-{{ $cat->id }}" action="{{ route('budget.category.destroy', $cat) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" @click="deleteCatFormId = 'del-cat-{{ $cat->id }}'"
                                                        class="p-1.5 rounded-lg hover:bg-white text-[#8F1D1D] transition cursor-pointer" aria-label="Hapus kategori {{ $cat->name }}">
                                                    <x-icon name="trash" class="w-3.5 h-3.5" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500 italic">Belum ada kategori.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Add Category Modal -->
        <div x-show="addCatModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60" @click="addCatModal = false"></div>
            <div class="brick-card-pop w-full max-w-md p-6 bg-white relative z-10" @click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-black text-[#263238]">Tambah Kategori Baru</h3>
                    <button type="button" @click="addCatModal = false" aria-label="Tutup" class="p-1 cursor-pointer"><x-icon name="x" class="w-5 h-5" /></button>
                </div>

                <form action="{{ route('budget.category.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="cat-bucket" class="block text-xs font-bold text-[#37474F] mb-1">Kelompok / Pos *</label>
                        <select id="cat-bucket" name="bucket" class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-bold text-xs focus:bg-white focus:border-[#245AE0] focus:outline-none">
                            <option value="needs">Kebutuhan (Pengeluaran)</option>
                            <option value="wants">Keinginan (Pengeluaran)</option>
                            <option value="emergency">Dana Darurat (Pengeluaran)</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </div>

                    <div>
                        <label for="cat-name" class="block text-xs font-bold text-[#37474F] mb-1">Nama Kategori *</label>
                        <input id="cat-name" type="text" name="name" required placeholder="Contoh: Asuransi, Dividen"
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-bold text-xs focus:bg-white focus:border-[#245AE0] focus:outline-none">
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" @click="addCatModal = false" class="brick-btn brick-btn-white px-4 py-2 text-xs font-bold">Batal</button>
                        <button type="submit" class="brick-btn brick-btn-green px-5 py-2 text-xs font-black">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Category Modal -->
        <div x-show="editCatModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60" @click="editCatModal = false"></div>
            <div class="brick-card-pop w-full max-w-md p-6 bg-white relative z-10" @click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-black text-[#263238]">Ubah Nama Kategori</h3>
                    <button type="button" @click="editCatModal = false" aria-label="Tutup" class="p-1 cursor-pointer"><x-icon name="x" class="w-5 h-5" /></button>
                </div>

                <form :action="'/budget/category/' + editCatId" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit-cat-name" class="block text-xs font-bold text-[#37474F] mb-1">Nama Kategori *</label>
                        <input id="edit-cat-name" type="text" name="name" required x-model="editCatName"
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-bold text-xs focus:bg-white focus:border-[#245AE0] focus:outline-none">
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" @click="editCatModal = false" class="brick-btn brick-btn-white px-4 py-2 text-xs font-bold">Batal</button>
                        <button type="submit" class="brick-btn brick-btn-blue px-5 py-2 text-xs font-black">Perbarui</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Category Confirmation Dialog -->
        <div x-show="deleteCatFormId !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60" @click="deleteCatFormId = null"></div>
            <div class="brick-card max-w-sm w-full p-6 bg-white relative z-10 text-center">
                <h3 class="text-lg font-black text-[#263238] mb-1">Hapus Kategori?</h3>
                <p class="text-xs text-gray-600 font-medium mb-5">Kategori ini belum pernah digunakan transaksi sehingga aman untuk dihapus.</p>
                <div class="flex items-center gap-3">
                    <button type="button" @click="deleteCatFormId = null" class="brick-btn brick-btn-white flex-1 py-2 text-xs font-bold">Batal</button>
                    <button type="button" @click="document.getElementById(deleteCatFormId).submit()" class="brick-btn brick-btn-red flex-1 py-2 text-xs font-black">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
