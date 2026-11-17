<div class="brick-card-pop p-6 bg-white"
     x-data="{
        canInstall: !!window.duitmooInstallPrompt,
        installedMsg: false,
        async installApp() {
            if (!window.duitmooInstallPrompt) return;
            window.duitmooInstallPrompt.prompt();
            const choice = await window.duitmooInstallPrompt.userChoice;
            if (choice && choice.outcome === 'accepted') {
                this.installedMsg = true;
            }
            window.duitmooInstallPrompt = null;
            this.canInstall = false;
        }
     }"
     x-init="window.addEventListener('duitmoo-pwa-installable', () => canInstall = true)">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-[#E0EAFF] border-2 border-[#263238] flex items-center justify-center text-[#163C94]">
                <x-icon name="smartphone" class="w-5 h-5" />
            </span>
            <div>
                <h2 class="text-lg font-black text-[#263238]">Pasang DuitMoo ke Layar Utama (PWA)</h2>
                <p class="text-xs text-gray-600 font-medium">Akses cepat seperti aplikasi mobile langsung dari HP atau desktop tanpa membuka tab baru.</p>
            </div>
        </div>

        <button type="button" x-show="canInstall" x-cloak @click="installApp()"
                class="brick-btn brick-btn-blue px-4 py-2 text-xs font-black shrink-0" style="display: none;">
            Pasang Sekarang
        </button>
    </div>

    <div x-show="installedMsg" x-cloak class="mb-4 p-3 rounded-xl bg-[#DDF7EA] border-2 border-[#263238] text-[#124D30] text-xs font-bold" style="display: none;">
        DuitMoo telah ditambahkan ke layar utama perangkatmu!
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
        <div class="p-4 rounded-2xl bg-[#FCF6EB] border border-[#263238]/20">
            <h3 class="font-black text-[#263238] mb-1.5">1. Android (Google Chrome)</h3>
            <ol class="list-decimal list-inside space-y-1 text-[#37474F] font-medium leading-relaxed">
                <li>Buka DuitMoo lewat Chrome.</li>
                <li>Ketuk ikon menu titik tiga (pojok kanan atas).</li>
                <li>Pilih <strong>Tambahkan ke Layar Utama</strong> / <strong>Install App</strong>.</li>
            </ol>
        </div>

        <div class="p-4 rounded-2xl bg-[#FCF6EB] border border-[#263238]/20">
            <h3 class="font-black text-[#263238] mb-1.5">2. iPhone / iPad (Safari)</h3>
            <ol class="list-decimal list-inside space-y-1 text-[#37474F] font-medium leading-relaxed">
                <li>Buka DuitMoo melalui Safari.</li>
                <li>Ketuk tombol <strong>Bagikan (Share)</strong> di bar bawah.</li>
                <li>Gulir lalu pilih <strong>Add to Home Screen</strong>.</li>
            </ol>
        </div>

        <div class="p-4 rounded-2xl bg-[#FCF6EB] border border-[#263238]/20">
            <h3 class="font-black text-[#263238] mb-1.5">3. Desktop (Chrome / Edge)</h3>
            <ol class="list-decimal list-inside space-y-1 text-[#37474F] font-medium leading-relaxed">
                <li>Lihat ikon monitor/install di sisi kanan address bar browser.</li>
                <li>Klik <strong>Install DuitMoo</strong> untuk mode jendela mandiri.</li>
            </ol>
        </div>
    </div>
</div>
