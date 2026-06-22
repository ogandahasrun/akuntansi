<?php
/**
 * reset_schema.php
 * ─────────────────────────────────────────────────────────────
 * Utilitas: hapus file flag .schema_ready agar pada request
 * berikutnya semua migrasi skema dijalankan ulang.
 *
 * Gunakan jika Anda:
 *   - Deploy update schema baru ke server
 *   - Ingin memaksa sinkronisasi ulang akun default
 *
 * AKSES: Jalankan sekali via browser, lalu hapus/batasi akses file ini.
 * ─────────────────────────────────────────────────────────────
 */

// Proteksi sederhana: hanya bisa diakses dari localhost atau dengan kunci
$kunciRahasia = 'reset123'; // Ganti dengan kunci Anda sendiri
$kunciInput   = $_GET['kunci'] ?? '';

$dariLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if (!$dariLocalhost && $kunciInput !== $kunciRahasia) {
    http_response_code(403);
    die('Akses ditolak. Tambahkan ?kunci=<kunci_rahasia> di URL.');
}

$flagFile = __DIR__ . '/.schema_ready';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    if (file_exists($flagFile)) {
        unlink($flagFile);
        $pesan = '✅ File .schema_ready berhasil dihapus. Schema akan disinkronisasi ulang pada request berikutnya.';
    } else {
        $pesan = 'ℹ️ File .schema_ready tidak ditemukan (belum pernah dibuat atau sudah dihapus).';
    }
}

$flagAda = file_exists($flagFile);
$flagIsi = $flagAda ? file_get_contents($flagFile) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Reset Schema Flag</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .status { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .ok   { background: #d1fae5; color: #065f46; }
        .warn { background: #fef3c7; color: #92400e; }
        .msg  { background: #dbeafe; color: #1e40af; }
        button { padding: 10px 20px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
        button:hover { background: #b91c1c; }
    </style>
</head>
<body>
    <h2>🔧 Reset Schema Flag</h2>

    <?php if (isset($pesan)): ?>
        <div class="status msg"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div class="status <?= $flagAda ? 'ok' : 'warn' ?>">
        Status file <code>.schema_ready</code>:
        <strong><?= $flagAda ? "ADA (dibuat pada: {$flagIsi})" : 'TIDAK ADA' ?></strong>
    </div>

    <?php if ($flagAda): ?>
        <p>File flag ada, artinya migrasi skema tidak akan dijalankan di setiap request (mode production). Klik tombol di bawah untuk menghapus flag dan memaksa sinkronisasi ulang.</p>
        <form method="post">
            <button type="submit" name="reset" value="1">Hapus .schema_ready (Reset)</button>
        </form>
    <?php else: ?>
        <p>File flag belum ada. Kunjungi halaman utama aplikasi untuk membuatnya secara otomatis.</p>
    <?php endif; ?>

    <br>
    <a href="index.php">← Kembali ke Dashboard</a>
</body>
</html>
