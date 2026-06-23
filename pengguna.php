<?php
require_once __DIR__ . '/fungsi.php';

// Proteksi Halaman: Hanya Admin yang boleh mengakses Kelola Pengguna
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    atur_flash('error', 'Akses ditolak. Anda tidak memiliki wewenang untuk mengakses halaman tersebut.');
    header('Location: index.php');
    exit;
}

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Handle POST: Tambah Pengguna
if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'tambah_pengguna') {
    try {
        tambah_pengguna($_POST);
        atur_flash('success', 'Pengguna baru berhasil ditambahkan.');
        header('Location: pengguna.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: pengguna.php');
        exit;
    }
}

// Handle POST/GET: Toggle Status Aktif/Nonaktif
if (isset($_GET['aksi']) && $_GET['aksi'] === 'toggle_status') {
    $id = (int) ($_GET['id'] ?? 0);
    try {
        toggle_status_pengguna($id);
        atur_flash('success', 'Status pengguna berhasil diperbarui.');
        header('Location: pengguna.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: pengguna.php');
        exit;
    }
}

$daftarPengguna = ambil_daftar_pengguna();

render_header('Kelola Pengguna', 'pengguna');
?>

<section class="grid-two balance-grid">
    <article class="panel">
        <h3>Tambah Pengguna Baru</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="tambah_pengguna">
            <label class="full-width">
                <span>Nama Lengkap</span>
                <input type="text" name="nama_lengkap" placeholder="Masukkan nama lengkap" required>
            </label>
            <label>
                <span>Username</span>
                <input type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
            </label>
            <label>
                <span>Role / Hak Akses</span>
                <select name="role">
                    <option value="akuntan">Akuntan (Input Jurnal & Laporan)</option>
                    <option value="admin">Administrator (Akses Penuh)</option>
                    <option value="pimpinan">Pimpinan (Hanya Lihat Laporan)</option>
                </select>
            </label>
            <label class="full-width">
                <span>Password</span>
                <input type="password" name="password" placeholder="Minimal 6 karakter" minlength="6" required>
            </label>
            <button type="submit" class="button primary">Simpan Pengguna</button>
        </form>
    </article>

    <article class="panel">
        <h3>Daftar Pengguna</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Hak Akses</th>
                    <th>Status</th>
                    <th class="align-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftarPengguna as $user) { ?>
                    <tr>
                        <td><strong><?php echo e($user['username']); ?></strong></td>
                        <td><?php echo e($user['nama_lengkap']); ?></td>
                        <td>
                            <span class="badge <?php echo $user['role'] === 'admin' ? 'success' : ''; ?>">
                                <?php echo e(ucfirst($user['role'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int)$user['aktif'] === 1) { ?>
                                <span class="text-positive">● Aktif</span>
                            <?php } else { ?>
                                <span class="text-negative">○ Nonaktif</span>
                            <?php } ?>
                        </td>
                        <td class="align-right">
                            <?php if ($user['id'] !== $_SESSION['user_id']) { ?>
                                <a href="pengguna.php?aksi=toggle_status&id=<?php echo (int) $user['id']; ?>" 
                                   class="button small <?php echo (int)$user['aktif'] === 1 ? 'danger' : 'ghost'; ?>"
                                   onclick="return confirm('Apakah Anda yakin ingin mengubah status pengguna ini?');">
                                    <?php echo (int)$user['aktif'] === 1 ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                </a>
                            <?php } else { ?>
                                <span class="helper-text">(Sedang Digunakan)</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </article>
</section>

<?php
render_footer();
