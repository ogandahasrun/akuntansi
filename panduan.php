<?php

require_once __DIR__ . '/fungsi.php';

render_header('Panduan Penggunaan Aplikasi', 'panduan');
?>

<style>
.panduan-container {
    max-width: 900px;
    margin: 0 auto;
}
details.panduan-item {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 16px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s ease-in-out;
}
details.panduan-item[open] {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
}
details.panduan-item summary {
    font-size: 1.15rem;
    font-weight: 600;
    color: #1f2937;
    cursor: pointer;
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    outline: none;
}
details.panduan-item summary::-webkit-details-marker {
    display: none;
}
details.panduan-item summary::after {
    content: "📁";
    font-size: 1.2rem;
    transition: transform 0.2s ease;
}
details.panduan-item[open] summary::after {
    content: "📂";
    transform: rotate(90deg);
}
.panduan-content {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #f3f4f6;
    color: #4b5563;
    line-height: 1.7;
}
.panduan-content h4 {
    color: #111827;
    margin: 16px 0 8px;
    font-size: 1.05rem;
}
.panduan-content ul, .panduan-content ol {
    margin-top: 8px;
    margin-bottom: 8px;
    padding-left: 20px;
}
.panduan-content li {
    margin-bottom: 6px;
}
.example-box {
    background-color: #f9fafb;
    border-left: 4px solid #10b981;
    padding: 12px 18px;
    border-radius: 4px;
    margin: 14px 0;
    font-size: 0.95rem;
}
.example-box code {
    font-family: Consolas, Monaco, monospace;
    background-color: #e5e7eb;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 0.9rem;
}
.badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.8rem;
    font-weight: 600;
    border-radius: 4px;
    margin-right: 5px;
}
.badge-debit { background-color: #d1fae5; color: #065f46; }
.badge-kredit { background-color: #fef3c7; color: #92400e; }
</style>

<div class="panduan-container">
    <section class="panel">
        <h2>Pusat Bantuan & Panduan Akuntansi</h2>
        <p class="subtle">Dokumentasi panduan operasional sistem pencatatan jurnal, pencarian akun, dan ayat jurnal penyesuaian (AJP) otomatis.</p>
    </section>

    <!-- Item 1: Pengenalan Jurnal Penyesuaian -->
    <details class="panduan-item" open>
        <summary>1. Apa itu Ayat Jurnal Penyesuaian (AJP)?</summary>
        <div class="panduan-content">
            <p><strong>Ayat Jurnal Penyesuaian (AJP)</strong> adalah pencatatan akuntansi yang dilakukan pada akhir periode untuk menyesuaikan akun-akun agar mencerminkan keadaan yang sebenarnya sebelum laporan keuangan disusun. Hal ini penting untuk memenuhi <em>accrual basis</em> dan prinsip kecocokan beban-pendapatan (<em>matching principle</em>).</p>
            <h4>Kapan AJP Diperlukan?</h4>
            <ul>
                <li>Adanya perlengkapan yang habis terpakai selama periode berjalan.</li>
                <li>Adanya beban dibayar di muka yang manfaatnya sudah dirasakan seiring waktu (seperti sewa gedung bulanan).</li>
                <li>Adanya beban yang sudah menjadi kewajiban tetapi belum dibayar (misal: gaji karyawan akhir bulan yang baru dibayar bulan berikutnya).</li>
                <li>Penyusutan nilai aset tetap secara berkala (Gedung, Kendaraan, Peralatan).</li>
            </ul>
        </div>
    </details>

    <!-- Item 2: Menggunakan Pencarian Akun -->
    <details class="panduan-item">
        <summary>2. Cara Menggunakan Fitur Pencarian Akun (Searchable Select)</summary>
        <div class="panduan-content">
            <p>Untuk mempermudah pemilihan akun dari ratusan daftar akun yang ada, sistem ini telah dilengkapi dengan kotak pencarian otomatis di halaman <strong>Jurnal Umum</strong> dan <strong>Jurnal Penyesuaian</strong>.</p>
            <h4>Langkah Pencarian:</h4>
            <ol>
                <li>Pada kolom <strong>Akun</strong> di tabel jurnal, Anda akan melihat kotak pencarian teks bertuliskan <em>"Cari kode / nama akun..."</em> di atas dropdown.</li>
                <li>Ketikkan potongan <strong>kode akun</strong> (misal: <code>6-3111</code>) atau <strong>nama akun</strong> (misal: <code>Gaji</code>).</li>
                <li>Dropdown select di bawahnya akan otomatis terfilter hanya menampilkan akun yang cocok dengan kata kunci tersebut secara instan.</li>
                <li>Pilih akun yang diinginkan. Jika Anda menambahkan baris baru dengan menekan <strong>"Tambah Baris"</strong>, kotak pencarian pada baris baru tersebut akan langsung aktif secara otomatis.</li>
            </ol>
        </div>
    </details>

    <!-- Item 3: Otomatisasi Aset Tetap -->
    <details class="panduan-item">
        <summary>3. Panduan Penggunaan Modul Aset Tetap & Depresiasi Otomatis</summary>
        <div class="panduan-content">
            <p>Aplikasi ini memiliki modul <strong>Aset Tetap</strong> untuk melacak inventaris fisik Anda dan memposting Jurnal Penyesuaian Penyusutan secara otomatis menggunakan <strong>Metode Garis Lurus</strong>.</p>
            <h4>Langkah Pendaftaran Aset Baru:</h4>
            <ol>
                <li>Masuk ke menu <strong>Aset Tetap</strong>.</li>
                <li>Isi Formulir <em>Daftarkan Aset Baru</em>: Nama, Kategori, Tanggal Perolehan (Tanggal Pembelian), Harga Perolehan, Umur Ekonomis (Tahun), dan Nilai Residu/Sisa (jika ada).</li>
                <li>Klik <strong>Daftarkan Aset</strong>. Aset akan masuk ke tabel inventaris dan nilai penyusutan bulanan akan dihitung otomatis.</li>
            </ol>
            <h4>Langkah Proses AJP Otomatis Bulanan:</h4>
            <ol>
                <li>Pada widget sebelah kanan <em>"Proses Penyusutan Otomatis (AJP)"</em>, pilih bulan dan tahun periode penyusutan (misal: <code>2026-06</code>).</li>
                <li>Klik tombol <strong>⚡ Proses Penyusutan Aset (AJP)</strong> dan konfirmasi tindakan.</li>
                <li>Sistem secara otomatis menghitung beban penyusutan semua aset yang aktif pada periode tersebut, mengelompokkannya per kategori, dan memposting Jurnal Penyesuaian penutup secara otomatis.</li>
            </ol>
            <div class="example-box" style="border-left-color: #3b82f6;">
                <strong>Catatan Penting:</strong>
                <ul>
                    <li>Aset tidak akan disusutkan jika periode bulan yang dipilih mendahului tanggal pembelian aset.</li>
                    <li>Sistem memiliki pengaman bawaan untuk menolak pemrosesan ganda pada bulan yang sama untuk mencegah pencatatan beban ganda.</li>
                </ul>
            </div>
        </div>
    </details>

    <!-- Item 4: Contoh Kasus & Jurnal AJP Manual -->
    <details class="panduan-item">
        <summary>4. Contoh Kasus & Jurnal Penyesuaian Manual Umum</summary>
        <div class="panduan-content">
            <p>Untuk kasus-kasus penyesuaian non-rutin, Anda dapat mencatatnya secara manual di menu <strong>Jurnal Penyesuaian</strong> menggunakan draf pencatatan berikut:</p>
            
            <h4>Kasus A: Beban Gaji yang Masih Harus Dibayar (Accrued Salary)</h4>
            <p>Gaji karyawan untuk bulan Juni belum dibayarkan sebesar Rp5.000.000,00.</p>
            <div class="example-box">
                <span class="badge badge-debit">DEBIT</span> <code>6-1900 - Biaya Gaji Dan Upah</code> $\rightarrow$ Rp5.000.000<br>
                <span class="badge badge-kredit">KREDIT</span> <code>2-1600 - Hutang Gaji Karyawan</code> $\rightarrow$ Rp5.000.000
            </div>

            <h4>Kasus B: Pemakaian Perlengkapan Kantor (Supplies Used)</h4>
            <p>Saldo awal akun Perlengkapan Kantor Rp3.000.000,00. Pada akhir bulan sisa fisik perlengkapan tinggal Rp1.000.000,00 (artinya Rp2.000.000,00 telah terpakai).</p>
            <div class="example-box">
                <span class="badge badge-debit">DEBIT</span> <code>6-2000 - Biaya Perlengkapan Kantor</code> $\rightarrow$ Rp2.000.000<br>
                <span class="badge badge-kredit">KREDIT</span> <code>1-1800 - Perlengkapan Kantor</code> $\rightarrow$ Rp2.000.000
            </div>

            <h4>Kasus C: Sewa Kantor Dibayar di Muka (Prepaid Expense)</h4>
            <p>Membayar sewa kantor Rp12.000.000,00 pada Januari untuk jangka waktu 1 tahun. Penyesuaian untuk bulan berjalan (1 bulan = Rp1.000.000,00):</p>
            <div class="example-box">
                <span class="badge badge-debit">DEBIT</span> <code>6-1500 - Biaya Sewa</code> &rarr; Rp1.000.000<br>
                <span class="badge badge-kredit">KREDIT</span> <code>1-2004 - Biaya Dibayar Dimuka</code> &rarr; Rp1.000.000
            </div>
        </div>
    </details>

    <!-- Item 5: Laporan Pendapatan & Pengeluaran Kas/Bank -->
    <details class="panduan-item">
        <summary>5. Panduan Laporan Pendapatan &amp; Pengeluaran Kas/Bank</summary>
        <div class="panduan-content">
            <p>Halaman <strong>Pendapatan &amp; Pengeluaran</strong> dirancang khusus untuk menganalisis pendapatan (uang masuk) dan pengeluaran beban (uang keluar) yang terjadi secara riil melalui rekening Kas atau Bank tertentu yang Anda pilih.</p>
            
            <h4>Bagaimana Logika Laporan Ini Bekerja?</h4>
            <ul>
                <li>Sistem memetakan seluruh transaksi jurnal yang melibatkan rekening Kas/Bank terpilih (misal: Kas Tunai, Bank BNI, atau Bank Mandiri).</li>
                <li>Dari transaksi tersebut, sistem mencari baris akun lawannya: jika merupakan akun berkategori <strong>Pendapatan</strong> akan dikelompokkan ke bagian atas (Uang Masuk), dan jika berkategori <strong>Beban</strong> akan dikelompokkan ke bagian bawah (Uang Keluar). Masing-masing kelompok diurutkan berdasarkan tanggal transaksi.</li>
            </ul>

            <h4>Bagaimana Cara Menambahkan Rekening Bank Baru di Filter Dropdown?</h4>
            <p>Saringan akun rekening di halaman ini bersifat 100% dinamis. Jika Anda membuka rekening bank baru (misalnya Bank BCA) dan ingin rekening tersebut muncul di filter halaman Pendapatan &amp; Pengeluaran:</p>
            <ol>
                <li>Buka menu <strong>Daftar Akun</strong>.</li>
                <li>Klik tombol <strong>Tambah Akun Baru</strong>.</li>
                <li>Isi form dengan detail akun baru (Kategori: <code>Aset</code>, Tipe Saldo: <code>Debit</code>, misal Kode: <code>1-1250</code>, Nama: <code>Tabungan Bank BCA</code>).</li>
                <li><strong>PENTING:</strong> Pada kolom pilihan <strong>"Tipe Detail Akun"</strong>, pilih opsi <strong>"Kas / Bank"</strong>. Pilihan ini akan memberi tanda <code>tipe_detail = 'Kas/Bank'</code> di database agar terbaca sebagai rekening kas/bank.</li>
                <li>Klik <strong>Simpan Akun</strong>. Akun Bank BCA tersebut secara otomatis akan langsung terdaftar pada pilihan filter laporan Pendapatan &amp; Pengeluaran.</li>
            </ol>
        </div>
    </details>

    <!-- Item 6: Alur Kerja & Siklus Akuntansi -->
    <details class="panduan-item">
        <summary>6. Alur Kerja Aplikasi Berdasarkan Siklus Akuntansi</summary>
        <div class="panduan-content">
            <p>Struktur menu navigasi pada sidebar sebelah kiri dirancang secara berurutan untuk mencerminkan tahapan <strong>Siklus Akuntansi (Accounting Cycle)</strong> standar:</p>
            
            <ol style="margin-bottom: 20px;">
                <li style="margin-bottom: 10px;">
                    <strong>UTAMA (Dashboard)</strong>
                    <br><span style="color: var(--muted); font-size: 0.9rem;">Tempat pemantauan data finansial secara real-time dan analisis grafik perkembangan pendapatan, beban, hutang, dan piutang rumah sakit.</span>
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>PENGATURAN AWAL (Daftar Akun &amp; Tahun Buku)</strong>
                    <br><span style="color: var(--muted); font-size: 0.9rem;">Menyiapkan bagan akun (Chart of Accounts) dan mengaktifkan periode pembukuan berjalan sebelum memulai pencatatan transaksi harian.</span>
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>PENCATATAN HARIAN (Jurnal Umum, Hutang, Piutang)</strong>
                    <br><span style="color: var(--muted); font-size: 0.9rem;">Mencatat mutasi harian secara kronologis (Jurnal Umum) serta mengelola saldo kewajiban (Hutang) dan hak penagihan (Piutang) per vendor/pasien.</span>
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>PENYESUAIAN PERIODIK (Aset Tetap &amp; Jurnal Penyesuaian)</strong>
                    <br><span style="color: var(--muted); font-size: 0.9rem;">Melakukan penghitungan biaya penyusutan aset tetap secara bulanan (Aset Tetap) dan mencatat koreksi jurnal penyesuaian (AJP) akhir bulan agar nilai saldo mencerminkan kondisi riil.</span>
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>LAPORAN &amp; REKAP (Buku Besar &amp; Pendapatan &amp; Pengeluaran)</strong>
                    <br><span style="color: var(--muted); font-size: 0.9rem;">Mengecek rincian mutasi per akun (Buku Besar) dan meninjau aliran rekapitulasi kas masuk/keluar khusus operasional kas/bank.</span>
                </li>
                <li style="margin-bottom: 10px;">
                    <strong>LAPORAN KEUANGAN (Laba Rugi, Perubahan Ekuitas, Neraca, Arus Kas)</strong>
                    <br><span style="color: var(--muted); font-size: 0.9rem;">Laporan akhir keuangan yang disusun secara sekuensial (laba bersih &rarr; modal akhir &rarr; neraca &rarr; arus kas) untuk pelaporan hasil kinerja rumah sakit.</span>
                </li>
            </ol>
            
            <div class="example-box" style="border-left-color: #10b981;">
                <strong>Mengapa Urutan Laporan Keuangan Begitu Penting?</strong>
                <p style="margin: 5px 0 0; font-size: 0.9rem;">
                    Penyusunan laporan keuangan wajib berurutan karena output dari satu laporan menjadi input bagi laporan berikutnya:
                    <br>1. Nilai Laba/Rugi Bersih dari <strong>Laporan Laba Rugi</strong> dikirim ke <strong>Laporan Perubahan Ekuitas</strong>.
                    <br>2. Nilai Modal Akhir dari <strong>Laporan Perubahan Ekuitas</strong> dikirim ke pos Ekuitas di <strong>Laporan Neraca</strong>.
                </p>
            </div>
        </div>
    </details>

    <!-- Item 7: Panduan Laporan & Akuntansi Persediaan -->
    <details class="panduan-item">
        <summary>7. Panduan Laporan &amp; Standar Akuntansi Persediaan (PSAK 14 / SAK EP)</summary>
        <div class="panduan-content">
            <p>Menu <strong>Laporan Persediaan</strong> menggabungkan Laporan Mutasi Nilai Persediaan dan Riwayat Penyesuaian persediaan guna memenuhi asas kecocokan beban-pendapatan (<em>matching principle</em>) serta standar akuntansi yang berlaku umum.</p>

            <h4>Dasar Hukum &amp; Standar Akuntansi (PSAK 14 / SAK EP)</h4>
            <ul>
                <li><strong>Definisi Aset Persediaan</strong>: Sesuai standar akuntansi keuangan (PSAK 14 / SAK EP), persediaan adalah aset dalam bentuk bahan atau perlengkapan (misalnya perlengkapan kantor, ATK, obat-obatan, BHP pelayanan) yang digunakan dalam proses pemberian jasa atau operasi harian perusahaan.</li>
                <li><strong>Metode Pencatatan</strong>: Sistem ini mendukung pencatatan persediaan secara <strong>periodik (fisik)</strong>. Pembelian persediaan dicatat ke akun persediaan (sebagai Aset) melalui Jurnal Umum, dan di akhir periode dilakukan penyesuaian (Stock Opname) untuk mengukur nilai barang habis pakai yang sudah benar-benar terpakai.</li>
                <li><strong>Matching Principle (Pengakuan Beban)</strong>: Nilai persediaan yang telah habis dikonsumsi harus diakui sebagai <strong>Beban/Biaya</strong> pada periode terjadinya pemakaian agar laba rugi dilaporkan secara adil.</li>
            </ul>

            <h4>Alur Penggunaan Fitur Persediaan di Aplikasi</h4>
            <ol style="margin-bottom: 14px;">
                <li>
                    <strong>Langkah 1: Pembelian Persediaan (Barang Masuk)</strong>
                    <br>Catat transaksi pembelian persediaan di menu <strong>Jurnal Umum</strong>.
                    <br>
                    <br><strong>Kasus A: Pembelian secara Kredit (Hutang)</strong>
                    <br>Pembelian Obat & BHP medis dari Pemasok sebesar Rp10.000.000,00 dengan cara Hutang Tempo:
                    <div class="example-box">
                        <span class="badge badge-debit">DEBIT</span> <code>1-1700 - Persediaan Obat & BHP</code> &rarr; Rp10.000.000<br>
                        <span class="badge badge-kredit">KREDIT</span> <code>2-1200 - Hutang Obat & BHP</code> &rarr; Rp10.000.000
                    </div>
                    <strong>Kasus B: Pembayaran Hutang Persediaan Tersebut</strong>
                    <br>Melunasi hutang pembelian obat di atas melalui transfer rekening Bank Mandiri:
                    <div class="example-box">
                        <span class="badge badge-debit">DEBIT</span> <code>2-1200 - Hutang Obat & BHP</code> &rarr; Rp10.000.000<br>
                        <span class="badge badge-kredit">KREDIT</span> <code>1-1300 - Tabungan Bank Mandiri</code> &rarr; Rp10.000.000
                    </div>
                </li>
                <li>
                    <strong>Langkah 2: Melakukan Stock Opname (Fisik)</strong>
                    <br>Di akhir bulan, lakukan perhitungan fisik persediaan yang masih tersisa di gudang/kantor.
                </li>
                <li>
                    <strong>Langkah 3: Jurnal Penyesuaian (Barang Keluar)</strong>
                    <br>Catat selisih nilai barang yang terpakai melalui menu <strong>Jurnal Penyesuaian</strong>. Contoh:
                    <div class="example-box">
                        <span class="badge badge-debit">DEBIT</span> <code>6-2000 - Biaya Perlengkapan Kantor</code> &rarr; Rp2.000.000<br>
                        <span class="badge badge-kredit">KREDIT</span> <code>1-1800 - Perlengkapan Kantor</code> &rarr; Rp2.000.000
                    </div>
                </li>
                <li>
                    <strong>Langkah 4: Pantau Laporan Persediaan</strong>
                    <br>Buka menu <strong>Laporan Persediaan</strong> di sidebar. Gunakan filter akun persediaan dan periode tanggal untuk memantau ringkasan saldo awal, mutasi masuk (Debit), mutasi keluar (Kredit), hingga sisa saldo aktif persediaan Anda secara terpadu.
                </li>
            </ol>
        </div>
    </details>

    <!-- Item 8: Bagaimana Pendapatan & Beban Memengaruhi Neraca -->
    <details class="panduan-item">
        <summary>8. Bagaimana Transaksi Pendapatan &amp; Beban Memengaruhi Neraca (Ilustrasi Aliran Keuangan)</summary>
        <div class="panduan-content">
            <p>Meskipun akun <strong>Pendapatan</strong> dan <strong>Beban</strong> hanya dicantumkan di Laporan Laba Rugi dan tidak muncul langsung sebagai baris di Neraca, transaksi keduanya secara otomatis memengaruhi keseimbangan Neraca pada akhir periode.</p>

            <h4>Ilustrasi Aliran Keuangan:</h4>
            
            <p><strong>1. Posisi Awal Neraca (Sebelum Transaksi):</strong></p>
            <ul>
                <li><strong>Sisi Kiri (Aset)</strong>: <code>1-1200 Tabungan Bank BNI</code> = Rp50.000.000</li>
                <li><strong>Sisi Kanan (Pasiva)</strong>: <code>2-1100 Hutang Usaha</code> = Rp10.000.000, <code>3-1100 Modal Pemilik</code> = Rp40.000.000</li>
                <li><em>Persamaan</em>: Aset (Rp50jt) = Hutang (Rp10jt) + Modal (Rp40jt) &rarr; <strong>Seimbang</strong>.</li>
            </ul>

            <p><strong>2. Pencatatan Transaksi Baru (Jurnal Umum):</strong></p>
            <ul>
                <li>
                    <strong>Transaksi 1: Menerima Pendapatan</strong>
                    <br>Menerima transfer pendapatan pelayanan ke Bank BNI sebesar Rp15.000.000.
                    <div class="example-box">
                        <span class="badge badge-debit">DEBIT</span> <code>1-1200 Tabungan Bank BNI</code> &rarr; Rp15.000.000<br>
                        <span class="badge badge-kredit">KREDIT</span> <code>4-1100 Pendapatan Umum</code> &rarr; Rp15.000.000
                    </div>
                </li>
                <li>
                    <strong>Transaksi 2: Membayar Beban</strong>
                    <br>Membayar biaya gaji perawat secara transfer via Bank BNI sebesar Rp5.000.000.
                    <div class="example-box">
                        <span class="badge badge-debit">DEBIT</span> <code>6-1900 Biaya Gaji Dan Upah</code> &rarr; Rp5.000.000<br>
                        <span class="badge badge-kredit">KREDIT</span> <code>1-1200 Tabungan Bank BNI</code> &rarr; Rp5.000.000
                    </div>
                </li>
            </ul>

            <p><strong>3. Perhitungan Laba Rugi:</strong></p>
            <p>Laba Bersih dihitung: Pendapatan (Rp15.000.000) - Beban (Rp5.000.000) = <strong>Laba Bersih Rp10.000.000</strong>.</p>

            <p><strong>4. Efek Akhir pada Neraca:</strong></p>
            <ul>
                <li><strong>Sisi Kiri (Aset)</strong>: Saldo Bank BNI bertambah bersih sebesar Rp10.000.000 (Pendapatan Rp15jt - Beban Rp5jt), sehingga saldo akhir Bank BNI menjadi <strong>Rp60.000.000</strong>.</li>
                <li><strong>Sisi Kanan (Pasiva)</strong>: Laba Bersih Rp10.000.000 otomatis masuk ke akun ekuitas/modal sebagai <strong>Laba Tahun Berjalan</strong>. Nilai modal akhir menjadi <strong>Rp50.000.000</strong> (Modal Awal Rp40jt + Laba Rp10jt).</li>
            </ul>

            <p><strong>Hasil Akhir Neraca Setelah Penyesuaian:</strong></p>
            <table class="table compact" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Sisi Kiri (Aset)</th>
                        <th class="align-right">Nominal</th>
                        <th>Sisi Kanan (Kewajiban &amp; Modal)</th>
                        <th class="align-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Tabungan Bank BNI</td>
                        <td class="align-right">Rp60.000.000</td>
                        <td>Hutang Usaha</td>
                        <td class="align-right">Rp10.000.000</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="align-right"></td>
                        <td>Modal Pemilik</td>
                        <td class="align-right">Rp40.000.000</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="align-right"></td>
                        <td>Laba Tahun Berjalan (Laba Rugi)</td>
                        <td class="align-right">Rp10.000.000</td>
                    </tr>
                    <tr class="total-row">
                        <td>TOTAL ASET</td>
                        <td class="align-right">Rp60.000.000</td>
                        <td>TOTAL PASIVA</td>
                        <td class="align-right">Rp60.000.000</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </details>
</div>

<?php
render_footer();
