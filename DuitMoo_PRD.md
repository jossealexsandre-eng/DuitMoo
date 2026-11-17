# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# DuitMoo — Your Money, Your Rules!

**Versi:** 1.0 (MVP)  
**Jenis produk:** Web app personal budgeting & manual expense tracking  
**Bahasa antarmuka:** Indonesia  
**Mata uang:** Rupiah (IDR)  
**Zona waktu:** Asia/Jakarta  
**Konsep desain:** Playful 3D toy-brick gameboard (orisinal; tidak memakai merek, logo, atau aset LEGO)  
**Target platform:** Mobile-first responsive web (desktop/tablet/mobile)

## 1. Ringkasan eksekutif
DuitMoo adalah aplikasi web untuk membantu seseorang mencatat dana yang dimiliki, membaginya secara otomatis ke dalam tiga pos anggaran, mencatat pemasukan serta pengeluaran, dan melihat perkembangan keuangannya dari bulan ke bulan. Alokasi awal: **Kebutuhan 60%**, **Keinginan 30%**, dan **Dana Darurat 10%**. Setiap pengguna bebas mengubah persentasenya selama total tetap 100%.

**Bukan layanan keuangan:** DuitMoo bukan bank, m-banking, e-wallet, investasi, sistem pembayaran, maupun pengelola uang sungguhan. Tidak ada koneksi rekening, transaksi transfer, isi saldo riil, pengambilan dana, pembayaran, atau janji keuntungan. Seluruh nilai dimasukkan secara manual oleh pengguna dan hanya untuk pencatatan/perencanaan.

## 2. Masalah pengguna
- Tidak tahu uang yang tersedia sudah dialokasikan untuk apa.
- Sulit melihat pengeluaran paling besar dalam satu bulan.
- Pencatatan pemasukan/pengeluaran terasa rumit dan membosankan.
- Sulit membandingkan kebiasaan keuangan antarbulan.
- Ingin pembagian uang sederhana tanpa rumus yang harus dihitung sendiri.

## 3. Tujuan produk
1. Pengguna baru bisa mendapatkan pembagian anggaran setelah mengisi satu nominal dan memilih persentase.
2. Pengguna dapat mencatat pemasukan dan pengeluaran manual dalam beberapa langkah singkat.
3. Pengguna dapat melihat total dana, realisasi belanja, sisa anggaran masing-masing pos, dan grafik 6–12 bulan.
4. Pengguna memiliki kontrol atas data, kategori, dan persentase sendiri.
5. Admin mengelola akun dan kesehatan aplikasi tanpa otomatis mengakses detail finansial pribadi pengguna.

### Non-goals (tidak dikerjakan dalam MVP)
Integrasi bank/e-wallet, pembayaran, top-up, transfer, OCR struk, sinkronisasi rekening, rekomendasi investasi, AI financial advisor, kurs multi-mata-uang, dan pengiriman notifikasi berbayar.

## 4. Persona dan peran
**Pengguna:** mahasiswa, pekerja, freelancer, atau siapa pun yang ingin mengatur uang bulanan secara sederhana. Dapat mendaftar, login, melakukan onboarding, mencatat transaksi, mengedit atau menghapus data sendiri, menyesuaikan alokasi, menambahkan subkategori pengeluaran, dan membaca riwayat.

**Administrator:** akun terpisah yang dibuat melalui seeder/command terpercaya, bukan melalui halaman registrasi. Dapat melihat jumlah akun, pengguna aktif, dan statistik operasional non-sensitif, menonaktifkan/mengaktifkan akun serta mengelola informasi aplikasi. Admin **tidak secara default** memiliki halaman untuk membuka/mengubah transaksi atau catatan uang pribadi milik pengguna.

## 5. User journey
1. Buka landing page DuitMoo → lihat deskripsi singkat dan demo perhitungan.
2. Daftar/login → onboarding satu kali.
3. Pilih bulan, input **Saldo Awal Bulan** (contoh: Rp5.000.000) → terapkan 60/30/10, atau ubah rasionya → simpan.
4. Dashboard memperlihatkan 3 kartu balok anggaran dan grafik.
5. Klik **+ Catat Transaksi** → pilih Pemasukan atau Pengeluaran → masukkan nominal, tanggal, kategori, dan catatan opsional.
6. Saldo dan sisa anggaran diperbarui otomatis. Jika melampaui jatah kategori, munculkan peringatan yang tidak menutupi datanya.
7. Pengguna dapat memilih bulan sebelumnya dan membandingkan pengeluaran 6–12 bulan.

