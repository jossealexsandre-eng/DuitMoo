<x-app-layout title="Laporan & Analisis">
    <div>
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/mascot/moowie-chart.png') }}" alt="Moowie" class="w-14 h-14 sm:w-16 sm:h-16 shrink-0 drop-shadow-[0_4px_8px_rgba(0,0,0,0.1)]">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-[#263238]">Laporan &amp; Analisis Keuangan</h1>
                    <p class="text-xs sm:text-sm text-gray-600 font-medium mt-1">
                        Evaluasi tren harian, perbandingan antarbulan, pengeluaran terbesar, dan ekspor CSV.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <select name="year" onchange="this.form.submit()" aria-label="Pilih Tahun"
                            class="min-h-[42px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-white font-bold text-xs sm:text-sm shadow-[0_2px_0_#263238]">
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ $selectedYear === $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                        @endforeach
                    </select>

                    <select name="month" onchange="this.form.submit()" aria-label="Pilih Bulan"
                            class="min-h-[42px] px-3 py-2 rounded-xl border-2 border-[#263238] bg-white font-bold text-xs sm:text-sm shadow-[0_2px_0_#263238]">
                        <option value="all" {{ $selectedMonth === 'all' ? 'selected' : '' }}>Semua Bulan</option>
                        @for($i = 1; $i <= 12; $i++)
                            @php $mCode = sprintf('%02d', $i); @endphp
                            <option value="{{ $mCode }}" {{ $selectedMonth === $mCode ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(2026, $i, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </form>

                <a href="{{ route('reports.export', ['year' => $selectedYear]) }}"
                   class="brick-btn brick-btn-white px-4 py-2 text-xs font-black inline-flex items-center gap-1.5">
                    <x-icon name="download" class="w-4 h-4" />
                    <span>Unduh CSV</span>
                </a>
            </div>
        </div>

        <!-- Period Quick Filter Bar -->
        <div class="brick-card p-4 bg-white mb-6 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-[#546E7A] mr-1">Filter periode:</span>
                @foreach(['today' => 'Hari Ini', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini', 'last_month' => 'Bulan Sebelumnya', 'year' => 'Tahun Ini', 'custom' => 'Custom Range'] as $pKey => $pLbl)
                    <a href="{{ route('reports.index', ['period' => $pKey, 'year' => $selectedYear, 'month' => $pKey === 'year' ? 'all' : $selectedMonth]) }}"
                       class="min-h-[40px] px-3.5 py-1.5 rounded-xl border-2 border-[#263238] text-xs font-bold inline-flex items-center transition {{ $period === $pKey ? 'bg-[#FFD54F] text-[#263238] shadow-[2px_2px_0_#263238]' : 'bg-[#FCF6EB] text-[#37474F] hover:bg-white' }}">
                        {{ $pLbl }}
                    </a>
                @endforeach
            </div>

            @if($period === 'custom')
                <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3 pt-2 border-t border-dashed border-[#263238]/15">
                    <input type="hidden" name="period" value="custom">
                    <div>
                        <label for="r-from" class="block text-xs font-bold text-[#37474F] mb-1">Dari tanggal</label>
                        <input id="r-from" type="date" name="date_from" value="{{ $dateFrom }}" class="min-h-[42px] px-3 py-1.5 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] text-xs font-bold focus:bg-white focus:border-[#245AE0] focus:outline-none">
                    </div>
                    <div>
                        <label for="r-to" class="block text-xs font-bold text-[#37474F] mb-1">Sampai tanggal</label>
                        <input id="r-to" type="date" name="date_to" value="{{ $dateTo }}" class="min-h-[42px] px-3 py-1.5 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] text-xs font-bold focus:bg-white focus:border-[#245AE0] focus:outline-none">
                    </div>
                    <button type="submit" class="brick-btn brick-btn-blue px-4 py-2 text-xs font-black">Terapkan</button>
                </form>
            @endif
        </div>

        <!-- Period KPI Cards (Total Pemasukan, Total Pengeluaran, Profit / Net Income, Rata-rata Harian, Pengeluaran Terbesar) -->
        @php
            $periodProfit = $periodIncome - $periodExpense;
            $periodProfitPct = $periodIncome > 0 ? round(($periodProfit / $periodIncome) * 100, 1) : 0;
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="brick-card p-5 bg-[#EDFAF3]">
                <span class="text-xs font-bold text-[#124D30]">Total Pemasukan ({{ $periodTitle }})</span>
                <p class="text-xl font-black text-[#1E824C] mt-1 tabular-nums">+Rp {{ number_format($periodIncome, 0, ',', '.') }}</p>
                <p class="text-[11px] text-[#546E7A] mt-1 font-medium tabular-nums">Total tahunan: Rp {{ number_format($totalYearIncome, 0, ',', '.') }}</p>
            </div>

            <div class="brick-card p-5 bg-[#FFF0ED]">
                <span class="text-xs font-bold text-[#8F1D1D]">Total Pengeluaran ({{ $periodTitle }})</span>
                <p class="text-xl font-black text-[#C93B3B] mt-1 tabular-nums">-Rp {{ number_format($periodExpense, 0, ',', '.') }}</p>
                <p class="text-[11px] text-[#546E7A] mt-1 font-semibold">
                    @if($expenseChangePercent === null)
                        Belum ada pembanding periode sebelumnya (Rp 0)
                    @elseif($expenseChangePercent > 0)
                        Naik {{ $expenseChangePercent }}% vs periode sebelumnya
                    @elseif($expenseChangePercent < 0)
                        Turun {{ abs($expenseChangePercent) }}% vs periode sebelumnya
                    @else
                        Sama dengan periode sebelumnya
                    @endif
                </p>
            </div>

            <div class="brick-card-pop p-5 {{ $periodProfit >= 0 ? 'bg-[#FFF9E6]' : 'bg-[#FFE2DE]' }}">
                <div class="flex items-center justify-between gap-1">
                    <span class="text-xs font-bold text-[#263238]">Profit / Net Income</span>
                    <span class="px-1.5 py-0.5 rounded border border-[#263238] {{ $periodProfit >= 0 ? 'bg-[#D8F5E5] text-[#114A2D]' : 'bg-white text-[#8C1D1D]' }} text-[10px] font-black">
                        {{ $periodProfit >= 0 ? 'PROFIT' : 'DEFISIT' }}
                    </span>
                </div>
                <p class="text-xl font-black {{ $periodProfit >= 0 ? 'text-[#1E824C]' : 'text-[#C93B3B]' }} mt-1 tabular-nums">
                    {{ $periodProfit >= 0 ? '+' : '-' }}Rp {{ number_format(abs($periodProfit), 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-[#37474F] mt-1 font-bold">
                    Rasio Profit: {{ $periodProfitPct }}% ({{ $periodTitle }})
                </p>
            </div>

            <div class="brick-card p-5 bg-white">
                <span class="text-xs font-bold text-[#546E7A]">Rata-rata harian</span>
                <p class="text-xl font-black text-[#263238] mt-1 tabular-nums">Rp {{ number_format($dailyAverageExpense, 0, ',', '.') }}</p>
                <p class="text-[11px] text-[#546E7A] mt-1 font-medium">Per hari pada rentang {{ $periodTitle }}</p>
            </div>

            <div class="brick-card p-5 bg-white">
                <span class="text-xs font-bold text-[#263238]">Pengeluaran terbesar</span>
                @if($largestExpenseTx)
                    <p class="text-xl font-black text-[#263238] mt-1 tabular-nums">Rp {{ number_format($largestExpenseTx->amount, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-[#37474F] mt-1 font-bold truncate">
                        {{ $largestExpenseTx->description ?: ($largestExpenseTx->category?->name ?? 'Transaksi') }}
                    </p>
                @else
                    <p class="text-xl font-black text-[#546E7A] mt-1">Rp 0</p>
                    <p class="text-[11px] text-[#546E7A] mt-1 font-medium">Belum ada pengeluaran tercatat</p>
                @endif
            </div>
        </div>

        <!-- Daily Expense Trend (shown when selected span <= 35 days) -->
        @if(count($dailyLabels) > 0)
            <div class="brick-card p-6 bg-white mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-black text-[#263238]">Bagaimana ritme belanja harianmu ({{ $periodTitle }})?</h2>
                </div>
                <div class="relative h-64">
                    <canvas id="dailyTrendChart"></canvas>
                </div>
            </div>
        @endif

        <!-- Annual Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="brick-card p-6 bg-white">
                <h3 class="text-base font-black text-[#263238] mb-4">Bagaimana tren kas masuk vs keluar sepanjang {{ $selectedYear }}?</h3>
                <div class="relative h-72 flex items-center justify-center">
                    <canvas id="yearlyTrendChart"></canvas>
                </div>
            </div>

            <div class="brick-card p-6 bg-white">
                <h3 class="text-base font-black text-[#263238] mb-4">Pos mana yang paling banyak menyerap dana tiap bulan?</h3>
                <div class="relative h-72 flex items-center justify-center">
                    <canvas id="stackedBucketChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Category Leaderboard & Pie Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="brick-card p-6 bg-white lg:col-span-1">
                <h3 class="text-base font-black text-[#263238] mb-4">Uang paling banyak habis di mana ({{ $periodTitle }})?</h3>

                @if(count($catChartData) > 0)
                    <div class="relative h-64 flex items-center justify-center">
                        <canvas id="categoryPieChart"></canvas>
                    </div>
                @else
                    <div class="h-64 flex flex-col items-center justify-center text-center p-4 border-2 border-dashed border-[#263238]/15 rounded-2xl">
                        <p class="text-xs font-bold text-[#546E7A]">Tidak ada pengeluaran pada periode ini.</p>
                    </div>
                @endif
            </div>

            <div class="brick-card p-6 bg-white lg:col-span-2">
                <h3 class="text-base font-black text-[#263238] mb-4">Kategori Pengeluaran Terbesar</h3>

                @if($topCategories->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b-2 border-[#263238] font-bold text-[#546E7A]">
                                    <th class="py-2.5 px-3">Kategori</th>
                                    <th class="py-2.5 px-3">Pos</th>
                                    <th class="py-2.5 px-3 text-center">Frekuensi</th>
                                    <th class="py-2.5 px-3 text-right">Total Belanja</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-semibold">
                                @foreach($topCategories as $idx => $tc)
                                    <tr class="hover:bg-[#FCF6EB] transition">
                                        <td class="py-3 px-3 font-bold text-[#263238] flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-full bg-[#FCF6EB] border border-[#263238]/30 text-[10px] font-black flex items-center justify-center">
                                                {{ $idx + 1 }}
                                            </span>
                                            <span>{{ $tc->category ? $tc->category->name : 'Lainnya' }}</span>
                                        </td>
                                        <td class="py-3 px-3">
                                            @php
                                                $bBadge = match($tc->bucket) {
                                                    'needs' => 'brick-badge-needs',
                                                    'wants' => 'brick-badge-wants',
                                                    'emergency' => 'brick-badge-emergency',
                                                    default => 'bg-gray-200 text-gray-700',
                                                };
                                            @endphp
                                            <span class="brick-badge {{ $bBadge }} text-[10px]">
                                                {{ ucfirst($tc->bucket ?? '-') }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-center text-gray-600 font-bold">{{ $tc->total_count }}x</td>
                                        <td class="py-3 px-3 text-right font-black text-[#C93B3B]">
                                            Rp {{ number_format($tc->total_amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center text-gray-500">
                        <p class="font-bold text-xs">Belum ada catatan pengeluaran pada periode ini.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Monthly Summaries Table -->
        <div class="brick-card p-6 bg-white">
            <h3 class="text-base font-black text-[#263238] mb-4">Rekapitulasi Keuangan per Bulan (Tahun {{ $selectedYear }})</h3>

            @if(count($monthlySummaries) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b-2 border-[#263238] font-black uppercase text-gray-600">
                                <th class="py-3 px-3">Bulan</th>
                                <th class="py-3 px-3 text-right">Saldo Awal</th>
                                <th class="py-3 px-3 text-right">Pemasukan</th>
                                <th class="py-3 px-3 text-right">Kebutuhan</th>
                                <th class="py-3 px-3 text-right">Keinginan</th>
                                <th class="py-3 px-3 text-right">Darurat</th>
                                <th class="py-3 px-3 text-right">Total Belanja</th>
                                <th class="py-3 px-3 text-right">Selisih Kas</th>
                                <th class="py-3 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-semibold">
                            @foreach($monthlySummaries as $ms)
                                <tr class="hover:bg-[#FCF6EB] transition">
                                    <td class="py-3 px-3 font-bold text-[#263238] whitespace-nowrap">{{ $ms['label'] }}</td>
                                    <td class="py-3 px-3 text-right text-gray-700 whitespace-nowrap">Rp {{ number_format($ms['opening'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right text-[#1E824C] font-bold whitespace-nowrap">+Rp {{ number_format($ms['income'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right text-gray-700 whitespace-nowrap">Rp {{ number_format($ms['needs'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right text-gray-700 whitespace-nowrap">Rp {{ number_format($ms['wants'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right text-gray-700 whitespace-nowrap">Rp {{ number_format($ms['emergency'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right text-[#C93B3B] font-bold whitespace-nowrap">-Rp {{ number_format($ms['expense'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right font-black whitespace-nowrap {{ $ms['net'] >= 0 ? 'text-[#1E824C]' : 'text-[#C93B3B]' }}">
                                        {{ $ms['net'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($ms['net']), 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <a href="{{ route('dashboard', ['month' => $ms['month_str']]) }}"
                                           class="px-3 py-1 bg-[#FFD54F] border border-[#263238] rounded-lg font-bold text-[11px] hover:bg-[#F5B800] transition">
                                            Buka
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-10 text-center text-gray-500">
                    <p class="font-bold text-xs">Belum ada data anggaran atau transaksi untuk tahun {{ $selectedYear }}.</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.Chart) return;

            @if(count($dailyLabels) > 0)
            const dailyCtx = document.getElementById('dailyTrendChart');
            if (dailyCtx) {
                new window.Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: @json($dailyLabels),
                        datasets: [{
                            label: 'Pengeluaran Harian',
                            data: @json($dailyExpenses),
                            borderColor: '#C93B3B',
                            backgroundColor: 'rgba(255, 107, 107, 0.16)',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#FFD54F',
                            pointBorderColor: '#263238',
                            fill: true,
                            tension: 0.25
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => ' Rp ' + (ctx.parsed.y || 0).toLocaleString('id-ID') } }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, callback: (v) => v >= 1000000 ? (v/1000000)+' Jt' : (v >= 1000 ? (v/1000)+' Rb' : v) } },
                            x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } } }
                        }
                    }
                });
            }
            @endif

            const yearlyCtx = document.getElementById('yearlyTrendChart');
            if (yearlyCtx) {
                new window.Chart(yearlyCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($monthLabels),
                        datasets: [
                            { label: 'Pemasukan', data: @json($monthlyIncomes), backgroundColor: '#45C486', borderColor: '#263238', borderWidth: 2, borderRadius: 6 },
                            { label: 'Pengeluaran', data: @json($monthlyExpenses), backgroundColor: '#FF6B6B', borderColor: '#263238', borderWidth: 2, borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', weight: 'bold' } } } },
                        scales: { y: { beginAtZero: true, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, callback: (v) => (v/1000000) + ' Jt' } } }
                    }
                });
            }

            const stackedCtx = document.getElementById('stackedBucketChart');
            if (stackedCtx) {
                new window.Chart(stackedCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($monthLabels),
                        datasets: [
                            { label: 'Kebutuhan', data: @json($monthlyNeeds), backgroundColor: '#45C486', borderColor: '#263238', borderWidth: 1.5 },
                            { label: 'Keinginan', data: @json($monthlyWants), backgroundColor: '#FFD54F', borderColor: '#263238', borderWidth: 1.5 },
                            { label: 'Dana Darurat', data: @json($monthlyEmergency), backgroundColor: '#9B7EDE', borderColor: '#263238', borderWidth: 1.5 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', weight: 'bold' } } } },
                        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, callback: (v) => (v/1000000) + ' Jt' } } }
                    }
                });
            }

            @if(count($catChartData) > 0)
            const catCtx = document.getElementById('categoryPieChart');
            if (catCtx) {
                new window.Chart(catCtx, {
                    type: 'pie',
                    data: {
                        labels: @json($catChartLabels),
                        datasets: [{ data: @json($catChartData), backgroundColor: @json($catChartColors), borderColor: '#263238', borderWidth: 2 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 10 } } } }
                    }
                });
            }
            @endif
        });
    </script>
</x-app-layout>
