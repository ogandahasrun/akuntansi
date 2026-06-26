<?php

require_once __DIR__ . '/fungsi.php';

// Pastikan skema database terbuat
ambil_pengaturan();

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pesanFlash = ambil_flash();

// Proses Aksi POST
if ($metodeRequest === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah_aset') {
        try {
            $nama = trim($_POST['nama_aset'] ?? '');
            $tanggal = trim($_POST['tanggal_perolehan'] ?? '');
            $harga = (float) str_replace(',', '', $_POST['harga_perolehan'] ?? 0);
            $umur = (int) ($_POST['umur_ekonomis'] ?? 0);
            $residu = (float) str_replace(',', '', $_POST['nilai_residu'] ?? 0);
            $kategori = trim($_POST['kategori_aset'] ?? '');

            if ($nama === '' || $tanggal === '' || $kategori === '') {
                throw new Exception('Semua field wajib diisi.');
            }
            if ($harga <= 0) {
                throw new Exception('Harga perolehan harus lebih dari 0.');
            }
            if ($umur <= 0) {
                throw new Exception('Umur ekonomis harus minimal 1 tahun.');
            }
            if ($residu < 0) {
                throw new Exception('Nilai residu tidak boleh negatif.');
            }
            if ($residu >= $harga) {
                throw new Exception('Nilai residu tidak boleh melebihi atau sama dengan harga perolehan.');
            }

            $stmt = $koneksi->prepare('INSERT INTO aset_tetap (nama_aset, tanggal_perolehan, harga_perolehan, umur_ekonomis, nilai_residu, kategori_aset) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssdiis', $nama, $tanggal, $harga, $umur, $residu, $kategori);
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal menyimpan aset tetap: ' . $stmt->error);
            }
            $stmt->close();

            atur_flash('success', 'Aset tetap berhasil ditambahkan.');
            header('Location: aset_tetap.php');
            exit;
        } catch (Throwable $exception) {
            atur_flash('error', $exception->getMessage());
            header('Location: aset_tetap.php');
            exit;
        }
    }

    if ($aksi === 'hapus_aset') {
        try {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $koneksi->prepare('DELETE FROM aset_tetap WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            atur_flash('success', 'Aset tetap berhasil dihapus.');
            header('Location: aset_tetap.php');
            exit;
        } catch (Throwable $exception) {
            atur_flash('error', $exception->getMessage());
            header('Location: aset_tetap.php');
            exit;
        }
    }

    if ($aksi === 'proses_penyusutan') {
        try {
            $periode = $_POST['periode'] ?? ''; // Format: YYYY-MM
            if ($periode === '') {
                throw new Exception('Pilih periode bulan dan tahun penyusutan.');
            }

            $tahunBuku = ambil_tahun_buku_aktif();
            $selectedYear = (int) substr($periode, 0, 4);
            $selectedMonth = (int) substr($periode, 5, 2);

            // Tentukan tanggal akhir bulan
            $lastDay = date('t', strtotime("$periode-01"));
            $tanggalJurnal = "$periode-$lastDay";

            if ($tahunBuku && ($tanggalJurnal < $tahunBuku['tanggal_mulai'] || $tanggalJurnal > $tahunBuku['tanggal_selesai'])) {
                throw new Exception("Periode penyusutan ($periode) berada di luar rentang Tahun Buku aktif.");
            }

            // Ambil semua aset tetap
            $resAset = $koneksi->query('SELECT * FROM aset_tetap');
            $daftarAset = [];
            while ($row = $resAset->fetch_assoc()) {
                $daftarAset[] = $row;
            }

            if (empty($daftarAset)) {
                throw new Exception('Tidak ada data aset tetap untuk disusutkan.');
            }

            // Cek apakah penyusutan bulan ini sudah diproses (untuk mencegah duplikasi)
            $cekJurnal = kueri_satu(
                "SELECT id FROM jurnal WHERE jenis_transaksi = 'Penyesuaian' AND tanggal = '" . addslashes($tanggalJurnal) . "' AND keterangan LIKE 'Penyusutan Aset Tetap Otomatis - %' LIMIT 1"
            );
            if ($cekJurnal) {
                $cekJurnal2 = false;
            } else {
                $cekJurnal2 = kueri_satu(
                    "SELECT id FROM jurnal WHERE jenis_transaksi = 'Umum' AND nomor_bukti = 'AJP-DEP-" . addslashes($periode) . "' LIMIT 1"
                );
            }

            if ($cekJurnal || $cekJurnal2) {
                throw new Exception("Penyusutan aset tetap untuk periode $periode sudah pernah diproses.");
            }

            // Map kategori aset ke kode akun (Beban & Akumulasi Penyusutan)
            $mapKategori = [
                'Gedung' => [
                    'beban' => '6-3109',
                    'akm' => '1-3110'
                ],
                'Kendaraan' => [
                    'beban' => '6-3110',
                    'akm' => '1-3130'
                ],
                'Peralatan Kantor' => [
                    'beban' => '6-3111',
                    'akm' => '1-3150'
                ],
                'Peralatan Medis' => [
                    'beban' => '6-3112',
                    'akm' => '1-3170'
                ],
                'Bangunan Lainnya' => [
                    'beban' => '6-3115',
                    'akm' => '1-3200'
                ]
            ];

            $bebanKategori = [];

            foreach ($daftarAset as $aset) {
                $tglPerolehan = $aset['tanggal_perolehan'];
                $purYear = (int) substr($tglPerolehan, 0, 4);
                $purMonth = (int) substr($tglPerolehan, 5, 2);

                // Hitung selisih bulan
                $monthsElapsed = ($selectedYear * 12 + $selectedMonth) - ($purYear * 12 + $purMonth);

                // Cek apakah aset sudah waktunya disusutkan dan belum habis umur ekonomisnya
                $totalBulanEkonomis = $aset['umur_ekonomis'] * 12;

                if ($monthsElapsed >= 0 && $monthsElapsed < $totalBulanEkonomis) {
                    $harga = (float) $aset['harga_perolehan'];
                    $residu = (float) $aset['nilai_residu'];
                    $penyusutanBulanan = ($harga - $residu) / $totalBulanEkonomis;

                    $kat = $aset['kategori_aset'];
                    if (!isset($bebanKategori[$kat])) {
                        $bebanKategori[$kat] = 0;
                    }
                    $bebanKategori[$kat] += $penyusutanBulanan;
                }
            }

            if (empty($bebanKategori)) {
                throw new Exception('Tidak ada aset yang perlu disusutkan pada periode ini (belum masuk tanggal perolehan atau umur ekonomis sudah habis).');
            }

            // Siapkan detail AJP
            $akunIds = [];
            $debits = [];
            $kredits = [];

            foreach ($bebanKategori as $kat => $nominal) {
                if ($nominal <= 0) {
                    continue;
                }

                $nominal = round($nominal, 2);
                $kodeBeban = $mapKategori[$kat]['beban'];
                $kodeAkm = $mapKategori[$kat]['akm'];

                // Cari ID akun berdasarkan kode_akun
                $akunBeban = kueri_satu("SELECT id FROM akun WHERE kode_akun = '$kodeBeban' LIMIT 1");
                $akunAkm = kueri_satu("SELECT id FROM akun WHERE kode_akun = '$kodeAkm' LIMIT 1");

                if (!$akunBeban || !$akunAkm) {
                    throw new Exception("Gagal memproses. Akun penyusutan untuk kategori $kat ($kodeBeban / $kodeAkm) belum terdaftar di Daftar Akun.");
                }

                // Baris Debit (Beban Penyusutan)
                $akunIds[] = (int) $akunBeban['id'];
                $debits[] = $nominal;
                $kredits[] = 0.0;

                // Baris Kredit (Akumulasi Penyusutan)
                $akunIds[] = (int) $akunAkm['id'];
                $debits[] = 0.0;
                $kredits[] = $nominal;
            }

            if (empty($akunIds)) {
                throw new Exception('Nilai nominal penyusutan bernilai nol.');
            }

            // Simpan jurnal penyesuaian
            $jurnalData = [
                'tanggal' => $tanggalJurnal,
                'nomor_bukti' => "AJP-DEP-$periode",
                'keterangan' => "Penyusutan Aset Tetap Otomatis - Periode $periode",
                'jenis_transaksi' => 'Penyesuaian',
                'akun_id' => $akunIds,
                'debit' => $debits,
                'kredit' => $kredits
            ];

            simpan_jurnal($jurnalData);

            atur_flash('success', "Sukses memproses penyusutan aset tetap periode $periode.");
            header('Location: aset_tetap.php');
            exit;
        } catch (Throwable $exception) {
            atur_flash('error', $exception->getMessage());
            header('Location: aset_tetap.php');
            exit;
        }
    }
}

