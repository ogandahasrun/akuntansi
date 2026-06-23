<?php
/**
 * reset_admin.php
 * Tool pemulihan / reset akun Administrator utama.
 */

require_once __DIR__ . '/fungsi.php';

// --- MODE 1: COMMAND LINE INTERFACE (CLI) ---
if (PHP_SAPI === 'cli') {
    global $argv;
    if (count($argv) < 3) {
        echo "Format perintah salah." . PHP_EOL;
        echo "Penggunaan: php reset_admin.php <username_baru> <password_baru>" . PHP_EOL;
        exit(1);
    }

    $username = trim($argv[1]);
    $password = $argv[2];

    if ($username === '' || strlen($password) < 6) {
        echo "Gagal: Username tidak boleh kosong, dan password minimal 6 karakter." . PHP_EOL;
        exit(1);
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    global $koneksi;

    // Cek apakah user admin sudah ada, jika belum insert baru, jika sudah update
    $stmt = $koneksi->prepare('INSERT INTO users (username, password, nama_lengkap, role, aktif) 
                               VALUES (?, ?, \'Administrator\', \'admin\', 1) 
                               ON DUPLICATE KEY UPDATE password = ?, nama_lengkap = \'Administrator\', role = \'admin\', aktif = 1');
    if (!$stmt) {
        echo "Gagal: Tabel 'users' tidak ditemukan di database atau akses ditolak. Silakan buat tabel 'users' terlebih dahulu." . PHP_EOL;
        exit(1);
    }
    $stmt->bind_param('ssss', $username, $passwordHash, $passwordHash);
    
    if ($stmt->execute()) {
        echo "Sukses: Akun Administrator dengan username '{$username}' berhasil diperbarui/dibuat." . PHP_EOL;
        $stmt->close();
        exit(0);
    } else {
        echo "Error database: " . $koneksi->error . PHP_EOL;
        $stmt->close();
        exit(1);
    }
}

// --- MODE 2: WEB BROWSER INTERFACE ---
$tokenFile = __DIR__ . '/reset_token.txt';

if (!file_exists($tokenFile)) {
    http_response_code(403);
    die('<h1>Akses Ditolak</h1><p>Untuk mereset admin melalui browser, Anda harus membuat file fisik bernama <code>reset_token.txt</code> di folder root aplikasi Anda, berisi kode rahasia pilihan Anda.</p>');
}

$tokenIsi = trim(file_get_contents($tokenFile));

if ($tokenIsi === '') {
    http_response_code(403);
    die('<h1>Akses Ditolak</h1><p>File <code>reset_token.txt</code> kosong. Isi file tersebut dengan token rahasia.</p>');
}

$tokenInput = $_GET['token'] ?? '';

if ($tokenInput !== $tokenIsi) {
    http_response_code(403);
    die('<h1>Akses Ditolak</h1><p>Token yang Anda masukkan salah. Gunakan URL format: <code>reset_admin.php?token=KODE_RAHASIA</code></p>');
}

// Reset admin ke default: admin / admin123
$username = 'admin';
$passwordHash = '$2y$10$K874CWBXketWi9Phwwyafe0HoU82HLo5hegEobuUC.jBmcpuGE6X.'; // admin123
global $koneksi;

$stmt = $koneksi->prepare('INSERT INTO users (username, password, nama_lengkap, role, aktif) 
                           VALUES (?, ?, \'Administrator\', \'admin\', 1) 
                           ON DUPLICATE KEY UPDATE username = ?, password = ?, nama_lengkap = \'Administrator\', role = \'admin\', aktif = 1');

if (!$stmt) {
    die('<h1>Gagal</h1><p>Tabel <code>users</code> tidak ditemukan di database. Pastikan tabel telah dibuat oleh Administrator Database sebelum menjalankan reset.</p>');
}

$stmt->bind_param('ssss', $username, $passwordHash, $username, $passwordHash);

if ($stmt->execute()) {
    $stmt->close();
    // Hapus file token secara otomatis untuk keamanan
    @unlink($tokenFile);
    
    echo '<h1>Reset Admin Berhasil!</h1>';
    echo '<p>Kredensial login Anda telah diatur ulang menjadi:</p>';
    echo '<ul>';
    echo '<li><strong>Username:</strong> admin</li>';
    echo '<li><strong>Password:</strong> admin123</li>';
    echo '</ul>';
    echo '<p style="color: green; font-weight: bold;">Keamanan: File <code>reset_token.txt</code> telah dihapus secara otomatis dari server.</p>';
    echo '<p><a href="login.php">Ke Halaman Login &rarr;</a></p>';
} else {
    echo '<h1>Error</h1><p>Gagal mereset ke database: ' . htmlspecialchars($koneksi->error) . '</p>';
    $stmt->close();
}
