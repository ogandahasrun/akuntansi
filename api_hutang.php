<?php
/**
 * API: Ambil daftar faktur hutang/piutang belum lunas per kontak.
 * Digunakan oleh form Jurnal Umum (Bayar Hutang / Terima Piutang).
 *
 * Parameter GET:
 *   kontak_id  : int   — ID kontak supplier/pelanggan
 *   jenis      : string — 'Hutang' atau 'Piutang'
 */

require_once __DIR__ . '/fungsi.php';

header('Content-Type: application/json; charset=utf-8');

$kontakId = (int) ($_GET['kontak_id'] ?? 0);
$jenis    = in_array($_GET['jenis'] ?? '', ['Hutang', 'Piutang'], true)
    ? $_GET['jenis']
    : 'Hutang';

if ($kontakId <= 0) {
    echo json_encode([]);
    exit;
}

$daftar = ambil_hutang_belum_lunas_by_kontak($kontakId, $jenis);

$hasil = [];
foreach ($daftar as $baris) {
    $label = format_tanggal_indonesia($baris['tanggal']);
    if (!empty($baris['keterangan'])) {
        $label .= ' — ' . $baris['keterangan'];
    }
    $label .= ' (Sisa: Rp ' . number_format($baris['sisa'], 0, ',', '.') . ')';

    $hasil[] = [
        'id'       => (int) $baris['id'],
        'label'    => $label,
        'sisa'     => $baris['sisa'],
        'nominal'  => (float) $baris['nominal'],
        'dibayar'  => (float) $baris['dibayar'],
        'jatuh_tempo' => $baris['jatuh_tempo'] ?? '',
        'keterangan'  => $baris['keterangan'] ?? '',
    ];
}

echo json_encode($hasil);
