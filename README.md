# DuitMoo — Your Money, Your Rules!

Aplikasi web **Personal Finance & Smart Budget Planner** berbahasa Indonesia dengan desain orisinal **Toy-Brick Gameboard**, dibangun dengan Laravel, Livewire, Tailwind CSS, Alpine.js, Chart.js, **Supabase PostgreSQL**, dan dukungan **Progressive Web App (PWA)**.

> **Disclaimer:** DuitMoo adalah aplikasi pencatatan dan perencanaan anggaran pribadi berbasis input manual pengguna. DuitMoo **bukan** bank, e-wallet, atau layanan investasi, dan tidak menyimpan maupun memindahkan dana nyata.

---

## Fitur Utama (DuitMoo 2.0)

- **Smart Budgeting Engine (60/30/10 & Custom Ratio):** Pembagian otomatis saldo awal + pemasukan ke pos **Kebutuhan**, **Keinginan**, dan **Dana Darurat** menggunakan perhitungan integer Rupiah (`BIGINT`) dan pembulatan deterministik (`floor` + sisa exact ke Dana Darurat). Snapshot rasio terkunci per bulan agar perubahan baru tidak merusak riwayat bulan lampau.
- **Quick Transaction (< 10 Detik di Mobile):** Tombol tambah cepat melayang (`+`) & modal slide-up mobile dengan keyboard angka (`inputmode="numeric"`), chip nominal cepat (`+10rb`, `+25rb`, `+50rb`, `+100rb`), filter kategori otomatis sesuai pos, serta peringatan non-blokir jika pengeluaran melewati batas.
- **Dashboard & Laporan Analitik:** 4 grafik Chart.js (Donut distribusi pos, Bar Pemasukan vs Pengeluaran 6 bulan, Line tren sisa saldo bulanan, Bar pengeluaran kategori teratas), filter rentang hari ini / minggu ini / bulan ini / bulan lalu / tahun ini / custom, perbandingan antarbulan aman terhadap pembagian nol, serta ekspor CSV.
- **Autentikasi, Profil & Portal Admin:** Registrasi, login, lupa/reset password, edit profil & zona waktu (`Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura`), kebijakan isolasi anti-IDOR ketat, serta Portal Admin yang hanya menampilkan metrik agregat non-finansial dan pengaturan aplikasi.
- **Progressive Web App (PWA):** Dilengkapi `manifest.webmanifest`, ikon orisinal, `sw.js` (online-first aman — **tidak pernah** menyimpan transaksi pribadi di cache publik), serta pendeteksi status offline.
- **Integrasi Google Sheets (Opsional):** Konfigurasi Spreadsheet pribadi + token OAuth terenkripsi dan log sinkronisasi idempoten.

---

## Panduan Instalasi & Koneksi Supabase PostgreSQL

