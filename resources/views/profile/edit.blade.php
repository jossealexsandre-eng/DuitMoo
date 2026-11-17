<x-app-layout title="Profil, PWA & Pengaturan">
    <div class="max-w-4xl mx-auto space-y-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-[#263238]">Profil, Preferensi &amp; Aplikasi</h1>
            <p class="text-xs sm:text-sm text-gray-600 font-medium mt-1">
                Kelola profil akun, zona waktu, password, pemasangan PWA di HP, dan integrasi opsional.
            </p>
        </div>

        @if($errors->any())
            <div class="p-4 bg-[#FFE8E8] text-[#8F1D1D] rounded-2xl border-2 border-[#263238] shadow-[2px_2px_0_#263238] text-xs font-bold space-y-1">
                @foreach($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif

        <!-- 1. Profile & Preferences Card -->
        <div class="brick-card p-6 sm:p-8 bg-white">
            <h2 class="text-lg font-black text-[#263238] mb-1">Informasi Akun &amp; Preferensi</h2>
            <p class="text-xs text-[#546E7A] mb-5">Perbarui nama tampilan, email login, serta zona waktu tampilan tanggal.</p>

            <form action="{{ route('profile.update') }}" method="POST" class="space-y-4 max-w-lg">
                @csrf
                @method('PATCH')

                <div>
                    <label for="prof-name" class="block text-xs font-bold text-[#37474F] mb-1">Nama lengkap</label>
                    <input id="prof-name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full min-h-[44px] px-4 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                </div>

                <div>
                    <label for="prof-email" class="block text-xs font-bold text-[#37474F] mb-1">Alamat email</label>
                    <input id="prof-email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full min-h-[44px] px-4 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="prof-tz" class="block text-xs font-bold text-[#37474F] mb-1">Zona waktu</label>
                        <select id="prof-tz" name="timezone" class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                            <option value="Asia/Jakarta" {{ ($prefs->timezone ?? 'Asia/Jakarta') === 'Asia/Jakarta' ? 'selected' : '' }}>WIB (Asia/Jakarta)</option>
                            <option value="Asia/Makassar" {{ ($prefs->timezone ?? '') === 'Asia/Makassar' ? 'selected' : '' }}>WITA (Asia/Makassar)</option>
                            <option value="Asia/Jayapura" {{ ($prefs->timezone ?? '') === 'Asia/Jayapura' ? 'selected' : '' }}>WIT (Asia/Jayapura)</option>
                        </select>
                    </div>
                    <div>
                        <label for="prof-df" class="block text-xs font-bold text-[#37474F] mb-1">Format tanggal</label>
                        <select id="prof-df" name="date_format" class="w-full min-h-[44px] px-3 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-bold text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                            <option value="d M Y" {{ ($prefs->date_format ?? 'd M Y') === 'd M Y' ? 'selected' : '' }}>16 Nov 2026</option>
                            <option value="d/m/Y" {{ ($prefs->date_format ?? '') === 'd/m/Y' ? 'selected' : '' }}>16/11/2026</option>
                            <option value="Y-m-d" {{ ($prefs->date_format ?? '') === 'Y-m-d' ? 'selected' : '' }}>2026-11-16</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="brick-btn brick-btn-blue px-6 py-2.5 text-xs font-black">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- 3. Google Sheets Optional Sync Card -->
        <div class="brick-card p-6 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-xl bg-[#E8F8F0] border-2 border-[#263238] flex items-center justify-center text-[#124D30] shrink-0">
                    <x-icon name="table" class="w-5 h-5" />
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-black text-[#263238]">Integrasi Google Sheets (Opsional)</h2>
                        <span class="brick-badge {{ $hasGoogleConnection ? 'brick-badge-needs' : 'brick-badge-wants' }} text-[10px]">
                            {{ $hasGoogleConnection ? 'Terhubung' : 'Belum Terhubung' }}
                        </span>
                    </div>
                    <p class="text-xs text-[#546E7A] font-medium mt-1">
                        Hubungkan spreadsheet pribadimu untuk mencadangkan baris transaksi baru secara otomatis.
                    </p>
                </div>
            </div>
            <a href="{{ route('settings.google-sheets') }}" class="brick-btn brick-btn-white px-4 py-2 text-xs font-black shrink-0">
                Kelola Integrasi
            </a>
        </div>

        <!-- 4. Update Password Card -->
        <div class="brick-card p-6 sm:p-8 bg-white">
            <h2 class="text-lg font-black text-[#263238] mb-1">Ubah Password</h2>
            <p class="text-xs text-[#546E7A] mb-5">Gunakan kata sandi minimal 8 karakter untuk menjaga keamanan akun.</p>

            <form action="{{ route('profile.password') }}" method="POST" class="space-y-4 max-w-lg">
                @csrf
                @method('PUT')

                <div>
                    <label for="cur-pw" class="block text-xs font-bold text-[#37474F] mb-1">Password saat ini</label>
                    <input id="cur-pw" type="password" name="current_password" required
                           class="w-full min-h-[44px] px-4 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-medium text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                </div>

                <div>
                    <label for="new-pw" class="block text-xs font-bold text-[#37474F] mb-1">Password baru (Min. 8 karakter)</label>
                    <input id="new-pw" type="password" name="password" required
                           class="w-full min-h-[44px] px-4 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-medium text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                </div>

                <div>
                    <label for="conf-pw" class="block text-xs font-bold text-[#37474F] mb-1">Konfirmasi password baru</label>
                    <input id="conf-pw" type="password" name="password_confirmation" required
                           class="w-full min-h-[44px] px-4 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-medium text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                </div>

                <div class="pt-2">
                    <button type="submit" class="brick-btn brick-btn-yellow px-6 py-2.5 text-xs font-black">
                        Perbarui Password
                    </button>
                </div>
            </form>
        </div>

        <!-- 5. Danger Zone -->
        <div class="brick-card p-6 sm:p-8 bg-[#FFF5F5] border-2 border-[#C93B3B]" x-data="{ confirmDelete: false }">
            <h2 class="text-lg font-black text-[#8F1D1D] mb-1">Zona Bahaya: Hapus Akun</h2>
            <p class="text-xs text-gray-700 mb-5 leading-relaxed">
                Seluruh data anggaran, transaksi, dan preferensi pribadimu akan <strong>dihapus permanen</strong> dari database.
            </p>

            <button type="button" x-show="!confirmDelete" @click="confirmDelete = true"
                    class="brick-btn brick-btn-red px-5 py-2.5 text-xs font-black">
                Hapus Akun Saya
            </button>

            <div x-show="confirmDelete" x-cloak class="p-4 bg-white rounded-xl border-2 border-[#C93B3B] max-w-lg space-y-4" style="display: none;">
                <p class="text-xs font-bold text-[#8F1D1D]">
                    Masukkan password akunmu untuk mengonfirmasi penghapusan permanen:
                </p>

                <form action="{{ route('profile.destroy') }}" method="POST" class="space-y-3">
                    @csrf
                    @method('DELETE')
                    <input type="password" name="password" required placeholder="Password konfirmasi"
                           class="w-full min-h-[44px] px-4 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-medium text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">

                    <div class="flex items-center gap-2">
                        <button type="button" @click="confirmDelete = false" class="brick-btn brick-btn-white px-4 py-2 text-xs font-bold">Batal</button>
                        <button type="submit" class="brick-btn brick-btn-red px-5 py-2 text-xs font-black">Ya, Hapus Permanen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
