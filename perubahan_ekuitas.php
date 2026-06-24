<?php

require_once __DIR__ . '/fungsi.php';

$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSelesai = $_GET['tanggal_selesai'] ?? '';

// Ambil Laporan Perubahan Ekuitas
$laporan = laporan_perubahan_ekuitas($tanggalMulai, $tanggalSelesai);
$pengaturan = ambil_pengaturan();
$tahunBuku = ambil_tahun_buku_aktif();

render_header('Laporan Perubahan Ekuitas', 'perubahan_ekuitas');
?>

<!-- Kop Surat Khusus Cetak (Print Only) -->
<div class="print-only">
    <div class="print-header">
        <?php if (!empty($pengaturan['logo'])) { ?>
            <img src="<?php echo e($pengaturan['logo']); ?>" alt="Logo" class="print-logo">
        <?php } ?>
        <h2><?php echo e($pengaturan['nama_perusahaan']); ?></h2>
        <h1>LAPORAN PERUBAHAN EKUITAS</h1>
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
    <h3>Filter Laporan Perubahan Ekuitas</h3>
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

<section class="panel">
    <h3>Perubahan Modal Pemilik</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Akun Ekuitas</th>
                <th class="align-right">Saldo Awal</th>
                <th class="align-right">Mutasi / Penambahan</th>
                <th class="align-right">Laba Bersih</th>
                <th class="align-right">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporan['baris'])) { ?>
                <tr>
                    <td colspan="5" class="empty-state">Belum ada akun ekuitas terdaftar.</td>
                </tr>
            <?php } ?>
            <?php foreach ($laporan['baris'] as $baris) { ?>
                <tr>
                    <td><strong><?php echo e($baris['kode_akun'] . ' - ' . $baris['nama_akun']); ?></strong></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['saldo_awal'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['mutasi'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['laba_bersih'])); ?></td>
                    <td class="align-right"><strong><?php echo e(format_rupiah($baris['saldo_akhir'])); ?></strong></td>
                </tr>
            <?php } ?>
            <tr class="grand-row">
                <td>TOTAL EKUITAS / MODAL</td>
                <td class="align-right"><?php echo e(format_rupiah($laporan['total_awal'])); ?></td>
                <td class="align-right"><?php echo e(format_rupiah($laporan['total_mutasi'])); ?></td>
                <td class="align-right"><?php echo e(format_rupiah($laporan['total_laba'])); ?></td>
                <td class="align-right"><?php echo e(format_rupiah($laporan['total_akhir'])); ?></td>
            </tr>
        </tbody>
    </table>
</section>

<script>
document.getElementById('btnPrint').addEventListener('click', function() {
    window.print();
});

document.getElementById('btnCopy').addEventListener('click', function() {
    const baris = <?php echo json_encode($laporan['baris']); ?>;
    const totalAwal = <?php echo (float) $laporan['total_awal']; ?>;
    const totalMutasi = <?php echo (float) $laporan['total_mutasi']; ?>;
    const totalLaba = <?php echo (float) $laporan['total_laba']; ?>;
    const totalAkhir = <?php echo (float) $laporan['total_akhir']; ?>;
    
    let output = "LAPORAN PERUBAHAN EKUITAS\r\n";
    output += "=========================================================================================\r\n";
    output += "Akun Ekuitas\tSaldo Awal\tMutasi / Penambahan\tLaba Bersih\tSaldo Akhir\r\n";
    output += "-----------------------------------------------------------------------------------------\r\n";
    
    baris.forEach(function(item) {
        output += item.kode_akun + " - " + item.nama_akun + "\t" +
                  parseFloat(item.saldo_awal).toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\t" +
                  parseFloat(item.mutasi).toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\t" +
                  parseFloat(item.laba_bersih).toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\t" +
                  parseFloat(item.saldo_akhir).toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n";
    });
    
    output += "=========================================================================================\r\n";
    output += "TOTAL EKUITAS / MODAL\t" +
              totalAwal.toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\t" +
              totalMutasi.toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\t" +
              totalLaba.toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\t" +
              totalAkhir.toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n";
              
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
