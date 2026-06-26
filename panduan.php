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
                <span class="badge badge-debit">DEBIT</span> <code>6-1500 - Biaya Sewa</code> $\rightarrow$ Rp1.000.000<br>
                <span class="badge badge-kredit">KREDIT</span> <code>1-2004 - Biaya Dibayar Dimuka</code> $\rightarrow$ Rp1.000.000
            </div>
        </div>
    </details>
</div>

<?php
render_footer();
