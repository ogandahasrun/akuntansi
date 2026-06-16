<?php

require_once __DIR__ . '/fungsi.php';

$ringkasan = ringkasan_dashboard();
$jurnalTerbaru = ambil_jurnal_terbaru();

render_header('Dashboard', 'dashboard');
?>
<section class="cards">
    <article class="card accent-a">
        <p>Total Aset</p>
        <strong><?php echo e(format_rupiah($ringkasan['aset'])); ?></strong>
    </article>
    <article class="card accent-b">
        <p>Total Kewajiban</p>
        <strong><?php echo e(format_rupiah($ringkasan['kewajiban'])); ?></strong>
    </article>
    <article class="card accent-c">
        <p>Total Piutang Berjalan</p>
        <strong><?php echo e(format_rupiah($ringkasan['piutang_berjalan'])); ?></strong>
    </article>
    <article class="card accent-d">
        <p>Total Hutang Berjalan</p>
        <strong><?php echo e(format_rupiah($ringkasan['hutang_berjalan'])); ?></strong>
    </article>
</section>

<section class="grid-two">
    <article class="panel">
        <h3>Mulai Pencatatan</h3>
        <p>Urutan kerja paling aman adalah melengkapi daftar akun, lalu membuat jurnal umum, dan memantau laporan melalui buku besar, arus kas, serta neraca.</p>
        <div class="button-row">
            <a href="akun.php" class="button">Kelola Akun</a>
            <a href="jurnal_umum.php" class="button primary">Input Jurnal</a>
        </div>
    </article>
    <article class="panel">
        <h3>Keseimbangan Dasar</h3>
        <table class="table compact">
            <tbody>
                <tr>
                    <td>Ekuitas</td>
                    <td class="align-right"><?php echo e(format_rupiah($ringkasan['ekuitas'])); ?></td>
                </tr>
                <tr>
                    <td>Aset</td>
                    <td class="align-right"><?php echo e(format_rupiah($ringkasan['aset'])); ?></td>
                </tr>
                <tr>
                    <td>Kewajiban + Ekuitas</td>
                    <td class="align-right"><?php echo e(format_rupiah($ringkasan['kewajiban'] + $ringkasan['ekuitas'])); ?></td>
                </tr>
            </tbody>
        </table>
    </article>
</section>

<section class="panel">
    <div class="section-title">
        <h3>Jurnal Terbaru</h3>
        <a href="jurnal_umum.php">Lihat dan tambah jurnal</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nomor Bukti</th>
                <th>Jenis</th>
                <th>Keterangan</th>
                <th class="align-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jurnalTerbaru)) { ?>
                <tr>
                    <td colspan="5" class="empty-state">Belum ada jurnal yang dicatat.</td>
                </tr>
            <?php } ?>
            <?php foreach ($jurnalTerbaru as $baris) { ?>
                <tr>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($baris['nomor_bukti']); ?></td>
                    <td><?php echo e($baris['jenis_transaksi']); ?></td>
                    <td><?php echo e($baris['keterangan']); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['total_nominal'])); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
<?php
render_footer();