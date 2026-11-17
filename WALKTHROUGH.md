# 🐮 DuitMoo — Walkthrough & Laporan Verifikasi Pengujian

Dokumen ini mendokumentasikan hasil implementasi nyata dan pengujian fungsional aplikasi **DuitMoo** di lingkungan localhost.

---

## 🎯 1. Ringkasan Implementasi

Aplikasi **DuitMoo** telah selesai dibangun dari nol (*from scratch*) dengan arsitektur penuh:
1. **Landing Page Publik (`/`):**
   - Menampilkan visual Toy-Brick Gameboard dengan palet warna #FFD54F, #407BFF, #45C486, #FF6B6B, #9B7EDE, #FFF9EB.
   - Dilengkapi **Live Interactive Budget Simulator**: pengunjung dapat langsung mengetik nominal (misal Rp 5.000.000) dan menguji alokasi 60/30/10 atau 50/30/20 di browser secara langsung sebelum mendaftar.
   - Disclaimer finansial transparan bahwa aplikasi adalah alat pencatatan manual, bukan bank atau e-wallet.

2. **Autentikasi & Otorisasi:**
   - Registrasi publik otomatis menetapkan role `user` (kebal manipulasi payload role).
   - Inisialisasi otomatis preferensi default (60/30/10) dan kategori default (Makanan, Transportasi, Tagihan, Hiburan, Belanja, Darurat Medis, dll.).
   - Login aman dengan proteksi akun nonaktif (`is_active = false`).
   - Artisan command aman `duitmoo:create-admin` untuk membuat akun administrator tanpa password hardcoded di kode sumber.

3. **Onboarding Flow (`/onboarding`):**
   - Wizard langkah demi langkah bagi pengguna baru untuk mengisi Saldo Awal, menentukan rasio alokasi, memvalidasi total persentase tepat 100%, melihat kartu balok 3D, dan menyimpan anggaran ke database.

4. **Dashboard Utama (`/dashboard`):**
   - Sapaan personal dan filter bulan/tahun interaktif.
   - **4 Kartu Ringkasan:** Saldo Awal, Pemasukan Bulan Ini, Pengeluaran Bulan Ini, dan Perkiraan Sisa Uang.
   - **Papan 3 Balok Anggaran (Toy-Brick Cards):**
     * 🥗 **Kebutuhan (Green):** Nominal alokasi, terpakai, sisa, progress bar.
     * ☕ **Keinginan (Yellow):** Nominal alokasi, terpakai, sisa, progress bar.
     * 🛡️ **Dana Darurat (Purple):** Nominal rencana alokasi, terpakai, sisa, progress bar.
     * **Peringatan Overspending:** Jika belanja melebihi jatah pos, kartu menampilkan badge peringatan dan angka negatif secara jujur.
   - **Financial Insights Dinamis:** Saran dan insight yang dihitung dari data riil database (misal pemakaian pos kebutuhan >= 80%, sisa pos keinginan, keutuhan pos darurat).
   - **Grafik Asli Chart.js:**
     * Donut Chart: Distribusi pengeluaran riil per pos.
     * Bar Chart: Perbandingan Pemasukan vs Pengeluaran selama 6 bulan terakhir.
   - **Tabel Transaksi Terbaru:** 5 transaksi terakhir dengan aksi cepat.
   - **Quick Add Transaction Modal:** Modal catat transaksi cepat langsung dari dashboard.

5. **Manajemen Transaksi CRUD (`/transactions`):**
   - Filter lengkap: Bulan & Tahun, Jenis (Pemasukan / Pengeluaran), Pos Anggaran (Kebutuhan / Keinginan / Darurat), serta Pencarian Teks.
   - Form Tambah Transaksi dengan kategori dinamis sesuai pos yang dipilih.
   - Form Edit Transaksi dengan modal interaktif Alpine.js.
   - Aksi Hapus Transaksi dengan konfirmasi dialog.
   - Proteksi Anti-IDOR: Pengguna hanya dapat melihat, mengedit, dan menghapus transaksi miliknya sendiri.

6. **Laporan & Analisis Tahunan (`/reports`):**
   - Evaluasi arus kas 12 bulan penuh dengan Chart.js.
   - Grafik bertumpuk (*stacked bar chart*) komposisi 3 pos anggaran per bulan.
   - Grafik pie & tabel peringkat kategori pengeluaran terbesar.
   - Tabel rekapitulasi keuangan bulanan dengan saldo awal, pemasukan, pengeluaran, dan selisih arus kas.
   - Tombol **Ekspor CSV** untuk mengunduh rekap transaksi tahunan.

7. **Pengaturan Anggaran & Kategori (`/budget/settings`):**
   - **Snapshot Bulanan:** Menyesuaikan rasio atau saldo awal bulan tertentu tanpa merusak riwayat bulan lampau.
   - **Preferensi Default:** Mengatur rasio bawaan untuk bulan-bulan baru mendatang.
   - **Kelola Kategori Pribadi:** Menambah, mengubah nama, atau menghapus kategori (dilindungi jika kategori sudah dipakai bertransaksi).

8. **Admin Portal (`/admin`):**
   - Akses strictly dibatasi oleh `AdminMiddleware` (user reguler ditolak dengan 403 Forbidden).
   - Menampilkan metrik operasional aplikasi: total pengguna terdaftar, pengguna aktif, akun dinonaktifkan, volume transaksi tercatat.
   - **Perlindungan Privasi:** Admin **tidak** memiliki akses melihat rincian finansial atau nominal transaksi pribadi pengguna.
   - Manajemen Pengguna: Cari pengguna dan ubah status aktif/nonaktif akun (auto-ban/unban).

---

