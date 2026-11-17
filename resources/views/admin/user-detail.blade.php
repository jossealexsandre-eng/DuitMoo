<x-admin-layout title="Detail Akun Pengguna">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-[#263238]">Detail Metadata Pengguna</h1>
                <p class="text-xs text-gray-600 font-medium">Informasi non-finansial untuk keperluan administrasi akun.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="brick-btn brick-btn-white px-4 py-2 text-xs font-bold">
                Kembali ke Daftar
            </a>
        </div>

        <div class="brick-card p-6 bg-white space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-4 rounded-xl bg-[#FCF6EB] border border-[#263238]/20">
                    <span class="font-bold text-[#546E7A] block">Nama pengguna</span>
                    <span class="text-base font-black text-[#263238] mt-1 block">{{ $targetUser->name }}</span>
                </div>
                <div class="p-4 rounded-xl bg-[#FCF6EB] border border-[#263238]/20">
                    <span class="font-bold text-[#546E7A] block">Email</span>
                    <span class="text-base font-bold text-[#263238] font-mono mt-1 block">{{ $targetUser->email }}</span>
                </div>
                <div class="p-4 rounded-xl bg-[#FCF6EB] border border-[#263238]/20">
                    <span class="font-bold text-[#546E7A] block">Peran &amp; status</span>
                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="brick-badge {{ $targetUser->isAdmin() ? 'brick-badge-danger' : 'brick-badge-blue' }}">{{ strtoupper($targetUser->role) }}</span>
                        <span class="brick-badge {{ $targetUser->is_active ? 'brick-badge-needs' : 'brick-badge-danger' }}">{{ $targetUser->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                </div>
                <div class="p-4 rounded-xl bg-[#FCF6EB] border border-[#263238]/20">
                    <span class="font-bold text-[#546E7A] block">Statistik non-sensitif</span>
                    <p class="text-xs font-bold text-[#263238] mt-1.5">
                        {{ $targetUser->monthly_budgets_count }} Periode Anggaran &middot; {{ $targetUser->transactions_count }} Baris Transaksi &middot; {{ $targetUser->categories_count }} Kategori
                    </p>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-[#F1EBFF] border-2 border-[#263238] text-xs font-bold text-[#3C1F7A]">
                Sesuai kebijakan privasi DuitMoo, nominal saldo, anggaran, dan detail catatan transaksi milik {{ $targetUser->name }} tidak ditampilkan di Portal Admin.
            </div>
        </div>

        <div class="brick-card p-6 bg-white">
            <h2 class="text-base font-black text-[#263238] mb-3">Log Aktivitas Akun Terbaru</h2>
            @if($recentLogs->count() > 0)
                <div class="space-y-2 text-xs">
                    @foreach($recentLogs as $log)
                        <div class="p-3 rounded-xl bg-[#FCF6EB] border border-[#263238]/15 flex items-center justify-between gap-2">
                            <span class="font-bold text-[#263238]">{{ $log->action }}</span>
                            <span class="text-[#546E7A]">{{ $log->created_at->format('d M Y H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500">Belum ada log aktivitas tercatat.</p>
            @endif
        </div>
    </div>
</x-admin-layout>
