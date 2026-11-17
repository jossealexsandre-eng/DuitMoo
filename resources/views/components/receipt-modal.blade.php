@props(['summary', 'monthLabel', 'selectedMonth', 'savingsGoals' => collect()])

@php
    $slipCode = 'MOO-' . strtoupper(substr(md5((auth()->id() ?? 1) . $selectedMonth), 0, 6));
    $hasOverspent = collect($summary['buckets'])->contains(fn($b) => $b['is_overspent']);
    $isHealthy = !$hasOverspent && $summary['estimated_remaining_cash'] >= 0;
    $totalCelengan = (int) $savingsGoals->sum('current_amount');
@endphp

<div
    x-data="{
        isOpen: false,
        copied: false,
        downloading: false,
        receiptData: {
            userName: @js(auth()->user()->name),
            monthLabel: @js($monthLabel),
            slipCode: @js($slipCode),
            printedAt: @js(now()->translatedFormat('d M Y, H:i')),
            openingBalance: @js((int) $summary['opening_balance']),
            income: @js((int) $summary['income']),
            totalAllocatable: @js((int) $summary['total_allocatable']),
            totalExpenses: @js((int) $summary['total_expenses']),
            remainingCash: @js((int) $summary['estimated_remaining_cash']),
            totalCelengan: @js($totalCelengan),
            isHealthy: @js($isHealthy),
            buckets: @js(array_values($summary['buckets'])),
        },
        openReceipt() {
            this.isOpen = true;
            this.copied = false;
            if (window.duitmooFeedback) window.duitmooFeedback('tap');
        },
        formatRp(val) {
            const prefix = val < 0 ? '-Rp ' : 'Rp ';
            return prefix + Math.abs(val).toLocaleString('id-ID');
        },
        async shareOrCopyText() {
            const d = this.receiptData;
            const lines = [
                '🐮 SLIP ANGGARAN DUITMOO (' + d.monthLabel + ')',
                'No: #' + d.slipCode + ' | ' + d.userName,
                '--------------------------------',
                'Saldo Awal: ' + this.formatRp(d.openingBalance),
                'Pemasukan: +' + this.formatRp(d.income),
                'Pengeluaran: -' + this.formatRp(d.totalExpenses),
                '--------------------------------',
                ...d.buckets.map(b => '• ' + b.label + ' (' + b.percent + '%): Terpakai ' + this.formatRp(b.spent) + ' dari ' + this.formatRp(b.allocated) + ' (' + b.progress + '%)'),
                '--------------------------------',
                'SISA UANG: ' + this.formatRp(d.remainingCash),
                'Status: ' + (d.isHealthy ? 'ANGGARAN SEHAT & TERKENDALI' : 'PERLU EVALUASI BELANJA'),
            ].join('\n');

            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Slip Anggaran DuitMoo - ' + d.monthLabel,
                        text: lines,
                    });
                    return;
                } catch (_) {}
            }

            try {
                await navigator.clipboard.writeText(lines);
                this.copied = true;
                if (window.duitmooFeedback) window.duitmooFeedback('coin');
                setTimeout(() => { this.copied = false; }, 2500);
            } catch (_) {}
        },
        downloadPngReceipt() {
            this.downloading = true;
            if (window.duitmooFeedback) window.duitmooFeedback('coin');

            const d = this.receiptData;
            const canvas = document.createElement('canvas');
            const w = 760;
            const h = 1060;
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');

            // Outer background
            ctx.fillStyle = '#FCF6EB';
            ctx.fillRect(0, 0, w, h);

            // Paper slip container with hard shadow
            const pad = 44;
            const slipW = w - pad * 2;
            const slipH = h - pad * 2;

            ctx.fillStyle = '#263238';
            ctx.fillRect(pad + 8, pad + 8, slipW, slipH);

            ctx.fillStyle = '#FFFDF9';
            ctx.fillRect(pad, pad, slipW, slipH);
            ctx.lineWidth = 4;
            ctx.strokeStyle = '#263238';
            ctx.strokeRect(pad, pad, slipW, slipH);

            // Top decorative yellow header band
            ctx.fillStyle = '#FFD54F';
            ctx.fillRect(pad + 2, pad + 2, slipW - 4, 118);
            ctx.beginPath();
            ctx.moveTo(pad, pad + 120);
            ctx.lineTo(pad + slipW, pad + 120);
            ctx.lineWidth = 3;
            ctx.strokeStyle = '#263238';
            ctx.stroke();

            // Header Text
            ctx.fillStyle = '#263238';
            ctx.textAlign = 'center';
            ctx.font = '900 28px Poppins, sans-serif';
            ctx.fillText('🐮 SLIP ANGGARAN DUITMOO', w / 2, pad + 56);
            ctx.font = '700 16px Poppins, sans-serif';
            ctx.fillText('Periode: ' + d.monthLabel + '  |  #' + d.slipCode, w / 2, pad + 92);

            let y = pad + 165;
            const leftX = pad + 38;
            const rightX = pad + slipW - 38;

            const drawRow = (label, val, bold = false, valColor = '#263238') => {
                ctx.textAlign = 'left';
                ctx.font = (bold ? '800 ' : '600 ') + '18px Poppins, sans-serif';
                ctx.fillStyle = '#526066';
                ctx.fillText(label, leftX, y);

                ctx.textAlign = 'right';
                ctx.font = (bold ? '900 ' : '800 ') + '19px Poppins, sans-serif';
                ctx.fillStyle = valColor;
                ctx.fillText(val, rightX, y);
                y += 38;
            };

            const drawDashedLine = () => {
                ctx.save();
                ctx.setLineDash([8, 6]);
                ctx.beginPath();
                ctx.moveTo(leftX, y - 14);
                ctx.lineTo(rightX, y - 14);
                ctx.lineWidth = 2;
                ctx.strokeStyle = '#B0BEC5';
                ctx.stroke();
                ctx.restore();
                y += 18;
            };

            drawRow('Nama Akun', d.userName, true);
            drawRow('Tanggal Cetak', d.printedAt, false);
            drawDashedLine();

            drawRow('Saldo Awal', this.formatRp(d.openingBalance));
            drawRow('Total Pemasukan', '+' + this.formatRp(d.income), false, '#1C7C49');
            drawRow('Total Dana Tersedia', this.formatRp(d.totalAllocatable), true, '#245AE0');
            drawRow('Total Pengeluaran', '-' + this.formatRp(d.totalExpenses), false, '#C93535');
            drawDashedLine();

            ctx.textAlign = 'left';
            ctx.font = '900 18px Poppins, sans-serif';
            ctx.fillStyle = '#263238';
            ctx.fillText('RINCIAN 3 POS ANGGARAN', leftX, y);
            y += 34;

            d.buckets.forEach((b) => {
                ctx.textAlign = 'left';
                ctx.font = '800 17px Poppins, sans-serif';
                ctx.fillStyle = '#263238';
                ctx.fillText(b.label + ' (' + b.percent + '%)', leftX, y);

                ctx.textAlign = 'right';
                ctx.font = '800 17px Poppins, sans-serif';
                ctx.fillStyle = b.is_overspent ? '#C93535' : '#1C7C49';
                ctx.fillText(this.formatRp(b.spent) + ' / ' + this.formatRp(b.allocated) + ' (' + b.progress + '%)', rightX, y);
                y += 36;
            });

            if (d.totalCelengan > 0) {
                drawRow('Tabungan Celengan Impian', this.formatRp(d.totalCelengan), false, '#3A1D75');
            }

            drawDashedLine();

            // Net Remaining Highlight Box
            ctx.fillStyle = '#245AE0';
            ctx.fillRect(leftX, y - 6, rightX - leftX, 82);
            ctx.strokeStyle = '#263238';
            ctx.lineWidth = 3;
            ctx.strokeRect(leftX, y - 6, rightX - leftX, 82);

            ctx.textAlign = 'left';
            ctx.fillStyle = '#E0EAFF';
            ctx.font = '700 15px Poppins, sans-serif';
            ctx.fillText('PERKIRAAN SISA UANG', leftX + 20, y + 26);

            ctx.fillStyle = '#FFFFFF';
            ctx.font = '900 24px Poppins, sans-serif';
            ctx.fillText(this.formatRp(d.remainingCash), leftX + 20, y + 58);

            // Postcase Stamp on Right
            ctx.save();
            ctx.translate(rightX - 115, y + 36);
            ctx.rotate(-0.08);
            ctx.fillStyle = d.isHealthy ? '#D8F5E5' : '#FFE2DE';
            ctx.fillRect(-95, -22, 190, 44);
            ctx.strokeStyle = d.isHealthy ? '#114A2D' : '#8C1D1D';
            ctx.lineWidth = 2.5;
            ctx.strokeRect(-95, -22, 190, 44);
            ctx.fillStyle = d.isHealthy ? '#114A2D' : '#8C1D1D';
            ctx.textAlign = 'center';
            ctx.font = '900 13px Poppins, sans-serif';
            ctx.fillText(d.isHealthy ? '★ ANGGARAN SEHAT ★' : '⚡ PERLU REM BELANJA', 0, 5);
            ctx.restore();

            // Footer
            ctx.textAlign = 'center';
            ctx.fillStyle = '#526066';
            ctx.font = '700 14px Poppins, sans-serif';
            ctx.fillText('Dicetak otomatis dari aplikasi DuitMoo 2.0', w / 2, h - pad - 24);

            const link = document.createElement('a');
            link.download = 'Struk-DuitMoo-' + @js($selectedMonth) + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();

            setTimeout(() => { this.downloading = false; }, 400);
        }
    }"
    @open-receipt-modal.window="openReceipt()"
    @keydown.escape.window="isOpen = false"
