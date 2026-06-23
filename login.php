<?php
require_once __DIR__ . '/fungsi.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password harus diisi.';
    } else {
        global $koneksi;
        $stmt = $koneksi->prepare('SELECT * FROM users WHERE username = ? AND aktif = 1 LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $hasil = $stmt->get_result();
        $user = $hasil ? $hasil->fetch_assoc() : null;
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            atur_flash('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
$pengaturan = ambil_pengaturan();
$logoPerusahaan = $pengaturan['logo'] ?? '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?php echo e($pengaturan['nama_perusahaan']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <?php if ($logoPerusahaan !== '') { ?>
                    <div class="logo-company login-logo">
                        <img src="<?php echo e($logoPerusahaan); ?>" alt="Logo <?php echo e($pengaturan['nama_perusahaan']); ?>">
                    </div>
                <?php } else { ?>
                    <div class="login-default-logo">💼</div>
                <?php } ?>
                <h2><?php echo e($pengaturan['nama_perusahaan']); ?></h2>
                <p class="subtle">Silakan masuk ke akun Anda</p>
            </div>

            <?php if ($error !== '') { ?>
                <div class="flash error"><?php echo e($error); ?></div>
            <?php } ?>

            <?php
            $flash = ambil_flash();
            if ($flash) {
                echo '<div class="flash ' . e($flash['jenis']) . '">' . e($flash['pesan']) . '</div>';
            }
            ?>

            <form method="post" action="login.php" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                </div>
                <button type="submit" class="button primary login-btn">Masuk Ke Sistem</button>
            </form>
        </div>
    </div>
</body>
</html>