## 📋 2. Verifikasi 15 Acceptance Criteria

| No | Kriteria Penerimaan | Status | Bukti Verifikasi |
|---|---|---|---|
| 1 | User bisa registrasi dan login | ✅ **LULUS** | Diuji via `AuthTest`: registrasi membuat role `user` dan login session aktif. |
| 2 | User bisa memasukkan Rp5.000.000 | ✅ **LULUS** | Diuji via `OnboardingWizard` dan `BudgetFlowTest`. |
| 3 | Menghasilkan Rp3 Jt kebutuhan, Rp1.5 Jt keinginan, Rp500 Rb darurat | ✅ **LULUS** | `BudgetCalculatorServiceTest`: Rp5.000.000 x 60/30/10 menghasilkan tepat Rp3.000.000, Rp1.500.000, Rp500.000. |
| 4 | User bisa mengubah rasio menjadi 50/30/20 | ✅ **LULUS** | `BudgetFlowTest`: request update snapshot dengan rasio 50/30/20 sukses. |
| 5 | Sistem menghitung ulang dengan tepat | ✅ **LULUS** | Menghasilkan tepat Rp2.500.000 kebutuhan, Rp1.500.000 keinginan, Rp1.000.000 darurat (total Rp5.000.000). |
| 6 | User bisa menambah transaksi pengeluaran | ✅ **LULUS** | Transaksi tersimpan ke tabel `transactions` dengan validasi pos dan kategori. |
| 7 | Sisa kategori otomatis berkurang | ✅ **LULUS** | `BudgetCalculatorService`: sisa anggaran = alokasi - pengeluaran pos terkait. |
| 8 | Saldo total otomatis diperbarui | ✅ **LULUS** | `estimated_remaining_cash` = (Saldo Awal + Pemasukan) - Total Pengeluaran. |
| 9 | Grafik membaca data asli dari database | ✅ **LULUS** | Donut chart dan Bar chart mengambil data langsung dari query agregasi Eloquent. |
| 10 | Filter bulan dan tahun berfungsi | ✅ **LULUS** | Filter pada Dashboard, Transaksi, dan Laporan mengisolasi data kalender secara akurat. |
| 11 | Transaksi bisa diedit dan dihapus | ✅ **LULUS** | Route `PUT /transactions/{id}` dan `DELETE /transactions/{id}` berfungsi dengan otorisasi pemilik. |
| 12 | User tidak bisa mengakses data user lain | ✅ **LULUS** | `SecurityAndIsolationTest`: User A diblokir (403/404) saat mencoba edit/hapus transaksi, budget, atau kategori User B. |
| 13 | Admin dashboard memiliki pembatasan akses | ✅ **LULUS** | User biasa mendapat 403 saat mengakses `/admin`; panel admin tidak memuat nominal transaksi pribadi user. |
| 14 | Website responsive di desktop dan mobile | ✅ **LULUS** | Layout desktop menggunakan sidebar; layout mobile (360px+) menggunakan header ringkas dan bottom navigation bar taktil. |
| 15 | Berhasil dijalankan tanpa error | ✅ **LULUS** | Seluruh 23 pengujian otomatis (95 assertions) lulus 100%, aset Vite terkompilasi bersih tanpa error. |

---

## 💻 3. Bukti Eksekusi Test Suite (PHPUnit)

```
   PASS  Tests\Unit\BudgetCalculatorServiceTest
  ✓ standard 5 million allocation 60 30 10
  ✓ custom ratio 50 30 20
  ✓ invalid ratio throws exception
  ✓ odd numbers and one rupiah preserves total
  ✓ zero amount

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

   PASS  Tests\Feature\AuthTest
  ✓ user can register and gets default role and categories
  ✓ user can login with valid credentials
  ✓ user cannot login with invalid password
  ✓ inactive user is blocked from login

   PASS  Tests\Feature\BudgetFlowTest
  ✓ 5 million opening balance allocation and custom ratio 50 30 20
  ✓ invalid ratio rejected
  ✓ transaction crud and overspending
  ✓ monthly snapshot isolation

   PASS  Tests\Feature\SecurityAndIsolationTest
  ✓ user a cannot update or delete user b transaction
  ✓ user a cannot modify user b monthly budget
  ✓ user a cannot delete user b category
  ✓ regular user cannot access admin dashboard
  ✓ admin can access admin dashboard and toggle status

   PASS  Tests\Feature\ReportAndProfileTest
  ✓ reports page and csv export
  ✓ profile update and password change
  ✓ delete account cascades all data

  Tests:    23 passed (95 assertions)
  Duration: 1.34s
```

---

## 🏁 4. Cara Pengujian Cepat di Komputer Lokal

1. Pastikan MySQL aktif (Port 3306, database `duitmoo`).
2. Jalankan server:
   ```bash
   php artisan serve
   ```
3. Buka browser: `http://localhost:8000`
4. Login dengan akun demo:
   - Email: `demo@duitmoo.test`
   - Password: `password123`
5. Uji fitur:
   - Ganti bulan dropdown (misal Oktober 2026 atau November 2026) untuk melihat grafik dan kartu alokasi berubah secara dinamis.
   - Klik **+ Catat Transaksi** untuk menambah pengeluaran atau pemasukan baru.
   - Buka menu **Atur Anggaran** untuk mengubah rasio menjadi 50/30/20 atau mengubah saldo awal.
   - Buka menu **Laporan** untuk melihat grafik tren tahunan dan klik **Unduh CSV**.
6. Uji akun Admin:
   - Logout lalu login dengan: `admin@duitmoo.test` / `password123`.
   - Buka `/admin` untuk melihat metrik operasional dan toggle status aktif pengguna.
