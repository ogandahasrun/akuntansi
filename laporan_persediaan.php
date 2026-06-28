<?php

require_once __DIR__ . '/fungsi.php';

$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSelesai = $_GET['tanggal_selesai'] ?? '';
$akunId = isset($_GET['akun_id']) ? (int)$_GET['akun_id'] : 0;

// Ambil tahun buku aktif
$tahunBuku = ambil_tahun_buku_aktif();
if ($tahunBuku) {
    if ($tanggalMulai === '') {
        $tanggalMulai = $tahunBuku['tanggal_mulai'];
    }
    if ($tanggalSelesai === '') {
        $tanggalSelesai = $tahunBuku['tanggal_selesai'];
    }
}

// Ambil daftar akun filter
$daftarAkunFilter = kueri_semua("SELECT id, kode_akun, nama_akun FROM akun WHERE tipe_detail = 'Persediaan' AND aktif = 1 ORDER BY kode_akun ASC");

// Ambil data laporan
$mutasiPersediaan = ambil_mutasi_persediaan($tanggalMulai, $tanggalSelesai, $akunId);
$riwayatPenyesuaian = ambil_riwayat_penyesuaian_persediaan($tanggalMulai, $tanggalSelesai, $akunId);
$pengaturan = ambil_pengaturan();

render_header('Laporan Persediaan', 'laporan_persediaan');
?>

<style>
.tab-container {
    margin-top: 10px;
    margin-bottom: 20px;
}
.tab-links {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid var(--line);
    margin-bottom: 20px;
    padding-bottom: 0;
}
.tab-link {
    background: none;
    border: 2px solid transparent;
    border-bottom: none;
    padding: 12px 24px;
    font-size: 1rem;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    transition: all 0.2s ease;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    margin-bottom: -2px;
}
.tab-link.active {
    color: var(--primary);
    background-color: var(--panel);
    border-color: var(--line);
    border-bottom: 2px solid var(--panel);
}
.tab-link:hover:not(.active) {
    color: var(--text);
    background-color: rgba(0,0,0,0.02);
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}

@media print {
    .tab-links, .screen-only, .tab-link {
        display: none !important;
    }
    .tab-content {
        display: block !important;
        page-break-after: always;
    }
    .tab-content:last-child {
        page-break-after: avoid;
    }
    .print-title {
        display: block !important;
        margin-top: 30px;
        border-bottom: 2px solid #000;
        padding-bottom: 5px;
    }
}
</style>

<!-- Kop Surat Khusus Cetak (Print Only) -->
<div class="print-only">
    <div class="print-header">
        <?php if (!empty($pengaturan['logo'])) { ?>
            <img src="<?php echo e($pengaturan['logo']); ?>" alt="Logo" class="print-logo">
        <?php } ?>
        <h2><?php echo e($pengaturan['nama_perusahaan']); ?></h2>
        <h1>LAPORAN PERSEDIAAN & PENYESUAIAN STOK</h1>
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
    <h3>Filter Laporan Persediaan</h3>
    <form method="get" class="form-grid filter-grid">
        <label>
            <span>Akun Persediaan</span>
            <select name="akun_id">
                <option value="0">-- Semua Akun Persediaan --</option>
                <?php foreach ($daftarAkunFilter as $item) { ?>
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

<!-- Tombol Aksi (Screen Only) -->
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

<!-- Container Tab Navigasi -->
<div class="tab-container screen-only">
    <div class="tab-links">
        <button type="button" class="tab-link active" onclick="switchTab('mutasi')">📊 Mutasi Nilai Persediaan (Opsi 1)</button>
        <button type="button" class="tab-link" onclick="switchTab('riwayat')">🔍 Riwayat Penyesuaian & Opname (Opsi 2)</button>
    </div>
</div>

<!-- Tab Content 1: Mutasi Nilai Persediaan -->
<div id="tab-mutasi" class="tab-content active">
    <h3 class="print-only print-title" style="display: none; font-size: 1.3rem; margin-bottom: 15px;">I. Laporan Mutasi Nilai Persediaan</h3>
    <section class="panel">
        <div class="section-title">
            <h3>Mutasi Nilai Akun Persediaan</h3>
            <span class="screen-only">Rekapitulasi nilai saldo awal dan mutasi masuk/keluar</span>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th class="align-right">Saldo Awal</th>
                    <th class="align-right">Mutasi Debit (Masuk)</th>
                    <th class="align-right">Mutasi Kredit (Keluar)</th>
                    <th class="align-right">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mutasiPersediaan['detail'])) { ?>
                    <tr>
                        <td colspan="6" class="empty-state">Tidak ada akun persediaan yang aktif.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($mutasiPersediaan['detail'] as $baris) { ?>
                        <tr>
                            <td><strong><?php echo e($baris['kode_akun']); ?></strong></td>
                            <td><?php echo e($baris['nama_akun']); ?></td>
                            <td class="align-right"><?php echo e(format_rupiah($baris['saldo_awal'])); ?></td>
                            <td class="align-right" style="color: var(--success); font-weight: 500;"><?php echo $baris['debit'] > 0 ? format_rupiah($baris['debit']) : '-'; ?></td>
                            <td class="align-right" style="color: var(--danger); font-weight: 500;"><?php echo $baris['kredit'] > 0 ? format_rupiah($baris['kredit']) : '-'; ?></td>
                            <td class="align-right"><strong><?php echo e(format_rupiah($baris['saldo_akhir'])); ?></strong></td>
                        </tr>
                    <?php } ?>
                    <tr class="total-row">
                        <td colspan="2">TOTAL KESELURUHAN</td>
                        <td class="align-right"><?php echo e(format_rupiah($mutasiPersediaan['total_saldo_awal'])); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($mutasiPersediaan['total_debit'])); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($mutasiPersediaan['total_kredit'])); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($mutasiPersediaan['total_saldo_akhir'])); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>
