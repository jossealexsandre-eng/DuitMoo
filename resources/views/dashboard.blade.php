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

            <div class="flex flex-wrap items-center gap-2">
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
                        class="hidden sm:inline-flex brick-btn brick-btn-white px-3.5 py-2 text-xs sm:text-sm font-black items-center gap-1.5 cursor-pointer">
                    <x-icon name="receipt" class="w-4 h-4 text-[#245AE0]" />
                    <span>Slip Struk</span>
                </button>

                <button type="button" @click="triggerFeedback('tap'); $dispatch('open-quick-add')"
                        class="hidden sm:inline-flex brick-btn brick-btn-yellow px-4 py-2 text-xs sm:text-sm font-black items-center gap-2 cursor-pointer">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Catat Transaksi</span>
                </button>
            </div>
        </div>

        <!-- B. Asymmetric Summary Strip (High Contrast Postcase Postcard Aesthetic) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 mb-8 items-stretch">
            <!-- Focal Primary Metric (Crisp Rich Blue Card) -->
            <div class="lg:col-span-5 p-5 sm:p-6 rounded-3xl border-2 border-[#263238] bg-[#245AE0] text-white shadow-[4px_4px_0_#263238] flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-white/10 rounded-full pointer-events-none"></div>
                <div class="flex items-start justify-between gap-3 relative z-10">
                    <div>
                        <span class="text-xs font-bold text-blue-100 block">Perkiraan Sisa Uang</span>
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
                    </div>
                    <span class="px-2 py-0.5 bg-white/20 rounded-lg text-[10px] uppercase font-black tracking-wider">{{ $monthLabel }}</span>
                </div>
            </div>

            <!-- 3 Executive Numbers: Total Pemasukan, Total Pengeluaran, & Profit / Net Income (Compact 3-Col Grid on Mobile & Desktop) -->
            <div class="lg:col-span-7 grid grid-cols-3 gap-2 sm:gap-4">
                <div class="p-3 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#263238] bg-[#EDFAF3] shadow-[2px_2px_0_#263238] sm:shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-[11px] sm:text-xs font-bold text-[#114A2D] truncate">Pemasukan</span>
                        <div class="p-1 sm:p-1.5 bg-[#D8F5E5] border border-[#263238] rounded-lg sm:rounded-xl shrink-0">
                            <x-icon name="arrow-down-left" class="w-3 h-3 sm:w-4 sm:h-4 text-[#1C7C49]" />
                        </div>
                    </div>
                    <p class="text-xs sm:text-lg lg:text-xl font-black text-[#1C7C49] mt-1.5 sm:mt-2 truncate">
                        <span x-show="!hideBalance">+Rp {{ number_format($summary['income'], 0, ',', '.') }}</span>
                        <span x-show="hideBalance" x-cloak>+Rp ••••</span>
                    </p>
                    <span class="hidden sm:block text-xs font-medium text-[#526066] mt-2 truncate">Dana masuk ({{ $monthLabel }})</span>
                </div>

                <div class="p-3 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#263238] bg-[#FFF0ED] shadow-[2px_2px_0_#263238] sm:shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-[11px] sm:text-xs font-bold text-[#8C1D1D] truncate">Pengeluaran</span>
                        <div class="p-1 sm:p-1.5 bg-[#FFE2DE] border border-[#263238] rounded-lg sm:rounded-xl shrink-0">
                            <x-icon name="arrow-up-right" class="w-3 h-3 sm:w-4 sm:h-4 text-[#C93535]" />
                        </div>
                    </div>
                    <p class="text-xs sm:text-lg lg:text-xl font-black text-[#C93535] mt-1.5 sm:mt-2 truncate">
                        <span x-show="!hideBalance">-Rp {{ number_format($summary['total_expenses'], 0, ',', '.') }}</span>
                        <span x-show="hideBalance" x-cloak>-Rp ••••</span>
                    </p>
                    <span class="hidden sm:block text-xs font-medium text-[#526066] mt-2 truncate">Dana keluar ({{ $monthLabel }})</span>
                </div>

                @php
                    $profitIncome = $summary['profit_income'] ?? ($summary['income'] - $summary['total_expenses']);
                    $profitMargin = $summary['profit_margin_percent'] ?? ($summary['income'] > 0 ? round(($profitIncome / $summary['income']) * 100, 1) : 0);
                    $isProfitable = $profitIncome >= 0;
                @endphp
                <div class="p-3 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#263238] {{ $isProfitable ? 'bg-[#FFF9E6]' : 'bg-[#FFE2DE]' }} shadow-[2px_2px_0_#263238] sm:shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-[11px] sm:text-xs font-bold text-[#263238] truncate">Profit</span>
                        <span class="px-1.5 py-0.5 rounded-md sm:rounded-lg border border-[#263238] {{ $isProfitable ? 'bg-[#D8F5E5] text-[#114A2D]' : 'bg-white text-[#8C1D1D]' }} text-[8px] sm:text-[10px] font-black shrink-0">
                            {{ $isProfitable ? 'PROFIT' : 'DEFISIT' }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-lg lg:text-xl font-black {{ $isProfitable ? 'text-[#1C7C49]' : 'text-[#C93535]' }} mt-1.5 sm:mt-2 truncate">
                        <span x-show="!hideBalance">{{ $isProfitable ? '+' : '-' }}Rp {{ number_format(abs($profitIncome), 0, ',', '.') }}</span>
                        <span x-show="hideBalance" x-cloak>{{ $isProfitable ? '+' : '-' }}Rp ••••</span>
                    </p>
                    <span class="hidden sm:block text-xs font-bold text-[#526066] mt-2 truncate">
                        Rasio: <strong class="text-[#263238]">{{ $profitMargin }}%</strong>
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Access Feature Grid (Solves Mobile Discoverability: 6 Core Features 1-Tap Away) -->
        <div class="mb-8">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h2 class="text-xs sm:text-sm font-black text-[#526066] uppercase tracking-wider flex items-center gap-1.5">
                    <x-icon name="dashboard" class="w-4 h-4 text-[#263238]" />
                    <span>Fitur & Menu Utama</span>
                </h2>
                <span class="text-[10px] sm:text-xs font-bold text-[#8C9BA5]">Akses cepat</span>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 sm:gap-3">
                <!-- 1. Atur Pos Anggaran -->
                <a href="{{ route('budget.settings', ['month' => $selectedMonth]) }}"
                   @click="triggerFeedback('tap')"
                   class="p-2.5 sm:p-3.5 rounded-2xl border-2 border-[#263238] bg-[#EAE2FF] shadow-[2px_2px_0_#263238] hover:shadow-[3px_3px_0_#263238] active:translate-y-0.5 active:shadow-none transition flex flex-col items-center text-center group cursor-pointer">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white border-2 border-[#263238] flex items-center justify-center text-[#3A1D75] group-hover:scale-105 transition shadow-[1px_1px_0_#263238] mb-1.5">
                        <x-icon name="sliders" class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <span class="text-[11px] sm:text-xs font-black text-[#263238] leading-tight">Atur Pos</span>
                    <span class="text-[9px] sm:text-[10px] text-[#526066] font-bold">50 / 30 / 20</span>
                </a>

                <!-- 2. Tagihan Rutin -->
                <a href="{{ route('bills.index') }}"
                   @click="triggerFeedback('tap')"
                   class="p-2.5 sm:p-3.5 rounded-2xl border-2 border-[#263238] bg-[#FFE2DE] shadow-[2px_2px_0_#263238] hover:shadow-[3px_3px_0_#263238] active:translate-y-0.5 active:shadow-none transition flex flex-col items-center text-center group cursor-pointer">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white border-2 border-[#263238] flex items-center justify-center text-[#8C1D1D] group-hover:scale-105 transition shadow-[1px_1px_0_#263238] mb-1.5">
                        <x-icon name="bell" class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <span class="text-[11px] sm:text-xs font-black text-[#263238] leading-tight">Tagihan</span>
                    <span class="text-[9px] sm:text-[10px] text-[#526066] font-bold">Pengingat</span>
                </a>

                <!-- 3. Celengan Impian -->
                <a href="{{ route('goals.index') }}"
                   @click="triggerFeedback('tap')"
                   class="p-2.5 sm:p-3.5 rounded-2xl border-2 border-[#263238] bg-[#FFF9E6] shadow-[2px_2px_0_#263238] hover:shadow-[3px_3px_0_#263238] active:translate-y-0.5 active:shadow-none transition flex flex-col items-center text-center group cursor-pointer">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white border-2 border-[#263238] flex items-center justify-center text-[#634800] group-hover:scale-105 transition shadow-[1px_1px_0_#263238] mb-1.5">
                        <x-icon name="piggy-bank" class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <span class="text-[11px] sm:text-xs font-black text-[#263238] leading-tight">Celengan</span>
                    <span class="text-[9px] sm:text-[10px] text-[#526066] font-bold">Tabungan</span>
                </a>

                <!-- 4. Laporan & Grafik -->
                <a href="{{ route('reports.index') }}"
                   @click="triggerFeedback('tap')"
                   class="p-2.5 sm:p-3.5 rounded-2xl border-2 border-[#263238] bg-[#D8F5E5] shadow-[2px_2px_0_#263238] hover:shadow-[3px_3px_0_#263238] active:translate-y-0.5 active:shadow-none transition flex flex-col items-center text-center group cursor-pointer">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white border-2 border-[#263238] flex items-center justify-center text-[#114A2D] group-hover:scale-105 transition shadow-[1px_1px_0_#263238] mb-1.5">
                        <x-icon name="trending-up" class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <span class="text-[11px] sm:text-xs font-black text-[#263238] leading-tight">Laporan</span>
                    <span class="text-[9px] sm:text-[10px] text-[#526066] font-bold">Statistik</span>
                </a>

                <!-- 5. Slip Struk -->
                <button type="button" @click="triggerFeedback('tap'); $dispatch('open-receipt-modal')"
                   class="p-2.5 sm:p-3.5 rounded-2xl border-2 border-[#263238] bg-[#E0EAFF] shadow-[2px_2px_0_#263238] hover:shadow-[3px_3px_0_#263238] active:translate-y-0.5 active:shadow-none transition flex flex-col items-center text-center group cursor-pointer">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white border-2 border-[#263238] flex items-center justify-center text-[#163C94] group-hover:scale-105 transition shadow-[1px_1px_0_#263238] mb-1.5">
                        <x-icon name="receipt" class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <span class="text-[11px] sm:text-xs font-black text-[#263238] leading-tight">Slip Struk</span>
                    <span class="text-[9px] sm:text-[10px] text-[#526066] font-bold">Unduh POS</span>
                </button>

                <!-- 6. Google Sheets Sync -->
                <a href="{{ route('settings.google-sheets') }}"
                   @click="triggerFeedback('tap')"
                   class="p-2.5 sm:p-3.5 rounded-2xl border-2 border-[#263238] bg-[#EDFAF3] shadow-[2px_2px_0_#263238] hover:shadow-[3px_3px_0_#263238] active:translate-y-0.5 active:shadow-none transition flex flex-col items-center text-center group cursor-pointer">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white border-2 border-[#263238] flex items-center justify-center text-[#0F9D58] group-hover:scale-105 transition shadow-[1px_1px_0_#263238] mb-1.5">
                        <x-icon name="file-text" class="w-4 h-4 sm:w-5 sm:h-5 text-[#0F9D58]" />
                    </div>
                    <span class="text-[11px] sm:text-xs font-black text-[#263238] leading-tight">G-Sheets</span>
                    <span class="text-[9px] sm:text-[10px] text-[#526066] font-bold">Sinkronisasi</span>
                </a>
            </div>
        </div>

        <!-- C. Budget Allocation Bricks (Distinct Harmonious Pastels inspired by Reference 3) -->
        <div class="mb-8">
            <div class="mb-4">
                <h2 class="text-lg sm:text-xl font-black text-[#263238]">Pembagian 3 Pos Anggaran</h2>
                <p class="text-xs text-[#526066] font-medium">
                    Dari total dana
                    <span x-show="!hideBalance">Rp {{ number_format($summary['total_allocatable'], 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>Rp ••••••••</span>
                    di bulan {{ $monthLabel }}
                </p>
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

        <!-- Bridge Banner to Full Financial Reports & Analytics -->
        <div class="p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-[#FFFDF9] border-2 border-[#263238] shadow-[3px_3px_0_#263238] flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-[#D8F5E5] border-2 border-[#263238] shadow-[1.5px_1.5px_0_#263238] flex items-center justify-center text-xl shrink-0">
                    📈
                </div>
                <div>
                    <h3 class="font-black text-sm sm:text-base text-[#263238]">Ingin Analisis &amp; Grafik Lengkap?</h3>
                    <p class="text-xs text-[#526066] font-medium">Buka evaluasi tren per bulan, peringkat kategori pengeluaran, dan unduh CSV.</p>
                </div>
            </div>
            <a href="{{ route('reports.index', ['month' => $selectedMonth]) }}"
               class="brick-btn brick-btn-white px-4 py-2 text-xs font-black inline-flex items-center justify-center gap-2 self-start sm:self-auto shrink-0 shadow-[2px_2px_0_#263238]">
                <span>Buka Laporan</span>
                <x-icon name="arrow-right" class="w-3.5 h-3.5" />
            </a>
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
</x-app-layout>
