<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodeRequest === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'simpan_kontak') {
            simpan_kontak($_POST);
            atur_flash('success', 'Kontak berhasil ditambahkan.');
        }

        if ($aksi === 'bayar_relasi') {
            catat_pembayaran_relasi((int) ($_POST['id_relasi'] ?? 0), $_POST['jumlah_bayar'] ?? 0);
            atur_flash('success', 'Pembayaran berhasil dicatat.');
        }

        header('Location: hutang_piutang.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: hutang_piutang.php');
        exit;
    }
}

$kontak = ambil_daftar_kontak();
$filterJenis = trim($_GET['jenis'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterKontakId = (int) ($_GET['kontak_id'] ?? 0);
$relasi = ambil_relasi_hutang_piutang([
    'jenis' => $filterJenis,
    'status' => $filterStatus,
    'kontak_id' => $filterKontakId,
]);
$kontakFilter = $kontak;

if ($filterJenis === 'Hutang') {
    $kontakFilter = array_values(array_filter($kontak, function ($item) {
        return ($item['jenis'] ?? '') === 'Pemasok';
    }));
}

if ($filterJenis === 'Piutang') {
    $kontakFilter = array_values(array_filter($kontak, function ($item) {
        return ($item['jenis'] ?? '') === 'Pelanggan';
    }));
}

$totalNominal = 0;
$totalDibayar = 0;
$totalSisa = 0;
foreach ($relasi as $itemRelasi) {
    $totalNominal += (float) $itemRelasi['nominal'];
    $totalDibayar += (float) $itemRelasi['dibayar'];
    $totalSisa += (float) $itemRelasi['sisa'];
}

render_header('Hutang dan Piutang', 'hutang_piutang');
?>
<section class="grid-two">
    <article class="panel">
        <h3>Tambah Kontak</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="simpan_kontak">
            <label>
                <span>Kode Kontak</span>
                <input type="text" name="kode_kontak" placeholder="Contoh: SUP-1">
            </label>
            <label>
                <span>Nama Kontak</span>
                <input type="text" name="nama" required>
            </label>
            <label>
                <span>Jenis Kontak</span>
                <select name="jenis">
                    <option value="Pelanggan">Pelanggan</option>
                    <option value="Pemasok">Pemasok</option>
                </select>
            </label>
            <label>
                <span>Telepon</span>
                <input type="text" name="telepon">
            </label>
            <label>
                <span>Alamat</span>
                <textarea name="alamat" rows="3"></textarea>
            </label>
            <button type="submit" class="button primary">Simpan Kontak</button>
        </form>
    </article>
    <article class="panel">
        <h3>Cara Pakai</h3>
        <p>Masukkan transaksi hutang atau piutang dari halaman jurnal umum. Di halaman ini Anda dapat memantau tagihan yang berjalan dan mencatat pembayaran secara ringkas.</p>
        <p class="helper-text">Agar arus kas tetap benar, pembayaran nyata tetap sebaiknya dicatat juga di jurnal umum.</p>
    </article>
</section>

<section class="panel">
    <div class="section-title">
        <h3>Daftar Hutang dan Piutang</h3>
        <span>Total kontak aktif: <?php echo count($kontak); ?></span>
    </div>
    <form method="get" class="form-grid filter-grid compact-filter-grid">
        <label>
            <span>Jenis Relasi</span>
            <select name="jenis" onchange="this.form.submit()">
                <option value="">Semua</option>
                <option value="Hutang" <?php echo $filterJenis === 'Hutang' ? 'selected' : ''; ?>>Hutang</option>
                <option value="Piutang" <?php echo $filterJenis === 'Piutang' ? 'selected' : ''; ?>>Piutang</option>
            </select>
        </label>
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
            <span>Kontak</span>
            <select name="kontak_id">
                <option value="0">Semua kontak</option>
                <?php foreach ($kontakFilter as $item) { ?>
                    <option value="<?php echo (int) $item['id']; ?>" <?php echo $filterKontakId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e(($item['kode_kontak'] ? $item['kode_kontak'] . ' - ' : '') . $item['nama']); ?></option>
                <?php } ?>
            </select>
        </label>
        <div class="button-row align-end-actions">
            <button type="submit" class="button primary">Terapkan</button>
            <a href="hutang_piutang.php" class="button ghost">Reset</a>
        </div>
    </form>
    <div class="cards three-up compact-cards">
        <article class="card accent-a">
            <p>Total Nominal</p>
            <strong><?php echo e(format_rupiah($totalNominal)); ?></strong>
        </article>
        <article class="card accent-b">
            <p>Total Dibayar</p>
            <strong><?php echo e(format_rupiah($totalDibayar)); ?></strong>
        </article>
        <article class="card accent-c">
            <p>Total Sisa</p>
            <strong><?php echo e(format_rupiah($totalSisa)); ?></strong>
        </article>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Kontak</th>
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
                    <td colspan="9" class="empty-state">Belum ada data hutang atau piutang.</td>
                </tr>
            <?php } ?>
            <?php foreach ($relasi as $baris) { ?>
                <tr>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($baris['jenis']); ?></td>
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
    <h3>Kontak Aktif</h3>
    <table class="table compact">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Jenis</th>
                <th>Telepon</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($kontak)) { ?>
                <tr>
                    <td colspan="5" class="empty-state">Belum ada kontak.</td>
                </tr>
            <?php } ?>
            <?php foreach ($kontak as $item) { ?>
                <tr>
                    <td><?php echo e($item['kode_kontak'] ?: '-'); ?></td>
                    <td><?php echo e($item['nama']); ?></td>
                    <td><?php echo e($item['jenis']); ?></td>
                    <td><?php echo e($item['telepon']); ?></td>
                    <td><?php echo e($item['alamat']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
<?php
render_footer();