### 1. Prasyarat
- PHP `^8.3` dengan ekstensi **`pdo_pgsql`**, **`pgsql`**, `openssl`, `mbstring`, `gd`.
- Composer & Node.js + npm.
- Akun & Project **Supabase** (https://supabase.com).

### 2. Mengaktifkan Ekstensi PostgreSQL di PHP (Windows / Laragon)
Jika `php -m` belum menampilkan `pdo_pgsql` dan `pgsql`:
1. Buka file `php.ini` (contoh di Laragon: `C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.ini`).
2. Cari baris berikut dan hapus tanda titik koma (`;`) di depannya:
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```
3. Simpan file lalu verifikasi dengan menjalankan:
   ```bash
   php -m | findstr pgsql
   ```

### 3. Setup Project & Koneksi Supabase
1. Login ke [Supabase Dashboard](https://supabase.com/dashboard) dan buat **New Project**.
2. Buka menu **Connect** di bagian atas dashboard project Anda.
3. Pilih **Transaction / Session Pooler** (disarankan **Session Pooler** port `5432` untuk lingkungan IPv4 lokal di Windows) atau **Direct Connection**.
4. Salin `.env.example` menjadi `.env` (jika belum ada) dan isi bagian database sesuai info dari menu Connect:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=<HOST_DARI_MENU_CONNECT_SUPABASE>
   DB_PORT=5432
   DB_DATABASE=postgres
   DB_USERNAME=<USERNAME_DARI_MENU_CONNECT_SUPABASE>
   DB_PASSWORD=<DATABASE_PASSWORD_ANDA>
   DB_SSLMODE=require
   ```
   > **Keamanan:** Jangan pernah membagikan password database atau memasukkan file `.env` ke Git.

### 4. Instalasi Dependency & Migrasi Database
```bash
composer install
npm install
npm run build
php artisan key:generate
php artisan migrate
```
*(Opsional untuk mengisi akun demo `demo@duitmoo.test` / `password123`)*:
```bash
php artisan db:seed
```

### 5. Membuat Akun Administrator
Akun admin **tidak** dibuat melalui halaman `/register`. Gunakan perintah CLI aman berikut:
```bash
php artisan duitmoo:create-admin
```
Ikuti petunjuk untuk mengisi nama, email, dan password minimal 8 karakter.

---

## Menjalankan Aplikasi di Localhost & Akses dari HP

### A. Menjalankan di Laptop/PC
1. Jalankan server Laravel:
   ```bash
   php artisan serve
   ```
2. Jika sedang mengembangkan tampilan CSS/JS, jalankan di terminal terpisah:
   ```bash
   npm run dev
   ```
   *(Atau cukup jalankan `npm run build` sekali jika tidak mengedit aset frontend).*
3. Buka **`http://localhost:8000`** di browser (**bukan** `localhost:5173` karena port 5173 hanya server asset Vite).

### B. Mengakses dari Smartphone (Jaringan WiFi Sama)
1. Cek IP lokal komputer Windows dengan perintah:
   ```powershell
   ipconfig
   ```
   Catat alamat **IPv4 Address** (contoh: `192.168.1.15`).
2. Jalankan Laravel agar menerima koneksi jaringan lokal:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
3. Pastikan Windows Firewall mengizinkan port `8000`, lalu buka `http://192.168.1.15:8000` di browser HP.

### C. Memasang PWA ke Home Screen
* **Android (Chrome):** Buka menu titik tiga di pojok kanan atas -> **Tambahkan ke Layar Utama / Install App**.
* **iPhone/iPad (Safari):** Ketuk tombol **Share** -> **Add to Home Screen**.
* **Desktop (Chrome/Edge):** Klik ikon install di sisi kanan address bar.
*(Catatan: Beberapa browser mewajibkan koneksi HTTPS untuk mengaktifkan prompt instalasi otomatis di server publik/production).*

---

## Integrasi Google Sheets (Opsional)
1. Buat OAuth Client ID di Google Cloud Console dengan Authorized Redirect URI:
   `http://localhost:8000/settings/google-sheets/callback`
2. Isi kredensial di `.env`:
   ```env
   GOOGLE_CLIENT_ID=
   GOOGLE_CLIENT_SECRET=
   GOOGLE_REDIRECT_URI="${APP_URL}/settings/google-sheets/callback"
   ```
3. Masuk ke menu **Google Sheets** di aplikasi, tempel URL/ID spreadsheet yang sudah ada (dengan tab `Pemasukan` & `Pengeluaran`), lalu hubungkan akun Google Anda.

---

## Menjalankan Pengujian Otomatis
```bash
php artisan test
```

## Batasan yang Diketahui (Known Limitations)
- **Ekstensi PostgreSQL Lokal:** Di Windows/Laragon, ekstensi `pdo_pgsql` dan `pgsql` harus diaktifkan di `php.ini` sebelum Laravel dapat berkomunikasi dengan Supabase PostgreSQL.
- **PWA Online-First:** Pencatatan dan pembaruan transaksi membutuhkan koneksi internet aktif agar konsistensi saldo dan keamanan cache pada perangkat bersama tetap terjaga.
- **Google Sheets API:** Sinkronisasi otomatis memerlukan kredensial Google OAuth (`GOOGLE_CLIENT_ID` & `GOOGLE_CLIENT_SECRET`) serta izin akses spreadsheet dari pengguna.
