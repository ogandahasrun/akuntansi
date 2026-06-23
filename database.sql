CREATE TABLE IF NOT EXISTS pengaturan (
    id INT PRIMARY KEY,
    nama_perusahaan VARCHAR(150) NOT NULL,
    alamat TEXT NULL,
    telepon VARCHAR(50) NULL,
    email VARCHAR(120) NULL,
    logo VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO pengaturan (id, nama_perusahaan, alamat, telepon, email, logo)
VALUES (1, 'Nama Perusahaan Anda', '', '', '', '')
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS akun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_akun VARCHAR(20) NOT NULL UNIQUE,
    nama_akun VARCHAR(120) NOT NULL,
    kategori ENUM('Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban') NOT NULL,
    tipe_saldo ENUM('Debit', 'Kredit') NOT NULL,
    is_kas TINYINT(1) NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tahun_buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO tahun_buku (id, nama, tanggal_mulai, tanggal_selesai, aktif)
VALUES (
    1,
    CONCAT('Tahun Buku ', YEAR(CURDATE())),
    CONCAT(YEAR(CURDATE()), '-01-01'),
    CONCAT(YEAR(CURDATE()), '-12-31'),
    1
)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO akun (kode_akun, nama_akun, kategori, tipe_saldo, is_kas, aktif)
VALUES
    ('1-1100', 'Kas', 'Aset', 'Debit', 1, 1),
    ('1-1200', 'Tabungan Bank BNI', 'Aset', 'Debit', 1, 1),
    ('1-1300', 'Tabungan Bank Mandiri', 'Aset', 'Debit', 1, 1),
    ('1-1400', 'Giro Bank Mandiri', 'Aset', 'Debit', 1, 1),
    ('1-1500', 'Piutang BPJS', 'Aset', 'Debit', 0, 1),
    ('1-1501', 'Piutang Pasien Umum', 'Aset', 'Debit', 0, 1),
    ('1-1600', 'Cadangan Kerugian Piutang', 'Aset', 'Kredit', 0, 1),
    ('1-1700', 'Persediaan Obat & BHP', 'Aset', 'Debit', 0, 1),
    ('1-1800', 'Perlengkapan Kantor', 'Aset', 'Debit', 0, 1),
    ('1-1801', 'Perlengkapan Pelayanan', 'Aset', 'Debit', 0, 1),
    ('1-1802', 'Alat Tulis Kantor', 'Aset', 'Debit', 0, 1),
    ('1-1900', 'Perlengkapan Medis', 'Aset', 'Debit', 0, 1),
    ('1-2000', 'PPN Masukan', 'Aset', 'Debit', 0, 1),
    ('1-2001', 'Penempatan Dana Koperasi', 'Aset', 'Debit', 0, 1),
    ('1-2002', 'Piutang Asuransi', 'Aset', 'Debit', 0, 1),
    ('1-2003', 'Piutang Lainnya', 'Aset', 'Debit', 0, 1),
    ('1-2004', 'Biaya Dibayar Dimuka', 'Aset', 'Debit', 0, 1),
    ('1-2005', 'Uang Muka Pajak PPh 25/29', 'Aset', 'Debit', 0, 1),
    ('1-2006', 'Piutang BPJS Ketenagakerjaan', 'Aset', 'Debit', 0, 1),
    ('1-2007', 'PPh 23 Dibayar di Muka', 'Aset', 'Debit', 0, 1),
    ('1-3100', 'Gedung', 'Aset', 'Debit', 0, 1),
    ('1-3110', 'Akm. Penyusutan Gedung', 'Aset', 'Kredit', 0, 1),
    ('1-3120', 'Kendaraan', 'Aset', 'Debit', 0, 1),
    ('1-3130', 'Akm. Penyusutan Kendaraan', 'Aset', 'Kredit', 0, 1),
    ('1-3140', 'Peralatan Kantor', 'Aset', 'Debit', 0, 1),
    ('1-3150', 'Akm. Penyusutan Peralatan Kantor', 'Aset', 'Kredit', 0, 1),
    ('1-3160', 'Peralatan Medis', 'Aset', 'Debit', 0, 1),
    ('1-3170', 'Akm. Penyusutan Medis', 'Aset', 'Kredit', 0, 1),
    ('1-3180', 'Tanah', 'Aset', 'Debit', 0, 1),
    ('1-3190', 'Bangunan Lainnya', 'Aset', 'Debit', 0, 1),
    ('1-3200', 'Akm. Bangunan Lainnya', 'Aset', 'Kredit', 0, 1),
    ('2-1100', 'Hutang Usaha', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1200', 'Hutang Obat & BHP', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1300', 'Hutang Optik', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1400', 'Hutang Pajak PPh 21', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1401', 'Hutang Pajak PPh 21 - Rekanan', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1500', 'PPN Keluaran', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1600', 'Hutang Gaji Karyawan', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1601', 'Hutang Insentif Asisten Operasi', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1700', 'Hutang Jasa dr. Spesialis Pasien Umum', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1701', 'Hutang Jasa dr. Spesialis Pasien BPJS', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1800', 'Hutang Jasa dr. Umum Pasien Umum', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1801', 'Hutang Jasa dr. Umum Pasien BPJS', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1900', 'Hutang Lain-lain', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1901', 'Hutang Listrik & Telepon', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1902', 'Hutang Pajak PPh 25/29', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1903', 'Hutang Pajak PPh 23', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1904', 'Hutang Biaya', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1905', 'Hutang Sewa', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1906', 'Hutang Pajak PPh Final', 'Kewajiban', 'Kredit', 0, 1),
    ('2-1907', 'Hutang Pajak PPN', 'Kewajiban', 'Kredit', 0, 1),
    ('2-2100', 'Hutang Jangka Panjang', 'Kewajiban', 'Kredit', 0, 1),
    ('2-2200', 'Hutang Bank Mandiri', 'Kewajiban', 'Kredit', 0, 1),
    ('3-1100', 'Modal', 'Ekuitas', 'Kredit', 0, 1),
    ('3-1200', 'Tambahan Modal', 'Ekuitas', 'Kredit', 0, 1),
    ('3-8000', 'Saldo Laba', 'Ekuitas', 'Kredit', 0, 1),
    ('3-8001', 'Dividen', 'Ekuitas', 'Debit', 0, 1),
    ('3-9000', 'Laba Tahun Berjalan', 'Ekuitas', 'Kredit', 0, 1),
    ('3-9001', 'Ikhtisar Laba Rugi', 'Ekuitas', 'Kredit', 0, 1),
    ('4-1100', 'Pendapatan Umum', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1101', 'Pendapatan BPJS', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1102', 'Retur Penjualan', 'Pendapatan', 'Debit', 0, 1),
    ('4-1103', 'Pendapatan Asuransi', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1104', 'Pendapatan Asuransi (Reliance)', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1105', 'Pendapatan BPJS Ketenagakerjaan', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1106', 'Potongan Pendapatan BPJS', 'Pendapatan', 'Debit', 0, 1),
    ('4-1110', 'Pendapatan Rawat Jalan', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1111', 'Pendapatan Rawat Inap', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1112', 'Pendapatan IGD', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1113', 'Pendapatan Laboratorium', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1114', 'Pendapatan Radiologi', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1115', 'Pendapatan Farmasi', 'Pendapatan', 'Kredit', 0, 1),
    ('4-1116', 'Pendapatan Tindakan Operasi', 'Pendapatan', 'Kredit', 0, 1),
    ('5-1000', 'Harga Pokok Barang Yang Dijual', 'Beban', 'Debit', 0, 1),
    ('5-1200', 'Biaya Angkut Pembelian Barang', 'Beban', 'Debit', 0, 1),
    ('5-1300', 'Potongan Pembelian', 'Beban', 'Kredit', 0, 1),
    ('5-1301', 'Retur Pembelian', 'Beban', 'Kredit', 0, 1),
    ('5-1400', 'Biaya Rujukan', 'Beban', 'Debit', 0, 1),
    ('6-1100', 'Biaya Iklan', 'Beban', 'Debit', 0, 1),
    ('6-1200', 'Biaya Konsumsi', 'Beban', 'Debit', 0, 1),
    ('6-1300', 'Biaya Listrik', 'Beban', 'Debit', 0, 1),
    ('6-1400', 'Biaya Telepon', 'Beban', 'Debit', 0, 1),
    ('6-1500', 'Biaya Sewa', 'Beban', 'Debit', 0, 1),
    ('6-1600', 'Biaya Penghapusan Piutang', 'Beban', 'Debit', 0, 1),
    ('6-1700', 'Biaya Administrasi', 'Beban', 'Debit', 0, 1),
    ('6-1900', 'Biaya Gaji Dan Upah', 'Beban', 'Debit', 0, 1),
    ('6-2000', 'Biaya Perlengkapan Kantor', 'Beban', 'Debit', 0, 1),
    ('6-2100', 'Biaya Alat Tulis Kantor', 'Beban', 'Debit', 0, 1),
    ('6-2200', 'Biaya BBM', 'Beban', 'Debit', 0, 1),
    ('6-2300', 'Biaya Fotocopy', 'Beban', 'Debit', 0, 1),
    ('6-2400', 'Biaya Kebersihan', 'Beban', 'Debit', 0, 1),
    ('6-2500', 'Biaya Materai', 'Beban', 'Debit', 0, 1),
    ('6-2600', 'Biaya Perjalanan Dinas', 'Beban', 'Debit', 0, 1),
    ('6-2700', 'Biaya BPJS Kesehatan', 'Beban', 'Debit', 0, 1),
    ('6-2701', 'Biaya BPJS Ketenagakerjaan', 'Beban', 'Debit', 0, 1),
    ('6-2800', 'Biaya Lain-lain', 'Beban', 'Debit', 0, 1),
    ('6-2900', 'Biaya Jasa dr. Spesialis Pasien Umum', 'Beban', 'Debit', 0, 1),
    ('6-2901', 'Biaya Jasa dr. Spesialis Pasien BPJS', 'Beban', 'Debit', 0, 1),
    ('6-3000', 'Biaya Jasa dr. Umum Pasien Umum', 'Beban', 'Debit', 0, 1),
    ('6-3001', 'Biaya Jasa dr. Umum Pasien BPJS', 'Beban', 'Debit', 0, 1),
    ('6-3100', 'Insentif Asisten Operasi', 'Beban', 'Debit', 0, 1),
    ('6-3101', 'Biaya PPh 21', 'Beban', 'Debit', 0, 1),
    ('6-3102', 'Biaya Pemeliharaan Inventaris', 'Beban', 'Debit', 0, 1),
    ('6-3103', 'Biaya Renovasi', 'Beban', 'Debit', 0, 1),
    ('6-3104', 'Biaya Pajak Lain-lain', 'Beban', 'Debit', 0, 1),
    ('6-3105', 'Biaya Internet', 'Beban', 'Debit', 0, 1),
    ('6-3106', 'Biaya PPh 25/29', 'Beban', 'Debit', 0, 1),
    ('6-3107', 'Biaya Akreditasi', 'Beban', 'Debit', 0, 1),
    ('6-3108', 'Biaya Kirim', 'Beban', 'Debit', 0, 1),
    ('6-3109', 'Biaya Penyusutan Gedung', 'Beban', 'Debit', 0, 1),
    ('6-3110', 'Biaya Penyusutan Kendaraan', 'Beban', 'Debit', 0, 1),
    ('6-3111', 'Biaya Penyusutan Peralatan Kantor', 'Beban', 'Debit', 0, 1),
    ('6-3112', 'Biaya Penyusutan Peralatan Medis', 'Beban', 'Debit', 0, 1),
    ('6-3113', 'Biaya PPh Pasal 4 Ayat (2)', 'Beban', 'Debit', 0, 1),
    ('6-3114', 'Biaya Penghapusan Piutang Khusus', 'Beban', 'Debit', 0, 1),
    ('6-3115', 'Biaya Penyusutan Bangunan Lainnya', 'Beban', 'Debit', 0, 1),
    ('6-3116', 'Biaya Upah Pegawai Tidak Tetap', 'Beban', 'Debit', 0, 1),
    ('6-3117', 'Biaya Promosi', 'Beban', 'Debit', 0, 1),
    ('6-3118', 'Biaya Pemeliharaan Gedung', 'Beban', 'Debit', 0, 1),
    ('6-3119', 'Biaya Limbah', 'Beban', 'Debit', 0, 1),
    ('6-3120', 'Biaya Tol & Parkir', 'Beban', 'Debit', 0, 1),
    ('6-3121', 'Biaya Transportasi', 'Beban', 'Debit', 0, 1),
    ('6-3122', 'Biaya Pelatihan Karyawan', 'Beban', 'Debit', 0, 1),
    ('6-3123', 'Biaya Konsultan', 'Beban', 'Debit', 0, 1),
    ('6-3124', 'Biaya Denda Pajak', 'Beban', 'Debit', 0, 1),
    ('6-3125', 'Biaya Sumbangan', 'Beban', 'Debit', 0, 1),
    ('6-3126', 'Biaya Pajak PPN', 'Beban', 'Debit', 0, 1),
    ('8-1100', 'Pendapatan Bunga', 'Pendapatan', 'Kredit', 0, 1),
    ('8-1200', 'Pendapatan Dividen', 'Pendapatan', 'Kredit', 0, 1),
    ('8-1300', 'Pendapatan Lain-lain', 'Pendapatan', 'Kredit', 0, 1),
    ('8-1400', 'Laba (Rugi) Penjualan Saham', 'Pendapatan', 'Kredit', 0, 1),
    ('8-1500', 'Laba (Rugi) Penjualan Peralatan', 'Pendapatan', 'Kredit', 0, 1),
    ('9-1100', 'Biaya Bunga', 'Beban', 'Debit', 0, 1),
    ('9-1200', 'Biaya Administrasi Bank', 'Beban', 'Debit', 0, 1),
    ('9-1300', 'Biaya Lain-lain', 'Beban', 'Debit', 0, 1)
ON DUPLICATE KEY UPDATE nama_akun = VALUES(nama_akun);

CREATE TABLE IF NOT EXISTS jurnal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nomor_bukti VARCHAR(50) NOT NULL,
    keterangan TEXT NULL,
    jenis_transaksi ENUM('Umum', 'Kas', 'Hutang', 'Piutang') NOT NULL DEFAULT 'Umum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS jurnal_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurnal_id INT NOT NULL,
    akun_id INT NOT NULL,
    debit DECIMAL(18,2) NOT NULL DEFAULT 0,
    kredit DECIMAL(18,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_jurnal_detail_jurnal FOREIGN KEY (jurnal_id) REFERENCES jurnal(id) ON DELETE CASCADE,
    CONSTRAINT fk_jurnal_detail_akun FOREIGN KEY (akun_id) REFERENCES akun(id)
);

CREATE TABLE IF NOT EXISTS saldo_awal_akun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tahun_buku_id INT NOT NULL,
    akun_id INT NOT NULL,
    nominal DECIMAL(18,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_saldo_awal_tahun_akun (tahun_buku_id, akun_id),
    CONSTRAINT fk_saldo_awal_tahun_buku FOREIGN KEY (tahun_buku_id) REFERENCES tahun_buku(id) ON DELETE CASCADE,
    CONSTRAINT fk_saldo_awal_akun FOREIGN KEY (akun_id) REFERENCES akun(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS kontak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_kontak VARCHAR(50) NULL,
    nama VARCHAR(120) NOT NULL,
    jenis ENUM('Pelanggan', 'Pemasok') NOT NULL,
    telepon VARCHAR(50) NULL,
    alamat TEXT NULL,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hutang_piutang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurnal_id INT NULL,
    kontak_id INT NOT NULL,
    jenis ENUM('Hutang', 'Piutang') NOT NULL,
    tanggal DATE NOT NULL,
    jatuh_tempo DATE NULL,
    keterangan VARCHAR(255) NULL,
    nominal DECIMAL(18,2) NOT NULL DEFAULT 0,
    dibayar DECIMAL(18,2) NOT NULL DEFAULT 0,
    status ENUM('Belum Lunas', 'Sebagian', 'Lunas') NOT NULL DEFAULT 'Belum Lunas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hutang_piutang_jurnal FOREIGN KEY (jurnal_id) REFERENCES jurnal(id) ON DELETE SET NULL,
    CONSTRAINT fk_hutang_piutang_kontak FOREIGN KEY (kontak_id) REFERENCES kontak(id)
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'akuntan', 'pimpinan') NOT NULL DEFAULT 'akuntan',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, nama_lengkap, role, aktif)
VALUES ('admin', '$2y$10$K874CWBXketWi9Phwwyafe0HoU82HLo5hegEobuUC.jBmcpuGE6X.', 'Administrator', 'admin', 1)
ON DUPLICATE KEY UPDATE id = id;