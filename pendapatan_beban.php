<?php

require_once __DIR__ . '/fungsi.php';

// Ambil daftar rekening Kas & Bank (akun dengan tipe_detail = 'Kas/Bank')
$kasBankList = kueri_semua("SELECT * FROM akun WHERE aktif = 1 AND tipe_detail = 'Kas/Bank' ORDER BY kode_akun ASC");

$akunIdInput = $_GET['akun_id'] ?? 'semua';
$akunId = ($akunIdInput === 'semua') ? 'semua' : (int) $akunIdInput;
$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSelesai = $_GET['tanggal_selesai'] ?? '';

$pengaturan = ambil_pengaturan();
$tahunBuku = ambil_tahun_buku_aktif();

// Normalisasi Tanggal berdasarkan Tahun Buku Aktif
if ($tahunBuku) {
    if ($tanggalMulai === '' || $tanggalMulai < $tahunBuku['tanggal_mulai']) {
        $tanggalMulai = $tahunBuku['tanggal_mulai'];
    }
    if ($tanggalSelesai === '' || $tanggalSelesai > $tahunBuku['tanggal_selesai']) {
        $tanggalSelesai = $tahunBuku['tanggal_selesai'];
    }
}
if ($tanggalSelesai !== '' && $tanggalMulai !== '' && $tanggalMulai > $tanggalSelesai) {
    $tanggalSelesai = $tanggalMulai;
}

$tanggalSebelumnya = date('Y-m-d', strtotime($tanggalMulai . ' -1 day'));

// 1. Hitung Saldo Awal dan Akhir Rekening Kas & Bank
$saldoAwal = 0;
$saldoAkhir = 0;
$namaRekening = "Semua Rekening Kas & Bank";
$kodeRekening = "";

if ($akunId === 'semua') {
    foreach ($kasBankList as $kb) {
        $saldoAwal += hitung_saldo_akun($kb['id'], $tanggalSebelumnya);
        $saldoAkhir += hitung_saldo_akun($kb['id'], $tanggalSelesai);
    }
} else {
    $targetAkun = kueri_satu("SELECT * FROM akun WHERE id = " . $akunId . " LIMIT 1");
    if ($targetAkun) {
        $namaRekening = $targetAkun['nama_akun'];
        $kodeRekening = $targetAkun['kode_akun'];
    }
    $saldoAwal = hitung_saldo_akun($akunId, $tanggalSebelumnya);
    $saldoAkhir = hitung_saldo_akun($akunId, $tanggalSelesai);
}

// 2. Tarik Data Mutasi Pendapatan & Beban Berdasarkan Rekening Kas & Bank
$pendapatanRows = [];
$bebanRows = [];