>
    <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="receipt-modal-title" style="display: none;">
        <div class="fixed inset-0 bg-[#263238]/65 backdrop-blur-xs transition-opacity" @click="isOpen = false"></div>

        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-[#FFFDF9] border-2 border-[#263238] rounded-3xl shadow-[6px_6px_0_#263238] overflow-hidden relative z-10"
                 @click.stop>

                <!-- Receipt Yellow Stamp Top Header -->
                <div class="bg-[#FFD54F] border-b-2 border-[#263238] px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-white border-2 border-[#263238] shadow-[2px_2px_0_#263238] flex items-center justify-center text-base">
                            🧾
                        </span>
                        <div>
                            <h2 id="receipt-modal-title" class="text-base font-black text-[#263238] leading-tight">Slip Struk Anggaran</h2>
                            <p class="text-[11px] font-bold text-[#634800]">#{{ $slipCode }} · {{ $monthLabel }}</p>
                        </div>
                    </div>
                    <button type="button" @click="isOpen = false" aria-label="Tutup struk"
                            class="w-9 h-9 rounded-xl bg-white/80 hover:bg-white border-2 border-[#263238] flex items-center justify-center text-[#263238] cursor-pointer">
                        <x-icon name="x" class="w-4 h-4" />
                    </button>
                </div>

                <!-- Thermal Paper Content Body -->
                <div class="p-5 sm:p-6 space-y-4 text-xs">
                    <div class="flex items-center justify-between text-[#526066] font-bold border-b-2 border-dashed border-[#263238]/25 pb-3">
                        <div>
                            <span class="block text-[10px] uppercase tracking-wider">Pemilik Anggaran</span>
                            <span class="text-sm font-black text-[#263238]">{{ auth()->user()->name }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[10px] uppercase tracking-wider">Tanggal Cetak</span>
                            <span class="text-xs font-black text-[#263238]">{{ now()->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>

                    <!-- Cash Flow Rows -->
                    <div class="space-y-2 border-b-2 border-dashed border-[#263238]/25 pb-3.5">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[#526066]">Saldo Awal</span>
                            <span class="font-black text-[#263238]">Rp {{ number_format($summary['opening_balance'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[#526066]">Total Pemasukan</span>
                            <span class="font-black text-[#1C7C49]">+Rp {{ number_format($summary['income'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[#526066]">Total Dana Tersedia</span>
                            <span class="font-black text-[#245AE0]">Rp {{ number_format($summary['total_allocatable'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[#526066]">Total Pengeluaran</span>
                            <span class="font-black text-[#C93535]">-Rp {{ number_format($summary['total_expenses'], 0, ',', '.') }}</span>
                        </div>
                        @php $slipProfit = ($summary['income'] ?? 0) - ($summary['total_expenses'] ?? 0); @endphp
                        <div class="flex justify-between items-center pt-1 border-t border-dashed border-[#263238]/15">
                            <span class="font-black text-[#263238]">Profit / Net Income</span>
                            <span class="font-black {{ $slipProfit >= 0 ? 'text-[#1C7C49]' : 'text-[#C93535]' }}">
                                {{ $slipProfit >= 0 ? '+' : '-' }}Rp {{ number_format(abs($slipProfit), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- 3 Pos Breakdown -->
                    <div class="space-y-2 border-b-2 border-dashed border-[#263238]/25 pb-3.5">
                        <p class="text-[11px] font-black uppercase tracking-wider text-[#263238]">Alokasi 3 Pos Anggaran</p>
                        @foreach($summary['buckets'] as $bucket)
                            <div class="flex justify-between items-center bg-[#FCF6EB] px-3 py-2 rounded-xl border border-[#263238]/20">
                                <div>
                                    <span class="font-black text-[#263238] block">{{ $bucket['label'] }} ({{ $bucket['percent'] }}%)</span>
                                    <span class="text-[10px] font-bold text-[#526066]">Alokasi: Rp {{ number_format($bucket['allocated'], 0, ',', '.') }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-black {{ $bucket['is_overspent'] ? 'text-[#C93535]' : 'text-[#114A2D]' }} block">
                                        Rp {{ number_format($bucket['spent'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] font-black {{ $bucket['is_overspent'] ? 'text-[#C93535]' : 'text-[#526066]' }}">
                                        {{ $bucket['progress'] }}% terpakai
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Net Remaining + Postcase Stamp -->
                    <div class="p-4 rounded-2xl bg-[#245AE0] text-white border-2 border-[#263238] shadow-[3px_3px_0_#263238] flex items-center justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-100 block">Perkiraan Sisa Uang</span>
                            <span class="text-xl font-black text-white">
                                Rp {{ number_format($summary['estimated_remaining_cash'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="-rotate-3 px-2.5 py-1 rounded-xl border-2 border-[#263238] {{ $isHealthy ? 'bg-[#D8F5E5] text-[#114A2D]' : 'bg-[#FFE2DE] text-[#8C1D1D]' }} font-black text-[10px] uppercase tracking-wider shadow-[2px_2px_0_#263238] shrink-0">
                            {{ $isHealthy ? '★ Anggaran Sehat' : '⚡ Perlu Rem' }}
                        </div>
                    </div>

                    <!-- Actions: Download PNG & Share/Copy -->
                    <div class="pt-2 grid grid-cols-2 gap-2.5">
                        <button type="button" @click="shareOrCopyText()"
                                class="brick-btn brick-btn-white py-2.5 px-3 text-xs font-black flex items-center justify-center gap-1.5 cursor-pointer">
                            <x-icon name="share" class="w-4 h-4" />
                            <span x-text="copied ? 'Teks Tersalin!' : 'Salin / Bagikan'"></span>
                        </button>

                        <button type="button" @click="downloadPngReceipt()" :disabled="downloading"
                                class="brick-btn brick-btn-yellow py-2.5 px-3 text-xs font-black flex items-center justify-center gap-1.5 cursor-pointer">
                            <x-icon name="download" class="w-4 h-4" />
                            <span x-text="downloading ? 'Mencetak...' : 'Unduh Gambar'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
