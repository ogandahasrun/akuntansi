<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodeRequest === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'simpan_kontak') {
            // Hardcode jenis sebagai Pelanggan
            $_POST['jenis'] = 'Pelanggan';
            simpan_kontak($_POST);
            atur_flash('success', 'Pelanggan berhasil ditambahkan.');
        }

        if ($aksi === 'bayar_relasi') {
            catat_pembayaran_relasi((int) ($_POST['id_relasi'] ?? 0), $_POST['jumlah_bayar'] ?? 0);
            atur_flash('success', 'Pembayaran piutang berhasil dicatat.');
        }

        header('Location: piutang.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: piutang.php');
        exit;
    }
}

$kontak = ambil_daftar_kontak();
// Filter hanya pelanggan
$kontakPelanggan = array_values(array_filter($kontak, function ($item) {
    return ($item['jenis'] ?? '') === 'Pelanggan';
}));

$filterStatus = trim($_GET['status'] ?? '');
$filterKontakId = (int) ($_GET['kontak_id'] ?? 0);
$relasi = ambil_relasi_hutang_piutang([
    'jenis' => 'Piutang',
    'status' => $filterStatus,
    'kontak_id' => $filterKontakId,
]);

$totalNominal = 0;
$totalDibayar = 0;
$totalSisa = 0;
foreach ($relasi as $itemRelasi) {
    $totalNominal += (float) $itemRelasi['nominal'];
    $totalDibayar += (float) $itemRelasi['dibayar'];
    $totalSisa += (float) $itemRelasi['sisa'];
}

render_header('Kelola Piutang', 'piutang');
?>
<section class="grid-two">
    <article class="panel">
        <h3>Tambah Pelanggan (Customer)</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="simpan_kontak">
            <label>
                <span>Kode Kontak</span>
                <input type="text" name="kode_kontak" placeholder="Contoh: CUST-1">
            </label>
            <label>
                <span>Nama Pelanggan</span>
                <input type="text" name="nama" required>
            </label>
            <label>
                <span>Telepon</span>
                <input type="text" name="telepon">
            </label>
            <label class="full-width">
                <span>Alamat</span>
                <textarea name="alamat" rows="3"></textarea>
            </label>
            <button type="submit" class="button primary">Simpan Pelanggan</button>
        </form>
    </article>
    <article class="panel">
        <h3>Cara Pakai</h3>
        <p>Masukkan transaksi piutang dari halaman <strong>Jurnal Umum</strong> dengan memilih jenis transaksi <strong>Piutang</strong>.</p>
        <p>Di halaman ini Anda dapat memantau tagihan piutang dari pelanggan yang sedang berjalan dan mencatat penerimaan/pembayaran secara ringkas.</p>
        <p class="helper-text">Agar catatan arus kas tetap benar, disarankan mencatat transaksi pembayaran nyata melalui Jurnal Umum menggunakan jenis <strong>Terima Piutang</strong>.</p>
    </article>
</section>

<section class="panel">
    <div class="section-title">
        <h3>Daftar Piutang (Tagihan)</h3>
        <span>Total pelanggan aktif: <?php echo count($kontakPelanggan); ?></span>
    </div>
    <form method="get" class="form-grid filter-grid compact-filter-grid">
        <label>
            <span>Status</span>
            <select name="status">
                <option value="">Semua</option>
                <option value="Belum Lunas" <?php echo $filterStatus === 'Belum Lunas' ? 'selected' : ''; ?>>Belum Lunas</option>
                <option value="Sebagian" <?php echo $filterStatus === 'Sebagian' ? 'selected' : ''; ?>>Sebagian</option>
                <option value="Lunas" <?php echo $filterStatus === 'Lunas' ? 'selected' : ''; ?>>Lunas</option>
            </select>
        </label>
        <label>
            <span>Pelanggan</span>
            <select name="kontak_id">
                <option value="0">Semua pelanggan</option>
                <?php foreach ($kontakPelanggan as $item) { ?>
                    <option value="<?php echo (int) $item['id']; ?>" <?php echo $filterKontakId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e(($item['kode_kontak'] ? $item['kode_kontak'] . ' - ' : '') . $item['nama']); ?></option>
                <?php } ?>
            </select>
        </label>
        <div class="button-row align-end-actions">
            <button type="submit" class="button primary">Terapkan</button>
            <a href="piutang.php" class="button ghost">Reset</a>
        </div>
    </form>
    <div class="cards three-up compact-cards">
        <article class="card accent-b">
            <p>Total Nominal Piutang</p>
            <strong><?php echo e(format_rupiah($totalNominal)); ?></strong>
        </article>
        <article class="card accent-a">
            <p>Total Dibayar</p>
            <strong><?php echo e(format_rupiah($totalDibayar)); ?></strong>
        </article>
        <article class="card accent-c">
            <p>Total Sisa Piutang</p>
            <strong><?php echo e(format_rupiah($totalSisa)); ?></strong>
        </article>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Jatuh Tempo</th>
                <th class="align-right">Nominal</th>
                <th class="align-right">Dibayar</th>
                <th class="align-right">Sisa</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($relasi)) { ?>
                <tr>
                    <td colspan="8" class="empty-state">Belum ada data piutang.</td>
                </tr>
            <?php } ?>
            <?php foreach ($relasi as $baris) { ?>
                <tr>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e(($baris['kode_kontak'] ? $baris['kode_kontak'] . ' - ' : '') . $baris['nama_kontak']); ?></td>
                    <td><?php echo e(format_tanggal_indonesia($baris['jatuh_tempo'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['nominal'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['dibayar'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['sisa'])); ?></td>
                    <td><?php echo e($baris['status']); ?></td>
                    <td>
                        <?php
                        $jurnalBayarId = (int) ($baris['jurnal_bayar_id'] ?? 0);
                        $sudahLunas    = $baris['status'] === 'Lunas';
                        $bayarViaJurnal = $jurnalBayarId > 0;
                        ?>
                        <?php if ($bayarViaJurnal) { ?>
                            <a href="jurnal_umum.php?edit=<?php echo $jurnalBayarId; ?>"
                               class="badge success"
                               title="Dilunasi via Jurnal #<?php echo $jurnalBayarId; ?>"
                               style="text-decoration:none">
                               ✓ Via Jurnal #<?php echo $jurnalBayarId; ?>
                            </a>
                        <?php } ?>
                        <?php if ((float) $baris['sisa'] > 0 && !$bayarViaJurnal) { ?>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="aksi" value="bayar_relasi">
                                <input type="hidden" name="id_relasi" value="<?php echo (int) $baris['id']; ?>">
                                <input type="number" name="jumlah_bayar" min="0" step="0.01" placeholder="Bayar" required>
                                <button type="submit" class="button small">Simpan</button>
                            </form>
                        <?php } elseif ($sudahLunas && !$bayarViaJurnal) { ?>
                            <span class="badge success">Selesai</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h3>Daftar Pelanggan Aktif</h3>
    <table class="table compact">
        <thead>
            <tr>
                <th>Kode Pelanggan</th>
                <th>Nama Pelanggan</th>
                <th>Telepon</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($kontakPelanggan)) { ?>
                <tr>
                    <td colspan="4" class="empty-state">Belum ada data pelanggan.</td>
                </tr>
            <?php } ?>
            <?php foreach ($kontakPelanggan as $item) { ?>
                <tr>
                    <td><?php echo e($item['kode_kontak'] ?: '-'); ?></td>
                    <td><?php echo e($item['nama']); ?></td>
                    <td><?php echo e($item['telepon']); ?></td>
                    <td><?php echo e($item['alamat']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
<?php
render_footer();
