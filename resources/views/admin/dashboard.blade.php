<x-admin-layout title="Admin Dashboard">
    <div class="space-y-8" x-data="{ toggleFormId: null }">

        <!-- Privacy Guarantee Banner -->
        <div class="p-4 bg-white rounded-2xl brick-card-pop flex items-start gap-3">
            <span class="w-10 h-10 rounded-xl bg-[#F1EBFF] border-2 border-[#263238] flex items-center justify-center text-[#3C1F7A] shrink-0">
                <x-icon name="lock" class="w-5 h-5" />
            </span>
            <div class="text-xs">
                <h2 class="font-black text-[#263238]">Prinsip Keamanan &amp; Privasi Data Pengguna</h2>
                <p class="text-[#37474F] mt-0.5 leading-relaxed font-medium">
                    Portal Admin hanya menampilkan <strong>metrik operasional agregat</strong> dan pengelolaan status akun. Rincian nominal saldo, anggaran, dan transaksi pribadi masing-masing pengguna diisolasi secara ketat.
                    <span class="inline-block ml-1 brick-badge brick-badge-blue text-[10px]">Driver DB: {{ strtoupper($dbDriver) }}</span>
                </p>
            </div>
        </div>

        <!-- Operational Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-5">
            <div class="brick-card p-5 bg-white">
                <span class="text-xs font-bold text-[#546E7A]">Total akun</span>
                <p class="text-2xl sm:text-3xl font-black text-[#263238] mt-1 tabular-nums">{{ $totalUsers }}</p>
                <span class="text-[11px] font-semibold text-[#546E7A] mt-1 block">Termasuk {{ $adminCount }} Admin</span>
            </div>

            <div class="brick-card p-5 bg-white">
                <span class="text-xs font-bold text-[#124D30]">Pengguna aktif</span>
                <p class="text-2xl sm:text-3xl font-black text-[#1E824C] mt-1 tabular-nums">{{ $activeUsers }}</p>
                <span class="text-[11px] font-semibold text-[#546E7A] mt-1 block">Dapat login normal</span>
            </div>

            <div class="brick-card p-5 bg-white">
                <span class="text-xs font-bold text-[#8F1D1D]">Dinonaktifkan</span>
                <p class="text-2xl sm:text-3xl font-black text-[#C93B3B] mt-1 tabular-nums">{{ $inactiveUsers }}</p>
                <span class="text-[11px] font-semibold text-[#546E7A] mt-1 block">Akses dibatasi</span>
            </div>

            <div class="brick-card p-5 bg-white">
                <span class="text-xs font-bold text-[#3C1F7A]">Akun baru (30 hr)</span>
                <p class="text-2xl sm:text-3xl font-black text-[#6B47B8] mt-1 tabular-nums">{{ $newUsersLast30Days }}</p>
                <span class="text-[11px] font-semibold text-[#546E7A] mt-1 block">Registrasi terbaru</span>
            </div>

            <div class="brick-card p-5 bg-white col-span-2 lg:col-span-1">
                <span class="text-xs font-bold text-[#163C94]">Volume transaksi</span>
                <p class="text-2xl sm:text-3xl font-black text-[#245AE0] mt-1 tabular-nums">{{ $totalTransactionsCount }}</p>
                <span class="text-[11px] font-semibold text-[#546E7A] mt-1 block">{{ $totalBudgets }} anggaran aktif</span>
            </div>
        </div>

        <!-- User Management Table -->
        <div class="brick-card p-6 bg-white">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-black text-[#263238]">Daftar Pengguna Aplikasi</h2>
                    <p class="text-xs text-[#546E7A] font-medium mt-0.5">Lihat metadata akun dan kelola status aktif pengguna.</p>
                </div>

                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <label for="adm-search" class="sr-only">Cari Pengguna</label>
                    <input id="adm-search" type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau email..."
                           class="min-h-[42px] px-3.5 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-medium text-xs focus:bg-white focus:border-[#245AE0] focus:outline-none w-48 sm:w-56">
                    <button type="submit" class="brick-btn brick-btn-blue px-4 py-2 text-xs font-bold">Cari</button>
                    @if(!empty($search))
                        <a href="{{ route('admin.dashboard') }}" class="brick-btn brick-btn-white px-3 py-2 text-xs font-bold">Reset</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b-2 border-[#263238] font-bold text-[#546E7A]">
                            <th class="py-3 px-3">Nama Pengguna</th>
                            <th class="py-3 px-3">Email</th>
                            <th class="py-3 px-3">Peran</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Bergabung</th>
                            <th class="py-3 px-3 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-semibold">
                        @foreach($users as $u)
                            <tr class="hover:bg-[#FCF6EB] transition">
                                <td class="py-3.5 px-3 font-bold text-[#263238]">
                                    <a href="{{ route('admin.user.show', $u) }}" class="hover:text-[#245AE0] hover:underline">
                                        {{ $u->name }}
                                    </a>
                                    @if($u->id === auth()->id())
                                        <span class="ml-1 text-[10px] text-[#546E7A] font-normal">(Anda)</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3 text-[#37474F] font-mono">{{ $u->email }}</td>
                                <td class="py-3.5 px-3">
                                    <span class="brick-badge {{ $u->isAdmin() ? 'brick-badge-danger' : 'brick-badge-blue' }} text-[10px]">
                                        {{ strtoupper($u->role) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="brick-badge {{ $u->is_active ? 'brick-badge-needs' : 'brick-badge-danger' }} text-[10px]">
                                        {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-[#546E7A]">{{ $u->created_at->translatedFormat('d M Y') }}</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.user.show', $u) }}" class="px-2.5 py-1.5 bg-[#FCF6EB] border border-[#263238]/30 rounded-lg text-[11px] font-bold hover:bg-[#FFD54F]">
                                            Detail
                                        </a>
                                        @if($u->id !== auth()->id())
                                            <form id="toggle-user-{{ $u->id }}" action="{{ route('admin.user.toggle', $u) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button" @click="toggleFormId = 'toggle-user-{{ $u->id }}'"
                                                        class="px-2.5 py-1.5 rounded-lg border border-[#263238] text-[11px] font-bold text-white cursor-pointer {{ $u->is_active ? 'bg-[#D93838] hover:bg-[#B82A2A]' : 'bg-[#1E824C] hover:bg-[#16653B]' }}">
                                                    {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100">
                {{ $users->links() }}
            </div>
        </div>

        <!-- Toggle Confirmation Dialog -->
        <div x-show="toggleFormId !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="fixed inset-0 bg-[#263238]/60" @click="toggleFormId = null"></div>
            <div class="brick-card max-w-sm w-full p-6 bg-white relative z-10 text-center">
                <h3 class="text-lg font-black text-[#263238] mb-1">Ubah Status Akun Pengguna?</h3>
                <p class="text-xs text-gray-600 font-medium mb-5">Pengguna yang dinonaktifkan tidak akan bisa login hingga diaktifkan kembali.</p>
                <div class="flex items-center gap-3">
                    <button type="button" @click="toggleFormId = null" class="brick-btn brick-btn-white flex-1 py-2 text-xs font-bold">Batal</button>
                    <button type="button" @click="document.getElementById(toggleFormId).submit()" class="brick-btn brick-btn-blue flex-1 py-2 text-xs font-black">Ya, Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
