<?php

require_once __DIR__ . '/fungsi.php';

$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSelesai = $_GET['tanggal_selesai'] ?? '';
$laporan = laporan_arus_kas($tanggalMulai, $tanggalSelesai);

render_header('Laporan Arus Kas', 'arus_kas');
?>
<section class="panel">
    <h3>Filter Arus Kas</h3>
    <form method="get" class="form-grid filter-grid">
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

<section class="cards three-up">
    <article class="card accent-a">
        <p>Kas Masuk</p>
        <strong><?php echo e(format_rupiah($laporan['total_masuk'])); ?></strong>
    </article>
    <article class="card accent-b">
        <p>Kas Keluar</p>
        <strong><?php echo e(format_rupiah($laporan['total_keluar'])); ?></strong>
    </article>
    <article class="card accent-c">
        <p>Saldo Bersih</p>
        <strong><?php echo e(format_rupiah($laporan['saldo_bersih'])); ?></strong>
    </article>
</section>

<section class="panel">
    <h3>Detail Arus Kas</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nomor Bukti</th>
                <th>Keterangan</th>
                <th>Akun Kas/Bank</th>
                <th class="align-right">Arus</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporan['baris'])) { ?>
                <tr>
                    <td colspan="5" class="empty-state">Belum ada transaksi kas pada periode ini.</td>
                </tr>
            <?php } ?>
            <?php foreach ($laporan['baris'] as $baris) { ?>
                <tr>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($baris['nomor_bukti']); ?></td>
                    <td><?php echo e($baris['keterangan']); ?></td>
                    <td><?php echo e($baris['nama_akun']); ?></td>
                    <td class="align-right <?php echo $baris['arus'] >= 0 ? 'text-positive' : 'text-negative'; ?>"><?php echo e(format_rupiah($baris['arus'])); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
<?php
render_footer();