if ($akunId === 'semua') {
    // Kueri Pendapatan untuk Semua Rekening Kas & Bank
    $stmtP = $koneksi->prepare("
        SELECT j.tanggal, j.nomor_bukti, j.keterangan, jd.debit, jd.kredit, jd.akun_id, a.kode_akun, a.nama_akun
        FROM jurnal_detail jd
        INNER JOIN jurnal j ON j.id = jd.jurnal_id
        INNER JOIN akun a ON a.id = jd.akun_id
        WHERE a.aktif = 1 AND a.kategori = 'Pendapatan'
          AND j.tanggal >= ? AND j.tanggal <= ?
          AND j.id IN (
              SELECT jd2.jurnal_id 
              FROM jurnal_detail jd2 
              INNER JOIN akun a2 ON a2.id = jd2.akun_id
              WHERE a2.tipe_detail = 'Kas/Bank'
          )
        ORDER BY j.tanggal ASC, j.id ASC, jd.id ASC
    ");
    $stmtP->bind_param("ss", $tanggalMulai, $tanggalSelesai);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    $pendapatanRows = $resP ? $resP->fetch_all(MYSQLI_ASSOC) : [];
    $stmtP->close();

    // Kueri Beban untuk Semua Rekening Kas & Bank
    $stmtB = $koneksi->prepare("
        SELECT j.tanggal, j.nomor_bukti, j.keterangan, jd.debit, jd.kredit, jd.akun_id, a.kode_akun, a.nama_akun
        FROM jurnal_detail jd
        INNER JOIN jurnal j ON j.id = jd.jurnal_id
        INNER JOIN akun a ON a.id = jd.akun_id
        WHERE a.aktif = 1 AND a.kategori = 'Beban'
          AND j.tanggal >= ? AND j.tanggal <= ?
          AND j.id IN (
              SELECT jd2.jurnal_id 
              FROM jurnal_detail jd2 
              INNER JOIN akun a2 ON a2.id = jd2.akun_id
              WHERE a2.tipe_detail = 'Kas/Bank'
          )
        ORDER BY j.tanggal ASC, j.id ASC, jd.id ASC
    ");
    $stmtB->bind_param("ss", $tanggalMulai, $tanggalSelesai);
    $stmtB->execute();
    $resB = $stmtB->get_result();
    $bebanRows = $resB ? $resB->fetch_all(MYSQLI_ASSOC) : [];
    $stmtB->close();
} else {
    // Kueri Pendapatan untuk Rekening Kas & Bank Spesifik
    $stmtP = $koneksi->prepare("
        SELECT j.tanggal, j.nomor_bukti, j.keterangan, jd.debit, jd.kredit, jd.akun_id, a.kode_akun, a.nama_akun
        FROM jurnal_detail jd
        INNER JOIN jurnal j ON j.id = jd.jurnal_id
        INNER JOIN akun a ON a.id = jd.akun_id
        WHERE a.aktif = 1 AND a.kategori = 'Pendapatan'
          AND j.tanggal >= ? AND j.tanggal <= ?
          AND j.id IN (
              SELECT jd2.jurnal_id 
              FROM jurnal_detail jd2 
              WHERE jd2.akun_id = ?
          )
        ORDER BY j.tanggal ASC, j.id ASC, jd.id ASC
    ");
    $stmtP->bind_param("ssi", $tanggalMulai, $tanggalSelesai, $akunId);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    $pendapatanRows = $resP ? $resP->fetch_all(MYSQLI_ASSOC) : [];
    $stmtP->close();

    // Kueri Beban untuk Rekening Kas & Bank Spesifik
    $stmtB = $koneksi->prepare("
        SELECT j.tanggal, j.nomor_bukti, j.keterangan, jd.debit, jd.kredit, jd.akun_id, a.kode_akun, a.nama_akun
        FROM jurnal_detail jd
        INNER JOIN jurnal j ON j.id = jd.jurnal_id
        INNER JOIN akun a ON a.id = jd.akun_id
        WHERE a.aktif = 1 AND a.kategori = 'Beban'
          AND j.tanggal >= ? AND j.tanggal <= ?
          AND j.id IN (
              SELECT jd2.jurnal_id 
              FROM jurnal_detail jd2 
              WHERE jd2.akun_id = ?
          )
        ORDER BY j.tanggal ASC, j.id ASC, jd.id ASC
    ");
    $stmtB->bind_param("ssi", $tanggalMulai, $tanggalSelesai, $akunId);
    $stmtB->execute();
    $resB = $stmtB->get_result();
    $bebanRows = $resB ? $resB->fetch_all(MYSQLI_ASSOC) : [];
    $stmtB->close();
}

// 3. Hitung Total Mutasi
$totalMutasiPendapatan = 0;
foreach ($pendapatanRows as $row) {
    // Normal saldo Pendapatan = Kredit
    $totalMutasiPendapatan += ($row['kredit'] - $row['debit']);
}

$totalMutasiBeban = 0;
foreach ($bebanRows as $row) {
    // Normal saldo Beban = Debit
    $totalMutasiBeban += ($row['debit'] - $row['kredit']);
}

render_header('Laporan Pendapatan & Pengeluaran', 'pendapatan_beban');
?>

<style>
/* Cetak Kop Surat Resmi */
.print-kop-surat {
    display: none;
    margin-bottom: 25px;
    border-bottom: 3px double #000;
    padding-bottom: 10px;
}
.print-kop-header {
    display: flex;
    align-items: center;
    gap: 20px;
}
.print-kop-logo {
    max-height: 80px;
    width: auto;
    object-fit: contain;
}
.print-kop-info {
    flex-grow: 1;
}
.print-kop-info h2 {
    margin: 0 0 5px 0;
    font-size: 1.6rem;
    font-weight: 700;
    color: #000;
}
.print-kop-info h1 {
    margin: 0 0 5px 0;
    font-size: 1.3rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #333;
}
.print-kop-info p {
    margin: 0;
    font-size: 0.9rem;
    color: #555;
}

@media print {
    .screen-only {
        display: none !important;
    }
    .print-kop-surat {
        display: block !important;
    }
    body {
        background: #fff !important;
        color: #000 !important;
    }
    .panel {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        background: transparent !important;
    }
    .table th {
        background-color: #f3f4f6 !important;
        color: #000 !important;
        border: 1px solid #d1d5db !important;
    }
    .table td {
        border: 1px solid #e5e7eb !important;
    }
}
</style>

<!-- Kop Surat Cetak -->
<div class="print-kop-surat">
    <div class="print-kop-header">
        <?php if (!empty($pengaturan['logo'])) { ?>
            <img src="<?php echo e($pengaturan['logo']); ?>" alt="Logo" class="print-kop-logo">
        <?php } ?>
        <div class="print-kop-info">
            <h2><?php echo e($pengaturan['nama_perusahaan']); ?></h2>
            <h1>LAPORAN PENDAPATAN & PENGELUARAN</h1>
            <p>
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
                <p>
                    <?php echo e($pengaturan['alamat']); ?>
                    <?php if (!empty($pengaturan['telepon'])) echo ' | Telp: ' . e($pengaturan['telepon']); ?>
                    <?php if (!empty($pengaturan['email'])) echo ' | Email: ' . e($pengaturan['email']); ?>
                </p>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Filter Box (Screen Only) -->
<section class="panel screen-only">
    <h3>Filter Pendapatan &amp; Pengeluaran Rinci per Kas/Bank</h3>
    <form method="get" class="form-grid filter-grid">
        <label>
            <span>Pilih Rekening Kas / Bank</span>
            <select name="akun_id" required>
                <option value="semua" <?php echo $akunIdInput === 'semua' ? 'selected' : ''; ?>>Semua Rekening Kas &amp; Bank</option>
                <?php foreach ($kasBankList as $item) { 
                    $label = $item['kode_akun'] . ' - ' . $item['nama_akun'];
                    ?>
                    <option value="<?php echo (int) $item['id']; ?>" <?php echo $akunIdInput === (string)$item['id'] ? 'selected' : ''; ?>><?php echo e($label); ?></option>
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

<!-- Action Buttons (Screen Only) -->
<div class="button-row screen-only" style="margin-bottom: 22px; justify-content: flex-end; display: flex; gap: 10px;">
    <button type="button" id="btnCopy" class="button ghost">📋 Salin ke Clipboard</button>
    <button type="button" id="btnPrint" class="button primary">🖨️ Cetak Laporan</button>
</div>

<!-- Main Data Report -->
<section class="panel">
    <div style="margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <span class="subtle" style="font-size: 0.9rem; text-transform: uppercase; font-weight: 600;">Rekening Kas / Bank</span>
            <h3 style="margin: 4px 0 0 0; color: var(--primary);">
                <?php echo $kodeRekening !== '' ? '[' . e($kodeRekening) . '] ' : ''; ?><?php echo e($namaRekening); ?>
            </h3>
        </div>
        <div style="text-align: right;">
            <span class="subtle" style="font-size: 0.85rem; display: block;">Saldo Awal Rekening</span>
            <strong style="font-size: 1.3rem; color: #10b981;"><?php echo e(format_rupiah($saldoAwal)); ?></strong>
        </div>
    </div>

    <!-- 1. KELOMPOK PENDAPATAN (PENERIMAAN KAS) -->
    <h4 style="margin: 20px 0 10px 0; color: #000000ff; background: #ecfdf5; padding: 8px 12px; border-radius: 4px; font-weight: 600;">PENDAPATAN</h4>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 60px;" class="align-center">No</th>
                <th style="width: 130px;">Tanggal</th>
                <th style="width: 220px;">Akun Lawan</th>
                <th>Keterangan</th>
                <th style="width: 160px;" class="align-right">Debit</th>
                <th style="width: 160px;" class="align-right">Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pendapatanRows)) { ?>
                <tr>
                    <td colspan="6" class="empty-state">Tidak ada penerimaan pendapatan pada periode ini.</td>
                </tr>
            <?php } ?>
            <?php 
            $noP = 1;
            foreach ($pendapatanRows as $baris) { 
                $namaAkunLengkap = '[' . $baris['kode_akun'] . '] ' . $baris['nama_akun'];
                $keteranganDetail = (!empty($baris['nomor_bukti']) ? '[' . $baris['nomor_bukti'] . '] ' : '') . $baris['keterangan'];
                ?>
                <tr>
                    <td class="align-center"><?php echo $noP++; ?></td>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($namaAkunLengkap); ?></td>
                    <td><?php echo e($keteranganDetail); ?></td>
                    <td class="align-right"><?php echo $baris['debit'] > 0 ? e(format_rupiah($baris['debit'])) : '-'; ?></td>
                    <td class="align-right"><?php echo $baris['kredit'] > 0 ? e(format_rupiah($baris['kredit'])) : '-'; ?></td>
                </tr>
            <?php } ?>
            <tr class="total-row">
                <td colspan="4">Total Penerimaan Pendapatan</td>
                <td colspan="2" class="align-right"><?php echo e(format_rupiah($totalMutasiPendapatan)); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- 2. KELOMPOK BEBAN (PENGELUARAN KAS) -->
    <h4 style="margin: 30px 0 10px 0; color: #000000ff; background: #fef2f2; padding: 8px 12px; border-radius: 4px; font-weight: 600;">PENGELUARAN</h4>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 60px;" class="align-center">No</th>
                <th style="width: 130px;">Tanggal</th>
                <th style="width: 220px;">Akun Lawan</th>
                <th>Keterangan</th>
                <th style="width: 160px;" class="align-right">Debit</th>
                <th style="width: 160px;" class="align-right">Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bebanRows)) { ?>
                <tr>
                    <td colspan="6" class="empty-state">Tidak ada pengeluaran beban pada periode ini.</td>
                </tr>
            <?php } ?>
            <?php 
            $noB = 1;
            foreach ($bebanRows as $baris) { 
                $namaAkunLengkap = '[' . $baris['kode_akun'] . '] ' . $baris['nama_akun'];
                $keteranganDetail = (!empty($baris['nomor_bukti']) ? '[' . $baris['nomor_bukti'] . '] ' : '') . $baris['keterangan'];
                ?>
                <tr>
                    <td class="align-center"><?php echo $noB++; ?></td>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($namaAkunLengkap); ?></td>
                    <td><?php echo e($keteranganDetail); ?></td>
                    <td class="align-right"><?php echo $baris['debit'] > 0 ? e(format_rupiah($baris['debit'])) : '-'; ?></td>
                    <td class="align-right"><?php echo $baris['kredit'] > 0 ? e(format_rupiah($baris['kredit'])) : '-'; ?></td>
                </tr>
            <?php } ?>
            <tr class="total-row">
                <td colspan="4">Total Pengeluaran Beban</td>
                <td colspan="2" class="align-right"><?php echo e(format_rupiah($totalMutasiBeban)); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Ringkasan Saldo Akhir Rekening -->
    <div style="margin-top: 30px; padding: 16px 20px; background-color: #f9fafb; border-radius: 6px; border-left: 4px solid var(--primary); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <strong style="font-size: 1.05rem; color: #111827; display: block;">Saldo Akhir Rekening Kas &amp; Bank</strong>
            <span class="subtle" style="font-size: 0.85rem;">*(Saldo akhir dipengaruhi juga oleh transaksi non-pendapatan/beban seperti transfer internal, pelunasan hutang, atau piutang).</span>
        </div>
        <div style="text-align: right;">
            <span class="subtle" style="font-size: 0.85rem; display: block; text-transform: uppercase;">Saldo Akhir Riil</span>
            <strong style="font-size: 1.8rem; color: var(--primary);"><?php echo e(format_rupiah($saldoAkhir)); ?></strong>
        </div>
    </div>
</section>

<script>
// Print Handler
document.getElementById('btnPrint').addEventListener('click', function() {
    window.print();
});

// Copy to Clipboard Handler
document.getElementById('btnCopy').addEventListener('click', function() {
    const isSemua = "<?php echo $akunId === 'semua' ? '1' : '0'; ?>";
    const tanggalMulaiText = "<?php echo e($tanggalMulai); ?>";
    const tanggalSelesaiText = "<?php echo e($tanggalSelesai); ?>";
    const namaPerusahaan = "<?php echo e($pengaturan['nama_perusahaan']); ?>";
    const namaRekening = "<?php echo e($namaRekening); ?>";
    const kodeRekening = "<?php echo e($kodeRekening); ?>";
    
    let periodeText = "Semua Periode";
    if (tanggalMulaiText && tanggalSelesaiText) {
        periodeText = tanggalMulaiText + " s/d " + tanggalSelesaiText;
    }
    
    let output = "LAPORAN PENDAPATAN & PENGELUARAN\r\n";
    output += "Perusahaan:\t" + namaPerusahaan + "\r\n";
    output += "Periode:\t" + periodeText + "\r\n";
    output += "Rekening:\t" + (kodeRekening ? "[" + kodeRekening + "] " : "") + namaRekening + "\r\n";
    output += "Saldo Awal:\tRp " + parseFloat("<?php echo $saldoAwal; ?>").toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n";
    output += "=========================================================\r\n\r\n";
    
    // PENDAPATAN
    output += "PENDAPATAN\r\n";
    output += "No\tTanggal\tAkun Lawan\tKeterangan\tDebit\tKredit\r\n";
    output += "---------------------------------------------------------\r\n";
    const pRows = <?php echo json_encode($pendapatanRows); ?>;
    let noP = 1;
    pRows.forEach(function(item) {
        const ak = "[" + item.kode_akun + "] " + item.nama_akun;
        const ket = (item.nomor_bukti ? "[" + item.nomor_bukti + "] " : "") + item.keterangan;
        const deb = item.debit > 0 ? "Rp " + parseFloat(item.debit).toLocaleString('id-ID', { minimumFractionDigits: 0 }) : "-";
        const kre = item.kredit > 0 ? "Rp " + parseFloat(item.kredit).toLocaleString('id-ID', { minimumFractionDigits: 0 }) : "-";
        output += noP + "\t" + item.tanggal + "\t" + ak + "\t" + ket + "\t" + deb + "\t" + kre + "\r\n";
        noP++;
    });
    output += "Total Penerimaan Pendapatan:\tRp " + parseFloat("<?php echo $totalMutasiPendapatan; ?>").toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n\r\n";
    
    // BEBAN
    output += "PENGELUARAN\r\n";
    output += "No\tTanggal\tAkun Lawan\tKeterangan\tDebit\tKredit\r\n";
    output += "---------------------------------------------------------\r\n";
    const bRows = <?php echo json_encode($bebanRows); ?>;
    let noB = 1;
    bRows.forEach(function(item) {
        const ak = "[" + item.kode_akun + "] " + item.nama_akun;
        const ket = (item.nomor_bukti ? "[" + item.nomor_bukti + "] " : "") + item.keterangan;
        const deb = item.debit > 0 ? "Rp " + parseFloat(item.debit).toLocaleString('id-ID', { minimumFractionDigits: 0 }) : "-";
        const kre = item.kredit > 0 ? "Rp " + parseFloat(item.kredit).toLocaleString('id-ID', { minimumFractionDigits: 0 }) : "-";
        output += noB + "\t" + item.tanggal + "\t" + ak + "\t" + ket + "\t" + deb + "\t" + kre + "\r\n";
        noB++;
    });
    output += "Total Pengeluaran Beban:\tRp " + parseFloat("<?php echo $totalMutasiBeban; ?>").toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n\r\n";
    
    output += "=========================================================\r\n";
    output += "Saldo Akhir Riil:\tRp " + parseFloat("<?php echo $saldoAkhir; ?>").toLocaleString('id-ID', { minimumFractionDigits: 0 }) + "\r\n";
    
    navigator.clipboard.writeText(output).then(function() {
        const btn = document.getElementById('btnCopy');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '✅ Tersalin!';
        btn.style.borderColor = 'var(--success, #10b981)';
        btn.style.color = 'var(--success, #10b981)';
        setTimeout(function() {
            btn.innerHTML = originalHTML;
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2000);
    }).catch(function(err) {
        alert('Gagal menyalin laporan: ' + err);
    });
});
</script>

<?php
render_footer();
