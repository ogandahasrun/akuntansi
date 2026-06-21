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

// Daftar akun yang boleh jadi induk: akun yang belum punya parent (level 1 saja)
$akunCalonInduk = array_filter($akun, function ($a) use ($akunEditId) {
    // Bukan sub-akun lain (parent_id kosong), bukan dirinya sendiri
    return empty($a['parent_id']) && (int) $a['id'] !== $akunEditId;
});

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
                <input type="text" name="kode_akun" placeholder="Contoh: 2-1201" value="<?php echo e($akunEdit['kode_akun'] ?? ''); ?>" required>
            </label>
            <label>
                <span>Nama Akun</span>
                <input type="text" name="nama_akun" placeholder="Contoh: Hutang - PT Kimia Farma" value="<?php echo e($akunEdit['nama_akun'] ?? ''); ?>" required>
            </label>
            <label>
                <span>Akun Induk <small style="color:var(--text-subtle)">(opsional — untuk sub-rekening)</small></span>
                <select name="parent_id">
                    <option value="">— Tidak ada (akun utama) —</option>
                    <?php foreach ($akunCalonInduk as $induk) { ?>
                        <option value="<?php echo (int) $induk['id']; ?>"
                            <?php echo ((int) ($akunEdit['parent_id'] ?? 0)) === (int) $induk['id'] ? 'selected' : ''; ?>>
                            <?php echo e($induk['kode_akun'] . ' — ' . $induk['nama_akun']); ?>
                        </option>
                    <?php } ?>
                </select>
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
        <p>Pilih <strong>Akun Induk</strong> untuk membuat sub-rekening. Misalnya, buat akun <em>"Hutang Obat &amp; BHP"</em> sebagai induk, lalu buat <em>"Hutang - PT Kimia Farma"</em> sebagai sub-akunnya.</p>
        <p>Akun induk <strong>tidak bisa dipakai langsung</strong> pada jurnal — hanya sub-akunnya yang bisa. Saldo akun induk dihitung otomatis dari penjumlahan seluruh sub-akunnya.</p>
        <p>Akun yang sudah pernah dipakai pada jurnal tidak dapat dihapus agar histori transaksi tetap aman.</p>
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
            <?php foreach ($akun as $baris) {
                $adaSubAkun   = (int) ($baris['jumlah_sub_akun'] ?? 0) > 0;
                $adalahSubAkun = !empty($baris['parent_id']);
            ?>
                <tr <?php echo $adaSubAkun ? 'style="background:var(--bg-subtle,#f8f9fb)"' : ''; ?>>
                    <td>
                        <?php if ($adalahSubAkun) { ?>
                            <span style="color:var(--text-subtle);margin-right:4px">└</span>
                        <?php } ?>
                        <?php echo e($baris['kode_akun']); ?>
                    </td>
                    <td>
                        <?php if ($adaSubAkun) { ?>
                            <strong><?php echo e($baris['nama_akun']); ?></strong>
                            <span style="margin-left:6px;font-size:0.7rem;background:var(--accent-subtle,#e8f0fe);color:var(--accent,#4a6cf7);border-radius:4px;padding:1px 6px;display:inline-block">Induk</span>
                        <?php } elseif ($adalahSubAkun) { ?>
                            <span style="padding-left:1.2em"><?php echo e($baris['nama_akun']); ?></span>
                        <?php } else { ?>
                            <?php echo e($baris['nama_akun']); ?>
                        <?php } ?>
                    </td>
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