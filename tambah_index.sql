-- =============================================================
-- Script penambahan INDEX untuk optimasi performa query
-- Jalankan sekali di database production:
--   mysql -u root -p akuntansi < tambah_index.sql
-- =============================================================

USE akuntansi;

-- Index pada jurnal.tanggal: mempercepat filter periode (tahun buku)
ALTER TABLE jurnal
    ADD INDEX IF NOT EXISTS idx_jurnal_tanggal (tanggal);

-- Index pada jurnal_detail.jurnal_id: mempercepat JOIN ke jurnal
ALTER TABLE jurnal_detail
    ADD INDEX IF NOT EXISTS idx_detail_jurnal_id (jurnal_id);

-- Index pada jurnal_detail.akun_id: mempercepat filter per akun di buku besar
ALTER TABLE jurnal_detail
    ADD INDEX IF NOT EXISTS idx_detail_akun_id (akun_id);

-- Index gabungan untuk laporan buku besar (akun + JOIN jurnal.tanggal)
ALTER TABLE jurnal_detail
    ADD INDEX IF NOT EXISTS idx_detail_akun_jurnal (akun_id, jurnal_id);

-- Index pada akun.aktif + kategori: mempercepat query daftar akun
ALTER TABLE akun
    ADD INDEX IF NOT EXISTS idx_akun_aktif_kategori (aktif, kategori);

-- Index pada akun.parent_id: mempercepat penghitungan sub-akun
ALTER TABLE akun
    ADD INDEX IF NOT EXISTS idx_akun_parent_id (parent_id);

-- Index pada hutang_piutang untuk filter & join
ALTER TABLE hutang_piutang
    ADD INDEX IF NOT EXISTS idx_hp_kontak_jenis  (kontak_id, jenis);

ALTER TABLE hutang_piutang
    ADD INDEX IF NOT EXISTS idx_hp_status        (status);

ALTER TABLE hutang_piutang
    ADD INDEX IF NOT EXISTS idx_hp_jurnal_id     (jurnal_id);

-- Index pada saldo_awal_akun untuk filter tahun buku
ALTER TABLE saldo_awal_akun
    ADD INDEX IF NOT EXISTS idx_saldo_awal_tahun (tahun_buku_id);

SELECT 'Index berhasil ditambahkan.' AS status;
