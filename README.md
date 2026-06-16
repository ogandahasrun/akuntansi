# Aplikasi Akuntansi Sederhana

Aplikasi ini dibuat dengan PHP dan MySQL/MariaDB untuk kebutuhan belajar akuntansi dasar.

## Fitur

- Jurnal umum dengan validasi debit dan kredit seimbang.
- Daftar akun beserta penandaan akun kas/bank.
- Buku besar per akun.
- Pemantauan hutang dan piutang.
- Laporan arus kas dari akun kas/bank.
- Neraca sederhana.
- Pengaturan nama perusahaan, alamat, telepon, dan email.

## Cara Menjalankan

1. Letakkan folder proyek di `c:\xampp\htdocs\akuntansi`.
2. Nyalakan Apache dan MySQL pada XAMPP.
3. Pastikan `koneksi.php` sesuai dengan akses MySQL Anda.
4. Buka `http://localhost/akuntansi` di browser.
5. Jika tabel belum ada, aplikasi akan mengarahkan ke halaman instalasi. Tekan tombol instalasi untuk membuat tabel dan data awal.

## Catatan Penggunaan

- Untuk transaksi hutang atau piutang, pilih jenis transaksi yang sesuai di jurnal umum lalu isi kontak dan nominal relasi.
- Jika ada pembayaran hutang atau penerimaan piutang, sebaiknya selain memperbarui status tagihan juga dibuatkan jurnal kas agar arus kas tetap lengkap.
- Neraca dihitung dari saldo akun kategori aset, kewajiban, dan ekuitas.