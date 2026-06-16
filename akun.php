<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$akunEditId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$akunEdit = $akunEditId > 0 ? ambil_akun_by_id($akunEditId) : null;

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'simpan_akun') {
    try {
        simpan_akun($_POST);
        atur_flash('success', 'Akun baru berhasil ditambahkan.');
        header('Location: akun.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: akun.php');
        exit;
    }
}

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'ubah_akun') {
    try {
        ubah_akun((int) ($_POST['id'] ?? 0), $_POST);
        atur_flash('success', 'Akun berhasil diperbarui.');
        header('Location: akun.php');
        exit;
    } catch (Throwable $exception) {
        $id = (int) ($_POST['id'] ?? 0);
        atur_flash('error', $exception->getMessage());
        header('Location: akun.php?edit=' . $id);
        exit;
    }
}

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'hapus_akun') {
    try {
        hapus_akun((int) ($_POST['id'] ?? 0));
        atur_flash('success', 'Akun berhasil dihapus.');
        header('Location: akun.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: akun.php');
        exit;
    }
}

$akun = ambil_daftar_akun();

if ($akunEdit && (int) $akunEdit['aktif'] !== 1) {
    $akunEdit = null;
}

render_header('Daftar Akun', 'akun');
?>
<section class="grid-two">
    <article class="panel">
        <h3><?php echo $akunEdit ? 'Edit Akun' : 'Tambah Akun'; ?></h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="<?php echo $akunEdit ? 'ubah_akun' : 'simpan_akun'; ?>">
            <?php if ($akunEdit) { ?>
                <input type="hidden" name="id" value="<?php echo (int) $akunEdit['id']; ?>">
            <?php } ?>
            <label>
                <span>Kode Akun</span>
                <input type="text" name="kode_akun" placeholder="Contoh: 101" value="<?php echo e($akunEdit['kode_akun'] ?? ''); ?>" required>
            </label>
            <label>
                <span>Nama Akun</span>
                <input type="text" name="nama_akun" placeholder="Contoh: Kas" value="<?php echo e($akunEdit['nama_akun'] ?? ''); ?>" required>
            </label>
            <label>
                <span>Kategori</span>
                <select name="kategori">
                    <?php foreach (['Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban'] as $kategori) { ?>
                        <option value="<?php echo e($kategori); ?>" <?php echo ($akunEdit['kategori'] ?? 'Aset') === $kategori ? 'selected' : ''; ?>><?php echo e($kategori); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>
                <span>Tipe Saldo</span>
                <select name="tipe_saldo">
                    <option value="Debit" <?php echo ($akunEdit['tipe_saldo'] ?? 'Debit') === 'Debit' ? 'selected' : ''; ?>>Debit</option>
                    <option value="Kredit" <?php echo ($akunEdit['tipe_saldo'] ?? 'Debit') === 'Kredit' ? 'selected' : ''; ?>>Kredit</option>
                </select>
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="is_kas" value="1" <?php echo !empty($akunEdit['is_kas']) ? 'checked' : ''; ?>>
                <span>Tandai sebagai akun kas/bank</span>
            </label>
            <div class="button-row full-width">
                <button type="submit" class="button primary"><?php echo $akunEdit ? 'Perbarui Akun' : 'Simpan Akun'; ?></button>
                <?php if ($akunEdit) { ?>
                    <a href="akun.php" class="button ghost">Batal</a>
                <?php } ?>
            </div>
        </form>
    </article>
    <article class="panel">
        <h3>Catatan Penggunaan</h3>
        <p>Akun kas atau bank akan muncul otomatis dalam laporan arus kas. Pastikan tipe saldo sesuai kaidah akuntansi agar buku besar dan neraca akurat.</p>
        <p>Akun yang sudah pernah dipakai pada jurnal tidak dapat dihapus agar histori transaksi tetap aman. Akun tersebut masih bisa diedit bila diperlukan.</p>
    </article>
</section>

<section class="panel">
    <h3>Daftar Akun Aktif</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Akun</th>
                <th>Kategori</th>
                <th>Tipe Saldo</th>
                <th>Kas/Bank</th>
                <th class="align-right">Saldo</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($akun as $baris) { ?>
                <tr>
                    <td><?php echo e($baris['kode_akun']); ?></td>
                    <td><?php echo e($baris['nama_akun']); ?></td>
                    <td><?php echo e($baris['kategori']); ?></td>
                    <td><?php echo e($baris['tipe_saldo']); ?></td>
                    <td><?php echo $baris['is_kas'] ? 'Ya' : 'Tidak'; ?></td>
                    <td class="align-right"><?php echo e(format_rupiah(hitung_saldo_akun((int) $baris['id']))); ?></td>
                    <td>
                        <div class="inline-actions">
                            <a href="akun.php?edit=<?php echo (int) $baris['id']; ?>" class="button small">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus akun ini?');">
                                <input type="hidden" name="aksi" value="hapus_akun">
                                <input type="hidden" name="id" value="<?php echo (int) $baris['id']; ?>">
                                <button type="submit" class="button small ghost">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
<?php
render_footer();