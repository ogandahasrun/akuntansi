<?php

require_once __DIR__ . '/fungsi.php';

$akun = ambil_daftar_akun();
$akunId = isset($_GET['akun_id']) ? (int) $_GET['akun_id'] : (isset($akun[0]['id']) ? (int) $akun[0]['id'] : 0);
$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSelesai = $_GET['tanggal_selesai'] ?? '';
$laporan = $akunId > 0 ? laporan_buku_besar($akunId, $tanggalMulai, $tanggalSelesai) : null;

render_header('Buku Besar', 'buku_besar');
?>
<section class="panel">
    <h3>Filter Buku Besar</h3>
    <form method="get" class="form-grid filter-grid">
        <label>
            <span>Akun</span>
            <select name="akun_id">
                <?php foreach ($akun as $item) { ?>
                    <option value="<?php echo (int) $item['id']; ?>" <?php echo $akunId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['kode_akun'] . ' - ' . $item['nama_akun']); ?></option>
                <?php } ?>
            </select>
        </label>
        <label>
            <span>Tanggal Mulai</span>
            <input type="date" name="tanggal_mulai" value="<?php echo e($tanggalMulai); ?>">
        </label>
        <label>
            <span>Tanggal Selesai</span>
            <input type="date" name="tanggal_selesai" value="<?php echo e($tanggalSelesai); ?>">
        </label>
        <button type="submit" class="button primary">Tampilkan</button>
    </form>
</section>

<section class="panel">
    <div class="section-title">
        <h3>Mutasi Akun</h3>
        <?php if ($laporan) { ?>
            <span><?php echo e($laporan['akun']['nama_akun']); ?> | Saldo akhir <?php echo e(format_rupiah($laporan['saldo_akhir'])); ?></span>
        <?php } ?>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nomor Bukti</th>
                <th>Keterangan</th>
                <th class="align-right">Debit</th>
                <th class="align-right">Kredit</th>
                <th class="align-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($laporan) { ?>
                <tr>
                    <td colspan="5"><strong>Saldo Awal</strong></td>
                    <td class="align-right"><strong><?php echo e(format_rupiah($laporan['saldo_awal'])); ?></strong></td>
                </tr>
                <?php if (empty($laporan['baris'])) { ?>
                    <tr>
                        <td colspan="6" class="empty-state">Tidak ada transaksi pada filter ini.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($laporan['baris'] as $baris) { ?>
                    <tr>
                        <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                        <td><?php echo e($baris['nomor_bukti']); ?></td>
                        <td><?php echo e($baris['keterangan']); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($baris['debit'])); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($baris['kredit'])); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($baris['saldo'])); ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6" class="empty-state">Belum ada akun untuk ditampilkan.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
<?php
render_footer();