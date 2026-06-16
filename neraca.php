<?php

require_once __DIR__ . '/fungsi.php';

$ringkasan = ringkasan_neraca();

render_header('Neraca', 'neraca');
?>
<section class="grid-two balance-grid">
    <article class="panel">
        <h3>Aset</h3>
        <table class="table compact">
            <tbody>
                <?php if (empty($ringkasan['Aset'])) { ?>
                    <tr>
                        <td class="empty-state">Belum ada saldo aset.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($ringkasan['Aset'] as $baris) { ?>
                    <tr>
                        <td><?php echo e($baris['kode_akun'] . ' - ' . $baris['nama_akun']); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($baris['saldo'])); ?></td>
                    </tr>
                <?php } ?>
                <tr class="total-row">
                    <td>Total Aset</td>
                    <td class="align-right"><?php echo e(format_rupiah($ringkasan['total_aset'])); ?></td>
                </tr>
            </tbody>
        </table>
    </article>
    <article class="panel">
        <h3>Kewajiban dan Ekuitas</h3>
        <table class="table compact">
            <tbody>
                <?php foreach ($ringkasan['Kewajiban'] as $baris) { ?>
                    <tr>
                        <td><?php echo e($baris['kode_akun'] . ' - ' . $baris['nama_akun']); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($baris['saldo'])); ?></td>
                    </tr>
                <?php } ?>
                <tr class="total-row">
                    <td>Total Kewajiban</td>
                    <td class="align-right"><?php echo e(format_rupiah($ringkasan['total_kewajiban'])); ?></td>
                </tr>
                <?php foreach ($ringkasan['Ekuitas'] as $baris) { ?>
                    <tr>
                        <td><?php echo e($baris['kode_akun'] . ' - ' . $baris['nama_akun']); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($baris['saldo'])); ?></td>
                    </tr>
                <?php } ?>
                <tr class="total-row">
                    <td>Total Ekuitas</td>
                    <td class="align-right"><?php echo e(format_rupiah($ringkasan['total_ekuitas'])); ?></td>
                </tr>
                <tr class="grand-row">
                    <td>Kewajiban + Ekuitas</td>
                    <td class="align-right"><?php echo e(format_rupiah($ringkasan['total_kewajiban'] + $ringkasan['total_ekuitas'])); ?></td>
                </tr>
            </tbody>
        </table>
    </article>
</section>
<?php
render_footer();