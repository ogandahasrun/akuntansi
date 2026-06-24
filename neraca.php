<?php

require_once __DIR__ . '/fungsi.php';

$ringkasan = ringkasan_neraca();
$pengaturan = ambil_pengaturan();
$tahunBuku = ambil_tahun_buku_aktif();

render_header('Neraca', 'neraca');
?>

<!-- Kop Surat Khusus Cetak (Print Only) -->
<div class="print-only">
    <div class="print-header">
        <?php if (!empty($pengaturan['logo'])) { ?>
            <img src="<?php echo e($pengaturan['logo']); ?>" alt="Logo" class="print-logo">
        <?php } ?>
        <h2><?php echo e($pengaturan['nama_perusahaan']); ?></h2>
        <h1>LAPORAN NERACA</h1>
        <p class="print-period">
            Periode: 
            <?php 
            if ($tahunBuku) {
                echo e($tahunBuku['nama']) . ' (' . format_tanggal_indonesia($tahunBuku['tanggal_mulai']) . ' s/d ' . format_tanggal_indonesia($tahunBuku['tanggal_selesai']) . ')';
            } else {
                echo 'Semua Periode';
            }
            ?>
        </p>
        <?php if (!empty($pengaturan['alamat']) || !empty($pengaturan['telepon']) || !empty($pengaturan['email'])) { ?>
            <p class="print-contact">
                <?php echo e($pengaturan['alamat']); ?>
                <?php if (!empty($pengaturan['telepon'])) echo ' | Telp: ' . e($pengaturan['telepon']); ?>
                <?php if (!empty($pengaturan['email'])) echo ' | Email: ' . e($pengaturan['email']); ?>
            </p>
        <?php } ?>
    </div>
</div>

<!-- Tombol Cetak & Salin (Screen Only) -->
<div class="button-row" style="margin-bottom: 22px; justify-content: flex-end;">
    <div style="display: inline-flex; gap: 10px;">
        <button type="button" id="btnCopy" class="button ghost">
            <span>📋 Salin ke Clipboard</span>
        </button>
        <button type="button" id="btnPrint" class="button primary">
            <span>🖨️ Cetak Laporan</span>
        </button>
    </div>
</div>

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

<script>
document.getElementById('btnPrint').addEventListener('click', function() {
    window.print();
});

document.getElementById('btnCopy').addEventListener('click', function() {
    const asets = <?php echo json_encode($ringkasan['Aset']); ?>;
    const kewajibans = <?php echo json_encode($ringkasan['Kewajiban']); ?>;
    const ekuitas = <?php echo json_encode($ringkasan['Ekuitas']); ?>;
    
    const totalAset = <?php echo (float) $ringkasan['total_aset']; ?>;
    const totalKewajiban = <?php echo (float) $ringkasan['total_kewajiban']; ?>;
    const totalEkuitas = <?php echo (float) $ringkasan['total_ekuitas']; ?>;
    const totalPasiva = totalKewajiban + totalEkuitas;
    
    // Siapkan baris sisi kiri (Aktiva/Aset)
    const left = [];
    left.push({ label: 'AKTIVA (ASET)', val: null });
    left.push({ label: '===================================', val: null });
    
    if (asets.length === 0) {
        left.push({ label: 'Belum ada saldo aset.', val: null });
    } else {
        asets.forEach(function(item) {
            left.push({ label: item.kode_akun + ' - ' + item.nama_akun, val: item.saldo });
        });
    }
    
    // Spacer baris agar total aset berada di bawah detail
    left.push({ label: '-----------------------------------', val: null });
    left.push({ label: 'TOTAL ASET', val: totalAset });

    // Siapkan baris sisi kanan (Pasiva/Kewajiban & Ekuitas)
    const right = [];
    right.push({ label: 'PASIVA (KEWAJIBAN & EKUITAS)', val: null });
    right.push({ label: '===================================', val: null });
    
    right.push({ label: '[KEWAJIBAN]', val: null });
    kewajibans.forEach(function(item) {
        right.push({ label: item.kode_akun + ' - ' + item.nama_akun, val: item.saldo });
    });
    right.push({ label: 'TOTAL KEWAJIBAN', val: totalKewajiban });
    
    right.push({ label: '', val: null }); // Baris pemisah kosong
    right.push({ label: '[EKUITAS]', val: null });
    ekuitas.forEach(function(item) {
        right.push({ label: item.kode_akun + ' - ' + item.nama_akun, val: item.saldo });
    });
    right.push({ label: 'TOTAL EKUITAS', val: totalEkuitas });
    
    right.push({ label: '-----------------------------------', val: null });
    right.push({ label: 'TOTAL KEWAJIBAN + EKUITAS', val: totalPasiva });

    // Menyatukan baris kiri dan kanan dengan pemisah Tab (\t)
    const maxRows = Math.max(left.length, right.length);
    let output = "AKTIVA (ASET)\tSALDO AKTIVA\tPASIVA (KEWAJIBAN & EKUITAS)\tSALDO PASIVA\r\n";
    output += "====================\t============\t============================\t============\r\n";
    
    for (let i = 0; i < maxRows; i++) {
        const l = left[i] || { label: '', val: null };
        const r = right[i] || { label: '', val: null };
        
        const lValStr = (l.val !== null) ? parseFloat(l.val).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) : '';
        const rValStr = (r.val !== null) ? parseFloat(r.val).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) : '';
        
        output += l.label + "\t" + lValStr + "\t" + r.label + "\t" + rValStr + "\r\n";
    }
    
    navigator.clipboard.writeText(output).then(function() {
        const btn = document.getElementById('btnCopy');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<span>✅ Tersalin!</span>';
        btn.style.borderColor = 'var(--success)';
        btn.style.color = 'var(--success)';
        setTimeout(function() {
            btn.innerHTML = originalHTML;
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2000);
    }).catch(function(err) {
        alert('Gagal menyalin data ke clipboard: ' + err);
    });
});
</script>
<?php
render_footer();