## 6. Aturan bisnis dan matematika (wajib akurat)
### 6.1 Definisi data uang
- **Saldo Awal Bulan:** nominal uang yang sudah tersedia di awal bulan yang dipilih; bukan "pemasukan bulan ini".
- **Pemasukan Bulan Ini:** jumlah transaksi bertipe pemasukan dalam bulan tersebut, **tidak** termasuk saldo awal.
- **Total Dana untuk Dialokasikan:** Saldo Awal Bulan + Pemasukan Bulan Ini.
- **Pengeluaran Bulan Ini:** jumlah transaksi bertipe pengeluaran.
- **Perkiraan Sisa Uang:** Total Dana untuk Dialokasikan − Pengeluaran Bulan Ini. Ini saldo berdasarkan catatan manual, **bukan saldo rekening aktual**.
- **Target Alokasi Pos:** Total Dana untuk Dialokasikan × persentase pos.
- **Sisa Anggaran Pos:** Target Alokasi Pos − pengeluaran yang ditandai pada pos tersebut. Bisa negatif (overspending).
- **Dana Darurat:** pos anggaran yang disisihkan *secara rencana*, bukan bukti uang telah dipindahkan atau ditabung. Pengeluaran yang benar-benar memakai pos ini boleh dicatat, tetapi aplikasi tidak melakukan penarikan/pemindahan nyata.

### 6.2 Pembulatan
Semua nilai uang disimpan sebagai **integer rupiah** (BIGINT), bukan float. Hitung target alokasi pos pertama (Kebutuhan) dan kedua (Keinginan) menggunakan pembulatan ke bawah/floor, kemudian set alokasi Dana Darurat sebagai **sisa total dana** sehingga penjumlahan ketiganya selalu persis sama dengan Total Dana untuk Dialokasikan, termasuk nominal tidak habis dibagi 100.

### 6.3 Contoh utama
- Saldo awal: Rp5.000.000; pemasukan: Rp0; pengeluaran: Rp0.
- Kebutuhan 60% = **Rp3.000.000**.
- Keinginan 30% = **Rp1.500.000**.
- Dana Darurat 10% = **Rp500.000**.
- Perkiraan sisa uang = **Rp5.000.000** (alokasi tidak mengurangi saldo).

**Contoh kelanjutan:** setelah ada tambahan pemasukan Rp1.000.000 dan pengeluaran kebutuhan Rp500.000: total dana Rp6.000.000; target kebutuhan Rp3.600.000; keinginan Rp1.800.000; darurat Rp600.000; sisa anggaran kebutuhan Rp3.100.000; perkiraan sisa uang Rp5.500.000.

### 6.4 Aturan bulan dan pengubahan persentase
- Setiap anggaran memiliki identitas unik pasangan `(user_id, bulan)`.
- Persentase bawaan pengguna disimpan di preferensi. Saat bulan baru pertama dibuka/disiapkan, persentase disalin ke anggaran bulan tersebut (snapshot).
- Perubahan preferensi **tidak mengubah bulan-bulan lama** secara diam-diam.
- Pengguna boleh mengedit alokasi bulan tertentu secara eksplisit, termasuk bulan lampau, dengan konfirmasi dan perhitungan ulang.
- Transaksi dihitung menurut tanggal transaksi dalam bulan kalender Asia/Jakarta.
- **MVP tidak melakukan carry-over otomatis** antarbulan. Pengguna mengisi saldo awal setiap bulan secara sadar; bantuan UI menjelaskan agar tidak terjadi pencatatan ganda.
- Persentase masing-masing harus angka 0–100; totalnya wajib 100%.
- Nominal saldo awal minimal Rp0; transaksi harus lebih besar dari Rp0; maksimal sesuai batas BIGINT aman dan validasi aplikasi.
- Pengeluaran di luar jatah kategori dapat tetap dicatat setelah konfirmasi; nilai sisa yang negatif ditampilkan sebagai peringatan, tidak disembunyikan atau dipaksakan menjadi nol.
