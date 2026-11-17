<x-app-layout title="Celengan Impian">
    <div x-data="{
        showGoalModal: false,
        depositGoal: null,
        depositAmount: '',
        recordDepositTx: true,
        goalTargetRaw: '',
        goalCurrentRaw: '',
        selectedGoalIcon: '🐮',
        selectedGoalColor: 'yellow',
        formatPreview(raw) {
            const num = parseInt(String(raw).replace(/[^0-9]/g, ''), 10);
            return isNaN(num) || num <= 0 ? 'Rp 0' : 'Rp ' + num.toLocaleString('id-ID');
        },
        openDeposit(goal) {
            this.depositGoal = goal;
            this.depositAmount = '';
            this.recordDepositTx = true;
            if (window.duitmooFeedback) window.duitmooFeedback('tap');
        },
        addDepositChip(val) {
            const cur = parseInt(String(this.depositAmount).replace(/[^0-9]/g, ''), 10) || 0;
            this.depositAmount = String(cur + val);
            if (window.duitmooFeedback) window.duitmooFeedback('tap');
        }
    }"
    @keydown.escape.window="showGoalModal = false; depositGoal = null">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#263238]">
                    Celengan Impian
                </h1>
                <p class="text-xs sm:text-sm text-[#526066] font-medium mt-0.5">
                    Tabung sedikit demi sedikit untuk mewujudkan barang atau rencana impianmu.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('bills.index') }}"
                   class="brick-btn brick-btn-white px-4 py-2 text-xs sm:text-sm font-black flex items-center gap-2 cursor-pointer">
                    <x-icon name="calendar" class="w-4 h-4 text-[#245AE0]" />
                    <span>Ke Tagihan Rutin</span>
                </a>
                <button type="button" @click="showGoalModal = true; triggerFeedback('tap')"
                        class="brick-btn brick-btn-yellow px-4 py-2 text-xs sm:text-sm font-black flex items-center gap-2 cursor-pointer">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Buat Celengan Baru</span>
                </button>
            </div>
        </div>

        <!-- Summary Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-[#FFF9E6] shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#634800]">Total Terkumpul di Celengan</span>
                    <span class="w-8 h-8 rounded-xl bg-[#FFD54F] border border-[#263238] flex items-center justify-center text-sm">🐮</span>
                </div>
                <p class="text-xl sm:text-2xl font-black text-[#263238] mt-2">
                    <span x-show="!hideBalance">Rp {{ number_format($totalGoalSaved, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                </p>
                <span class="text-xs font-bold text-[#526066] mt-1">Saldo tabungan aktif</span>
            </div>

            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-white shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#526066]">Total Target Impian</span>
                    <span class="w-8 h-8 rounded-xl bg-[#E0EAFF] border border-[#263238] flex items-center justify-center text-sm">🎯</span>
                </div>
                <p class="text-xl sm:text-2xl font-black text-[#245AE0] mt-2">
                    <span x-show="!hideBalance">Rp {{ number_format($totalGoalTarget, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                </p>
                <span class="text-xs font-bold text-[#526066] mt-1">Dari {{ $savingsGoals->count() }} celengan</span>
            </div>

            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-[#EDFAF3] shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#114A2D]">Celengan Tercapai</span>
                    <span class="w-8 h-8 rounded-xl bg-[#D8F5E5] border border-[#263238] flex items-center justify-center text-sm">★</span>
                </div>
                <p class="text-xl sm:text-2xl font-black text-[#1C7C49] mt-2">
                    {{ $achievedCount ?? 0 }} Target
                </p>
                <span class="text-xs font-bold text-[#526066] mt-1">Sudah 100% penuh</span>
            </div>
        </div>

        <!-- CELENGAN IMPIAN GRID -->
        <div class="mb-10">
            @if($savingsGoals->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @php
                        $colorMap = [
                            'yellow' => ['bg' => 'bg-[#FFF9E6]', 'bar' => 'bg-[#FFB703]', 'badge' => 'bg-[#FFD54F]'],
                            'green' => ['bg' => 'bg-[#EDFAF3]', 'bar' => 'bg-[#45C486]', 'badge' => 'bg-[#D8F5E5]'],
                            'purple' => ['bg' => 'bg-[#F5F0FF]', 'bar' => 'bg-[#9B7EDE]', 'badge' => 'bg-[#EAE2FF]'],
                            'blue' => ['bg' => 'bg-[#EEF4FF]', 'bar' => 'bg-[#245AE0]', 'badge' => 'bg-[#E0EAFF]'],
                        ];
                    @endphp
                    @foreach($savingsGoals as $goal)
                        @php
                            $theme = $colorMap[$goal->color] ?? $colorMap['yellow'];
                            $pct = $goal->progress_percent;
                        @endphp
                        <div class="p-5 rounded-3xl border-2 border-[#263238] {{ $theme['bg'] }} shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-11 h-11 rounded-2xl {{ $theme['badge'] }} border-2 border-[#263238] shadow-[2px_2px_0_#263238] flex items-center justify-center text-xl shrink-0">
                                            {{ $goal->icon }}
                                        </span>
                                        <div class="min-w-0">
                                            <h2 class="font-black text-base text-[#263238] truncate">{{ $goal->name }}</h2>
                                            @if($goal->target_date)
                                                <p class="text-[11px] font-bold text-[#526066]">Target: {{ $goal->target_date->translatedFormat('d M Y') }}</p>
                                            @else
                                                <p class="text-[11px] font-bold text-[#526066]">Celengan Aktif</p>
                                            @endif
                                        </div>
                                    </div>

                                    @if($goal->is_achieved)
                                        <span class="px-2.5 py-1 rounded-xl bg-[#45C486] text-[#114A2D] border border-[#263238] font-black text-[10px] uppercase shrink-0 shadow-[1.5px_1.5px_0_#263238]">
                                            ★ Tercapai!
                                        </span>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <div class="flex items-baseline justify-between mb-1">
                                        <span class="text-xs font-bold text-[#526066]">Terkumpul</span>
                                        <span class="text-xs font-black text-[#263238]">{{ $pct }}%</span>
                                    </div>
                                    <p class="text-xl font-black text-[#263238]">
                                        <span x-show="!hideBalance">Rp {{ number_format($goal->current_amount, 0, ',', '.') }}</span>
                                        <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                                    </p>
                                    <p class="text-xs font-bold text-[#526066] mt-0.5">
                                        Target:
                                        <span x-show="!hideBalance">Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</span>
                                        <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                                    </p>
                                </div>

                                <div class="w-full bg-white border-2 border-[#263238] rounded-full h-4 p-0.5 overflow-hidden mb-4">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $theme['bar'] }}"
                                         style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-[#263238]/15 flex items-center justify-between gap-2">
                                <button type="button"
                                        @click="openDeposit({ id: {{ $goal->id }}, name: @js($goal->name), remaining: {{ $goal->remaining_amount }}, action: @js(route('goals.deposit', $goal)) })"
                                        class="brick-btn brick-btn-yellow flex-1 py-2 px-3 text-xs font-black flex items-center justify-center gap-1.5 cursor-pointer">
                                    <x-icon name="plus" class="w-3.5 h-3.5" />
                                    <span>Isi Celengan</span>
                                </button>

                                <form action="{{ route('goals.destroy', $goal) }}" method="POST" onsubmit="return confirm('Hapus celengan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Hapus celengan {{ $goal->name }}"
                                            class="p-2 rounded-xl bg-white hover:bg-[#FFE2DE] border-2 border-[#263238] text-[#526066] hover:text-[#C93535] transition cursor-pointer">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-10 rounded-3xl bg-white border-2 border-dashed border-[#263238]/35 text-center flex flex-col items-center justify-center gap-2">
                    <img src="{{ asset('images/mascot/moowie-thumb.png') }}" alt="Moowie" class="w-16 h-16 mb-1 drop-shadow-[0_4px_8px_rgba(0,0,0,0.1)]">
                    <p class="font-black text-base text-[#263238]">Belum ada Celengan Impian</p>
                    <p class="text-xs text-[#526066] max-w-sm mb-3">Punya target beli barang impian, dana liburan, atau tabungan khusus? Yuk buat celengan pertamamu!</p>
                    <button type="button" @click="showGoalModal = true; triggerFeedback('tap')"
                            class="brick-btn brick-btn-yellow px-5 py-2.5 text-xs font-black cursor-pointer">
                        + Buat Celengan Pertama
                    </button>
                </div>
            @endif
        </div>

        <!-- MODAL 1: Buat Celengan Impian -->
        <div x-show="showGoalModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/65 backdrop-blur-xs" @click="showGoalModal = false"></div>
            <div class="min-h-screen flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-white border-2 border-[#263238] rounded-3xl shadow-[5px_5px_0_#263238] p-6 relative z-10" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-black text-[#263238]">🐮 Buat Celengan Impian</h3>
                        <button type="button" @click="showGoalModal = false" aria-label="Tutup" class="p-1.5 rounded-xl hover:bg-[#FCF6EB] cursor-pointer">
                            <x-icon name="x" class="w-5 h-5" />
                        </button>
                    </div>

                    <form action="{{ route('goals.store') }}" method="POST" @submit="triggerFeedback('coin')" class="space-y-4">
                        @csrf
                        <input type="hidden" name="icon" :value="selectedGoalIcon">
                        <input type="hidden" name="color" :value="selectedGoalColor">

                        <div>
                            <label for="goal-name" class="block text-xs font-bold text-[#263238] mb-1">Nama Target Impian</label>
                            <input id="goal-name" type="text" name="name" required placeholder="Contoh: Beli Laptop Baru, Liburan Bali"
                                   class="w-full min-h-[44px] px-3.5 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:outline-none">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label for="goal-target" class="text-xs font-bold text-[#263238]">Target Dana (Rp)</label>
                                </div>
                                <input id="goal-target" type="text" inputmode="numeric" name="target_amount" x-model="goalTargetRaw"
                                       @input="goalTargetRaw = goalTargetRaw.replace(/[^0-9]/g, '')" required placeholder="5000000"
                                       class="w-full min-h-[44px] px-3.5 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-black text-sm focus:bg-white focus:outline-none">
                                <span class="text-[11px] font-bold text-[#245AE0] mt-0.5 block" x-text="formatPreview(goalTargetRaw)"></span>
                            </div>

                            <div>
                                <div class="flex justify-between mb-1">
                                    <label for="goal-current" class="text-xs font-bold text-[#263238]">Saldo Awal (Opsional)</label>
                                </div>
                                <input id="goal-current" type="text" inputmode="numeric" name="current_amount" x-model="goalCurrentRaw"
                                       @input="goalCurrentRaw = goalCurrentRaw.replace(/[^0-9]/g, '')" placeholder="0"
                                       class="w-full min-h-[44px] px-3.5 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-black text-sm focus:bg-white focus:outline-none">
                                <span class="text-[11px] font-bold text-[#1C7C49] mt-0.5 block" x-text="formatPreview(goalCurrentRaw)"></span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-[#263238] mb-1.5">Pilih Ikon &amp; Warna Kartu</span>
                            <div class="flex flex-wrap gap-1.5 mb-2.5">
                                @foreach(['🐮', '💻', '✈️', '🏠', '💍', '🏍️', '🎓', '📱'] as $ic)
                                    <button type="button" @click="selectedGoalIcon = '{{ $ic }}'; triggerFeedback('tap')"
                                            :class="selectedGoalIcon === '{{ $ic }}' ? 'bg-[#FFD54F] border-2 border-[#263238] shadow-[2px_2px_0_#263238]' : 'bg-[#FCF6EB] border border-[#263238]/25'"
                                            class="w-10 h-10 rounded-xl flex items-center justify-center text-lg cursor-pointer transition">
                                        {{ $ic }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach(['yellow' => ['Kuning', 'bg-[#FFF9E6]'], 'green' => ['Hijau', 'bg-[#EDFAF3]'], 'purple' => ['Ungu', 'bg-[#F5F0FF]'], 'blue' => ['Biru', 'bg-[#EEF4FF]']] as $cKey => $cMeta)
                                    <button type="button" @click="selectedGoalColor = '{{ $cKey }}'; triggerFeedback('tap')"
                                            :class="selectedGoalColor === '{{ $cKey }}' ? 'border-2 border-[#263238] shadow-[2px_2px_0_#263238]' : 'border border-[#263238]/30 opacity-75'"
                                            class="py-2 rounded-xl {{ $cMeta[1] }} font-black text-xs text-[#263238] cursor-pointer">
                                        {{ $cMeta[0] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="goal-date" class="block text-xs font-bold text-[#263238] mb-1">Target Tanggal Tercapai (Opsional)</label>
                            <input id="goal-date" type="date" name="target_date"
                                   class="w-full min-h-[44px] px-3.5 py-2 rounded-xl border border-[#263238]/40 bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:outline-none">
                        </div>

                        <div class="pt-2 flex justify-end gap-2.5">
                            <button type="button" @click="showGoalModal = false" class="brick-btn brick-btn-white px-4 py-2.5 text-xs font-bold cursor-pointer">Batal</button>
                            <button type="submit" class="brick-btn brick-btn-yellow px-5 py-2.5 text-xs font-black cursor-pointer">Simpan Celengan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL 2: Isi / Tabung ke Celengan -->
        <div x-show="depositGoal !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/65 backdrop-blur-xs" @click="depositGoal = null"></div>
            <div class="min-h-screen flex items-center justify-center p-4">
                <div class="w-full max-w-md bg-white border-2 border-[#263238] rounded-3xl shadow-[5px_5px_0_#263238] p-6 relative z-10" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-black text-[#263238]">Isi Celengan Impian</h3>
                            <p class="text-xs font-bold text-[#526066]" x-text="depositGoal ? depositGoal.name : ''"></p>
                        </div>
                        <button type="button" @click="depositGoal = null" aria-label="Tutup" class="p-1.5 rounded-xl hover:bg-[#FCF6EB] cursor-pointer">
                            <x-icon name="x" class="w-5 h-5" />
                        </button>
                    </div>

                    <form :action="depositGoal ? depositGoal.action : '#'" method="POST" @submit="triggerFeedback('coin')" class="space-y-4">
                        @csrf
                        <div>
                            <div class="flex justify-between mb-1">
                                <label for="dep-amount" class="text-xs font-bold text-[#263238]">Nominal Tabungan</label>
                                <span class="text-xs font-black text-[#1C7C49]" x-text="formatPreview(depositAmount)"></span>
                            </div>
                            <input id="dep-amount" type="text" inputmode="numeric" name="amount" x-model="depositAmount"
                                   @input="depositAmount = depositAmount.replace(/[^0-9]/g, '')" required placeholder="100000"
                                   class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-black text-lg focus:bg-white focus:outline-none">

                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <button type="button" @click="addDepositChip(50000)" class="px-2.5 py-1 rounded-lg bg-[#FCF6EB] hover:bg-[#FFD54F] border border-[#263238]/30 text-xs font-bold cursor-pointer">+50rb</button>
                                <button type="button" @click="addDepositChip(100000)" class="px-2.5 py-1 rounded-lg bg-[#FCF6EB] hover:bg-[#FFD54F] border border-[#263238]/30 text-xs font-bold cursor-pointer">+100rb</button>
                                <button type="button" @click="addDepositChip(250000)" class="px-2.5 py-1 rounded-lg bg-[#FCF6EB] hover:bg-[#FFD54F] border border-[#263238]/30 text-xs font-bold cursor-pointer">+250rb</button>
                                <button type="button" @click="addDepositChip(500000)" class="px-2.5 py-1 rounded-lg bg-[#FCF6EB] hover:bg-[#FFD54F] border border-[#263238]/30 text-xs font-bold cursor-pointer">+500rb</button>
                            </div>
                        </div>

                        <label class="flex items-start gap-2.5 p-3 rounded-xl bg-[#FCF6EB] border border-[#263238]/25 cursor-pointer">
                            <input type="checkbox" name="record_transaction" value="1" x-model="recordDepositTx" class="mt-0.5 w-4 h-4 rounded border-[#263238]">
                            <span class="text-xs font-bold text-[#263238] leading-snug">
                                Catat otomatis ke pengeluaran pos <strong>Dana Darurat</strong> bulan ini agar sisa uang sinkron
                            </span>
                        </label>

                        <div class="pt-2 flex justify-end gap-2.5">
                            <button type="button" @click="depositGoal = null" class="brick-btn brick-btn-white px-4 py-2.5 text-xs font-bold cursor-pointer">Batal</button>
                            <button type="submit" class="brick-btn brick-btn-yellow px-5 py-2.5 text-xs font-black cursor-pointer">Tambah Tabungan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
