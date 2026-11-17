<x-app-layout title="Integrasi Google Sheets">
    <div x-data="{
        copiedTsv: false,
        tsvData: @js($tsvClipboard ?? ''),
        async copyTableForSheets() {
            try {
                await navigator.clipboard.writeText(this.tsvData);
                this.copiedTsv = true;
                if (window.duitmooFeedback) window.duitmooFeedback('coin');
                setTimeout(() => { this.copiedTsv = false; }, 3500);
            } catch (_) {}
        }
    }" class="max-w-5xl mx-auto space-y-6">

        <!-- Header & Live Auto-Sync Status -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="px-2.5 py-0.5 rounded-lg bg-[#D8F5E5] text-[#114A2D] border border-[#263238] text-[10px] font-black uppercase">
                        Auto-Sync Real-Time
                    </span>
                    @if($connection && $connection->last_synced_at)
                        <span class="text-xs font-bold text-[#526066]">
                            Sinkron terakhir: {{ $connection->last_synced_at->diffForHumans() }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#263238]">Laporan &amp; Sinkronisasi Google Sheets</h1>
                <p class="text-xs sm:text-sm text-[#526066] font-medium mt-0.5">
                    Setiap perubahan transaksi (tambah, edit, hapus) di aplikasi otomatis menghitung ulang Total Pemasukan, Pengeluaran, dan Profit Income di Sheet.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" @click="triggerFeedback('tap'); $dispatch('open-quick-add')"
                        class="brick-btn brick-btn-yellow px-4 py-2.5 text-xs font-black flex items-center gap-1.5 cursor-pointer">
                    <x-icon name="plus" class="w-4 h-4" />
                    <span>Catat Transaksi</span>
                </button>

                @if($connection && $connection->spreadsheet_id)
                    <a href="https://docs.google.com/spreadsheets/d/{{ $connection->spreadsheet_id }}/edit"
                       target="_blank" rel="noopener noreferrer"
                       class="brick-btn brick-btn-white px-4 py-2.5 text-xs font-black inline-flex items-center gap-1.5">
                        <x-icon name="table" class="w-4 h-4 text-[#1C7C49]" />
                        <span>Buka Google Sheets ↗</span>
                    </a>
                @endif
            </div>
        </div>

        @if($errors->any())
            <div class="p-4 bg-[#FFE2DE] text-[#8C1D1D] rounded-2xl border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-xs font-bold space-y-1">
                @foreach($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif

        <!-- Clean 1-Click Activation Guide if Google Cloud Sheets API is not yet enabled -->
        @if(!empty($apiEnableUrl))
            <div class="p-5 sm:p-6 rounded-3xl border-2 border-[#263238] bg-[#FFF9E6] shadow-[4px_4px_0_#263238]">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <span class="px-2.5 py-0.5 rounded-lg bg-[#FFD54F] border border-[#263238] text-[10px] font-black uppercase">
                            Langkah Terakhir Diperlukan (1 Kali Saja)
                        </span>
                        <h2 class="text-base sm:text-lg font-black text-[#263238]">
                            Aktifkan Izin Layanan Google Sheets API di Google Cloud
                        </h2>
                        <p class="text-xs text-[#526066] font-medium leading-relaxed max-w-2xl">
                            Akun Google Anda <strong>sudah berhasil terhubung</strong>, namun fitur <em>Google Sheets API</em> pada project Google Cloud Anda (<code class="font-bold">739925186794</code>) masih berstatus <em>Disabled</em>. Klik tombol biru di samping, tekan tombol <strong>"Enable"</strong> di halaman Google tersebut, lalu klik <strong>"Sinkronkan Sekarang"</strong>.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <a href="{{ $apiEnableUrl }}" target="_blank" rel="noopener noreferrer"
                           class="brick-btn brick-btn-blue px-4 py-2.5 text-xs font-black inline-flex items-center gap-1.5">
                            <span>1. Klik Enable di Google Cloud ↗</span>
                        </a>

                        <form action="{{ route('settings.google-sheets.sync-now') }}" method="POST">
                            @csrf
                            <button type="submit" class="brick-btn brick-btn-green px-4 py-2.5 text-xs font-black flex items-center gap-1.5 cursor-pointer">
                                <x-icon name="check" class="w-4 h-4" />
                                <span>2. Sinkronkan Sekarang</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- A. Executive Financial Summary Strip (Total Pemasukan, Total Pengeluaran, Profit / Net Income) -->
        @php
            $isProfit = ($profitIncome ?? 0) >= 0;
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total Pemasukan -->
            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-[#EDFAF3] shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#114A2D]">Total Pemasukan (Income)</span>
                    <div class="p-1.5 bg-[#D8F5E5] border border-[#263238] rounded-xl">
                        <x-icon name="arrow-down-left" class="w-4 h-4 text-[#1C7C49]" />
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-[#1C7C49] mt-2 tabular-nums">
                    <span x-show="!hideBalance">+Rp {{ number_format($totalIncome ?? 0, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>+Rp ••••••••</span>
                </p>
                <span class="text-[11px] font-bold text-[#526066] mt-1">Rumus Sheet: <code class="font-mono">=SUMIF(Pemasukan)</code></span>
            </div>

            <!-- 2. Total Pengeluaran -->
            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-[#FFF0ED] shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#8C1D1D]">Total Pengeluaran (Expense)</span>
                    <div class="p-1.5 bg-[#FFE2DE] border border-[#263238] rounded-xl">
                        <x-icon name="arrow-up-right" class="w-4 h-4 text-[#C93535]" />
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-[#C93535] mt-2 tabular-nums">
                    <span x-show="!hideBalance">-Rp {{ number_format($totalExpense ?? 0, 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>-Rp ••••••••</span>
                </p>
                <span class="text-[11px] font-bold text-[#526066] mt-1">Rumus Sheet: <code class="font-mono">=SUMIF(Pengeluaran)</code></span>
            </div>

            <!-- 3. Profit / Net Income -->
            <div class="p-5 rounded-3xl border-2 border-[#263238] {{ $isProfit ? 'bg-[#245AE0] text-white' : 'bg-[#FFE2DE] text-[#8C1D1D]' }} shadow-[3px_3px_0_#263238] flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold {{ $isProfit ? 'text-blue-100' : 'text-[#8C1D1D]' }}">Profit / Net Income</span>
                    <span class="px-2 py-0.5 rounded-lg border border-[#263238] {{ $isProfit ? 'bg-[#FFD54F] text-[#263238]' : 'bg-white text-[#8C1D1D]' }} text-[10px] font-black uppercase">
                        {{ $isProfit ? 'SURPLUS / PROFIT' : 'DEFISIT' }}
                    </span>
                </div>
                <p class="text-xl sm:text-2xl font-black {{ $isProfit ? 'text-white' : 'text-[#C93535]' }} mt-2 tabular-nums">
                    <span x-show="!hideBalance">{{ $isProfit ? '+' : '-' }}Rp {{ number_format(abs($profitIncome ?? 0), 0, ',', '.') }}</span>
                    <span x-show="hideBalance" x-cloak>{{ $isProfit ? '+' : '-' }}Rp ••••••••</span>
                </p>
                <span class="text-[11px] font-bold {{ $isProfit ? 'text-blue-100' : 'text-[#8C1D1D]' }} mt-1">
                    Rasio Profit: <strong>{{ $profitMarginPercent ?? 0 }}%</strong> (<code class="font-mono">=B4-B5</code>)
                </span>
            </div>

            <!-- 4. Breakdown 3 Pos Anggaran -->
            <div class="p-5 rounded-3xl border-2 border-[#263238] bg-white shadow-[3px_3px_0_#263238] flex flex-col justify-between text-xs">
                <span class="font-black text-[#263238] block mb-1.5">Rincian Pengeluaran Pos</span>
                <div class="space-y-1 font-bold">
                    <div class="flex justify-between">
                        <span class="text-[#114A2D]">Kebutuhan:</span>
                        <span class="text-[#263238]" x-show="!hideBalance">Rp {{ number_format($needsSpent ?? 0, 0, ',', '.') }}</span>
                        <span class="text-[#263238]" x-show="hideBalance" x-cloak>Rp ••••••</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#634800]">Keinginan:</span>
                        <span class="text-[#263238]" x-show="!hideBalance">Rp {{ number_format($wantsSpent ?? 0, 0, ',', '.') }}</span>
                        <span class="text-[#263238]" x-show="hideBalance" x-cloak>Rp ••••••</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#3A1D75]">Dana Darurat:</span>
                        <span class="text-[#263238]" x-show="!hideBalance">Rp {{ number_format($emergencySpent ?? 0, 0, ',', '.') }}</span>
                        <span class="text-[#263238]" x-show="hideBalance" x-cloak>Rp ••••••</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- B. Live Spreadsheet Preview & Sync Bar (Mirrors exact Google Sheet Structure) -->
        <div class="rounded-3xl border-2 border-[#263238] bg-white shadow-[4px_4px_0_#263238] overflow-hidden">
            <!-- Top Toolbar like Google Sheets -->
            <div class="bg-[#FCF6EB] border-b-2 border-[#263238] px-5 py-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-[#D8F5E5] border-2 border-[#263238] shadow-[2px_2px_0_#263238] flex items-center justify-center text-[#114A2D]">
                        <x-icon name="table" class="w-5 h-5" />
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-black text-[#263238]">
                                {{ $connection?->spreadsheet_name ?? 'Laporan Eksekutif Spreadsheet' }}
                            </h2>
                            @if($connection && !empty($connection->access_token) && empty($apiEnableUrl))
                                <span class="px-2 py-0.5 rounded-md bg-[#D8F5E5] text-[#114A2D] border border-[#263238] text-[10px] font-black">
                                    Auto-Sync Aktif ✓
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-[#526066] font-medium">
                            Tabel di bawah ini otomatis dikirim &amp; dihitung di Google Sheets setiap ada perubahan transaksi.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form action="{{ route('settings.google-sheets.sync-now') }}" method="POST">
                        @csrf
                        <button type="submit" class="brick-btn brick-btn-green px-4 py-2 text-xs font-black flex items-center gap-1.5 cursor-pointer">
                            <x-icon name="check" class="w-4 h-4" />
                            <span>{{ ($connection && !empty($connection->access_token)) ? 'Update ke Google Sheets Sekarang' : 'Hubungkan OAuth & Auto-Sync' }}</span>
                        </button>
                    </form>

                    <button type="button" @click="copyTableForSheets()"
                            class="brick-btn brick-btn-yellow px-3.5 py-2 text-xs font-black flex items-center gap-1.5 cursor-pointer">
                        <x-icon name="table" class="w-4 h-4" />
                        <span x-text="copiedTsv ? 'Tersalin! Tekan Ctrl+V di A1 Sheet' : 'Salin Laporan & Rumus'"></span>
                    </button>
                </div>
            </div>

            <!-- Connection Settings Form -->
            <div class="p-5 sm:p-6 bg-[#FFFDF9] space-y-5">
                <form action="{{ route('settings.google-sheets.save') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="gs-url" class="block text-xs font-bold text-[#263238] mb-1">Spreadsheet ID atau URL Google Sheets *</label>
                        <input id="gs-url" type="text" name="spreadsheet_input"
                               value="{{ old('spreadsheet_input', $connection?->spreadsheet_id) }}" required
                               placeholder="https://docs.google.com/spreadsheets/d/1ABC.../edit"
                               class="w-full min-h-[44px] px-4 py-2 rounded-xl border-2 border-[#263238] bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="gs-name" class="block text-xs font-bold text-[#263238] mb-1">Nama Laporan Sheet</label>
                            <input id="gs-name" type="text" name="spreadsheet_name"
                                   value="{{ old('spreadsheet_name', $connection?->spreadsheet_name ?? 'Keuangan Pribadi') }}"
                                   class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#263238]/40 bg-[#FCF6EB] font-bold text-xs focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label for="gs-inc-tab" class="block text-xs font-bold text-[#263238] mb-1">Nama Tab Pemasukan *</label>
                            <input id="gs-inc-tab" type="text" name="income_sheet_name" required
                                   value="{{ old('income_sheet_name', $connection?->income_sheet_name ?? 'Pemasukan') }}"
                                   class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#263238]/40 bg-[#FCF6EB] font-bold text-xs focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label for="gs-exp-tab" class="block text-xs font-bold text-[#263238] mb-1">Nama Tab Pengeluaran *</label>
                            <input id="gs-exp-tab" type="text" name="expense_sheet_name" required
                                   value="{{ old('expense_sheet_name', $connection?->expense_sheet_name ?? 'Pengeluaran') }}"
                                   class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#263238]/40 bg-[#FCF6EB] font-bold text-xs focus:bg-white focus:outline-none">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <button type="submit" class="brick-btn brick-btn-blue px-5 py-2.5 text-xs font-black cursor-pointer">
                                Simpan &amp; Perbarui Konfigurasi
                            </button>

                            <a href="{{ route('settings.google-sheets.connect') }}"
                               class="brick-btn brick-btn-green px-4 py-2.5 text-xs font-black">
                                {{ ($connection && !empty($connection->access_token)) ? 'Otorisasi Ulang Akun Google' : 'Hubungkan Akun Google (OAuth)' }}
                            </a>
                        </div>

                        @if($connection && !empty($connection->access_token))
                            <button type="button"
                                    onclick="document.getElementById('disconnect-gs-form').submit()"
                                    class="text-xs font-black text-[#C93535] hover:underline cursor-pointer">
                                Putuskan Koneksi OAuth
                            </button>
                        @endif
                    </div>
                </form>

                @if($connection && !empty($connection->access_token))
                    <form id="disconnect-gs-form" action="{{ route('settings.google-sheets.disconnect') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
