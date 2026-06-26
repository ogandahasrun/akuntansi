<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';

// Flag file untuk menandai bahwa schema migration sudah selesai.
// Setelah file ini ada, semua DDL (SHOW COLUMNS, ALTER TABLE, CREATE TABLE)
// tidak akan dieksekusi lagi di setiap request → penghematan besar di server.
define('SCHEMA_READY_FLAG', __DIR__ . '/.schema_ready');

// Self-healing: jika flag schema_ready ada tetapi tabel users belum ada (update aplikasi),
// hapus flag agar migrasi dijalankan ulang.
if (file_exists(SCHEMA_READY_FLAG)) {
    $cekTabel = $koneksi->query("SHOW TABLES LIKE 'users'");
    if (!$cekTabel || $cekTabel->num_rows === 0) {
        @unlink(SCHEMA_READY_FLAG);
    }
}

function schema_sudah_siap(): bool
{
    return file_exists(SCHEMA_READY_FLAG);
}

function tandai_schema_selesai(): void
{
    @file_put_contents(SCHEMA_READY_FLAG, date('c'));
}

function reset_schema_flag(): void
{
    if (file_exists(SCHEMA_READY_FLAG)) {
        @unlink(SCHEMA_READY_FLAG);
    }
}

function e($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function format_rupiah($nilai)
{
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
}

function format_tanggal_indonesia($tanggal)
{
    if (empty($tanggal)) {
        return '-';
    }

    return date('d-m-Y', strtotime($tanggal));
}

function atur_flash($jenis, $pesan)
{
    $_SESSION['flash'] = [
        'jenis' => $jenis,
        'pesan' => $pesan,
    ];
}

function ambil_flash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function kueri_semua($sql)
{
    global $koneksi;

    $hasil = $koneksi->query($sql);
    if (!$hasil) {
        return [];
    }

    $data = [];
    while ($baris = $hasil->fetch_assoc()) {
        $data[] = $baris;
    }

    return $data;
}

function kueri_satu($sql)
{
    $data = kueri_semua($sql);
    return $data[0] ?? null;
}

function database_terpasang()
{
    static $status = null;
    global $koneksi;

    if ($status !== null) {
        return $status;
    }

    $hasil = $koneksi->query("SHOW TABLES LIKE 'pengaturan'");
    $status = $hasil && $hasil->num_rows > 0;

    return $status;
}

function sinkronisasi_skema_pengaturan()
{
    static $sudahDicek = false;
    global $koneksi;

    // Lewati jika flag sudah ada atau sudah dicek dalam request ini
    if ($sudahDicek || schema_sudah_siap() || !database_terpasang()) {
        return;
    }

    $hasil = $koneksi->query("SHOW COLUMNS FROM pengaturan LIKE 'logo'");
    if ($hasil && $hasil->num_rows === 0) {
        $koneksi->query("ALTER TABLE pengaturan ADD COLUMN logo VARCHAR(255) NULL AFTER email");
    }

    $sudahDicek = true;
}

function sinkronisasi_skema_kontak()
{
    static $sudahDicek = false;
    global $koneksi;

    if ($sudahDicek || schema_sudah_siap() || !database_terpasang()) {
        return;
    }

    $hasil = $koneksi->query("SHOW COLUMNS FROM kontak LIKE 'kode_kontak'");
    if ($hasil && $hasil->num_rows === 0) {
        $koneksi->query("ALTER TABLE kontak ADD COLUMN kode_kontak VARCHAR(50) NULL AFTER id");
    }

    $sudahDicek = true;
}

function sinkronisasi_skema_akun()
{
    static $sudahDicek = false;
    global $koneksi;

    if ($sudahDicek || schema_sudah_siap() || !database_terpasang()) {
        return;
    }

    // Tambah kolom parent_id jika belum ada
    $hasil = $koneksi->query("SHOW COLUMNS FROM akun LIKE 'parent_id'");
    if ($hasil && $hasil->num_rows === 0) {
        $koneksi->query("ALTER TABLE akun ADD COLUMN parent_id INT NULL DEFAULT NULL AFTER kode_akun");
        // Tambah foreign key (ignore error jika sudah ada)
        $koneksi->query(
            "ALTER TABLE akun ADD CONSTRAINT fk_akun_parent FOREIGN KEY (parent_id) REFERENCES akun(id) ON DELETE SET NULL"
        );
    }

    $sudahDicek = true;
}

function sinkronisasi_skema_jurnal()
{
    static $sudahDicek = false;
    global $koneksi;

    if ($sudahDicek || schema_sudah_siap() || !database_terpasang()) {
        return;
    }

    // Perluas ENUM jenis_transaksi dengan nilai Bayar Hutang, Terima Piutang, dan Penyesuaian
    $koneksi->query(
        "ALTER TABLE jurnal MODIFY jenis_transaksi ENUM('Umum','Kas','Hutang','Piutang','Bayar Hutang','Terima Piutang','Penyesuaian') NOT NULL DEFAULT 'Umum'"
    );

    // Tambah kolom hutang_piutang_id jika belum ada
    $hasil = $koneksi->query("SHOW COLUMNS FROM jurnal LIKE 'hutang_piutang_id'");
    if ($hasil && $hasil->num_rows === 0) {
        $koneksi->query(
            "ALTER TABLE jurnal ADD COLUMN hutang_piutang_id INT NULL DEFAULT NULL AFTER jenis_transaksi"
        );
    }

    // Tambah kolom nominal_bayar jika belum ada (menyimpan jumlah yang dibayar via jurnal ini)
    $hasil2 = $koneksi->query("SHOW COLUMNS FROM jurnal LIKE 'nominal_bayar'");
    if ($hasil2 && $hasil2->num_rows === 0) {
        $koneksi->query(
            "ALTER TABLE jurnal ADD COLUMN nominal_bayar DECIMAL(18,2) NULL DEFAULT NULL AFTER hutang_piutang_id"
        );
    }

    $sudahDicek = true;
}

function sinkronisasi_skema_tahun_buku()
{
    static $sudahDicek = false;
    global $koneksi;

    if ($sudahDicek || schema_sudah_siap() || !database_terpasang()) {
        return;
    }

    $koneksi->query(
        "CREATE TABLE IF NOT EXISTS tahun_buku (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(120) NOT NULL,
            tanggal_mulai DATE NOT NULL,
            tanggal_selesai DATE NOT NULL,
            aktif TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $koneksi->query(
        "CREATE TABLE IF NOT EXISTS saldo_awal_akun (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tahun_buku_id INT NOT NULL,
            akun_id INT NOT NULL,
            nominal DECIMAL(18,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_saldo_awal_tahun_akun (tahun_buku_id, akun_id),
            CONSTRAINT fk_saldo_awal_tahun_buku FOREIGN KEY (tahun_buku_id) REFERENCES tahun_buku(id) ON DELETE CASCADE,
            CONSTRAINT fk_saldo_awal_akun FOREIGN KEY (akun_id) REFERENCES akun(id) ON DELETE CASCADE
        )"
    );

    $dataTahunBuku = kueri_satu('SELECT COUNT(*) AS total, COALESCE(SUM(aktif), 0) AS total_aktif FROM tahun_buku');
    $totalTahunBuku = (int) ($dataTahunBuku['total'] ?? 0);
    $totalAktif = (int) ($dataTahunBuku['total_aktif'] ?? 0);

    if ($totalTahunBuku === 0) {
        $tahunSekarang = date('Y');
        $nama = 'Tahun Buku ' . $tahunSekarang;
        $tanggalMulai = $tahunSekarang . '-01-01';
        $tanggalSelesai = $tahunSekarang . '-12-31';

        $stmt = $koneksi->prepare('INSERT INTO tahun_buku (nama, tanggal_mulai, tanggal_selesai, aktif) VALUES (?, ?, ?, 1)');
        $stmt->bind_param('sss', $nama, $tanggalMulai, $tanggalSelesai);
        $stmt->execute();
        $stmt->close();
    } elseif ($totalAktif === 0) {
        $tahunBukuTerbaru = kueri_satu('SELECT id FROM tahun_buku ORDER BY tanggal_mulai DESC, id DESC LIMIT 1');
        if ($tahunBukuTerbaru) {
            $stmt = $koneksi->prepare('UPDATE tahun_buku SET aktif = 1 WHERE id = ?');
            $idTahunBuku = (int) $tahunBukuTerbaru['id'];
            $stmt->bind_param('i', $idTahunBuku);
            $stmt->execute();
            $stmt->close();
        }
    }

    $sudahDicek = true;
}

function daftar_template_akun_rumah_sakit()
{
    return [
        ['kode_akun' => '1-1100', 'nama_akun' => 'Kas', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 1],
        ['kode_akun' => '1-1200', 'nama_akun' => 'Tabungan Bank BNI', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 1],
        ['kode_akun' => '1-1300', 'nama_akun' => 'Tabungan Bank Mandiri', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 1],
        ['kode_akun' => '1-1400', 'nama_akun' => 'Giro Bank Mandiri', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 1],
        ['kode_akun' => '1-1500', 'nama_akun' => 'Piutang BPJS', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-1501', 'nama_akun' => 'Piutang Pasien Umum', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-1600', 'nama_akun' => 'Cadangan Kerugian Piutang', 'kategori' => 'Aset', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '1-1700', 'nama_akun' => 'Persediaan Obat & BHP', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-1800', 'nama_akun' => 'Perlengkapan Kantor', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-1801', 'nama_akun' => 'Perlengkapan Pelayanan', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-1802', 'nama_akun' => 'Alat Tulis Kantor', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-1900', 'nama_akun' => 'Perlengkapan Medis', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-2000', 'nama_akun' => 'PPN Masukan', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-2001', 'nama_akun' => 'Penempatan Dana Koperasi', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-2002', 'nama_akun' => 'Piutang Asuransi', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-2003', 'nama_akun' => 'Piutang Lainnya', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-2004', 'nama_akun' => 'Biaya Dibayar Dimuka', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-2005', 'nama_akun' => 'Uang Muka Pajak PPh 25/29', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-2006', 'nama_akun' => 'Piutang BPJS Ketenagakerjaan', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-2007', 'nama_akun' => 'PPh 23 Dibayar di Muka', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-3100', 'nama_akun' => 'Gedung', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-3110', 'nama_akun' => 'Akm. Penyusutan Gedung', 'kategori' => 'Aset', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '1-3120', 'nama_akun' => 'Kendaraan', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-3130', 'nama_akun' => 'Akm. Penyusutan Kendaraan', 'kategori' => 'Aset', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '1-3140', 'nama_akun' => 'Peralatan Kantor', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-3150', 'nama_akun' => 'Akm. Penyusutan Peralatan Kantor', 'kategori' => 'Aset', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '1-3160', 'nama_akun' => 'Peralatan Medis', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-3170', 'nama_akun' => 'Akm. Penyusutan Medis', 'kategori' => 'Aset', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '1-3180', 'nama_akun' => 'Tanah', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-3190', 'nama_akun' => 'Bangunan Lainnya', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '1-3200', 'nama_akun' => 'Akm. Bangunan Lainnya', 'kategori' => 'Aset', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1100', 'nama_akun' => 'Hutang Usaha', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1200', 'nama_akun' => 'Hutang Obat & BHP', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1300', 'nama_akun' => 'Hutang Optik', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1400', 'nama_akun' => 'Hutang Pajak PPh 21', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1401', 'nama_akun' => 'Hutang Pajak PPh 21 - Rekanan', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1500', 'nama_akun' => 'PPN Keluaran', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1600', 'nama_akun' => 'Hutang Gaji Karyawan', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1601', 'nama_akun' => 'Hutang Insentif Asisten Operasi', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1700', 'nama_akun' => 'Hutang Jasa dr. Spesialis Pasien Umum', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1701', 'nama_akun' => 'Hutang Jasa dr. Spesialis Pasien BPJS', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1800', 'nama_akun' => 'Hutang Jasa dr. Umum Pasien Umum', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1801', 'nama_akun' => 'Hutang Jasa dr. Umum Pasien BPJS', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1900', 'nama_akun' => 'Hutang Lain-lain', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1901', 'nama_akun' => 'Hutang Listrik & Telepon', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1902', 'nama_akun' => 'Hutang Pajak PPh 25/29', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1903', 'nama_akun' => 'Hutang Pajak PPh 23', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1904', 'nama_akun' => 'Hutang Biaya', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1905', 'nama_akun' => 'Hutang Sewa', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1906', 'nama_akun' => 'Hutang Pajak PPh Final', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-1907', 'nama_akun' => 'Hutang Pajak PPN', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-2100', 'nama_akun' => 'Hutang Jangka Panjang', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '2-2200', 'nama_akun' => 'Hutang Bank Mandiri', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '3-1100', 'nama_akun' => 'Modal', 'kategori' => 'Ekuitas', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '3-1200', 'nama_akun' => 'Tambahan Modal', 'kategori' => 'Ekuitas', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '3-8000', 'nama_akun' => 'Saldo Laba', 'kategori' => 'Ekuitas', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '3-8001', 'nama_akun' => 'Dividen', 'kategori' => 'Ekuitas', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '3-9000', 'nama_akun' => 'Laba Tahun Berjalan', 'kategori' => 'Ekuitas', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '3-9001', 'nama_akun' => 'Ikhtisar Laba Rugi', 'kategori' => 'Ekuitas', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1100', 'nama_akun' => 'Pendapatan Umum', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1101', 'nama_akun' => 'Pendapatan BPJS', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1102', 'nama_akun' => 'Retur Penjualan', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '4-1103', 'nama_akun' => 'Pendapatan Asuransi', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1104', 'nama_akun' => 'Pendapatan Asuransi (Reliance)', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1105', 'nama_akun' => 'Pendapatan BPJS Ketenagakerjaan', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1106', 'nama_akun' => 'Potongan Pendapatan BPJS', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '4-1110', 'nama_akun' => 'Pendapatan Rawat Jalan', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1111', 'nama_akun' => 'Pendapatan Rawat Inap', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1112', 'nama_akun' => 'Pendapatan IGD', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1113', 'nama_akun' => 'Pendapatan Laboratorium', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1114', 'nama_akun' => 'Pendapatan Radiologi', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1115', 'nama_akun' => 'Pendapatan Farmasi', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '4-1116', 'nama_akun' => 'Pendapatan Tindakan Operasi', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '5-1000', 'nama_akun' => 'Harga Pokok Barang Yang Dijual', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '5-1200', 'nama_akun' => 'Biaya Angkut Pembelian Barang', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '5-1300', 'nama_akun' => 'Potongan Pembelian', 'kategori' => 'Beban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '5-1301', 'nama_akun' => 'Retur Pembelian', 'kategori' => 'Beban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '5-1400', 'nama_akun' => 'Biaya Rujukan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-1100', 'nama_akun' => 'Biaya Iklan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-1200', 'nama_akun' => 'Biaya Konsumsi', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-1300', 'nama_akun' => 'Biaya Listrik', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-1400', 'nama_akun' => 'Biaya Telepon', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-1500', 'nama_akun' => 'Biaya Sewa', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-1600', 'nama_akun' => 'Biaya Penghapusan Piutang', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-1700', 'nama_akun' => 'Biaya Administrasi', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-1900', 'nama_akun' => 'Biaya Gaji Dan Upah', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2000', 'nama_akun' => 'Biaya Perlengkapan Kantor', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2100', 'nama_akun' => 'Biaya Alat Tulis Kantor', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2200', 'nama_akun' => 'Biaya BBM', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2300', 'nama_akun' => 'Biaya Fotocopy', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2400', 'nama_akun' => 'Biaya Kebersihan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2500', 'nama_akun' => 'Biaya Materai', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2600', 'nama_akun' => 'Biaya Perjalanan Dinas', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2700', 'nama_akun' => 'Biaya BPJS Kesehatan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2701', 'nama_akun' => 'Biaya BPJS Ketenagakerjaan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2800', 'nama_akun' => 'Biaya Lain-lain', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2900', 'nama_akun' => 'Biaya Jasa dr. Spesialis Pasien Umum', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-2901', 'nama_akun' => 'Biaya Jasa dr. Spesialis Pasien BPJS', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3000', 'nama_akun' => 'Biaya Jasa dr. Umum Pasien Umum', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3001', 'nama_akun' => 'Biaya Jasa dr. Umum Pasien BPJS', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3100', 'nama_akun' => 'Insentif Asisten Operasi', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3101', 'nama_akun' => 'Biaya PPh 21', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3102', 'nama_akun' => 'Biaya Pemeliharaan Inventaris', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3103', 'nama_akun' => 'Biaya Renovasi', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3104', 'nama_akun' => 'Biaya Pajak Lain-lain', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3105', 'nama_akun' => 'Biaya Internet', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3106', 'nama_akun' => 'Biaya PPh 25/29', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3107', 'nama_akun' => 'Biaya Akreditasi', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3108', 'nama_akun' => 'Biaya Kirim', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3109', 'nama_akun' => 'Biaya Penyusutan Gedung', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3110', 'nama_akun' => 'Biaya Penyusutan Kendaraan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3111', 'nama_akun' => 'Biaya Penyusutan Peralatan Kantor', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3112', 'nama_akun' => 'Biaya Penyusutan Peralatan Medis', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3113', 'nama_akun' => 'Biaya PPh Pasal 4 Ayat (2)', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3114', 'nama_akun' => 'Biaya Penghapusan Piutang Khusus', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3115', 'nama_akun' => 'Biaya Penyusutan Bangunan Lainnya', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3116', 'nama_akun' => 'Biaya Upah Pegawai Tidak Tetap', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3117', 'nama_akun' => 'Biaya Promosi', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3118', 'nama_akun' => 'Biaya Pemeliharaan Gedung', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3119', 'nama_akun' => 'Biaya Limbah', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3120', 'nama_akun' => 'Biaya Tol & Parkir', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3121', 'nama_akun' => 'Biaya Transportasi', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3122', 'nama_akun' => 'Biaya Pelatihan Karyawan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3123', 'nama_akun' => 'Biaya Konsultan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3124', 'nama_akun' => 'Biaya Denda Pajak', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3125', 'nama_akun' => 'Biaya Sumbangan', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '6-3126', 'nama_akun' => 'Biaya Pajak PPN', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '8-1100', 'nama_akun' => 'Pendapatan Bunga', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '8-1200', 'nama_akun' => 'Pendapatan Dividen', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '8-1300', 'nama_akun' => 'Pendapatan Lain-lain', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '8-1400', 'nama_akun' => 'Laba (Rugi) Penjualan Saham', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '8-1500', 'nama_akun' => 'Laba (Rugi) Penjualan Peralatan', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        ['kode_akun' => '9-1100', 'nama_akun' => 'Biaya Bunga', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '9-1200', 'nama_akun' => 'Biaya Administrasi Bank', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        ['kode_akun' => '9-1300', 'nama_akun' => 'Biaya Lain-lain', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
    ];
}

function migrasi_akun_legacy_ke_template_rumah_sakit()
{
    global $koneksi;

    $petaLegacy = [
        '101' => ['kode_akun' => '1-1100', 'nama_akun' => 'Kas', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 1],
        '102' => ['kode_akun' => '1-1200', 'nama_akun' => 'Tabungan Bank BNI', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 1],
        '103' => ['kode_akun' => '1-1501', 'nama_akun' => 'Piutang Pasien Umum', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        '104' => ['kode_akun' => '1-1700', 'nama_akun' => 'Persediaan Obat & BHP', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        '105' => ['kode_akun' => '1-3140', 'nama_akun' => 'Peralatan Kantor', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        '106' => ['kode_akun' => '1-2000', 'nama_akun' => 'PPN Masukan', 'kategori' => 'Aset', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        '201' => ['kode_akun' => '2-1100', 'nama_akun' => 'Hutang Usaha', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        '202' => ['kode_akun' => '2-1500', 'nama_akun' => 'PPN Keluaran', 'kategori' => 'Kewajiban', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        '301' => ['kode_akun' => '3-1100', 'nama_akun' => 'Modal', 'kategori' => 'Ekuitas', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        '401' => ['kode_akun' => '4-1100', 'nama_akun' => 'Pendapatan Umum', 'kategori' => 'Pendapatan', 'tipe_saldo' => 'Kredit', 'is_kas' => 0],
        '501' => ['kode_akun' => '6-2800', 'nama_akun' => 'Biaya Lain-lain', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        '502' => ['kode_akun' => '6-1900', 'nama_akun' => 'Biaya Gaji Dan Upah', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
        '503' => ['kode_akun' => '6-1300', 'nama_akun' => 'Biaya Listrik', 'kategori' => 'Beban', 'tipe_saldo' => 'Debit', 'is_kas' => 0],
    ];

    $stmtUpdate = $koneksi->prepare('UPDATE akun SET kode_akun = ?, nama_akun = ?, kategori = ?, tipe_saldo = ?, is_kas = ?, aktif = 1 WHERE kode_akun = ?');
    $stmtDeactivate = $koneksi->prepare('UPDATE akun SET aktif = 0 WHERE kode_akun = ?');

    foreach ($petaLegacy as $kodeLama => $akunBaru) {
        $akunLama = kueri_satu("SELECT id FROM akun WHERE kode_akun = '" . $koneksi->real_escape_string($kodeLama) . "' LIMIT 1");
        if (!$akunLama) {
            continue;
        }

        $akunSudahAda = kueri_satu("SELECT id FROM akun WHERE kode_akun = '" . $koneksi->real_escape_string($akunBaru['kode_akun']) . "' LIMIT 1");
        if ($akunSudahAda) {
            $stmtDeactivate->bind_param('s', $kodeLama);
            $stmtDeactivate->execute();
            continue;
        }

        $stmtUpdate->bind_param('ssssis', $akunBaru['kode_akun'], $akunBaru['nama_akun'], $akunBaru['kategori'], $akunBaru['tipe_saldo'], $akunBaru['is_kas'], $kodeLama);
        $stmtUpdate->execute();
    }

    $stmtUpdate->close();
    $stmtDeactivate->close();
}

function terapkan_template_akun_rumah_sakit($perbaruiYangSudahAda = false)
{
    // Fitur impor template dinonaktifkan agar tidak mengacaukan daftar akun yang sudah dirapikan.
    return;

    global $koneksi;

    if (!database_terpasang()) {
        return;
    }

    if ($perbaruiYangSudahAda) {
        migrasi_akun_legacy_ke_template_rumah_sakit();
    }

    $akunTemplate = daftar_template_akun_rumah_sakit();
    $sql = 'INSERT INTO akun (kode_akun, nama_akun, kategori, tipe_saldo, is_kas, aktif) VALUES (?, ?, ?, ?, ?, 1)';
    if ($perbaruiYangSudahAda) {
        $sql .= ' ON DUPLICATE KEY UPDATE nama_akun = VALUES(nama_akun), kategori = VALUES(kategori), tipe_saldo = VALUES(tipe_saldo), is_kas = VALUES(is_kas), aktif = 1';
    } else {
        $sql .= ' ON DUPLICATE KEY UPDATE aktif = aktif';
    }

    $stmt = $koneksi->prepare($sql);
    foreach ($akunTemplate as $akun) {
        $stmt->bind_param('ssssi', $akun['kode_akun'], $akun['nama_akun'], $akun['kategori'], $akun['tipe_saldo'], $akun['is_kas']);
        $stmt->execute();
    }
    $stmt->close();
}

function sinkronisasi_skema_users()
{
    static $sudahDicek = false;
    global $koneksi;

    if ($sudahDicek || schema_sudah_siap() || !database_terpasang()) {
        return;
    }

    $koneksi->query(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            nama_lengkap VARCHAR(100) NOT NULL,
            role ENUM('admin', 'akuntan', 'pimpinan') NOT NULL DEFAULT 'akuntan',
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );

    // Cek apakah tabel users ada (agar tidak fatal error jika CREATE TABLE gagal karena hak akses)
    $cekTabel = $koneksi->query("SHOW TABLES LIKE 'users'");
    if ($cekTabel && $cekTabel->num_rows > 0) {
        $dataUser = kueri_satu('SELECT COUNT(*) AS total FROM users');
        $totalUser = (int) ($dataUser['total'] ?? 0);
        if ($totalUser === 0) {
            $username = 'admin';
            $passwordHash = '$2y$10$K874CWBXketWi9Phwwyafe0HoU82HLo5hegEobuUC.jBmcpuGE6X.';
            $namaLengkap = 'Administrator';
            $role = 'admin';

            $stmt = $koneksi->prepare('INSERT INTO users (username, password, nama_lengkap, role, aktif) VALUES (?, ?, ?, ?, 1)');
            if ($stmt) {
                $stmt->bind_param('ssss', $username, $passwordHash, $namaLengkap, $role);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    $sudahDicek = true;
}

function sinkronisasi_akun_default()
{
    static $sudahDicek = false;

    // Jika flag schema_ready sudah ada, tidak perlu insert akun default lagi
    if ($sudahDicek || schema_sudah_siap() || !database_terpasang()) {
        return;
    }

    // terapkan_template_akun_rumah_sakit(false); // Dinonaktifkan
    $sudahDicek = true;
}

if (basename($_SERVER['PHP_SELF']) !== 'install.php' && !database_terpasang()) {
    header('Location: install.php');
    exit;
}

if (PHP_SAPI !== 'cli') {
    $halamanSekarang = basename($_SERVER['PHP_SELF']);
    $halamanBebasAkses = ['login.php', 'install.php', 'reset_admin.php'];
    if (!in_array($halamanSekarang, $halamanBebasAkses, true)) {
        // Cek apakah tabel users sudah ada. Jika belum ada, jangan kunci halaman demi kelancaran setup.
        global $koneksi;
        $cekTabel = $koneksi->query("SHOW TABLES LIKE 'users'");
        if ($cekTabel && $cekTabel->num_rows > 0) {
            if (!isset($_SESSION['user_id'])) {
                header('Location: login.php');
                exit;
            }
        }
    }
}

function ambil_pengaturan()
{
    static $pengaturan = null;

    // Jalankan semua sinkronisasi schema hanya jika flag belum ada.
    // Setelah flag ada, blok ini jadi no-op (cepat).
    if (!schema_sudah_siap()) {
        sinkronisasi_skema_pengaturan();
        sinkronisasi_skema_tahun_buku();
        sinkronisasi_skema_akun();
        sinkronisasi_skema_jurnal();
        sinkronisasi_skema_users();
        sinkronisasi_akun_default();
        // Tandai bahwa semua skema dan data default sudah siap
        tandai_schema_selesai();
    }

    if ($pengaturan !== null) {
        return $pengaturan;
    }

    $pengaturan = kueri_satu("SELECT * FROM pengaturan ORDER BY id ASC LIMIT 1");

    if (!$pengaturan) {
        $pengaturan = [
            'nama_perusahaan' => 'Aplikasi Akuntansi Sederhana',
            'alamat' => '',
            'telepon' => '',
            'email' => '',
            'logo' => '',
        ];
    }

    return $pengaturan;
}

function path_logo_perusahaan($pathRelatif)
{
    $pathRelatif = str_replace(['..\\', '../'], '', (string) $pathRelatif);
    return __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pathRelatif);
}

function hapus_logo_lokal($pathRelatif)
{
    if ($pathRelatif === '') {
        return;
    }

    $pathPenuh = path_logo_perusahaan($pathRelatif);
    if (is_file($pathPenuh)) {
        @unlink($pathPenuh);
    }
}

function simpan_file_logo($file, $logoLama = '')
{
    if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $logoLama;
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload logo gagal. Silakan coba lagi.');
    }

    if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new Exception('Ukuran logo maksimal 2 MB.');
    }

    $ekstensi = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $ekstensiDiizinkan = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ekstensi, $ekstensiDiizinkan, true)) {
        throw new Exception('Format logo harus JPG, PNG, WEBP, atau GIF.');
    }

    $mimeDiizinkan = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string) finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }

    if ($mime !== '' && !in_array($mime, $mimeDiizinkan, true)) {
        throw new Exception('Berkas logo tidak valid.');
    }

    $folderUpload = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($folderUpload) && !mkdir($folderUpload, 0777, true) && !is_dir($folderUpload)) {
        throw new Exception('Folder upload tidak bisa dibuat.');
    }

    $namaBaru = 'logo-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ekstensi;
    $pathRelatif = 'uploads/' . $namaBaru;
    $tujuan = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $namaBaru;

    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        throw new Exception('Logo gagal disimpan ke server.');
    }

    if ($logoLama !== '' && $logoLama !== $pathRelatif) {
        hapus_logo_lokal($logoLama);
    }

    return $pathRelatif;
}

function render_header($judul, $halamanAktif = '')
{
    $pengaturan = ambil_pengaturan();
    $flash = ambil_flash();
    $logoPerusahaan = $pengaturan['logo'] ?? '';
    $menu = [
        'dashboard' => ['label' => 'Dashboard', 'url' => 'index.php'],
        'akun' => ['label' => 'Daftar Akun', 'url' => 'akun.php'],
        'jurnal' => ['label' => 'Jurnal Umum', 'url' => 'jurnal_umum.php'],
        'jurnal_penyesuaian' => ['label' => 'Jurnal Penyesuaian', 'url' => 'jurnal_penyesuaian.php'],
        'buku_besar' => ['label' => 'Buku Besar', 'url' => 'buku_besar.php'],
        'hutang' => ['label' => 'Hutang', 'url' => 'hutang.php'],
        'piutang' => ['label' => 'Piutang', 'url' => 'piutang.php'],
        'arus_kas' => ['label' => 'Arus Kas', 'url' => 'arus_kas.php'],
        'neraca' => ['label' => 'Neraca', 'url' => 'neraca.php'],
        'laba_rugi' => ['label' => 'Laba Rugi', 'url' => 'laba_rugi.php'],
        'perubahan_ekuitas' => ['label' => 'Perubahan Ekuitas', 'url' => 'perubahan_ekuitas.php'],
        'tahun_buku' => ['label' => 'Tahun Buku', 'url' => 'tahun_buku.php'],
    ];

    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $menu['pengguna'] = ['label' => 'Kelola Pengguna', 'url' => 'pengguna.php'];
    }

    $menu['pengaturan'] = ['label' => 'Pengaturan', 'url' => 'pengaturan.php'];
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e($judul); ?> - <?php echo e($pengaturan['nama_perusahaan']); ?></title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="layout">
        <aside class="sidebar">
            <div>
                <?php if ($logoPerusahaan !== '') { ?>
                    <div class="logo-company sidebar-logo">
                        <img src="<?php echo e($logoPerusahaan); ?>" alt="Logo <?php echo e($pengaturan['nama_perusahaan']); ?>">
                    </div>
                <?php } ?>
                <p class="eyebrow">Aplikasi Akuntansi</p>
                <h1><?php echo e($pengaturan['nama_perusahaan']); ?></h1>
                <p class="subtle">Pencatatan jurnal, buku besar, hutang, piutang, arus kas, dan neraca.</p>
            </div>
            <nav class="menu">
                <?php foreach ($menu as $kunci => $item) { ?>
                    <a href="<?php echo e($item['url']); ?>" class="<?php echo $halamanAktif === $kunci ? 'aktif' : ''; ?>"><?php echo e($item['label']); ?></a>
                <?php } ?>
            </nav>
            <?php if (isset($_SESSION['nama_lengkap'])) { ?>
                <div class="user-profile-box">
                    <div class="user-avatar">👤</div>
                    <div class="user-details">
                        <span class="user-name"><?php echo e($_SESSION['nama_lengkap']); ?></span>
                        <span class="user-role"><?php echo e(ucfirst($_SESSION['role'])); ?></span>
                    </div>
                    <a href="logout.php" class="button-logout" title="Keluar">🚪</a>
                </div>
            <?php } ?>
        </aside>
        <main class="main-content">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Sistem Informasi Akuntansi</p>
                    <h2><?php echo e($judul); ?></h2>
                </div>
                <div class="company-meta">
                    <?php if ($logoPerusahaan !== '') { ?>
                        <div class="logo-company header-logo">
                            <img src="<?php echo e($logoPerusahaan); ?>" alt="Logo <?php echo e($pengaturan['nama_perusahaan']); ?>">
                        </div>
                    <?php } ?>
                    <?php if (!empty($pengaturan['alamat'])) { ?>
                        <span><?php echo e($pengaturan['alamat']); ?></span>
                    <?php } ?>
                    <?php if (!empty($pengaturan['telepon'])) { ?>
                        <span><?php echo e($pengaturan['telepon']); ?></span>
                    <?php } ?>
                </div>
            </header>
            <?php if ($flash) { ?>
                <div class="flash <?php echo e($flash['jenis']); ?>"><?php echo e($flash['pesan']); ?></div>
            <?php } ?>
    <?php
}

function render_footer()
{
    ?>
        </main>
    </div>
    </body>
    </html>
    <?php
}

function ambil_daftar_akun()
{
    // Sinkronisasi hanya diperlukan jika schema belum siap (ditangani oleh ambil_pengaturan)
    return kueri_semua(
        "SELECT a.*,
                p.nama_akun AS nama_induk,
                p.kode_akun AS kode_induk,
                (SELECT COUNT(*) FROM akun anak WHERE anak.parent_id = a.id AND anak.aktif = 1) AS jumlah_sub_akun
         FROM akun a
         LEFT JOIN akun p ON p.id = a.parent_id
         WHERE a.aktif = 1
         ORDER BY COALESCE(a.parent_id, a.id), a.parent_id IS NULL DESC, a.kode_akun ASC"
    );
}

function ambil_akun_untuk_jurnal()
{
    // Hanya kembalikan akun leaf (tidak punya sub-akun aktif) agar akun induk
    // tidak bisa dipakai langsung di baris jurnal.
    return kueri_semua(
        "SELECT a.*,
                p.nama_akun AS nama_induk,
                p.kode_akun AS kode_induk
         FROM akun a
         LEFT JOIN akun p ON p.id = a.parent_id
         WHERE a.aktif = 1
           AND NOT EXISTS (
               SELECT 1 FROM akun anak WHERE anak.parent_id = a.id AND anak.aktif = 1
           )
         ORDER BY a.kode_akun ASC"
    );
}

function ambil_akun_by_id($id)
{
    global $koneksi;

    $id = (int) $id;
    $stmt = $koneksi->prepare('SELECT * FROM akun WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $akun = $hasil ? $hasil->fetch_assoc() : null;
    $stmt->close();

    return $akun;
}

function ambil_daftar_kontak($jenis = '')
{
    global $koneksi;

    // sinkronisasi_skema_kontak sudah ditangani oleh ambil_pengaturan()
    if ($jenis === '') {
        return kueri_semua("SELECT * FROM kontak WHERE aktif = 1 ORDER BY nama ASC");
    }

    $stmt = $koneksi->prepare("SELECT * FROM kontak WHERE aktif = 1 AND jenis = ? ORDER BY nama ASC");
    $stmt->bind_param('s', $jenis);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $data = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $data;
}

function ambil_daftar_tahun_buku()
{
    sinkronisasi_skema_tahun_buku();

    return kueri_semua('SELECT * FROM tahun_buku ORDER BY tanggal_mulai DESC, id DESC');
}

function ambil_tahun_buku_by_id($id)
{
    sinkronisasi_skema_tahun_buku();

    return kueri_satu('SELECT * FROM tahun_buku WHERE id = ' . (int) $id . ' LIMIT 1');
}

function ambil_tahun_buku_aktif()
{
    sinkronisasi_skema_tahun_buku();

    $tahunBuku = kueri_satu('SELECT * FROM tahun_buku WHERE aktif = 1 ORDER BY tanggal_mulai DESC, id DESC LIMIT 1');
    if ($tahunBuku) {
        return $tahunBuku;
    }

    return kueri_satu('SELECT * FROM tahun_buku ORDER BY tanggal_mulai DESC, id DESC LIMIT 1');
}

function ambil_batas_tahun_buku($tanggalHingga = '')
{
    $tahunBuku = ambil_tahun_buku_aktif();
    if (!$tahunBuku) {
        return [null, '', ''];
    }

    $tanggalMulai = $tahunBuku['tanggal_mulai'];
    $tanggalSelesai = $tahunBuku['tanggal_selesai'];

    if ($tanggalHingga !== '') {
        if ($tanggalHingga < $tanggalMulai) {
            return [$tahunBuku, '', ''];
        }

        if ($tanggalHingga < $tanggalSelesai) {
            $tanggalSelesai = $tanggalHingga;
        }
    }

    return [$tahunBuku, $tanggalMulai, $tanggalSelesai];
}

function ambil_saldo_awal_akun($tahunBukuId)
{
    global $koneksi;

    // Cache statis: data saldo awal untuk tahun buku yang sama tidak perlu
    // di-query ulang dalam satu request (mencegah N+1 dari hitung_saldo_akun).
    static $cache = [];

    $tahunBukuId = (int) $tahunBukuId;
    if ($tahunBukuId <= 0) {
        return [];
    }

    if (isset($cache[$tahunBukuId])) {
        return $cache[$tahunBukuId];
    }

    $stmt = $koneksi->prepare('SELECT akun_id, nominal FROM saldo_awal_akun WHERE tahun_buku_id = ?');
    $stmt->bind_param('i', $tahunBukuId);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $baris = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    $saldoAwal = [];
    foreach ($baris as $item) {
        $saldoAwal[(int) $item['akun_id']] = (float) $item['nominal'];
    }

    $cache[$tahunBukuId] = $saldoAwal;
    return $saldoAwal;
}

function hitung_mutasi_saldo($tipeSaldo, $debit, $kredit)
{
    if ($tipeSaldo === 'Debit') {
        return (float) $debit - (float) $kredit;
    }

    return (float) $kredit - (float) $debit;
}

function simpan_akun($data)
{
    global $koneksi;

    sinkronisasi_skema_akun();

    $kodeAkun  = trim($data['kode_akun'] ?? '');
    $namaAkun  = trim($data['nama_akun'] ?? '');
    $kategori  = trim($data['kategori'] ?? 'Aset');
    $tipeSaldo = trim($data['tipe_saldo'] ?? 'Debit');
    $isKas     = isset($data['is_kas']) ? 1 : 0;
    $parentId  = ($data['parent_id'] ?? '') !== '' ? (int) $data['parent_id'] : null;

    if ($kodeAkun === '' || $namaAkun === '') {
        throw new Exception('Kode akun dan nama akun wajib diisi.');
    }

    // Validasi akun induk: harus ada, aktif, dan bukan sub-akun lain
    if ($parentId !== null) {
        $induk = kueri_satu('SELECT id, parent_id FROM akun WHERE id = ' . $parentId . ' AND aktif = 1 LIMIT 1');
        if (!$induk) {
            throw new Exception('Akun induk yang dipilih tidak ditemukan atau tidak aktif.');
        }
        if (!empty($induk['parent_id'])) {
            throw new Exception('Akun induk tidak boleh merupakan sub-akun dari akun lain (maksimal 2 level).');
        }
    }

    $stmt = $koneksi->prepare('INSERT INTO akun (kode_akun, parent_id, nama_akun, kategori, tipe_saldo, is_kas, aktif) VALUES (?, ?, ?, ?, ?, ?, 1)');
    $stmt->bind_param('sisssi', $kodeAkun, $parentId, $namaAkun, $kategori, $tipeSaldo, $isKas);

    if (!$stmt->execute()) {
        $pesan = $stmt->error;
        $stmt->close();
        throw new Exception('Gagal menyimpan akun: ' . $pesan);
    }

    $stmt->close();
}

function ubah_akun($id, $data)
{
    global $koneksi;

    sinkronisasi_skema_akun();

    $id = (int) $id;
    $akun = ambil_akun_by_id($id);
    if (!$akun || (int) $akun['aktif'] !== 1) {
        throw new Exception('Akun yang akan diubah tidak ditemukan.');
    }

    $kodeAkun  = trim($data['kode_akun'] ?? '');
    $namaAkun  = trim($data['nama_akun'] ?? '');
    $kategori  = trim($data['kategori'] ?? 'Aset');
    $tipeSaldo = trim($data['tipe_saldo'] ?? 'Debit');
    $isKas     = isset($data['is_kas']) ? 1 : 0;
    $parentId  = ($data['parent_id'] ?? '') !== '' ? (int) $data['parent_id'] : null;

    if ($kodeAkun === '' || $namaAkun === '') {
        throw new Exception('Kode akun dan nama akun wajib diisi.');
    }

    // Tidak boleh menjadikan dirinya sendiri sebagai induk
    if ($parentId === $id) {
        throw new Exception('Akun tidak bisa menjadi induk dari dirinya sendiri.');
    }

    // Tidak boleh menjadi sub-akun jika akun ini sendiri sudah punya sub-akun
    if ($parentId !== null) {
        $cekSubAkun = kueri_satu('SELECT COUNT(*) AS total FROM akun WHERE parent_id = ' . $id . ' AND aktif = 1');
        if ((int) ($cekSubAkun['total'] ?? 0) > 0) {
            throw new Exception('Akun yang sudah punya sub-akun tidak bisa dijadikan sub-akun dari akun lain.');
        }

        $induk = kueri_satu('SELECT id, parent_id FROM akun WHERE id = ' . $parentId . ' AND aktif = 1 LIMIT 1');
        if (!$induk) {
            throw new Exception('Akun induk yang dipilih tidak ditemukan atau tidak aktif.');
        }
        if (!empty($induk['parent_id'])) {
            throw new Exception('Akun induk tidak boleh merupakan sub-akun dari akun lain (maksimal 2 level).');
        }
    }

    $stmt = $koneksi->prepare('UPDATE akun SET kode_akun = ?, parent_id = ?, nama_akun = ?, kategori = ?, tipe_saldo = ?, is_kas = ? WHERE id = ?');
    $stmt->bind_param('sissiii', $kodeAkun, $parentId, $namaAkun, $kategori, $tipeSaldo, $isKas, $id);

    if (!$stmt->execute()) {
        $pesan = $stmt->error;
        $stmt->close();
        throw new Exception('Gagal mengubah akun: ' . $pesan);
    }

    $stmt->close();
}

function hapus_akun($id)
{
    global $koneksi;

    $id = (int) $id;
    $akun = ambil_akun_by_id($id);
    if (!$akun || (int) $akun['aktif'] !== 1) {
        throw new Exception('Akun yang akan dihapus tidak ditemukan.');
    }

    // Cek apakah akun ini masih punya sub-akun aktif
    $cekSubAkun = kueri_satu('SELECT COUNT(*) AS total FROM akun WHERE parent_id = ' . $id . ' AND aktif = 1');
    if ((int) ($cekSubAkun['total'] ?? 0) > 0) {
        throw new Exception('Akun induk tidak bisa dihapus selama masih memiliki sub-akun aktif.');
    }

    $stmtCek = $koneksi->prepare('SELECT COUNT(*) AS total FROM jurnal_detail WHERE akun_id = ?');
    $stmtCek->bind_param('i', $id);
    $stmtCek->execute();
    $hasil = $stmtCek->get_result();
    $pemakaian = $hasil ? $hasil->fetch_assoc() : ['total' => 0];
    $stmtCek->close();

    if ((int) $pemakaian['total'] > 0) {
        throw new Exception('Akun tidak bisa dihapus karena sudah dipakai pada jurnal.');
    }

    $stmt = $koneksi->prepare('UPDATE akun SET aktif = 0 WHERE id = ?');
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $pesan = $stmt->error;
        $stmt->close();
        throw new Exception('Gagal menghapus akun: ' . $pesan);
    }

    $stmt->close();
}

function simpan_kontak($data)
{
    global $koneksi;

    sinkronisasi_skema_kontak();

    $kodeKontak = trim($data['kode_kontak'] ?? '');
    $nama = trim($data['nama'] ?? '');
    $jenis = trim($data['jenis'] ?? 'Pelanggan');
    $telepon = trim($data['telepon'] ?? '');
    $alamat = trim($data['alamat'] ?? '');

    if ($nama === '') {
        throw new Exception('Nama kontak wajib diisi.');
    }

    $stmtCek = $koneksi->prepare('SELECT id FROM kontak WHERE LOWER(nama) = LOWER(?) AND jenis = ? AND aktif = 1 LIMIT 1');
    $stmtCek->bind_param('ss', $nama, $jenis);
    $stmtCek->execute();
    $hasilCek = $stmtCek->get_result();
    $kontakAda = $hasilCek ? $hasilCek->fetch_assoc() : null;
    $stmtCek->close();

    if ($kontakAda) {
        throw new Exception('Kontak dengan nama dan jenis yang sama sudah ada.');
    }

    if ($kodeKontak !== '') {
        $stmtKode = $koneksi->prepare('SELECT id FROM kontak WHERE LOWER(kode_kontak) = LOWER(?) LIMIT 1');
        $stmtKode->bind_param('s', $kodeKontak);
        $stmtKode->execute();
        $hasilKode = $stmtKode->get_result();
        $kodeAda = $hasilKode ? $hasilKode->fetch_assoc() : null;
        $stmtKode->close();

        if ($kodeAda) {
            throw new Exception('Kode kontak sudah digunakan.');
        }
    }

    $stmt = $koneksi->prepare('INSERT INTO kontak (kode_kontak, nama, jenis, telepon, alamat, aktif) VALUES (?, ?, ?, ?, ?, 1)');
    $stmt->bind_param('sssss', $kodeKontak, $nama, $jenis, $telepon, $alamat);

    if (!$stmt->execute()) {
        $pesan = $stmt->error;
        $stmt->close();
        throw new Exception('Gagal menyimpan kontak: ' . $pesan);
    }

    $stmt->close();
}

function simpan_kontak_jika_belum_ada($nama, $jenis = 'Pemasok', $telepon = '', $alamat = '')
{
    global $koneksi;

    sinkronisasi_skema_kontak();

    $nama = trim((string) $nama);
    $jenis = trim((string) $jenis);
    $telepon = trim((string) $telepon);
    $alamat = trim((string) $alamat);

    if ($nama === '') {
        return false;
    }

    $stmtCek = $koneksi->prepare('SELECT id FROM kontak WHERE LOWER(nama) = LOWER(?) AND jenis = ? LIMIT 1');
    $stmtCek->bind_param('ss', $nama, $jenis);
    $stmtCek->execute();
    $hasilCek = $stmtCek->get_result();
    $kontakAda = $hasilCek ? $hasilCek->fetch_assoc() : null;
    $stmtCek->close();

    if ($kontakAda) {
        $stmtAktif = $koneksi->prepare('UPDATE kontak SET aktif = 1 WHERE id = ?');
        $idKontak = (int) $kontakAda['id'];
        $stmtAktif->bind_param('i', $idKontak);
        $stmtAktif->execute();
        $stmtAktif->close();

        return false;
    }

    $stmt = $koneksi->prepare('INSERT INTO kontak (nama, jenis, telepon, alamat, aktif) VALUES (?, ?, ?, ?, 1)');
    $stmt->bind_param('ssss', $nama, $jenis, $telepon, $alamat);
    $berhasil = $stmt->execute();
    $stmt->close();

    return $berhasil;
}

function simpan_kontak_berkode_jika_belum_ada($kodeKontak, $nama, $jenis = 'Pemasok', $telepon = '', $alamat = '')
{
    global $koneksi;

    sinkronisasi_skema_kontak();

    $kodeKontak = trim((string) $kodeKontak);
    $nama = trim((string) $nama);
    $jenis = trim((string) $jenis);
    $telepon = trim((string) $telepon);
    $alamat = trim((string) $alamat);

    if ($nama === '') {
        return false;
    }

    if ($kodeKontak !== '') {
        $stmtKode = $koneksi->prepare('SELECT id FROM kontak WHERE LOWER(kode_kontak) = LOWER(?) LIMIT 1');
        $stmtKode->bind_param('s', $kodeKontak);
        $stmtKode->execute();
        $hasilKode = $stmtKode->get_result();
        $kontakAda = $hasilKode ? $hasilKode->fetch_assoc() : null;
        $stmtKode->close();

        if ($kontakAda) {
            $stmtAktif = $koneksi->prepare('UPDATE kontak SET aktif = 1 WHERE id = ?');
            $idKontak = (int) $kontakAda['id'];
            $stmtAktif->bind_param('i', $idKontak);
            $stmtAktif->execute();
            $stmtAktif->close();

            return false;
        }
    }

    $stmtNama = $koneksi->prepare('SELECT id FROM kontak WHERE LOWER(nama) = LOWER(?) AND jenis = ? LIMIT 1');
    $stmtNama->bind_param('ss', $nama, $jenis);
    $stmtNama->execute();
    $hasilNama = $stmtNama->get_result();
    $kontakNamaAda = $hasilNama ? $hasilNama->fetch_assoc() : null;
    $stmtNama->close();

    if ($kontakNamaAda) {
        $stmtUpdate = $koneksi->prepare('UPDATE kontak SET kode_kontak = COALESCE(NULLIF(kode_kontak, \'\'), ?), aktif = 1 WHERE id = ?');
        $idKontak = (int) $kontakNamaAda['id'];
        $stmtUpdate->bind_param('si', $kodeKontak, $idKontak);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        return false;
    }

    $stmt = $koneksi->prepare('INSERT INTO kontak (kode_kontak, nama, jenis, telepon, alamat, aktif) VALUES (?, ?, ?, ?, ?, 1)');
    $stmt->bind_param('sssss', $kodeKontak, $nama, $jenis, $telepon, $alamat);
    $berhasil = $stmt->execute();
    $stmt->close();

    return $berhasil;
}

function validasi_detail_jurnal($data)
{
    $akunIds = $data['akun_id'] ?? [];
    $debitList = $data['debit'] ?? [];
    $kreditList = $data['kredit'] ?? [];
    $detail = [];
    $totalDebit = 0;
    $totalKredit = 0;

    foreach ($akunIds as $index => $akunId) {
        $akunId = (int) $akunId;
        $debit = (float) str_replace(',', '', $debitList[$index] ?? 0);
        $kredit = (float) str_replace(',', '', $kreditList[$index] ?? 0);

        if ($akunId === 0 && $debit === 0.0 && $kredit === 0.0) {
            continue;
        }

        if ($akunId === 0) {
            throw new Exception('Setiap baris jurnal harus memilih akun.');
        }

        if ($debit < 0 || $kredit < 0) {
            throw new Exception('Nilai debit dan kredit tidak boleh negatif.');
        }

        if ($debit === 0.0 && $kredit === 0.0) {
            throw new Exception('Setiap baris jurnal harus memiliki nilai debit atau kredit.');
        }

        $detail[] = [
            'akun_id' => $akunId,
            'debit' => $debit,
            'kredit' => $kredit,
        ];

        $totalDebit += $debit;
        $totalKredit += $kredit;
    }

    if (count($detail) < 2) {
        throw new Exception('Jurnal minimal terdiri dari dua baris.');
    }

    if (round($totalDebit, 2) !== round($totalKredit, 2)) {
        throw new Exception('Total debit dan kredit harus seimbang.');
    }

    return [$detail, $totalDebit, $totalKredit];
}

function ambil_relasi_by_jurnal_id($jurnalId)
{
    global $koneksi;

    $jurnalId = (int) $jurnalId;
    $stmt = $koneksi->prepare('SELECT * FROM hutang_piutang WHERE jurnal_id = ? ORDER BY id ASC LIMIT 1');
    $stmt->bind_param('i', $jurnalId);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $relasi = $hasil ? $hasil->fetch_assoc() : null;
    $stmt->close();

    return $relasi;
}

function ambil_hutang_belum_lunas_by_kontak($kontakId, $jenis = 'Hutang')
{
    global $koneksi;

    sinkronisasi_skema_jurnal();

    $kontakId = (int) $kontakId;
    if ($kontakId <= 0) {
        return [];
    }

    $stmt = $koneksi->prepare(
        "SELECT hp.*, k.nama AS nama_kontak
         FROM hutang_piutang hp
         JOIN kontak k ON k.id = hp.kontak_id
         WHERE hp.kontak_id = ? AND hp.jenis = ? AND hp.status != 'Lunas'
         ORDER BY hp.jatuh_tempo ASC, hp.tanggal ASC"
    );
    $stmt->bind_param('is', $kontakId, $jenis);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $data = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    foreach ($data as &$baris) {
        $baris['sisa'] = round((float) $baris['nominal'] - (float) $baris['dibayar'], 2);
    }

    return $data;
}

function pastikan_jurnal_bisa_dimutasi($jurnalId)
{
    $relasi = ambil_relasi_by_jurnal_id($jurnalId);
    if ($relasi && round((float) ($relasi['dibayar'] ?? 0), 2) > 0) {
        throw new Exception('Jurnal tidak bisa diubah atau dihapus karena hutang/piutangnya sudah memiliki pembayaran.');
    }

    return $relasi;
}

function ambil_jurnal_by_id($jurnalId)
{
    global $koneksi;

    $jurnalId = (int) $jurnalId;
    $stmtJurnal = $koneksi->prepare('SELECT * FROM jurnal WHERE id = ? LIMIT 1');
    $stmtJurnal->bind_param('i', $jurnalId);
    $stmtJurnal->execute();
    $hasilJurnal = $stmtJurnal->get_result();
    $jurnal = $hasilJurnal ? $hasilJurnal->fetch_assoc() : null;
    $stmtJurnal->close();

    if (!$jurnal) {
        return null;
    }

    $stmtDetail = $koneksi->prepare('SELECT akun_id, debit, kredit FROM jurnal_detail WHERE jurnal_id = ? ORDER BY id ASC');
    $stmtDetail->bind_param('i', $jurnalId);
    $stmtDetail->execute();
    $hasilDetail = $stmtDetail->get_result();
    $detail = $hasilDetail ? $hasilDetail->fetch_all(MYSQLI_ASSOC) : [];
    $stmtDetail->close();

    $jurnal['detail'] = $detail;
    $jurnal['relasi'] = ambil_relasi_by_jurnal_id($jurnalId);

    // Include info hutang_piutang_id dan nominal_bayar untuk jurnal jenis Bayar Hutang
    // (kolom ini mungkin belum ada jika belum dimigrasi)
    if (!isset($jurnal['hutang_piutang_id'])) {
        $jurnal['hutang_piutang_id'] = null;
        $jurnal['nominal_bayar']     = null;
    }

    return $jurnal;
}

function normalisasi_input_jurnal($data)
{
    $tanggal = $data['tanggal'] ?? date('Y-m-d');
    $nomorBukti = trim($data['nomor_bukti'] ?? '');
    $keterangan = trim($data['keterangan'] ?? '');
    $jenisTransaksi = trim($data['jenis_transaksi'] ?? 'Umum');
    $kontakId = (int) ($data['kontak_id'] ?? 0);
    $jatuhTempo = trim($data['jatuh_tempo'] ?? '');
    $nominalRelasi = (float) str_replace(',', '', $data['nominal_relasi'] ?? 0);
    $hutangPiutangId = (int) ($data['hutang_piutang_id'] ?? 0);
    $tahunBukuAktif = ambil_tahun_buku_aktif();

    if ($nomorBukti === '') {
        if ($jenisTransaksi === 'Penyesuaian') {
            $nomorBukti = 'AJP-' . date('YmdHis');
        } else {
            $nomorBukti = 'JR-' . date('YmdHis');
        }
    } elseif ($jenisTransaksi === 'Penyesuaian') {
        if (stripos($nomorBukti, 'AJP-') !== 0 && stripos($nomorBukti, 'TEST-AJP-') !== 0) {
            $nomorBukti = 'AJP-' . $nomorBukti;
        }
    }

    if ($tahunBukuAktif && ($tanggal < $tahunBukuAktif['tanggal_mulai'] || $tanggal > $tahunBukuAktif['tanggal_selesai'])) {
        throw new Exception('Tanggal jurnal harus berada dalam tahun buku aktif: ' . $tahunBukuAktif['nama'] . '.');
    }

    // Validasi khusus untuk jenis Bayar Hutang / Terima Piutang
    if (in_array($jenisTransaksi, ['Bayar Hutang', 'Terima Piutang'], true)) {
        if ($hutangPiutangId <= 0) {
            throw new Exception('Pilih faktur yang akan dilunasi.');
        }
        if ($nominalRelasi <= 0) {
            throw new Exception('Nominal pembayaran harus diisi.');
        }
    }

    [$detail] = validasi_detail_jurnal($data);

    return [
        'tanggal'           => $tanggal,
        'nomor_bukti'       => $nomorBukti,
        'keterangan'        => $keterangan,
        'jenis_transaksi'   => $jenisTransaksi,
        'kontak_id'         => $kontakId,
        'jatuh_tempo'       => $jatuhTempo,
        'nominal_relasi'    => $nominalRelasi,
        'hutang_piutang_id' => $hutangPiutangId,
        'detail'            => $detail,
    ];
}

function simpan_detail_jurnal($jurnalId, array $detail)
{
    global $koneksi;

    $stmtDetail = $koneksi->prepare('INSERT INTO jurnal_detail (jurnal_id, akun_id, debit, kredit) VALUES (?, ?, ?, ?)');

    foreach ($detail as $baris) {
        $stmtDetail->bind_param('iidd', $jurnalId, $baris['akun_id'], $baris['debit'], $baris['kredit']);

        if (!$stmtDetail->execute()) {
            throw new Exception('Gagal menyimpan detail jurnal: ' . $stmtDetail->error);
        }
    }

    $stmtDetail->close();
}

function rollback_pembayaran_jurnal($jurnalId)
{
    global $koneksi;

    // Baca data jurnal untuk tahu apakah ada pembayaran yang perlu di-rollback
    $jurnalLama = kueri_satu(
        "SELECT jenis_transaksi, hutang_piutang_id, nominal_bayar FROM jurnal WHERE id = $jurnalId LIMIT 1"
    );

    if (
        $jurnalLama
        && in_array($jurnalLama['jenis_transaksi'], ['Bayar Hutang', 'Terima Piutang'], true)
        && (int) ($jurnalLama['hutang_piutang_id'] ?? 0) > 0
        && (float) ($jurnalLama['nominal_bayar'] ?? 0) > 0
    ) {
        $hpId        = (int) $jurnalLama['hutang_piutang_id'];
        $nominalLama = (float) $jurnalLama['nominal_bayar'];

        $hp = kueri_satu("SELECT nominal, dibayar FROM hutang_piutang WHERE id = $hpId LIMIT 1");
        if ($hp) {
            $dibayarBaru = max(0, round((float) $hp['dibayar'] - $nominalLama, 2));
            $nominal     = (float) $hp['nominal'];
            $status      = $dibayarBaru <= 0 ? 'Belum Lunas' : ($dibayarBaru < $nominal ? 'Sebagian' : 'Lunas');

            $stmt = $koneksi->prepare('UPDATE hutang_piutang SET dibayar = ?, status = ? WHERE id = ?');
            $stmt->bind_param('dsi', $dibayarBaru, $status, $hpId);
            $stmt->execute();
            $stmt->close();
        }

        // Reset kolom pembayaran di jurnal
        $koneksi->query("UPDATE jurnal SET hutang_piutang_id = NULL, nominal_bayar = NULL WHERE id = $jurnalId");
    }
}

function sinkronkan_relasi_jurnal($jurnalId, array $inputJurnal)
{
    global $koneksi;

    // Rollback pembayaran lama terlebih dahulu (untuk kasus edit jurnal)
    rollback_pembayaran_jurnal($jurnalId);

    // Hapus record hutang_piutang yang langsung terhubung ke jurnal ini (jenis Hutang/Piutang)
    $stmtHapus = $koneksi->prepare('DELETE FROM hutang_piutang WHERE jurnal_id = ?');
    $stmtHapus->bind_param('i', $jurnalId);
    $stmtHapus->execute();
    $stmtHapus->close();

    if (in_array($inputJurnal['jenis_transaksi'], ['Hutang', 'Piutang'], true)
        && $inputJurnal['kontak_id'] > 0
        && $inputJurnal['nominal_relasi'] > 0
    ) {
        // Jurnal pembelian/piutang baru → buat record hutang_piutang
        $stmtRelasi = $koneksi->prepare(
            'INSERT INTO hutang_piutang (jurnal_id, kontak_id, jenis, tanggal, jatuh_tempo, keterangan, nominal, dibayar, status) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)'
        );
        $status           = 'Belum Lunas';
        $jatuhTempoSimpan = $inputJurnal['jatuh_tempo'] !== '' ? $inputJurnal['jatuh_tempo'] : null;
        $stmtRelasi->bind_param(
            'iissssds',
            $jurnalId,
            $inputJurnal['kontak_id'],
            $inputJurnal['jenis_transaksi'],
            $inputJurnal['tanggal'],
            $jatuhTempoSimpan,
            $inputJurnal['keterangan'],
            $inputJurnal['nominal_relasi'],
            $status
        );

        if (!$stmtRelasi->execute()) {
            throw new Exception('Gagal menyimpan data hutang/piutang: ' . $stmtRelasi->error);
        }

        $stmtRelasi->close();

    } elseif (in_array($inputJurnal['jenis_transaksi'], ['Bayar Hutang', 'Terima Piutang'], true)
        && $inputJurnal['hutang_piutang_id'] > 0
        && $inputJurnal['nominal_relasi'] > 0
    ) {
        // Jurnal pelunasan → update hutang_piutang yang dipilih
        $hpId        = $inputJurnal['hutang_piutang_id'];
        $nominalBayar = $inputJurnal['nominal_relasi'];

        $hp = kueri_satu("SELECT nominal, dibayar FROM hutang_piutang WHERE id = $hpId LIMIT 1");
        if (!$hp) {
            throw new Exception('Faktur hutang/piutang yang dipilih tidak ditemukan.');
        }

        $dibayarBaru = round((float) $hp['dibayar'] + $nominalBayar, 2);
        if ($dibayarBaru > round((float) $hp['nominal'], 2)) {
            throw new Exception('Jumlah pembayaran melebihi sisa tagihan faktur.');
        }

        $status = $dibayarBaru <= 0 ? 'Belum Lunas' : (
            $dibayarBaru < (float) $hp['nominal'] ? 'Sebagian' : 'Lunas'
        );

        $stmtUpdate = $koneksi->prepare('UPDATE hutang_piutang SET dibayar = ?, status = ? WHERE id = ?');
        $stmtUpdate->bind_param('dsi', $dibayarBaru, $status, $hpId);
        if (!$stmtUpdate->execute()) {
            throw new Exception('Gagal memperbarui status hutang/piutang: ' . $stmtUpdate->error);
        }
        $stmtUpdate->close();

        // Simpan referensi dan nominal pembayaran ke tabel jurnal
        $stmtJurnal = $koneksi->prepare(
            'UPDATE jurnal SET hutang_piutang_id = ?, nominal_bayar = ? WHERE id = ?'
        );
        $stmtJurnal->bind_param('idi', $hpId, $nominalBayar, $jurnalId);
        $stmtJurnal->execute();
        $stmtJurnal->close();
    }
}

function simpan_jurnal($data)
{
    global $koneksi;
    $inputJurnal = normalisasi_input_jurnal($data);

    $koneksi->begin_transaction();

    try {
        $stmtJurnal = $koneksi->prepare('INSERT INTO jurnal (tanggal, nomor_bukti, keterangan, jenis_transaksi) VALUES (?, ?, ?, ?)');
        $stmtJurnal->bind_param('ssss', $inputJurnal['tanggal'], $inputJurnal['nomor_bukti'], $inputJurnal['keterangan'], $inputJurnal['jenis_transaksi']);

        if (!$stmtJurnal->execute()) {
            // Fallback jika jenis_transaksi = 'Penyesuaian' ditolak oleh ENUM database
            if ($inputJurnal['jenis_transaksi'] === 'Penyesuaian' && stripos($stmtJurnal->error, 'truncated') !== false) {
                $stmtJurnal->close();
                $jenisFallback = 'Umum';
                if (stripos($inputJurnal['nomor_bukti'], 'AJP-') !== 0 && stripos($inputJurnal['nomor_bukti'], 'TEST-AJP-') !== 0) {
                    $inputJurnal['nomor_bukti'] = 'AJP-' . $inputJurnal['nomor_bukti'];
                }
                $stmtJurnal = $koneksi->prepare('INSERT INTO jurnal (tanggal, nomor_bukti, keterangan, jenis_transaksi) VALUES (?, ?, ?, ?)');
                $stmtJurnal->bind_param('ssss', $inputJurnal['tanggal'], $inputJurnal['nomor_bukti'], $inputJurnal['keterangan'], $jenisFallback);
                if (!$stmtJurnal->execute()) {
                    throw new Exception('Gagal menyimpan jurnal (dengan fallback): ' . $stmtJurnal->error);
                }
            } else {
                throw new Exception('Gagal menyimpan jurnal: ' . $stmtJurnal->error);
            }
        }

        $jurnalId = $stmtJurnal->insert_id;
        $stmtJurnal->close();

        simpan_detail_jurnal($jurnalId, $inputJurnal['detail']);
        sinkronkan_relasi_jurnal($jurnalId, $inputJurnal);

        $koneksi->commit();
    } catch (Throwable $exception) {
        $koneksi->rollback();
        throw $exception;
    }
}

function ubah_jurnal($jurnalId, $data)
{
    global $koneksi;

    $jurnalId = (int) $jurnalId;
    $jurnal = ambil_jurnal_by_id($jurnalId);
    if (!$jurnal) {
        throw new Exception('Jurnal yang akan diubah tidak ditemukan.');
    }

    pastikan_jurnal_bisa_dimutasi($jurnalId);
    $inputJurnal = normalisasi_input_jurnal($data);

    $koneksi->begin_transaction();

    try {
        $stmtJurnal = $koneksi->prepare(
            'UPDATE jurnal SET tanggal = ?, nomor_bukti = ?, keterangan = ?, jenis_transaksi = ? WHERE id = ?'
        );
        $stmtJurnal->bind_param(
            'ssssi',
            $inputJurnal['tanggal'],
            $inputJurnal['nomor_bukti'],
            $inputJurnal['keterangan'],
            $inputJurnal['jenis_transaksi'],
            $jurnalId
        );

        if (!$stmtJurnal->execute()) {
            if ($inputJurnal['jenis_transaksi'] === 'Penyesuaian' && stripos($stmtJurnal->error, 'truncated') !== false) {
                $stmtJurnal->close();
                $jenisFallback = 'Umum';
                if (stripos($inputJurnal['nomor_bukti'], 'AJP-') !== 0 && stripos($inputJurnal['nomor_bukti'], 'TEST-AJP-') !== 0) {
                    $inputJurnal['nomor_bukti'] = 'AJP-' . $inputJurnal['nomor_bukti'];
                }
                $stmtJurnal = $koneksi->prepare(
                    'UPDATE jurnal SET tanggal = ?, nomor_bukti = ?, keterangan = ?, jenis_transaksi = ? WHERE id = ?'
                );
                $stmtJurnal->bind_param(
                    'ssssi',
                    $inputJurnal['tanggal'],
                    $inputJurnal['nomor_bukti'],
                    $inputJurnal['keterangan'],
                    $jenisFallback,
                    $jurnalId
                );
                if (!$stmtJurnal->execute()) {
                    throw new Exception('Gagal mengubah jurnal (dengan fallback): ' . $stmtJurnal->error);
                }
            } else {
                throw new Exception('Gagal mengubah jurnal: ' . $stmtJurnal->error);
            }
        }

        $stmtJurnal->close();

        $stmtHapusDetail = $koneksi->prepare('DELETE FROM jurnal_detail WHERE jurnal_id = ?');
        $stmtHapusDetail->bind_param('i', $jurnalId);
        $stmtHapusDetail->execute();
        $stmtHapusDetail->close();

        simpan_detail_jurnal($jurnalId, $inputJurnal['detail']);
        sinkronkan_relasi_jurnal($jurnalId, $inputJurnal);

        $koneksi->commit();
    } catch (Throwable $exception) {
        $koneksi->rollback();
        throw $exception;
    }
}

function hapus_jurnal($jurnalId)
{
    global $koneksi;

    $jurnalId = (int) $jurnalId;
    $jurnal = ambil_jurnal_by_id($jurnalId);
    if (!$jurnal) {
        throw new Exception('Jurnal yang akan dihapus tidak ditemukan.');
    }

    pastikan_jurnal_bisa_dimutasi($jurnalId);

    $koneksi->begin_transaction();

    try {
        // Rollback pembayaran jika jurnal ini adalah jurnal pelunasan
        rollback_pembayaran_jurnal($jurnalId);

        $stmtHapusRelasi = $koneksi->prepare('DELETE FROM hutang_piutang WHERE jurnal_id = ?');
        $stmtHapusRelasi->bind_param('i', $jurnalId);
        $stmtHapusRelasi->execute();
        $stmtHapusRelasi->close();

        $stmtHapusJurnal = $koneksi->prepare('DELETE FROM jurnal WHERE id = ?');
        $stmtHapusJurnal->bind_param('i', $jurnalId);

        if (!$stmtHapusJurnal->execute()) {
            throw new Exception('Gagal menghapus jurnal: ' . $stmtHapusJurnal->error);
        }

        $stmtHapusJurnal->close();
        $koneksi->commit();
    } catch (Throwable $exception) {
        $koneksi->rollback();
        throw $exception;
    }
}

function catat_pembayaran_relasi($idRelasi, $jumlahBayar)
{
    global $koneksi;

    $idRelasi = (int) $idRelasi;
    $jumlahBayar = (float) str_replace(',', '', $jumlahBayar);

    if ($idRelasi <= 0 || $jumlahBayar <= 0) {
        throw new Exception('Data pembayaran tidak valid.');
    }

    $stmt = $koneksi->prepare('SELECT nominal, dibayar FROM hutang_piutang WHERE id = ?');
    $stmt->bind_param('i', $idRelasi);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $relasi = $hasil ? $hasil->fetch_assoc() : null;
    $stmt->close();

    if (!$relasi) {
        throw new Exception('Data hutang/piutang tidak ditemukan.');
    }

    $dibayarBaru = (float) $relasi['dibayar'] + $jumlahBayar;
    if ($dibayarBaru > (float) $relasi['nominal']) {
        throw new Exception('Jumlah pembayaran melebihi sisa tagihan.');
    }

    $status = 'Sebagian';
    if ($dibayarBaru == 0.0) {
        $status = 'Belum Lunas';
    } elseif (round($dibayarBaru, 2) >= round((float) $relasi['nominal'], 2)) {
        $status = 'Lunas';
    }

    $stmtUpdate = $koneksi->prepare('UPDATE hutang_piutang SET dibayar = ?, status = ? WHERE id = ?');
    $stmtUpdate->bind_param('dsi', $dibayarBaru, $status, $idRelasi);

    if (!$stmtUpdate->execute()) {
        $pesan = $stmtUpdate->error;
        $stmtUpdate->close();
        throw new Exception('Gagal memperbarui pembayaran: ' . $pesan);
    }

    $stmtUpdate->close();
}

function simpan_pengaturan($data, $files = [])
{
    global $koneksi;

    sinkronisasi_skema_pengaturan();

    $namaPerusahaan = trim($data['nama_perusahaan'] ?? '');
    $alamat = trim($data['alamat'] ?? '');
    $telepon = trim($data['telepon'] ?? '');
    $email = trim($data['email'] ?? '');
    $hapusLogo = isset($data['hapus_logo']) && (string) $data['hapus_logo'] === '1';
    $pengaturanSebelumnya = ambil_pengaturan();
    $logo = $pengaturanSebelumnya['logo'] ?? '';

    if ($namaPerusahaan === '') {
        throw new Exception('Nama perusahaan wajib diisi.');
    }

    if ($hapusLogo) {
        hapus_logo_lokal($logo);
        $logo = '';
    }

    if (isset($files['logo']) && is_array($files['logo'])) {
        $logo = simpan_file_logo($files['logo'], $logo);
    }

    $stmt = $koneksi->prepare('INSERT INTO pengaturan (id, nama_perusahaan, alamat, telepon, email, logo) VALUES (1, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama_perusahaan = VALUES(nama_perusahaan), alamat = VALUES(alamat), telepon = VALUES(telepon), email = VALUES(email), logo = VALUES(logo)');
    $stmt->bind_param('sssss', $namaPerusahaan, $alamat, $telepon, $email, $logo);

    if (!$stmt->execute()) {
        $pesan = $stmt->error;
        $stmt->close();
        throw new Exception('Gagal menyimpan pengaturan: ' . $pesan);
    }

    $stmt->close();
}

function simpan_tahun_buku($data)
{
    global $koneksi;

    sinkronisasi_skema_tahun_buku();

    $id = (int) ($data['id'] ?? 0);
    $nama = trim($data['nama'] ?? '');
    $tanggalMulai = trim($data['tanggal_mulai'] ?? '');
    $tanggalSelesai = trim($data['tanggal_selesai'] ?? '');

    if ($nama === '' || $tanggalMulai === '' || $tanggalSelesai === '') {
        throw new Exception('Nama, tanggal mulai, dan tanggal selesai tahun buku wajib diisi.');
    }

    if ($tanggalMulai > $tanggalSelesai) {
        throw new Exception('Tanggal mulai tahun buku tidak boleh melebihi tanggal selesai.');
    }

    if ($id > 0 && !ambil_tahun_buku_by_id($id)) {
        throw new Exception('Tahun buku yang akan diperbarui tidak ditemukan.');
    }

    $koneksi->begin_transaction();

    try {
        $koneksi->query('UPDATE tahun_buku SET aktif = 0');

        if ($id > 0) {
            $stmt = $koneksi->prepare('UPDATE tahun_buku SET nama = ?, tanggal_mulai = ?, tanggal_selesai = ?, aktif = 1 WHERE id = ?');
            $stmt->bind_param('sssi', $nama, $tanggalMulai, $tanggalSelesai, $id);
        } else {
            $stmt = $koneksi->prepare('INSERT INTO tahun_buku (nama, tanggal_mulai, tanggal_selesai, aktif) VALUES (?, ?, ?, 1)');
            $stmt->bind_param('sss', $nama, $tanggalMulai, $tanggalSelesai);
        }

        if (!$stmt->execute()) {
            $pesan = $stmt->error;
            $stmt->close();
            throw new Exception('Gagal menyimpan tahun buku: ' . $pesan);
        }

        if ($id === 0) {
            $id = $stmt->insert_id;
        }

        $stmt->close();
        $koneksi->commit();

        return $id;
    } catch (Throwable $exception) {
        $koneksi->rollback();
        throw $exception;
    }
}

function simpan_saldo_awal_akun_tahun($tahunBukuId, $data)
{
    global $koneksi;

    sinkronisasi_skema_tahun_buku();

    $tahunBukuId = (int) $tahunBukuId;
    $tahunBuku = ambil_tahun_buku_by_id($tahunBukuId);
    if (!$tahunBuku) {
        throw new Exception('Tahun buku untuk saldo awal tidak ditemukan.');
    }

    $akun = ambil_daftar_akun();
    $saldoAwalInput = $data['saldo_awal'] ?? [];
    $saldoValid = [];
    $totalDebit = 0;
    $totalKredit = 0;

    foreach ($akun as $baris) {
        $akunId = (int) $baris['id'];
        $nominal = (float) str_replace(',', '', $saldoAwalInput[$akunId] ?? 0);

        if ($nominal < 0) {
            throw new Exception('Saldo awal tidak boleh bernilai negatif.');
        }

        if ($nominal == 0.0) {
            continue;
        }

        $saldoValid[$akunId] = $nominal;

        if ($baris['tipe_saldo'] === 'Debit') {
            $totalDebit += $nominal;
        } else {
            $totalKredit += $nominal;
        }
    }

    if (round($totalDebit, 2) !== round($totalKredit, 2)) {
        throw new Exception('Total saldo awal akun bertipe debit dan kredit harus seimbang.');
    }

    $koneksi->begin_transaction();

    try {
        $stmtHapus = $koneksi->prepare('DELETE FROM saldo_awal_akun WHERE tahun_buku_id = ?');
        $stmtHapus->bind_param('i', $tahunBukuId);
        $stmtHapus->execute();
        $stmtHapus->close();

        $stmtSimpan = $koneksi->prepare('INSERT INTO saldo_awal_akun (tahun_buku_id, akun_id, nominal) VALUES (?, ?, ?)');
        foreach ($saldoValid as $akunId => $nominal) {
            $stmtSimpan->bind_param('iid', $tahunBukuId, $akunId, $nominal);
            if (!$stmtSimpan->execute()) {
                $pesan = $stmtSimpan->error;
                $stmtSimpan->close();
                throw new Exception('Gagal menyimpan saldo awal akun: ' . $pesan);
            }
        }
        $stmtSimpan->close();

        $koneksi->commit();
    } catch (Throwable $exception) {
        $koneksi->rollback();
        throw $exception;
    }

    return [
        'tahun_buku' => $tahunBuku,
        'total_debit' => $totalDebit,
        'total_kredit' => $totalKredit,
    ];
}

function hitung_saldo_akun($akunId, $hinggaTanggal = '')
{
    global $koneksi;

    $akunId = (int) $akunId;
    $akun = kueri_satu('SELECT * FROM akun WHERE id = ' . $akunId . ' LIMIT 1');
    if (!$akun) {
        return 0;
    }

    // Ambil semua sub-akun aktif beserta tipe saldonya dalam satu query
    $subAkun = kueri_semua('SELECT id, tipe_saldo FROM akun WHERE parent_id = ' . $akunId . ' AND aktif = 1');
    
    $daftarId = [$akunId];
    $tipeSaldoMap = [$akunId => $akun['tipe_saldo']];
    foreach ($subAkun as $sub) {
        $idSub = (int) $sub['id'];
        $daftarId[] = $idSub;
        $tipeSaldoMap[$idSub] = $sub['tipe_saldo'];
    }

    [$tahunBuku, $tanggalMulai, $tanggalSelesai] = ambil_batas_tahun_buku($hinggaTanggal);

    $saldoAwalTotal = 0;
    if ($tahunBuku) {
        $saldoAwalPerAkun = ambil_saldo_awal_akun((int) $tahunBuku['id']);
        foreach ($daftarId as $id) {
            $saldoAwalTotal += (float) ($saldoAwalPerAkun[$id] ?? 0);
        }
    }

    if ($tahunBuku && ($tanggalMulai === '' || $tanggalSelesai === '')) {
        return $saldoAwalTotal;
    }

    $placeholders = implode(',', array_fill(0, count($daftarId), '?'));
    $sql = 'SELECT jd.akun_id, COALESCE(SUM(jd.debit), 0) AS total_debit, COALESCE(SUM(jd.kredit), 0) AS total_kredit
            FROM jurnal_detail jd
            INNER JOIN jurnal j ON j.id = jd.jurnal_id
            WHERE jd.akun_id IN (' . $placeholders . ')';
    $types = str_repeat('i', count($daftarId));
    $params = $daftarId;

    if ($tahunBuku) {
        $sql .= ' AND j.tanggal >= ? AND j.tanggal <= ?';
        $types .= 'ss';
        $params[] = $tanggalMulai;
        $params[] = $tanggalSelesai;
    }

    if ($hinggaTanggal !== '') {
        if (!$tahunBuku) {
            $sql .= ' AND j.tanggal <= ?';
            $types .= 's';
            $params[] = $hinggaTanggal;
        }
    }

    $sql .= ' GROUP BY jd.akun_id';

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $mutasiBaris = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    $totalMutasi = 0;
    foreach ($mutasiBaris as $mutasi) {
        $id = (int) $mutasi['akun_id'];
        $tipeSaldo = $tipeSaldoMap[$id] ?? $akun['tipe_saldo'];
        $totalMutasi += hitung_mutasi_saldo($tipeSaldo, $mutasi['total_debit'], $mutasi['total_kredit']);
    }

    return $saldoAwalTotal + $totalMutasi;
}

function ringkasan_dashboard()
{
    global $koneksi;

    $ringkasan = [
        'aset'             => 0,
        'kewajiban'        => 0,
        'ekuitas'          => 0,
        'hutang_berjalan'  => 0,
        'piutang_berjalan' => 0,
    ];

    // Ambil tahun buku aktif sekali
    $tahunBuku = ambil_tahun_buku_aktif();
    $saldoAwal = $tahunBuku ? ambil_saldo_awal_akun((int) $tahunBuku['id']) : [];

    // -----------------------------------------------------------------------
    // SEBELUMNYA: loop foreach akun → 80+ query (1 per akun)
    // SEKARANG  : 1 query agregat JOIN untuk semua akun sekaligus
    // -----------------------------------------------------------------------
    $sql = "SELECT
                a.id,
                a.kode_akun,
                a.kategori,
                a.tipe_saldo,
                a.parent_id,
                COALESCE(SUM(jd.debit), 0)  AS total_debit,
                COALESCE(SUM(jd.kredit), 0) AS total_kredit
            FROM akun a
            LEFT JOIN jurnal_detail jd ON jd.akun_id = a.id
            LEFT JOIN jurnal j ON j.id = jd.jurnal_id";

    $params = [];
    $types  = '';

    if ($tahunBuku) {
        $sql .= " AND j.tanggal >= ? AND j.tanggal <= ?";
        $types    .= 'ss';
        $params[]  = $tahunBuku['tanggal_mulai'];
        $params[]  = $tahunBuku['tanggal_selesai'];
    }

    $sql .= " WHERE a.aktif = 1
                AND a.kategori IN ('Aset', 'Kewajiban', 'Ekuitas')
              GROUP BY a.id, a.kode_akun, a.kategori, a.tipe_saldo, a.parent_id";

    $stmt = $koneksi->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $hasil = $stmt->get_result();
    $barisAkun = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    // Hitung saldo per akun (mutasi + saldo awal) dan akumulasi per kategori
    // Akun induk (parent_id IS NULL yang punya anak) diabaikan dari penjumlahan
    // karena saldo-nya sudah tercakup di sub-akun.
    $akunIdDenganAnak = [];
    foreach ($barisAkun as $b) {
        if ($b['parent_id'] !== null) {
            $akunIdDenganAnak[(int) $b['parent_id']] = true;
        }
    }

    $labaRugi = hitung_laba_rugi_bersih($tahunBuku ? $tahunBuku['tanggal_mulai'] : '', $tahunBuku ? $tahunBuku['tanggal_selesai'] : '');
    $labaBersih = $labaRugi['laba_bersih'];

    foreach ($barisAkun as $b) {
        $id = (int) $b['id'];

        // Lewati akun induk — saldo-nya sudah terhitung via sub-akun
        if (isset($akunIdDenganAnak[$id])) {
            continue;
        }

        if ($b['kode_akun'] === '3-9000') {
            $saldo = $labaBersih;
        } else {
            $saldoAkunIni = (float) ($saldoAwal[$id] ?? 0);
            $mutasi       = hitung_mutasi_saldo($b['tipe_saldo'], $b['total_debit'], $b['total_kredit']);
            $saldo        = $saldoAkunIni + $mutasi;
        }

        if ($b['kategori'] === 'Aset') {
            $ringkasan['aset'] += $saldo;
        } elseif ($b['kategori'] === 'Kewajiban') {
            $ringkasan['kewajiban'] += $saldo;
        } elseif ($b['kategori'] === 'Ekuitas') {
            $ringkasan['ekuitas'] += $saldo;
        }
    }

    // Hutang & piutang berjalan: 1 query agregat
    $relasiRows = kueri_semua("SELECT jenis, COALESCE(SUM(nominal - dibayar), 0) AS sisa
                               FROM hutang_piutang
                               WHERE status != 'Lunas'
                               GROUP BY jenis");
    foreach ($relasiRows as $baris) {
        if ($baris['jenis'] === 'Hutang') {
            $ringkasan['hutang_berjalan'] = (float) $baris['sisa'];
        }
        if ($baris['jenis'] === 'Piutang') {
            $ringkasan['piutang_berjalan'] = (float) $baris['sisa'];
        }
    }

    return $ringkasan;
}

function ambil_jurnal_terbaru($batas = 10, $excludePenyesuaian = false)
{
    $batas = (int) $batas;

    $tahunBuku = ambil_tahun_buku_aktif();
    $conds = [];
    if ($tahunBuku) {
        $conds[] = "j.tanggal >= '" . addslashes($tahunBuku['tanggal_mulai']) . "' AND j.tanggal <= '" . addslashes($tahunBuku['tanggal_selesai']) . "'";
    }
    if ($excludePenyesuaian) {
        $conds[] = "j.jenis_transaksi != 'Penyesuaian' AND j.nomor_bukti NOT LIKE 'AJP-%' AND j.nomor_bukti NOT LIKE 'TEST-AJP-%'";
    }

    $where = '';
    if (!empty($conds)) {
        $where = " WHERE " . implode(" AND ", $conds);
    }

    return kueri_semua(
        'SELECT j.*, COALESCE(SUM(jd.debit), 0) AS total_nominal
         FROM jurnal j
         LEFT JOIN jurnal_detail jd ON jd.jurnal_id = j.id
         ' . $where . '
         GROUP BY j.id
         ORDER BY j.tanggal DESC, j.id DESC
         LIMIT ' . $batas
    );
}

function laporan_buku_besar($akunId, $tanggalMulai = '', $tanggalSelesai = '')
{
    global $koneksi;

    $akunId = (int) $akunId;
    $akun = kueri_satu('SELECT * FROM akun WHERE id = ' . $akunId . ' LIMIT 1');
    if (!$akun) {
        return null;
    }

    $tahunBuku = ambil_tahun_buku_aktif();
    if ($tahunBuku) {
        if ($tanggalMulai === '' || $tanggalMulai < $tahunBuku['tanggal_mulai']) {
            $tanggalMulai = $tahunBuku['tanggal_mulai'];
        }

        if ($tanggalSelesai === '' || $tanggalSelesai > $tahunBuku['tanggal_selesai']) {
            $tanggalSelesai = $tahunBuku['tanggal_selesai'];
        }
    }

    if ($tanggalSelesai !== '' && $tanggalMulai !== '' && $tanggalMulai > $tanggalSelesai) {
        $tanggalSelesai = $tanggalMulai;
    }

    $saldoAwal = 0;
    if ($tanggalMulai !== '') {
        $tanggalSebelumnya = date('Y-m-d', strtotime($tanggalMulai . ' -1 day'));
        $saldoAwal = hitung_saldo_akun($akunId, $tanggalSebelumnya);
    }

    // Ambil daftar sub-akun aktif jika ada
    $subAkun = kueri_semua('SELECT id FROM akun WHERE parent_id = ' . $akunId . ' AND aktif = 1');
    $isInduk = !empty($subAkun);

    $daftarId = [$akunId];
    foreach ($subAkun as $sub) {
        $daftarId[] = (int) $sub['id'];
    }

    $placeholders = implode(',', array_fill(0, count($daftarId), '?'));

    $sql = 'SELECT j.tanggal, j.nomor_bukti, j.keterangan, jd.debit, jd.kredit, jd.akun_id, a.kode_akun, a.nama_akun
            FROM jurnal_detail jd
            INNER JOIN jurnal j ON j.id = jd.jurnal_id
            INNER JOIN akun a ON a.id = jd.akun_id
            WHERE jd.akun_id IN (' . $placeholders . ')';
    $types = str_repeat('i', count($daftarId));
    $params = $daftarId;

    if ($tanggalMulai !== '') {
        $sql .= ' AND j.tanggal >= ?';
        $types .= 's';
        $params[] = $tanggalMulai;
    }

    if ($tanggalSelesai !== '') {
        $sql .= ' AND j.tanggal <= ?';
        $types .= 's';
        $params[] = $tanggalSelesai;
    }

    $sql .= ' ORDER BY j.tanggal ASC, j.id ASC, jd.id ASC';
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $baris = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    $saldoBerjalan = $saldoAwal;
    foreach ($baris as &$item) {
        $saldoBerjalan += hitung_mutasi_saldo($akun['tipe_saldo'], $item['debit'], $item['kredit']);
        $item['saldo'] = $saldoBerjalan;

        // Prepend sub-account info if this is a parent ledger view and the transaction is from a sub-account
        if ($isInduk && (int) $item['akun_id'] !== $akunId) {
            $item['keterangan'] = '[' . $item['kode_akun'] . ' - ' . $item['nama_akun'] . '] ' . $item['keterangan'];
        }
    }
    unset($item);

    return [
        'akun' => $akun,
        'tahun_buku' => $tahunBuku,
        'saldo_awal' => $saldoAwal,
        'baris' => $baris,
        'saldo_akhir' => $saldoBerjalan,
    ];
}

function laporan_arus_kas($tanggalMulai = '', $tanggalSelesai = '')
{
    global $koneksi;

    $tahunBuku = ambil_tahun_buku_aktif();
    if ($tahunBuku) {
        if ($tanggalMulai === '' || $tanggalMulai < $tahunBuku['tanggal_mulai']) {
            $tanggalMulai = $tahunBuku['tanggal_mulai'];
        }

        if ($tanggalSelesai === '' || $tanggalSelesai > $tahunBuku['tanggal_selesai']) {
            $tanggalSelesai = $tahunBuku['tanggal_selesai'];
        }
    }

    $sql = "SELECT j.tanggal, j.nomor_bukti, j.keterangan, a.nama_akun, jd.debit, jd.kredit
            FROM jurnal_detail jd
            INNER JOIN jurnal j ON j.id = jd.jurnal_id
            INNER JOIN akun a ON a.id = jd.akun_id
            WHERE a.is_kas = 1";
    $types = '';
    $params = [];

    if ($tanggalMulai !== '') {
        $sql .= ' AND j.tanggal >= ?';
        $types .= 's';
        $params[] = $tanggalMulai;
    }

    if ($tanggalSelesai !== '') {
        $sql .= ' AND j.tanggal <= ?';
        $types .= 's';
        $params[] = $tanggalSelesai;
    }

    $sql .= ' ORDER BY j.tanggal ASC, j.id ASC, jd.id ASC';
    $stmt = $koneksi->prepare($sql);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $hasil = $stmt->get_result();
    $baris = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    $totalMasuk = 0;
    $totalKeluar = 0;
    foreach ($baris as &$item) {
        $arus = (float) $item['debit'] - (float) $item['kredit'];
        $item['arus'] = $arus;
        if ($arus >= 0) {
            $totalMasuk += $arus;
        } else {
            $totalKeluar += abs($arus);
        }
    }
    unset($item);

    return [
        'baris' => $baris,
        'total_masuk' => $totalMasuk,
        'total_keluar' => $totalKeluar,
        'saldo_bersih' => $totalMasuk - $totalKeluar,
    ];
}

function ringkasan_neraca()
{
    global $koneksi;

    $hasil = [
        'Aset'            => [],
        'Kewajiban'       => [],
        'Ekuitas'         => [],
        'total_aset'      => 0,
        'total_kewajiban' => 0,
        'total_ekuitas'   => 0,
    ];

    // Ambil tahun buku aktif sekali
    $tahunBuku = ambil_tahun_buku_aktif();
    $saldoAwal = $tahunBuku ? ambil_saldo_awal_akun((int) $tahunBuku['id']) : [];

    // -----------------------------------------------------------------------
    // SEBELUMNYA: loop foreach akun → 80+ query (1 per akun)
    // SEKARANG  : 1 query agregat JOIN untuk semua akun sekaligus
    // -----------------------------------------------------------------------
    $sql = "SELECT
                a.id,
                a.kode_akun,
                a.nama_akun,
                a.kategori,
                a.tipe_saldo,
                a.parent_id,
                COALESCE(SUM(jd.debit), 0)  AS total_debit,
                COALESCE(SUM(jd.kredit), 0) AS total_kredit
            FROM akun a
            LEFT JOIN jurnal_detail jd ON jd.akun_id = a.id
            LEFT JOIN jurnal j ON j.id = jd.jurnal_id";

    $params = [];
    $types  = '';

    if ($tahunBuku) {
        $sql .= " AND j.tanggal >= ? AND j.tanggal <= ?";
        $types    .= 'ss';
        $params[]  = $tahunBuku['tanggal_mulai'];
        $params[]  = $tahunBuku['tanggal_selesai'];
    }

    $sql .= " WHERE a.aktif = 1
                AND a.kategori IN ('Aset', 'Kewajiban', 'Ekuitas')
              GROUP BY a.id, a.kode_akun, a.nama_akun, a.kategori, a.tipe_saldo, a.parent_id
              ORDER BY a.kode_akun ASC";

    $stmt = $koneksi->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $barisAkun = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    // Mapping akun by id
    $labaRugi = hitung_laba_rugi_bersih($tahunBuku ? $tahunBuku['tanggal_mulai'] : '', $tahunBuku ? $tahunBuku['tanggal_selesai'] : '');
    $labaBersih = $labaRugi['laba_bersih'];

    $akunMap = [];
    foreach ($barisAkun as $b) {
        $id = (int) $b['id'];
        if ($b['kode_akun'] === '3-9000') {
            $saldo = $labaBersih;
        } else {
            $saldoAkunIni = (float) ($saldoAwal[$id] ?? 0);
            $mutasi       = hitung_mutasi_saldo($b['tipe_saldo'], $b['total_debit'], $b['total_kredit']);
            $saldo        = $saldoAkunIni + $mutasi;
        }
        
        $b['saldo_sendiri'] = $saldo;
        $b['saldo_total']   = $saldo;
        $akunMap[$id]       = $b;
    }

    // Roll up (akumulasi) saldo ke akun induk (parent)
    foreach ($akunMap as $id => $akun) {
        $saldo = $akun['saldo_sendiri'];
        if ($saldo == 0) {
            continue;
        }
        
        $parentId = $akun['parent_id'];
        while ($parentId !== null && isset($akunMap[(int)$parentId])) {
            $akunMap[(int)$parentId]['saldo_total'] += $saldo;
            $parentId = $akunMap[(int)$parentId]['parent_id'];
        }
    }

    // Ambil hanya akun tingkat atas (Akun Utama / parent_id IS NULL)
    foreach ($akunMap as $id => $akun) {
        if ($akun['parent_id'] === null) {
            $saldoTotal = $akun['saldo_total'];
            if (abs($saldoTotal) < 0.005) {
                continue;
            }

            $hasil[$akun['kategori']][] = [
                'kode_akun' => $akun['kode_akun'],
                'nama_akun' => $akun['nama_akun'],
                'saldo'     => $saldoTotal,
            ];

            if ($akun['kategori'] === 'Aset') {
                $hasil['total_aset'] += $saldoTotal;
            } elseif ($akun['kategori'] === 'Kewajiban') {
                $hasil['total_kewajiban'] += $saldoTotal;
            } elseif ($akun['kategori'] === 'Ekuitas') {
                $hasil['total_ekuitas'] += $saldoTotal;
            }
        }
    }

    return $hasil;
}

function ambil_relasi_hutang_piutang($filter = [])
{
    global $koneksi;

    sinkronisasi_skema_jurnal();

    $jenis    = trim($filter['jenis'] ?? '');
    $status   = trim($filter['status'] ?? '');
    $kontakId = (int) ($filter['kontak_id'] ?? 0);

    // Sertakan jurnal_bayar_id: ID jurnal yang melunasi faktur ini (jika ada)
    $sql = "SELECT hp.*, k.nama AS nama_kontak, k.jenis AS jenis_kontak, k.kode_kontak,
                   (hp.nominal - hp.dibayar) AS sisa,
                   j_bayar.id AS jurnal_bayar_id
            FROM hutang_piutang hp
            INNER JOIN kontak k ON k.id = hp.kontak_id
            LEFT JOIN jurnal j_bayar ON j_bayar.hutang_piutang_id = hp.id
            WHERE 1 = 1";
    $types  = '';
    $params = [];

    if ($jenis !== '' && in_array($jenis, ['Hutang', 'Piutang'], true)) {
        $sql    .= ' AND hp.jenis = ?';
        $types  .= 's';
        $params[] = $jenis;
    }

    if ($status !== '' && in_array($status, ['Belum Lunas', 'Sebagian', 'Lunas'], true)) {
        $sql    .= ' AND hp.status = ?';
        $types  .= 's';
        $params[] = $status;
    }

    if ($kontakId > 0) {
        $sql    .= ' AND hp.kontak_id = ?';
        $types  .= 'i';
        $params[] = $kontakId;
    }

    $sql .= ' ORDER BY hp.tanggal DESC, hp.id DESC';
    $stmt = $koneksi->prepare($sql);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $hasil = $stmt->get_result();
    $data  = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $data;
}

function ambil_daftar_pengguna()
{
    return kueri_semua("SELECT id, username, nama_lengkap, role, aktif FROM users ORDER BY username ASC");
}

function tambah_pengguna($data)
{
    global $koneksi;

    $username = trim($data['username'] ?? '');
    $namaLengkap = trim($data['nama_lengkap'] ?? '');
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'akuntan';

    if ($username === '' || $namaLengkap === '' || $password === '') {
        throw new Exception('Semua kolom harus diisi.');
    }

    if (strlen($password) < 6) {
        throw new Exception('Password minimal harus 6 karakter.');
    }

    if (!in_array($role, ['admin', 'akuntan', 'pimpinan'], true)) {
        throw new Exception('Role tidak valid.');
    }

    // Cek apakah username sudah ada
    $cek = kueri_satu("SELECT id FROM users WHERE username = '" . $koneksi->real_escape_string($username) . "' LIMIT 1");
    if ($cek) {
        throw new Exception('Username sudah digunakan.');
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role, aktif) VALUES (?, ?, ?, ?, 1)");
    if (!$stmt) {
        throw new Exception('Gagal menyiapkan query database.');
    }

    $stmt->bind_param('ssss', $username, $passwordHash, $namaLengkap, $role);
    $sukses = $stmt->execute();
    $stmt->close();

    if (!$sukses) {
        throw new Exception('Gagal menyimpan pengguna baru.');
    }
}

function toggle_status_pengguna($id)
{
    global $koneksi;
    $id = (int) $id;

    if ($id === (int) $_SESSION['user_id']) {
        throw new Exception('Anda tidak dapat menonaktifkan akun Anda sendiri.');
    }

    $user = kueri_satu("SELECT aktif FROM users WHERE id = {$id} LIMIT 1");
    if (!$user) {
        throw new Exception('Pengguna tidak ditemukan.');
    }

    $statusBaru = (int) $user['aktif'] === 1 ? 0 : 1;
    $koneksi->query("UPDATE users SET aktif = {$statusBaru} WHERE id = {$id}");
}

function ubah_password_sendiri($passwordLama, $passwordBaru, $konfirmasi)
{
    global $koneksi;
    $userId = (int) $_SESSION['user_id'];

    if ($passwordLama === '' || $passwordBaru === '' || $konfirmasi === '') {
        throw new Exception('Semua kolom password harus diisi.');
    }

    if ($passwordBaru !== $konfirmasi) {
        throw new Exception('Konfirmasi password baru tidak cocok.');
    }

    if (strlen($passwordBaru) < 6) {
        throw new Exception('Password baru minimal harus 6 karakter.');
    }

    // Ambil password lama dari DB
    $user = kueri_satu("SELECT password FROM users WHERE id = {$userId} LIMIT 1");
    if (!$user) {
        throw new Exception('Pengguna tidak ditemukan.');
    }

    if (!password_verify($passwordLama, $user['password'])) {
        throw new Exception('Password saat ini salah.');
    }

    $passwordHash = password_hash($passwordBaru, PASSWORD_BCRYPT);
    $stmt = $koneksi->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Gagal menyiapkan query database.');
    }

    $stmt->bind_param('si', $passwordHash, $userId);
    $sukses = $stmt->execute();
    $stmt->close();

    if (!$sukses) {
        throw new Exception('Gagal mengganti password.');
    }
}

function hitung_laba_rugi_bersih($tanggalMulai = '', $tanggalSelesai = '')
{
    global $koneksi;

    $tahunBuku = ambil_tahun_buku_aktif();
    if ($tahunBuku) {
        if ($tanggalMulai === '' || $tanggalMulai < $tahunBuku['tanggal_mulai']) {
            $tanggalMulai = $tahunBuku['tanggal_mulai'];
        }
        if ($tanggalSelesai === '' || $tanggalSelesai > $tahunBuku['tanggal_selesai']) {
            $tanggalSelesai = $tahunBuku['tanggal_selesai'];
        }
    }

    $sql = "SELECT a.id, a.kode_akun, a.nama_akun, a.kategori, a.tipe_saldo, a.parent_id,
                   COALESCE(SUM(jd.debit), 0) AS total_debit,
                   COALESCE(SUM(jd.kredit), 0) AS total_kredit
            FROM akun a
            LEFT JOIN jurnal_detail jd ON jd.akun_id = a.id
            LEFT JOIN jurnal j ON j.id = jd.jurnal_id";

    $params = [];
    $types  = '';

    if ($tahunBuku) {
        $sql .= " AND j.tanggal >= ? AND j.tanggal <= ?";
        $types    .= 'ss';
        $params[]  = $tanggalMulai;
        $params[]  = $tanggalSelesai;
    }

    $sql .= " WHERE a.aktif = 1 AND a.kategori IN ('Pendapatan', 'Beban')
              GROUP BY a.id, a.kode_akun, a.nama_akun, a.kategori, a.tipe_saldo, a.parent_id
              ORDER BY a.kode_akun ASC";

    $stmt = $koneksi->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $barisAkun = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    $tahunBukuId = $tahunBuku ? (int)$tahunBuku['id'] : 0;
    $saldoAwal = $tahunBukuId > 0 ? ambil_saldo_awal_akun($tahunBukuId) : [];

    // Map and roll up
    $akunMap = [];
    foreach ($barisAkun as $b) {
        $id = (int) $b['id'];
        $saldoAkunIni = (float) ($saldoAwal[$id] ?? 0);
        $mutasi       = hitung_mutasi_saldo($b['tipe_saldo'], $b['total_debit'], $b['total_kredit']);
        $saldo        = $saldoAkunIni + $mutasi;

        $b['saldo_sendiri'] = $saldo;
        $b['saldo_total']   = $saldo;
        $akunMap[$id]       = $b;
    }

    // Roll up (accumulate) to parents
    foreach ($akunMap as $id => $akun) {
        $saldo = $akun['saldo_sendiri'];
        if ($saldo == 0) {
            continue;
        }

        $parentId = $akun['parent_id'];
        while ($parentId !== null && isset($akunMap[(int)$parentId])) {
            $akunMap[(int)$parentId]['saldo_total'] += $saldo;
            $parentId = $akunMap[(int)$parentId]['parent_id'];
        }
    }

    $totalPendapatan = 0;
    $totalBeban = 0;
    $detailPendapatan = [];
    $detailBeban = [];

    // Filter main categories (parent_id IS NULL)
    foreach ($akunMap as $id => $akun) {
        if ($akun['parent_id'] === null) {
            $saldoTotal = $akun['saldo_total'];
            if (abs($saldoTotal) < 0.005) {
                continue;
            }

            $item = [
                'id' => $akun['id'],
                'kode_akun' => $akun['kode_akun'],
                'nama_akun' => $akun['nama_akun'],
                'saldo' => $saldoTotal
            ];

            if ($akun['kategori'] === 'Pendapatan') {
                $totalPendapatan += $saldoTotal;
                $detailPendapatan[] = $item;
            } else {
                $totalBeban += $saldoTotal;
                $detailBeban[] = $item;
            }
        }
    }

    return [
        'total_pendapatan' => $totalPendapatan,
        'total_beban' => $totalBeban,
        'laba_bersih' => $totalPendapatan - $totalBeban,
        'detail_pendapatan' => $detailPendapatan,
        'detail_beban' => $detailBeban
    ];
}

function hitung_mutasi_jurnal_akun($akunId, $tipeSaldo, $tglMulai, $tglSelesai)
{
    global $koneksi;

    $akunId = (int) $akunId;
    $subAkun = kueri_semua('SELECT id, tipe_saldo FROM akun WHERE parent_id = ' . $akunId . ' AND aktif = 1');
    $daftarId = [$akunId];
    foreach ($subAkun as $sub) {
        $daftarId[] = (int) $sub['id'];
    }

    $placeholders = implode(',', array_fill(0, count($daftarId), '?'));
    $sql = 'SELECT COALESCE(SUM(jd.debit), 0) AS total_debit, COALESCE(SUM(jd.kredit), 0) AS total_kredit
            FROM jurnal_detail jd
            INNER JOIN jurnal j ON j.id = jd.jurnal_id
            WHERE jd.akun_id IN (' . $placeholders . ')
              AND j.tanggal >= ? AND j.tanggal <= ?';

    $types = str_repeat('i', count($daftarId)) . 'ss';
    $params = array_merge($daftarId, [$tglMulai, $tglSelesai]);

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return 0.0;
    }

    return hitung_mutasi_saldo($tipeSaldo, $row['total_debit'], $row['total_kredit']);
}

function laporan_perubahan_ekuitas($tanggalMulai = '', $tanggalSelesai = '')
{
    global $koneksi;

    $tahunBuku = ambil_tahun_buku_aktif();
    if ($tahunBuku) {
        if ($tanggalMulai === '' || $tanggalMulai < $tahunBuku['tanggal_mulai']) {
            $tanggalMulai = $tahunBuku['tanggal_mulai'];
        }
        if ($tanggalSelesai === '' || $tanggalSelesai > $tahunBuku['tanggal_selesai']) {
            $tanggalSelesai = $tahunBuku['tanggal_selesai'];
        }
    }

    $saldoAwalTahun = $tahunBuku ? ambil_saldo_awal_akun((int) $tahunBuku['id']) : [];

    $sql = "SELECT a.id, a.kode_akun, a.nama_akun, a.tipe_saldo, a.parent_id
            FROM akun a
            WHERE a.aktif = 1 AND a.kategori = 'Ekuitas'
            ORDER BY a.kode_akun ASC";
    $akunEkuitas = kueri_semua($sql);

    $labaRugi = hitung_laba_rugi_bersih($tanggalMulai, $tanggalSelesai);
    $labaBersih = $labaRugi['laba_bersih'];

    $hasil = [];
    $totalAwal = 0;
    $totalMutasi = 0;
    $totalLaba = 0;
    $totalAkhir = 0;

    foreach ($akunEkuitas as $akun) {
        $id = (int) $akun['id'];
        
        $saldoAwalPeriode = (float) ($saldoAwalTahun[$id] ?? 0);
        if ($tahunBuku && $tanggalMulai > $tahunBuku['tanggal_mulai']) {
            $tanggalSebelumnya = date('Y-m-d', strtotime($tanggalMulai . ' -1 day'));
            $mutasiSebelumnya = hitung_mutasi_jurnal_akun($id, $akun['tipe_saldo'], $tahunBuku['tanggal_mulai'], $tanggalSebelumnya);
            $saldoAwalPeriode += $mutasiSebelumnya;
        }

        $mutasiPeriode = 0;
        if ($akun['kode_akun'] !== '3-9000') {
            $mutasiPeriode = hitung_mutasi_jurnal_akun($id, $akun['tipe_saldo'], $tanggalMulai, $tanggalSelesai);
        }

        $labaAkun = 0;
        if ($akun['kode_akun'] === '3-9000') {
            $labaAkun = $labaBersih;
        }

        $saldoAkhirPeriode = $saldoAwalPeriode + $mutasiPeriode + $labaAkun;

        $valAwal = $akun['tipe_saldo'] === 'Debit' ? -$saldoAwalPeriode : $saldoAwalPeriode;
        $valMutasi = $akun['tipe_saldo'] === 'Debit' ? -$mutasiPeriode : $mutasiPeriode;
        $valLaba = $labaAkun;
        $valAkhir = $valAwal + $valMutasi + $valLaba;

        if ($akun['parent_id'] === null) {
            $hasil[] = [
                'kode_akun' => $akun['kode_akun'],
                'nama_akun' => $akun['nama_akun'],
                'saldo_awal' => $valAwal,
                'mutasi' => $valMutasi,
                'laba_bersih' => $valLaba,
                'saldo_akhir' => $valAkhir,
            ];

            $totalAwal += $valAwal;
            $totalMutasi += $valMutasi;
            $totalLaba += $valLaba;
            $totalAkhir += $valAkhir;
        }
    }

    return [
        'baris' => $hasil,
        'total_awal' => $totalAwal,
        'total_mutasi' => $totalMutasi,
        'total_laba' => $totalLaba,
        'total_akhir' => $totalAkhir,
    ];
}

function ambil_jurnal_penyesuaian($batas = 15)
{
    $batas = (int) $batas;
    $tahunBuku = ambil_tahun_buku_aktif();
    
    $filterPeriode = " WHERE (j.jenis_transaksi = 'Penyesuaian' OR (j.jenis_transaksi = 'Umum' AND (j.nomor_bukti LIKE 'AJP-%' OR j.nomor_bukti LIKE 'TEST-AJP-%')))";
    if ($tahunBuku) {
        $filterPeriode .= " AND j.tanggal >= '" . addslashes($tahunBuku['tanggal_mulai']) . "' AND j.tanggal <= '" . addslashes($tahunBuku['tanggal_selesai']) . "'";
    }

    return kueri_semua(
        'SELECT j.*, COALESCE(SUM(jd.debit), 0) AS total_nominal
         FROM jurnal j
         LEFT JOIN jurnal_detail jd ON jd.jurnal_id = j.id
         ' . $filterPeriode . '
         GROUP BY j.id
         ORDER BY j.tanggal DESC, j.id DESC
         LIMIT ' . $batas
    );
}