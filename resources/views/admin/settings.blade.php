<x-admin-layout title="Pengaturan Aplikasi">
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-black text-[#263238]">Pengaturan Aplikasi DuitMoo</h1>
            <p class="text-xs text-gray-600 font-medium mt-1">Konfigurasi operasional aplikasi yang berlaku secara global.</p>
        </div>

        <div class="brick-card p-6 bg-white space-y-5">
            <div class="p-3.5 rounded-xl bg-[#E9F0FF] border-2 border-[#263238] text-xs font-bold text-[#163C94] flex items-center justify-between">
                <span>Koneksi Database Aktif:</span>
                <span class="font-mono uppercase">{{ $dbDriver }}</span>
            </div>

            <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-4">
                @csrf

                <label class="flex items-center gap-3 p-4 rounded-xl bg-[#FCF6EB] border-2 border-[#263238]/20 cursor-pointer">
                    <input type="checkbox" name="registration_enabled" value="1" {{ $registrationEnabled ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-2 border-[#263238] text-[#245AE0]">
                    <div>
                        <span class="font-black text-sm text-[#263238] block">Izinkan Pendaftaran Pengguna Baru</span>
                        <span class="text-xs text-[#546E7A]">Jika dimatikan, halaman /register tidak dapat membuat akun baru.</span>
                    </div>
                </label>

                <div>
                    <label for="adm-ann" class="block text-xs font-bold text-[#37474F] mb-1">Pengumuman sistem (opsional)</label>
                    <textarea id="adm-ann" name="app_announcement" rows="2" placeholder="Contoh: Jadwal pemeliharaan rutin atau info versi baru..."
                              class="w-full p-3 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-medium text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">{{ old('app_announcement', $appAnnouncement) }}</textarea>
                </div>

                <div>
                    <label for="adm-email" class="block text-xs font-bold text-[#37474F] mb-1">Email dukungan / kontak admin</label>
                    <input id="adm-email" type="email" name="support_email" value="{{ old('support_email', $supportEmail) }}"
                           class="w-full min-h-[44px] px-4 py-2 rounded-xl border-2 border-[#263238]/20 bg-[#FCF6EB] font-medium text-sm focus:bg-white focus:border-[#245AE0] focus:outline-none">
                </div>

                <button type="submit" class="brick-btn brick-btn-blue px-6 py-2.5 text-xs font-black">
                    Simpan Pengaturan Aplikasi
                </button>
            </form>
        </div>
    </div>
</x-admin-layout>
