<x-app-layout title="Dashboard">
    <div x-data="{ deleteFormId: null }">

        <!-- A. Calm Greeting Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#263238]">
                    Halo, {{ auth()->user()->name }}
                </h1>
                <p class="text-xs sm:text-sm text-[#526066] font-medium mt-0.5">
                    Ringkasan uang dan alokasi anggaran periode {{ $monthLabel }}.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center">
                    <label for="dash-month-select" class="sr-only">Pilih Bulan</label>
                    <select id="dash-month-select" name="month" onchange="this.form.submit()"
                            class="min-h-[44px] px-3.5 py-2 rounded-xl border-2 border-[#263238] bg-white font-bold text-xs sm:text-sm focus:outline-none cursor-pointer">
                        @foreach($availableMonths as $val => $label)
                            <option value="{{ $val }}" {{ $selectedMonth === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <button type="button" @click="$dispatch('open-receipt-modal')"
                        class="brick-btn brick-btn-white px-3.5 py-2 text-xs sm:text-sm font-black flex items-center gap-1.5 cursor-pointer">
                    <x-icon name="receipt" class="w-4 h-4 text-[#245AE0]" />
                    <span>Slip Struk</span>
                </button>

                <button type="button" @click="triggerFeedback('tap'); $dispatch('open-quick-add')"
                        class="brick-btn brick-btn-yellow px-4 py-2 text-xs sm:text-sm font-black flex items-center gap-2 cursor-pointer">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Catat Transaksi</span>
                </button>
            </div>
        </div>

        <!-- B. Asymmetric Summary Strip (High Contrast Postcase Postcard Aesthetic + Privacy Eye) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 mb-8 items-stretch">
            <!-- Focal Primary Metric (Crisp Rich Blue Card) -->
            <div class="lg:col-span-5 p-5 sm:p-6 rounded-3xl border-2 border-[#263238] bg-[#245AE0] text-white shadow-[4px_4px_0_#263238] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full pointer-events-none"></div>
                <div class="flex items-start justify-between gap-3 relative z-10">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-blue-100">Perkiraan Sisa Uang</span>
                            <button type="button"
                                    @click="togglePrivacy()"
                                    :aria-label="hideBalance ? 'Tampilkan nominal saldo' : 'Sembunyikan nominal saldo'"
                                    class="p-1 rounded-lg bg-white/15 hover:bg-white/25 border border-white/30 text-white transition cursor-pointer">
                                <template x-if="!hideBalance">
                                    <x-icon name="eye" class="w-3.5 h-3.5" />
                                </template>
                                <template x-if="hideBalance">
                                    <x-icon name="eye-off" class="w-3.5 h-3.5" />
                                </template>
                            </button>
                        </div>
                        <p class="text-2xl sm:text-3xl font-black tracking-tight mt-1 text-white">
                            <span x-show="!hideBalance">Rp {{ number_format($summary['estimated_remaining_cash'], 0, ',', '.') }}</span>
                            <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                        </p>
                    </div>
                    <div class="p-2 bg-white/20 rounded-2xl border border-white/30 shrink-0">
                        <x-duitmoo-mascot variant="eyes" class="w-10 h-5" />
                    </div>
                </div>

                <div class="mt-5 pt-3.5 border-t border-white/20 flex flex-wrap items-center justify-between gap-2 text-xs text-blue-100 font-bold relative z-10">
                    <div>
                        <span>
                            Saldo Awal:
                            <span x-show="!hideBalance">Rp {{ number_format($summary['opening_balance'], 0, ',', '.') }}</span>
                            <span x-show="hideBalance" x-cloak>Rp ••••••</span>
                        </span>
                        <span class="mx-1.5 opacity-60">·</span>
                        <a href="{{ route('budget.settings', ['month' => $selectedMonth]) }}" class="text-[#FFD54F] hover:underline font-black">
                            Ubah &rarr;
                        </a>
                    </div>
                    <span class="px-2 py-0.5 bg-white/20 rounded-lg text-[10px] uppercase font-black tracking-wider">{{ $monthLabel }}</span>
                </div>
            </div>

            <!-- 3 Executive Numbers: Total Pemasukan, Total Pengeluaran, & Profit / Net Income -->
            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 sm:p-5 rounded-3xl border-2 border-[#263238] bg-[#EDFAF3] shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#114A2D]">Total Pemasukan</span>
                        <div class="p-1.5 bg-[#D8F5E5] border border-[#263238] rounded-xl">
                            <x-icon name="arrow-down-left" class="w-4 h-4 text-[#1C7C49]" />
                        </div>
                    </div>
                    <p class="text-lg sm:text-xl font-black text-[#1C7C49] mt-2 truncate">
                        <span x-show="!hideBalance">+Rp {{ number_format($summary['income'], 0, ',', '.') }}</span>
                        <span x-show="hideBalance" x-cloak>+Rp ••••••••</span>
                    </p>
                    <span class="text-xs font-medium text-[#526066] mt-2 block">Dana masuk ({{ $monthLabel }})</span>
                </div>

                <div class="p-4 sm:p-5 rounded-3xl border-2 border-[#263238] bg-[#FFF0ED] shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#8C1D1D]">Total Pengeluaran</span>
                        <div class="p-1.5 bg-[#FFE2DE] border border-[#263238] rounded-xl">
                            <x-icon name="arrow-up-right" class="w-4 h-4 text-[#C93535]" />
                        </div>
                    </div>
                    <p class="text-lg sm:text-xl font-black text-[#C93535] mt-2 truncate">
                        <span x-show="!hideBalance">-Rp {{ number_format($summary['total_expenses'], 0, ',', '.') }}</span>
                        <span x-show="hideBalance" x-cloak>-Rp ••••••••</span>
                    </p>
                    <span class="text-xs font-medium text-[#526066] mt-2 block">Dana keluar ({{ $monthLabel }})</span>
                </div>

                @php
                    $profitIncome = $summary['profit_income'] ?? ($summary['income'] - $summary['total_expenses']);
                    $profitMargin = $summary['profit_margin_percent'] ?? ($summary['income'] > 0 ? round(($profitIncome / $summary['income']) * 100, 1) : 0);
                    $isProfitable = $profitIncome >= 0;
                @endphp
                <div class="p-4 sm:p-5 rounded-3xl border-2 border-[#263238] {{ $isProfitable ? 'bg-[#FFF9E6]' : 'bg-[#FFE2DE]' }} shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-xs font-bold text-[#263238]">Profit / Net Income</span>
                        <span class="px-2 py-0.5 rounded-lg border border-[#263238] {{ $isProfitable ? 'bg-[#D8F5E5] text-[#114A2D]' : 'bg-white text-[#8C1D1D]' }} text-[10px] font-black">
                            {{ $isProfitable ? 'PROFIT' : 'DEFISIT' }}
                        </span>
                    </div>
                    <p class="text-lg sm:text-xl font-black {{ $isProfitable ? 'text-[#1C7C49]' : 'text-[#C93535]' }} mt-2 truncate">
                        <span x-show="!hideBalance">{{ $isProfitable ? '+' : '-' }}Rp {{ number_format(abs($profitIncome), 0, ',', '.') }}</span>
                        <span x-show="hideBalance" x-cloak>{{ $isProfitable ? '+' : '-' }}Rp ••••••••</span>
                    </p>
                    <span class="text-xs font-bold text-[#526066] mt-2 block">
                        Rasio Profit: <strong class="text-[#263238]">{{ $profitMargin }}%</strong>
                    </span>
                </div>
            </div>
        </div>

        <!-- C. Budget Allocation Bricks (Distinct Harmonious Pastels inspired by Reference 3) -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-[#263238]">Pembagian 3 Pos Anggaran</h2>
                    <p class="text-xs text-[#526066] font-medium">
                        Dari total dana
                        <span x-show="!hideBalance">Rp {{ number_format($summary['total_allocatable'], 0, ',', '.') }}</span>
                        <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                        di bulan {{ $monthLabel }}
                    </p>
                </div>
                <a href="{{ route('budget.settings', ['month' => $selectedMonth]) }}"
                   class="text-xs font-black text-[#245AE0] hover:underline self-start sm:self-auto py-1">
                    Sesuaikan persentase &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @php
                    $posConfig = [
                        'needs' => [
                            'cardBg' => 'bg-[#EDFAF3]',
                            'badgeBg' => 'bg-[#45C486]',
                            'badgeText' => 'text-[#114A2D]',
                            'fillBg' => 'bg-[#45C486]',
                            'textColor' => 'text-[#114A2D]',
                        ],
                        'wants' => [
                            'cardBg' => 'bg-[#FFF9E6]',
                            'badgeBg' => 'bg-[#FFD54F]',
                            'badgeText' => 'text-[#634800]',
                            'fillBg' => 'bg-[#FFB703]',
                            'textColor' => 'text-[#263238]',
                        ],
                        'emergency' => [
                            'cardBg' => 'bg-[#F5F0FF]',
                            'badgeBg' => 'bg-[#C4B5FD]',
                            'badgeText' => 'text-[#3A1D75]',
                            'fillBg' => 'bg-[#9B7EDE]',
                            'textColor' => 'text-[#3A1D75]',
                        ],
                    ];
                @endphp

                @foreach($summary['buckets'] as $key => $bucket)
                    @php $cfg = $posConfig[$key]; @endphp
                    <div class="p-5 sm:p-6 rounded-3xl border-2 border-[#263238] {{ $cfg['cardBg'] }} shadow-[3px_3px_0_#263238] flex flex-col justify-between transition hover:-translate-y-0.5">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-3 py-1 rounded-xl border border-[#263238] {{ $cfg['badgeBg'] }} {{ $cfg['badgeText'] }} font-black text-xs shadow-[1.5px_1.5px_0_#263238]">
                                    {{ $bucket['label'] }} ({{ $bucket['percent'] }}%)
                                </span>
                                @if($bucket['is_overspent'])
                                    <span class="px-2.5 py-0.5 rounded-lg border border-[#263238] bg-[#FFE2DE] text-[#8C1D1D] font-black text-[11px]">
                                        Melebihi batas
                                    </span>
                                @else
                                    <x-duitmoo-mascot variant="eyes" class="w-8 h-4 opacity-90" />
                                @endif
                            </div>

                            <div class="mb-4">
                                <p class="text-xs font-bold text-[#526066]">{{ $key === 'emergency' ? 'Alokasi Rencana' : 'Alokasi Anggaran' }}</p>
                                <p class="text-2xl font-black text-[#263238] mt-0.5 tracking-tight">
                                    <span x-show="!hideBalance">Rp {{ number_format($bucket['allocated'], 0, ',', '.') }}</span>
                                    <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                                </p>
                            </div>

                            <div class="mb-4">
                                <div class="flex justify-between text-xs font-bold mb-1.5">
                                    <span class="text-[#526066]">
                                        Terpakai:
                                        <span x-show="!hideBalance">Rp {{ number_format($bucket['spent'], 0, ',', '.') }}</span>
                                        <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                                    </span>
                                    <span class="{{ $bucket['is_overspent'] ? 'text-[#C93535]' : $cfg['textColor'] }} font-black">
                                        {{ $bucket['progress'] }}%
                                    </span>
                                </div>
                                <div class="w-full bg-white border-2 border-[#263238] rounded-full h-4 p-0.5 overflow-hidden shadow-inner">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $bucket['is_overspent'] ? 'bg-[#FF6B6B]' : $cfg['fillBg'] }}"
                                         style="width: {{ min(100, $bucket['progress']) }}%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3.5 border-t border-[#263238]/15 flex items-center justify-between">
                            <span class="text-xs font-bold text-[#526066]">Sisa Pos</span>
                            <span class="text-base font-black {{ $bucket['is_overspent'] ? 'text-[#C93535]' : $cfg['textColor'] }}">
                                <span x-show="!hideBalance">{{ $bucket['is_overspent'] ? '-' : '' }}Rp {{ number_format(abs($bucket['remaining']), 0, ',', '.') }}</span>
                                <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- C2. Celengan Impian & Pengingat Tagihan Rutin (New Feature Cards) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8 items-stretch">
            <!-- Celengan Impian Widget -->
            <div class="lg:col-span-6 rounded-3xl border-2 border-[#263238] bg-[#FFF9E6] p-5 sm:p-6 shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-[#FFD54F] border-2 border-[#263238] shadow-[1.5px_1.5px_0_#263238] flex items-center justify-center text-base">
                                🐮
                            </span>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-[#263238] leading-tight">Celengan Impian</h2>
                                <p class="text-xs text-[#634800] font-medium">Progress target tabunganmu</p>
                            </div>
                        </div>
                        <a href="{{ route('goals.index') }}" class="text-xs font-black text-[#245AE0] hover:underline">
                            Kelola celengan &rarr;
                        </a>
                    </div>

                    @if(isset($savingsGoals) && $savingsGoals->count() > 0)
                        <div class="space-y-3">
                            @foreach($savingsGoals as $goal)
                                <div class="p-3.5 rounded-2xl bg-white border-2 border-[#263238] shadow-[2px_2px_0_#263238]">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="text-base">{{ $goal->icon }}</span>
                                            <span class="font-black text-xs sm:text-sm text-[#263238] truncate">{{ $goal->name }}</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-lg bg-[#FFEAA0] border border-[#263238] font-black text-[10px] text-[#263238]">
                                            {{ $goal->progress_percent }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-[#FCF6EB] border border-[#263238] rounded-full h-3 p-0.5 overflow-hidden mb-1.5">
                                        <div class="h-full rounded-full bg-[#FFB703]" style="width: {{ $goal->progress_percent }}%;"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] font-bold text-[#526066]">
                                        <span>
                                            Terkumpul:
                                            <strong class="text-[#263238]" x-show="!hideBalance">Rp {{ number_format($goal->current_amount, 0, ',', '.') }}</strong>
                                            <strong class="text-[#263238]" x-show="hideBalance" x-cloak>Rp ••••••</strong>
                                        </span>
                                        <span>
                                            Target:
                                            <span x-show="!hideBalance">Rp {{ number_format($goal->target_amount, 0, ',', '.') }}</span>
                                            <span x-show="hideBalance" x-cloak>Rp ••••••</span>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 rounded-2xl bg-white/80 border-2 border-dashed border-[#263238]/30 text-center">
                            <p class="font-black text-xs sm:text-sm text-[#263238]">Belum ada target Celengan Impian</p>
                            <p class="text-xs text-[#526066] mt-1 mb-3">Buat target tabungan (misal: Beli Laptop, Liburan) dan pantau progress-nya di sini.</p>
                            <a href="{{ route('goals.index') }}" class="brick-btn brick-btn-yellow px-4 py-2 text-xs font-black inline-flex items-center gap-1.5">
                                <x-icon name="plus" class="w-3.5 h-3.5" />
                                <span>Buat Celengan Impian</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pengingat Tagihan Rutin Widget -->
            <div class="lg:col-span-6 rounded-3xl border-2 border-[#263238] bg-[#FFFDF9] p-5 sm:p-6 shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-[#FFE2DE] border-2 border-[#263238] shadow-[1.5px_1.5px_0_#263238] flex items-center justify-center text-base">
                                ⚡
                            </span>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-[#263238] leading-tight">Tagihan Rutin ({{ $monthLabel }})</h2>
                                <p class="text-xs text-[#526066] font-medium">Pengingat langganan &amp; tagihan bulanan</p>
                            </div>
                        </div>
                        <a href="{{ route('bills.index') }}" class="text-xs font-black text-[#245AE0] hover:underline">
                            Atur tagihan &rarr;
                        </a>
                    </div>

                    @if(isset($recurringBills) && $recurringBills->count() > 0)
                        <div class="space-y-2.5">
                            @foreach($recurringBills as $bill)
                                @php $status = $bill->getStatusBadgeForMonth($selectedMonth); @endphp
                                <div class="p-3 rounded-2xl {{ $status['is_paid'] ? 'bg-[#EDFAF3]' : 'bg-[#FCF6EB]' }} border-2 border-[#263238] flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-9 h-9 rounded-xl bg-white border border-[#263238] flex items-center justify-center text-base shrink-0">
                                            {{ $bill->icon }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <p class="font-black text-xs sm:text-sm text-[#263238] truncate">{{ $bill->name }}</p>
                                                <span class="px-1.5 py-0.5 rounded-md border border-[#263238] {{ $status['bg'] }} {{ $status['text'] }} font-black text-[9px] shrink-0">
                                                    {{ $status['label'] }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] font-bold text-[#526066]">
                                                <span x-show="!hideBalance">Rp {{ number_format($bill->amount, 0, ',', '.') }}</span>
                                                <span x-show="hideBalance" x-cloak>Rp ••••••</span>
                                                · Tgl {{ $bill->due_day }}
                                            </p>
                                        </div>
                                    </div>

                                    @if(!$status['is_paid'])
                                        <form action="{{ route('bills.pay', $bill) }}" method="POST" @submit="triggerFeedback('coin')">
                                            @csrf
                                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                            <input type="hidden" name="record_transaction" value="1">
                                            <button type="submit" class="brick-btn brick-btn-green px-3 py-1.5 text-[11px] font-black shrink-0 cursor-pointer">
                                                Bayar
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg bg-[#D8F5E5] text-[#114A2D] border border-[#263238] text-[10px] font-black shrink-0">
                                            Lunas ✓
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 rounded-2xl bg-[#FCF6EB] border-2 border-dashed border-[#263238]/30 text-center">
                            <p class="font-black text-xs sm:text-sm text-[#263238]">Belum ada pengingat tagihan rutin</p>
                            <p class="text-xs text-[#526066] mt-1 mb-3">Catat jadwal tagihan WiFi, Listrik, Kosan, atau Netflix agar tidak terlewat setiap bulan.</p>
                            <a href="{{ route('bills.index') }}" class="brick-btn brick-btn-white px-4 py-2 text-xs font-black inline-flex items-center gap-1.5">
                                <x-icon name="bell" class="w-3.5 h-3.5 text-[#245AE0]" />
                                <span>Tambah Tagihan Rutin</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- D. Recent Transactions (Full-Width Clean Card - Catatan Anggaran moved to Top Navbar Bell) -->
        <div class="rounded-3xl border-2 border-[#263238] bg-white p-5 sm:p-6 shadow-[3px_3px_0_#263238] mb-8">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-black text-[#263238]">Catatan Terbaru</h2>
                    <p class="text-xs text-[#526066] font-medium">5 transaksi terakhir di {{ $monthLabel }}</p>
                </div>
                <a href="{{ route('transactions.index', ['month' => $selectedMonth]) }}"
                   class="text-xs font-black text-[#245AE0] hover:underline py-1">
                    Lihat semua &rarr;
                </a>
            </div>

            @if($recentTransactions->count() > 0)
                <div class="space-y-2.5">
                    @foreach($recentTransactions as $tx)
                        @php
                            $rowTint = match($tx->bucket) {
                                'wants' => 'bg-[#FFF9E6]',
                                'emergency' => 'bg-[#F5F0FF]',
                                default => $tx->type === 'income' ? 'bg-[#EDFAF3]' : 'bg-[#FCF6EB]',
                            };
                            $bLabel = match($tx->bucket) {
                                'needs' => 'Kebutuhan',
                                'wants' => 'Keinginan',
                                'emergency' => 'Darurat',
                                default => 'Pemasukan',
                            };
                            $catTitle = $tx->category?->name ?? ($tx->type === 'income' ? 'Pemasukan' : $bLabel);
                        @endphp
                        <div class="p-3.5 rounded-2xl {{ $rowTint }} border-2 border-[#263238]/20 hover:border-[#263238] transition flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <x-category-badge-icon :bucket="$tx->type === 'income' ? 'income' : ($tx->bucket ?? 'needs')" :categoryName="$catTitle" />
                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-[#263238] truncate">
                                        {{ $tx->description ?: ($tx->type === 'income' ? ($tx->income_source ?: 'Pemasukan') : $catTitle) }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-[11px] text-[#526066] font-medium">
                                        <span>{{ \Carbon\Carbon::parse($tx->transaction_date)->translatedFormat('d M') }}</span>
                                        <span>·</span>
                                        <span class="font-bold text-[#263238]">{{ $bLabel }}</span>
                                        @if($tx->category)
                                            <span>({{ $tx->category->name }})</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="font-black text-sm {{ $tx->type === 'income' ? 'text-[#1C7C49]' : 'text-[#C93535]' }}">
                                    <span x-show="!hideBalance">{{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}</span>
                                    <span x-show="hideBalance" x-cloak>{{ $tx->type === 'income' ? '+' : '-' }}Rp ••••••</span>
                                </span>
                                <form id="del-recent-{{ $tx->id }}" action="{{ route('transactions.destroy', $tx) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" @click="deleteFormId = 'del-recent-{{ $tx->id }}'"
                                            class="p-2 text-[#526066] hover:text-[#C93535] transition cursor-pointer rounded-lg" aria-label="Hapus transaksi">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 rounded-2xl bg-[#FCF6EB] border-2 border-dashed border-[#263238]/30 text-center flex flex-col items-center justify-center gap-2">
                    <div class="w-12 h-12 bg-white rounded-2xl border-2 border-[#263238] shadow-[2px_2px_0_#263238] flex items-center justify-center text-xl mb-1">
                        📮
                    </div>
                    <p class="font-black text-sm text-[#263238]">Belum ada transaksi di bulan {{ $monthLabel }}</p>
                    <p class="text-xs text-[#526066] max-w-sm mb-3">Catat pemasukan atau pengeluaran pertamamu agar grafik dan sisa pos langsung terhitung.</p>
                    <button type="button" @click="triggerFeedback('tap'); $dispatch('open-quick-add')" class="brick-btn brick-btn-yellow px-5 py-2 text-xs font-black shadow-[2px_2px_0_#263238]">
                        Catat Transaksi Pertama
                    </button>
                </div>
            @endif
        </div>

        <!-- E. Unified Single Chart Section (1 Section with Interactive Switcher Tabs) -->
        <div x-data="{ activeChart: 'donut' }" class="rounded-3xl border-2 border-[#263238] bg-white p-5 sm:p-6 shadow-[3px_3px_0_#263238] mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 mb-5 border-b border-dashed border-[#263238]/20">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-lg bg-[#FFEAA0] border border-[#263238] text-[10px] font-black uppercase">Visualisasi Data</span>
                        <span class="text-xs font-bold text-[#526066]">{{ $monthLabel }}</span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-black text-[#263238] mt-1">
                        <span x-show="activeChart === 'donut'">Ke mana uangmu keluar bulan ini?</span>
                        <span x-show="activeChart === 'trend'" x-cloak>Apakah pemasukan menutup belanja 6 bulan ini?</span>
                        <span x-show="activeChart === 'cash'" x-cloak>Bagaimana sisa saldomu bergerak tiap bulan?</span>
                        <span x-show="activeChart === 'category'" x-cloak>Kategori mana yang paling banyak menyerap dana?</span>
                    </h2>
                </div>

                <!-- Chart Switcher Tabs inside the Single Section -->
                <div class="flex flex-wrap items-center gap-1.5 bg-[#FCF6EB] p-1.5 rounded-2xl border-2 border-[#263238]">
                    <button type="button" @click="activeChart = 'donut'; triggerFeedback('tap')"
                            :class="activeChart === 'donut' ? 'bg-[#FFD54F] text-[#263238] border border-[#263238] shadow-[1.5px_1.5px_0_#263238]' : 'text-[#526066] hover:text-[#263238]'"
                            class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer">
                        Distribusi Pos
                    </button>
                    <button type="button" @click="activeChart = 'trend'; triggerFeedback('tap')"
                            :class="activeChart === 'trend' ? 'bg-[#FFD54F] text-[#263238] border border-[#263238] shadow-[1.5px_1.5px_0_#263238]' : 'text-[#526066] hover:text-[#263238]'"
                            class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer">
                        Tren 6 Bulan
                    </button>
                    <button type="button" @click="activeChart = 'cash'; triggerFeedback('tap')"
                            :class="activeChart === 'cash' ? 'bg-[#FFD54F] text-[#263238] border border-[#263238] shadow-[1.5px_1.5px_0_#263238]' : 'text-[#526066] hover:text-[#263238]'"
                            class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer">
                        Pergerakan Saldo
                    </button>
                    <button type="button" @click="activeChart = 'category'; triggerFeedback('tap')"
                            :class="activeChart === 'category' ? 'bg-[#FFD54F] text-[#263238] border border-[#263238] shadow-[1.5px_1.5px_0_#263238]' : 'text-[#526066] hover:text-[#263238]'"
                            class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer">
                        Top Kategori
                    </button>
                </div>
            </div>

            <!-- Panel 1: Donut Chart -->
            <div x-show="activeChart === 'donut'">
                @if($summary['total_expenses'] > 0)
                    <div class="relative h-72 flex items-center justify-center">
                        <canvas id="expenseDonutChart"></canvas>
                    </div>
                @else
                    <div class="h-72 flex flex-col items-center justify-center text-center p-6 border-2 border-dashed border-[#263238]/25 rounded-2xl bg-[#FCF6EB]">
                        <p class="font-bold text-sm text-[#263238]">Belum ada pengeluaran di {{ $monthLabel }}</p>
                        <p class="text-xs text-[#526066] mt-1">Grafik distribusi pos akan muncul setelah pengeluaran pertama dicatat.</p>
                    </div>
                @endif
            </div>

            <!-- Panel 2: 6-Month Income vs Expense Trend -->
            <div x-show="activeChart === 'trend'" x-cloak>
                <div class="relative h-72 flex items-center justify-center">
                    <canvas id="trendBarChart"></canvas>
                </div>
            </div>

            <!-- Panel 3: 6-Month Remaining Balance Line Chart -->
            <div x-show="activeChart === 'cash'" x-cloak>
                <div class="relative h-72 flex items-center justify-center">
                    <canvas id="cashLineChart"></canvas>
                </div>
            </div>

            <!-- Panel 4: Top Categories Bar Chart -->
            <div x-show="activeChart === 'category'" x-cloak>
                @if(count($categoryBarData) > 0)
                    <div class="relative h-72 flex items-center justify-center">
                        <canvas id="categoryBarChart"></canvas>
                    </div>
                @else
                    <div class="h-72 flex flex-col items-center justify-center text-center p-6 border-2 border-dashed border-[#263238]/25 rounded-2xl bg-[#FCF6EB]">
                        <p class="font-bold text-sm text-[#263238]">Belum ada rincian kategori bulan ini</p>
                        <p class="text-xs text-[#526066] mt-1">Pilih kategori saat mencatat pengeluaran untuk melihat peringkatnya di sini.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div x-show="deleteFormId !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60" @click="deleteFormId = null"></div>
            <div class="brick-card-pop max-w-sm w-full p-6 bg-white relative z-10 text-center">
                <h3 class="text-lg font-black text-[#263238] mb-1">Hapus Transaksi Ini?</h3>
                <p class="text-xs text-[#526066] font-medium mb-5">Saldo dan sisa anggaran bulan ini akan otomatis dihitung ulang.</p>
                <div class="flex items-center gap-3">
                    <button type="button" @click="deleteFormId = null" class="brick-btn brick-btn-white flex-1 py-2 text-xs font-bold">Batal</button>
                    <button type="button" @click="document.getElementById(deleteFormId).submit()" class="brick-btn brick-btn-red flex-1 py-2 text-xs font-black">Ya, Hapus</button>
                </div>
            </div>
        </div>

        <!-- Aesthetic Shareable Receipt Slip Modal -->
        <x-receipt-modal
            :summary="$summary"
            :monthLabel="$monthLabel"
            :selectedMonth="$selectedMonth"
            :savingsGoals="$savingsGoals ?? collect()"
        />
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.Chart) return;

            @if($summary['total_expenses'] > 0)
            const donutCtx = document.getElementById('expenseDonutChart');
            if (donutCtx) {
                new window.Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($donutLabels),
                        datasets: [{
                            data: @json($donutData),
                            backgroundColor: @json($donutColors),
                            borderColor: '#263238',
                            borderWidth: 2,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: { position: 'bottom', labels: { font: { family: 'Poppins', weight: 'bold', size: 12 }, color: '#263238', padding: 14 } },
                            tooltip: { callbacks: { label: (ctx) => ' ' + ctx.label + ': Rp ' + (ctx.parsed || 0).toLocaleString('id-ID') } }
                        }
                    }
                });
            }
            @endif

            const trendCtx = document.getElementById('trendBarChart');
            if (trendCtx) {
                new window.Chart(trendCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($historicalLabels),
                        datasets: [
                            { label: 'Pemasukan', data: @json($historicalIncome), backgroundColor: '#45C486', borderColor: '#263238', borderWidth: 1.5, borderRadius: 6 },
                            { label: 'Pengeluaran', data: @json($historicalExpense), backgroundColor: '#FF6B6B', borderColor: '#263238', borderWidth: 1.5, borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { font: { family: 'Poppins', weight: 'bold', size: 12 }, color: '#263238', padding: 14 } },
                            tooltip: { callbacks: { label: (ctx) => ' ' + ctx.dataset.label + ': Rp ' + (ctx.parsed.y || 0).toLocaleString('id-ID') } }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#EFE6D5' }, ticks: { font: { family: 'Poppins', size: 10 }, callback: (v) => v >= 1000000 ? (v / 1000000) + ' Jt' : (v >= 1000 ? (v / 1000) + ' Rb' : v) } },
                            x: { grid: { display: false }, ticks: { font: { family: 'Poppins', weight: 'bold', size: 11 }, color: '#263238' } }
                        }
                    }
                });
            }

            const lineCtx = document.getElementById('cashLineChart');
            if (lineCtx) {
                new window.Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: @json($historicalLabels),
                        datasets: [{
                            label: 'Sisa Saldo Bulanan',
                            data: @json($historicalRemainingCash),
                            borderColor: '#245AE0',
                            backgroundColor: 'rgba(36, 90, 224, 0.12)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#FFD54F',
                            pointBorderColor: '#263238',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { font: { family: 'Poppins', weight: 'bold', size: 12 }, color: '#263238' } },
                            tooltip: { callbacks: { label: (ctx) => ' Sisa: Rp ' + (ctx.parsed.y || 0).toLocaleString('id-ID') } }
                        },
                        scales: {
                            y: { grid: { color: '#EFE6D5' }, ticks: { font: { family: 'Poppins', size: 10 }, callback: (v) => Math.abs(v) >= 1000000 ? (v / 1000000) + ' Jt' : (Math.abs(v) >= 1000 ? (v / 1000) + ' Rb' : v) } },
                            x: { grid: { display: false }, ticks: { font: { family: 'Poppins', weight: 'bold', size: 11 }, color: '#263238' } }
                        }
                    }
                });
            }

            @if(count($categoryBarData) > 0)
            const catBarCtx = document.getElementById('categoryBarChart');
            if (catBarCtx) {
                new window.Chart(catBarCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($categoryBarLabels),
                        datasets: [{
                            label: 'Pengeluaran Kategori',
                            data: @json($categoryBarData),
                            backgroundColor: @json($categoryBarColors),
                            borderColor: '#263238',
                            borderWidth: 1.5,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => ' Rp ' + (ctx.parsed.x || 0).toLocaleString('id-ID') } }
                        },
                        scales: {
                            x: { beginAtZero: true, grid: { color: '#EFE6D5' }, ticks: { font: { family: 'Poppins', size: 10 }, callback: (v) => v >= 1000000 ? (v / 1000000) + ' Jt' : (v >= 1000 ? (v / 1000) + ' Rb' : v) } },
                            y: { grid: { display: false }, ticks: { font: { family: 'Poppins', weight: 'bold', size: 11 }, color: '#263238' } }
                        }
                    }
                });
            }
            @endif
        });
    </script>
</x-app-layout>
