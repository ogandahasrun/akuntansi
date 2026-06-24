<?php

require_once __DIR__ . '/fungsi.php';

$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSelesai = $_GET['tanggal_selesai'] ?? '';

// Hitung Laba/Rugi
$laporan = hitung_laba_rugi_bersih($tanggalMulai, $tanggalSelesai);
$pengaturan = ambil_pengaturan();
$tahunBuku = ambil_tahun_buku_aktif();

render_header('Laporan Rugi Laba', 'rugi_laba');
?>

<!-- Kop Surat Khusus Cetak (Print Only) -->
<div class="print-only">
    <div class="print-header">
        <?php if (!empty($pengaturan['logo'])) { ?>
            <img src="<?php echo e($pengaturan['logo']); ?>" alt="Logo" class="print-logo">
        <?php } ?>
        <h2><?php echo e($pengaturan['nama_perusahaan']); ?></h2>
        <h1>LAPORAN RUGI LABA</h1>
        <p class="print-period">
            Periode: 
            <?php 
            if ($tanggalMulai !== '' && $tanggalSelesai !== '') {
                echo format_tanggal_indonesia($tanggalMulai) . ' s/d ' . format_tanggal_indonesia($tanggalSelesai);
            } elseif ($tahunBuku) {
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

<!-- Tombol & Filter (Screen Only) -->
<section class="panel screen-only">
    <h3>Filter Laporan Rugi Laba</h3>
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

<div class="button-row screen-only" style="margin-bottom: 22px; justify-content: flex-end;">
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
        <h3>Pendapatan</h3>
        <table class="table compact">
            <tbody>
                <?php if (empty($laporan['detail_pendapatan'])) { ?>
                    <tr>
                        <td class="empty-state">Belum ada saldo pendapatan.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($laporan['detail_pendapatan'] as $baris) { ?>
                    <tr>
                        <td><?php echo e($baris['kode_akun'] . ' - ' . $baris['nama_akun']); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($baris['saldo'])); ?></td>
                    </tr>
                <?php } ?>
                <tr class="total-row">
                    <td>Total Pendapatan</td>
                    <td class="align-right"><?php echo e(format_rupiah($laporan['total_pendapatan'])); ?></td>
                </tr>
            </tbody>
        </table>
    </article>
    <article class="panel">
        <h3>Beban</h3>
        <table class="table compact">
            <tbody>
                <?php if (empty($laporan['detail_beban'])) { ?>
                    <tr>
                        <td class="empty-state">Belum ada saldo beban.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($laporan['detail_beban'] as $baris) { ?>
                    <tr>
                        <td><?php echo e($baris['kode_akun'] . ' - ' . $baris['nama_akun']); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($baris['saldo'])); ?></td>
                    </tr>
                <?php } ?>
                <tr class="total-row">
                    <td>Total Beban</td>
                    <td class="align-right"><?php echo e(format_rupiah($laporan['total_beban'])); ?></td>
                </tr>
            </tbody>
        </table>
    </article>
</section>

<section class="panel" style="margin-top: 22px;">
    <div class="cards" style="display: block;">
        <article class="card <?php echo $laporan['laba_bersih'] >= 0 ? 'accent-a' : 'accent-b'; ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 2rem;">
            <div>
                <h3 style="margin: 0; color: var(--text);">Hasil Kinerja Operasional</h3>
                <p style="margin: 5px 0 0; color: var(--muted); font-size: 0.95rem;">Selisih total pendapatan dan total beban dalam periode akuntansi.</p>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.95rem; font-weight: 500; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em; display: block; margin-bottom: 5px;">
                    <?php echo $laporan['laba_bersih'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih'; ?>
                </span>
                <strong style="font-size: 2.2rem; color: <?php echo $laporan['laba_bersih'] >= 0 ? 'var(--primary)' : 'var(--danger)'; ?>;">
                    <?php echo e(format_rupiah(abs($laporan['laba_bersih']))); ?>
                </strong>
            </div>
        </article>
    </div>
</section>

<script>
document.getElementById('btnPrint').addEventListener('click', function() {
    window.print();
});

document.getElementById('btnCopy').addEventListener('click', function() {
    const pendapatans = <?php echo json_encode($laporan['detail_pendapatan']); ?>;
    const bebans = <?php echo json_encode($laporan['detail_beban']); ?>;
    const totalPendapatan = <?php echo (float) $laporan['total_pendapatan']; ?>;
    const totalBeban = <?php echo (float) $laporan['total_beban']; ?>;
    const labaBersih = <?php echo (float) $laporan['laba_bersih']; ?>;
    
    let output = "LAPORAN RUGI LABA\r\n";
    output += "===================================\r\n\r\n";
    
    output += "PENDAPATAN\r\n";
    output += "-----------------------------------\r\n";
    pendapatans.forEach(function(item) {
        output += item.kode_akun + " - " + item.nama_akun + "\tRp " + parseFloat(item.saldo).toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n";
    });
    output += "TOTAL PENDAPATAN\tRp " + totalPendapatan.toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n\r\n";
    
    output += "BEBAN\r\n";
    output += "-----------------------------------\r\n";
    bebans.forEach(function(item) {
        output += item.kode_akun + " - " + item.nama_akun + "\tRp " + parseFloat(item.saldo).toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n";
    });
    output += "TOTAL BEBAN\tRp " + totalBeban.toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n\r\n";
    
    output += "===================================\r\n";
    output += (labaBersih >= 0 ? "LABA BERSIH" : "RUGI BERSIH") + "\tRp " + Math.abs(labaBersih).toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n";
    
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