// Ambil semua aset tetap untuk ditampilkan
$res = $koneksi->query('SELECT * FROM aset_tetap ORDER BY kategori_aset ASC, nama_aset ASC');
$asetTetap = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $asetTetap[] = $row;
    }
}

render_header('Pencatatan & Penyusutan Aset Tetap', 'aset_tetap');
?>

<?php if ($pesanFlash) { ?>
    <div class="flash <?php echo e($pesanFlash['jenis']); ?>"><?php echo e($pesanFlash['pesan']); ?></div>
<?php } ?>

<section class="grid-two">
    <!-- Formulir Pendaftaran Aset Baru -->
    <article class="panel">
        <h3>Daftarkan Aset Baru</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="tambah_aset">
            
            <label class="full-width">
                <span>Nama Aset Tetap</span>
                <input type="text" name="nama_aset" placeholder="Contoh: Mobil Box Operasional" required>
            </label>

            <label>
                <span>Kategori Aset</span>
                <select name="kategori_aset" required>
                    <option value="">Pilih kategori</option>
                    <option value="Gedung">Gedung</option>
                    <option value="Kendaraan">Kendaraan</option>
                    <option value="Peralatan Kantor">Peralatan Kantor</option>
                    <option value="Peralatan Medis">Peralatan Medis</option>
                    <option value="Bangunan Lainnya">Bangunan Lainnya</option>
                </select>
            </label>

            <label>
                <span>Tanggal Perolehan</span>
                <input type="date" name="tanggal_perolehan" required value="<?php echo date('Y-m-d'); ?>">
            </label>

            <label>
                <span>Harga Perolehan (Acquisition Cost)</span>
                <input type="number" name="harga_perolehan" min="1" step="0.01" placeholder="Masukkan harga beli" required>
            </label>

            <label>
                <span>Umur Ekonomis (Tahun)</span>
                <input type="number" name="umur_ekonomis" min="1" placeholder="Contoh: 5" required>
            </label>

            <label class="full-width">
                <span>Nilai Residu / Nilai Sisa (Salvage Value)</span>
                <input type="number" name="nilai_residu" min="0" step="0.01" value="0" placeholder="Biarkan 0 jika tidak ada sisa">
            </label>

            <div class="button-row full-width" style="margin-top: 10px;">
                <button type="submit" class="button primary">Daftarkan Aset</button>
            </div>
        </form>
    </article>

    <!-- Panel Proses Penyusutan Otomatis -->
    <article class="panel" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3>Proses Penyusutan Otomatis (AJP)</h3>
            <p>Sistem akan menghitung beban penyusutan bulanan menggunakan **Metode Garis Lurus** untuk semua aset tetap yang masih aktif dalam umur ekonomisnya, lalu membuat jurnal penyesuaian secara otomatis.</p>
            <br>
            <form method="post" class="form-grid" onsubmit="return confirm('Proses penyusutan untuk bulan terpilih? Tindakan ini akan membuat Jurnal Penyesuaian baru secara otomatis.');">
                <input type="hidden" name="aksi" value="proses_penyusutan">
                <label class="full-width">
                    <span>Pilih Periode Penyusutan (Bulan & Tahun)</span>
                    <input type="month" name="periode" required value="<?php echo date('Y-m'); ?>">
                </label>
                
                <div class="button-row full-width" style="margin-top: 15px;">
                    <button type="submit" class="button primary" style="width: 100%; display: block; text-align: center;">⚡ Proses Penyusutan Aset (AJP)</button>
                </div>
            </form>
        </div>
        
        <div style="background-color: #f3f4f6; border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 4px; font-size: 0.9rem; color: #555; margin-top: 20px;">
            <strong>Catatan Akuntansi:</strong>
            <ul style="margin: 5px 0 0; padding-left: 20px;">
                <li>Rumus: `(Harga - Residu) / (Umur Ekonomis * 12)`</li>
                <li>Aset tidak akan disusutkan jika periode terpilih mendahului tanggal pembelian atau telah melewati umur ekonomisnya.</li>
                <li>Jurnal AJP otomatis akan diposting di tanggal akhir bulan terpilih.</li>
            </ul>
        </div>
    </article>