</div>

<!-- Tab Content 2: Riwayat Penyesuaian & Opname -->
<div id="tab-riwayat" class="tab-content">
    <h3 class="print-only print-title" style="display: none; font-size: 1.3rem; margin-bottom: 15px;">II. Riwayat Penyesuaian & Stock Opname</h3>
    <section class="panel">
        <div class="section-title">
            <h3>Jurnal Penyesuaian Persediaan</h3>
            <span class="screen-only">Log transaksi penyesuaian/pemakaian dari AJP</span>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nomor Bukti</th>
                    <th>Akun Persediaan</th>
                    <th>Keterangan</th>
                    <th class="align-right">Debit (Penambahan)</th>
                    <th class="align-right">Kredit (Pengurangan)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayatPenyesuaian)) { ?>
                    <tr>
                        <td colspan="6" class="empty-state">Tidak ada transaksi penyesuaian persediaan pada periode ini.</td>
                    </tr>
                <?php } else { ?>
                    <?php 
                    $totalAdjDebit = 0;
                    $totalAdjKredit = 0;
                    foreach ($riwayatPenyesuaian as $baris) { 
                        $totalAdjDebit += (float)$baris['debit'];
                        $totalAdjKredit += (float)$baris['kredit'];
                        ?>
                        <tr>
                            <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                            <td><code><?php echo e($baris['nomor_bukti']); ?></code></td>
                            <td><?php echo e($baris['kode_akun'] . ' - ' . $baris['nama_akun']); ?></td>
                            <td><?php echo e($baris['keterangan']); ?></td>
                            <td class="align-right" style="color: var(--success); font-weight: 500;"><?php echo $baris['debit'] > 0 ? format_rupiah($baris['debit']) : '-'; ?></td>
                            <td class="align-right" style="color: var(--danger); font-weight: 500;"><?php echo $baris['kredit'] > 0 ? format_rupiah($baris['kredit']) : '-'; ?></td>
                        </tr>
                    <?php } ?>
                    <tr class="total-row">
                        <td colspan="4">TOTAL PENYESUAIAN</td>
                        <td class="align-right"><?php echo e(format_rupiah($totalAdjDebit)); ?></td>
                        <td class="align-right"><?php echo e(format_rupiah($totalAdjKredit)); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>
</div>

<script>
// Tab Switching
let activeTab = 'mutasi';

function switchTab(tabName) {
    activeTab = tabName;
    
    // Toggle active class on links
    document.querySelectorAll('.tab-link').forEach(link => {
        link.classList.remove('active');
    });
    const selectedLink = document.querySelector(`.tab-link[onclick="switchTab('${tabName}')"]`);
    if (selectedLink) selectedLink.classList.add('active');
    
    // Toggle active class on content divs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tabName}`).classList.add('active');
}

// Print Action
document.getElementById('btnPrint').addEventListener('click', function() {
    window.print();
});

// Copy to Clipboard Action
document.getElementById('btnCopy').addEventListener('click', function() {
    let output = "";
    const pNama = <?php echo json_encode($pengaturan['nama_perusahaan']); ?>;
    const tMulai = <?php echo json_encode(format_tanggal_indonesia($tanggalMulai)); ?>;
    const tSelesai = <?php echo json_encode(format_tanggal_indonesia($tanggalSelesai)); ?>;

    if (activeTab === 'mutasi') {
        const detail = <?php echo json_encode($mutasiPersediaan['detail']); ?>;
        const totalAwal = <?php echo (float) $mutasiPersediaan['total_saldo_awal']; ?>;
        const totalDebit = <?php echo (float) $mutasiPersediaan['total_debit']; ?>;
        const totalKredit = <?php echo (float) $mutasiPersediaan['total_kredit']; ?>;
        const totalAkhir = <?php echo (float) $mutasiPersediaan['total_saldo_akhir']; ?>;
        
        output += `LAPORAN MUTASI NILAI PERSEDIAAN\r\n`;
        output += `Perusahaan: ${pNama}\r\n`;
        output += `Periode: ${tMulai} s/d ${tSelesai}\r\n`;
        output += `=========================================================================\r\n`;
        output += `Kode\tNama Akun\tSaldo Awal\tDebit (Masuk)\tKredit (Keluar)\tSaldo Akhir\r\n`;
        output += `-------------------------------------------------------------------------\r\n`;
        
        detail.forEach(function(item) {
            output += `${item.kode_akun}\t${item.nama_akun}\tRp ${parseFloat(item.saldo_awal).toLocaleString('id-ID', { minimumFractionDigits: 0 })}\tRp ${parseFloat(item.debit).toLocaleString('id-ID', { minimumFractionDigits: 0 })}\tRp ${parseFloat(item.kredit).toLocaleString('id-ID', { minimumFractionDigits: 0 })}\tRp ${parseFloat(item.saldo_akhir).toLocaleString('id-ID', { minimumFractionDigits: 0 })}\r\n`;
        });
        
        output += `-------------------------------------------------------------------------\r\n`;
        output += `TOTAL KESELURUHAN\t\tRp ${totalAwal.toLocaleString('id-ID')}\tRp ${totalDebit.toLocaleString('id-ID')}\tRp ${totalKredit.toLocaleString('id-ID')}\tRp ${totalAkhir.toLocaleString('id-ID')}\r\n`;
        output += `=========================================================================\r\n`;
        
    } else {
        const riwayat = <?php echo json_encode($riwayatPenyesuaian); ?>;
        
        output += `LAPORAN RIWAYAT PENYESUAIAN & STOCK OPNAME\r\n`;
        output += `Perusahaan: ${pNama}\r\n`;
        output += `Periode: ${tMulai} s/d ${tSelesai}\r\n`;
        output += `=========================================================================\r\n`;
        output += `Tanggal\tNomor Bukti\tAkun Persediaan\tKeterangan\tDebit (Adj+)\tKredit (Adj-)\r\n`;
        output += `-------------------------------------------------------------------------\r\n`;
        
        let totalD = 0;
        let totalK = 0;
        
        riwayat.forEach(function(item) {
            totalD += parseFloat(item.debit);
            totalK += parseFloat(item.kredit);
            
            output += `${item.tanggal}\t${item.nomor_bukti}\t${item.kode_akun} - ${item.nama_akun}\t${item.keterangan}\tRp ${parseFloat(item.debit).toLocaleString('id-ID', { minimumFractionDigits: 0 })}\tRp ${parseFloat(item.kredit).toLocaleString('id-ID', { minimumFractionDigits: 0 })}\r\n`;
        });
        
        output += `-------------------------------------------------------------------------\r\n`;
        output += `TOTAL PENYESUAIAN\t\t\t\tRp ${totalD.toLocaleString('id-ID')}\tRp ${totalK.toLocaleString('id-ID')}\r\n`;
        output += `=========================================================================\r\n`;
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
?>
