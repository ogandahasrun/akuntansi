<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodeRequest === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'simpan_kontak') {
            // Hardcode jenis sebagai Pemasok
            $_POST['jenis'] = 'Pemasok';
            simpan_kontak($_POST);
            atur_flash('success', 'Pemasok berhasil ditambahkan.');
        }

        if ($aksi === 'bayar_relasi') {
            catat_pembayaran_relasi((int) ($_POST['id_relasi'] ?? 0), $_POST['jumlah_bayar'] ?? 0);
            atur_flash('success', 'Pembayaran hutang berhasil dicatat.');
        }

        header('Location: hutang.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: hutang.php');
        exit;
    }
}

$kontak = ambil_daftar_kontak();
// Filter hanya pemasok
$kontakPemasok = array_values(array_filter($kontak, function ($item) {
    return ($item['jenis'] ?? '') === 'Pemasok';
}));

$filterStatus = trim($_GET['status'] ?? '');
$filterKontakId = (int) ($_GET['kontak_id'] ?? 0);
$relasi = ambil_relasi_hutang_piutang([
    'jenis' => 'Hutang',
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

render_header('Kelola Hutang', 'hutang');
?>
<section class="grid-two">
    <article class="panel">
        <h3>Tambah Pemasok (Supplier)</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="simpan_kontak">
            <label>
                <span>Kode Kontak</span>
                <input type="text" name="kode_kontak" placeholder="Contoh: SUP-1">
            </label>
            <label>
                <span>Nama Pemasok</span>
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
            <button type="submit" class="button primary">Simpan Pemasok</button>
        </form>
    </article>
    <article class="panel">
        <h3>Cara Pakai</h3>
        <p>Masukkan transaksi hutang dari halaman <strong>Jurnal Umum</strong> dengan memilih jenis transaksi <strong>Hutang</strong>.</p>
        <p>Di halaman ini Anda dapat memantau tagihan hutang kepada pemasok yang sedang berjalan dan mencatat pelunasan/pembayaran secara ringkas.</p>
        <p class="helper-text">Agar catatan arus kas tetap benar, disarankan mencatat transaksi pembayaran nyata melalui Jurnal Umum menggunakan jenis <strong>Bayar Hutang</strong>.</p>
    </article>
</section>

<section class="panel">
    <div class="section-title">
        <h3>Daftar Hutang (Kewajiban)</h3>
        <span>Total pemasok aktif: <?php echo count($kontakPemasok); ?></span>
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
            <span>Pemasok</span>
            <select name="kontak_id">
                <option value="0">Semua pemasok</option>
                <?php foreach ($kontakPemasok as $item) { ?>
                    <option value="<?php echo (int) $item['id']; ?>" <?php echo $filterKontakId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e(($item['kode_kontak'] ? $item['kode_kontak'] . ' - ' : '') . $item['nama']); ?></option>
                <?php } ?>
            </select>
        </label>
        <div class="button-row align-end-actions">
            <button type="submit" class="button primary">Terapkan</button>
            <a href="hutang.php" class="button ghost">Reset</a>
        </div>
    </form>
    <div class="cards three-up compact-cards">
        <article class="card accent-b">
            <p>Total Nominal Hutang</p>
            <strong><?php echo e(format_rupiah($totalNominal)); ?></strong>
        </article>
        <article class="card accent-a">
            <p>Total Dibayar</p>
            <strong><?php echo e(format_rupiah($totalDibayar)); ?></strong>
        </article>
        <article class="card accent-c">
            <p>Total Sisa Hutang</p>
            <strong><?php echo e(format_rupiah($totalSisa)); ?></strong>
        </article>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pemasok</th>
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
                    <td colspan="8" class="empty-state">Belum ada data hutang.</td>
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
    <h3>Daftar Pemasok Aktif</h3>
    <table class="table compact">
        <thead>
            <tr>
                <th>Kode Pemasok</th>
                <th>Nama Pemasok</th>
                <th>Telepon</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($kontakPemasok)) { ?>
                <tr>
                    <td colspan="4" class="empty-state">Belum ada data pemasok.</td>
                </tr>
            <?php } ?>
            <?php foreach ($kontakPemasok as $item) { ?>
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