</section>

<!-- Tabel Daftar Aset Tetap -->
<section class="panel" style="margin-top: 24px;">
    <h3>Daftar Inventaris Aset Tetap</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Nama Aset</th>
                <th>Kategori</th>
                <th>Tgl Perolehan</th>
                <th class="align-right">Harga Perolehan</th>
                <th class="align-center">Umur Ekonomis</th>
                <th class="align-right">Nilai Residu</th>
                <th class="align-right">Penyusutan Bulanan</th>
                <th class="align-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($asetTetap)) { ?>
                <tr>
                    <td colspan="8" class="empty-state">Belum ada aset tetap yang didaftarkan.</td>
                </tr>
            <?php } ?>
            <?php foreach ($asetTetap as $aset) { 
                $umurBulan = $aset['umur_ekonomis'] * 12;
                $depresiasiBulanan = ($aset['harga_perolehan'] - $aset['nilai_residu']) / $umurBulan;
                ?>
                <tr>
                    <td><strong><?php echo e($aset['nama_aset']); ?></strong></td>
                    <td><?php echo e($aset['kategori_aset']); ?></td>
                    <td><?php echo e(format_tanggal_indonesia($aset['tanggal_perolehan'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($aset['harga_perolehan'])); ?></td>
                    <td class="align-center"><?php echo e($aset['umur_ekonomis']); ?> Tahun</td>
                    <td class="align-right"><?php echo e(format_rupiah($aset['nilai_residu'])); ?></td>
                    <td class="align-right"><strong><?php echo e(format_rupiah($depresiasiBulanan)); ?></strong>/bln</td>
                    <td class="align-center">
                        <form method="post" onsubmit="return confirm('Hapus aset ini dari daftar inventaris?');" style="display:inline;">
                            <input type="hidden" name="aksi" value="hapus_aset">
                            <input type="hidden" name="id" value="<?php echo (int) $aset['id']; ?>">
                            <button type="submit" class="button small ghost" style="color: #dc2626; border-color: #dc2626;">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<?php
render_footer();
