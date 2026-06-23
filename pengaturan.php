<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'simpan_pengaturan') {
    try {
        simpan_pengaturan($_POST, $_FILES);
        atur_flash('success', 'Pengaturan perusahaan berhasil diperbarui.');
        header('Location: pengaturan.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: pengaturan.php');
        exit;
    }
}

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'ganti_password') {
    try {
        ubah_password_sendiri(
            $_POST['password_lama'] ?? '',
            $_POST['password_baru'] ?? '',
            $_POST['konfirmasi_password'] ?? ''
        );
        atur_flash('success', 'Password Anda berhasil diperbarui.');
        header('Location: pengaturan.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: pengaturan.php');
        exit;
    }
}
$pengaturan = ambil_pengaturan();

render_header('Pengaturan Perusahaan', 'pengaturan');
?>
<section class="panel narrow-panel">
    <h3>Identitas Perusahaan</h3>
    <form method="post" class="form-grid" enctype="multipart/form-data">
        <input type="hidden" name="aksi" value="simpan_pengaturan">
        <label>
            <span>Nama Perusahaan</span>
            <input type="text" name="nama_perusahaan" value="<?php echo e($pengaturan['nama_perusahaan']); ?>" required>
        </label>
        <label>
            <span>Alamat</span>
            <textarea name="alamat" rows="3"><?php echo e($pengaturan['alamat']); ?></textarea>
        </label>
        <label>
            <span>Telepon</span>
            <input type="text" name="telepon" value="<?php echo e($pengaturan['telepon']); ?>">
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" value="<?php echo e($pengaturan['email']); ?>">
        </label>
        <label>
            <span>Logo Perusahaan</span>
            <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
            <small class="helper-text">Format yang didukung: JPG, PNG, WEBP, GIF. Maksimal 2 MB.</small>
        </label>
        <div class="full-width logo-settings">
            <?php if (!empty($pengaturan['logo'])) { ?>
                <div class="logo-company preview-logo">
                    <img src="<?php echo e($pengaturan['logo']); ?>" alt="Logo <?php echo e($pengaturan['nama_perusahaan']); ?>">
                </div>
                <label class="checkbox-row">
                    <input type="checkbox" name="hapus_logo" value="1">
                    <span>Hapus logo saat menyimpan</span>
                </label>
            <?php } else { ?>
                <p class="helper-text">Belum ada logo perusahaan yang tersimpan.</p>
            <?php } ?>
        </div>
        <button type="submit" class="button primary">Simpan Pengaturan</button>
    </form>
</section>

<section class="panel narrow-panel">
    <h3>Keamanan: Ganti Password</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="aksi" value="ganti_password">
        <label class="full-width">
            <span>Password Saat Ini</span>
            <input type="password" name="password_lama" required placeholder="Masukkan password lama">
        </label>
        <label>
            <span>Password Baru</span>
            <input type="password" name="password_baru" required placeholder="Minimal 6 karakter" minlength="6">
        </label>
        <label>
            <span>Konfirmasi Password Baru</span>
            <input type="password" name="konfirmasi_password" required placeholder="Ulangi password baru" minlength="6">
        </label>
        <div class="full-width">
            <button type="submit" class="button primary">Perbarui Password</button>
        </div>
    </form>
</section>
<?php
render_footer();