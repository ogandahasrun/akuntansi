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

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'simpan_tahun_buku') {
    $targetId = (int) ($_POST['id'] ?? 0);

    try {
        $targetId = simpan_tahun_buku($_POST);
        atur_flash('success', 'Tahun buku berhasil disimpan dan diaktifkan.');
        header('Location: pengaturan.php?tahun_buku_id=' . $targetId);
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: pengaturan.php' . ($targetId > 0 ? '?tahun_buku_id=' . $targetId : '?tahun_buku_id=baru'));
        exit;
    }
}

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'simpan_saldo_awal') {
    $tahunBukuId = (int) ($_POST['tahun_buku_id'] ?? 0);

    try {
        $ringkasan = simpan_saldo_awal_akun_tahun($tahunBukuId, $_POST);
        atur_flash('success', 'Saldo awal akun untuk ' . $ringkasan['tahun_buku']['nama'] . ' berhasil disimpan.');
        header('Location: pengaturan.php?tahun_buku_id=' . $tahunBukuId);
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: pengaturan.php' . ($tahunBukuId > 0 ? '?tahun_buku_id=' . $tahunBukuId : ''));
        exit;
    }
}

$pengaturan = ambil_pengaturan();
$daftarTahunBuku = ambil_daftar_tahun_buku();
$tahunBukuAktif = ambil_tahun_buku_aktif();
$tahunBukuParam = $_GET['tahun_buku_id'] ?? (($tahunBukuAktif['id'] ?? 0) > 0 ? (string) $tahunBukuAktif['id'] : 'baru');
$tahunBukuDipilih = $tahunBukuParam === 'baru' ? null : ambil_tahun_buku_by_id((int) $tahunBukuParam);

if (!$tahunBukuDipilih && $tahunBukuParam !== 'baru') {
    $tahunBukuDipilih = $tahunBukuAktif;
}

$formTahunBuku = $tahunBukuDipilih ?: [
    'id' => 0,
    'nama' => 'Tahun Buku ' . date('Y'),
    'tanggal_mulai' => date('Y') . '-01-01',
    'tanggal_selesai' => date('Y') . '-12-31',
];

$akun = ambil_daftar_akun();
$saldoAwalDipilih = !empty($tahunBukuDipilih['id']) ? ambil_saldo_awal_akun((int) $tahunBukuDipilih['id']) : [];
$totalSaldoAwalDebit = 0;
$totalSaldoAwalKredit = 0;

foreach ($akun as $barisAkun) {
    $nominalSaldoAwal = (float) ($saldoAwalDipilih[(int) $barisAkun['id']] ?? 0);
    if ($barisAkun['tipe_saldo'] === 'Debit') {
        $totalSaldoAwalDebit += $nominalSaldoAwal;
    } else {
        $totalSaldoAwalKredit += $nominalSaldoAwal;
    }
}

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

<section class="grid-two balance-grid">
    <article class="panel">
        <h3>Tahun Buku</h3>
        <form method="get" class="inline-form">
            <label>
                <span>Pilih Tahun Buku</span>
                <select name="tahun_buku_id">
                    <?php foreach ($daftarTahunBuku as $item) { ?>
                        <option value="<?php echo (int) $item['id']; ?>" <?php echo ($formTahunBuku['id'] ?? 0) === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['nama'] . ' (' . format_tanggal_indonesia($item['tanggal_mulai']) . ' s/d ' . format_tanggal_indonesia($item['tanggal_selesai']) . ')'); ?></option>
                    <?php } ?>
                    <option value="baru" <?php echo ($formTahunBuku['id'] ?? 0) === 0 ? 'selected' : ''; ?>>Buat tahun buku baru</option>
                </select>
            </label>
            <button type="submit" class="button ghost">Tampilkan</button>
        </form>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="simpan_tahun_buku">
            <input type="hidden" name="id" value="<?php echo (int) ($formTahunBuku['id'] ?? 0); ?>">
            <label>
                <span>Nama Tahun Buku</span>
                <input type="text" name="nama" value="<?php echo e($formTahunBuku['nama'] ?? ''); ?>" required>
            </label>
            <label>
                <span>Status</span>
                <input type="text" value="<?php echo !empty($tahunBukuAktif['id']) && (int) ($formTahunBuku['id'] ?? 0) === (int) $tahunBukuAktif['id'] ? 'Aktif' : 'Akan dijadikan aktif saat disimpan'; ?>" readonly>
            </label>
            <label>
                <span>Tanggal Mulai</span>
                <input type="date" name="tanggal_mulai" value="<?php echo e($formTahunBuku['tanggal_mulai'] ?? ''); ?>" required>
            </label>
            <label>
                <span>Tanggal Selesai</span>
                <input type="date" name="tanggal_selesai" value="<?php echo e($formTahunBuku['tanggal_selesai'] ?? ''); ?>" required>
            </label>
            <button type="submit" class="button primary"><?php echo !empty($formTahunBuku['id']) ? 'Perbarui Tahun Buku' : 'Simpan Tahun Buku Baru'; ?></button>
        </form>
        <p class="helper-text">Tahun buku aktif dipakai untuk membatasi tanggal jurnal dan menjadi dasar semua saldo laporan.</p>
    </article>
    <article class="panel">
        <h3>Ringkasan Saldo Awal</h3>
        <?php if (!empty($formTahunBuku['id'])) { ?>
            <p><strong><?php echo e($formTahunBuku['nama']); ?></strong></p>
            <p class="helper-text">Isi nilai positif sesuai tipe saldo normal akun. Sistem akan menolak simpan bila total sisi debit dan kredit belum seimbang.</p>
            <table class="table compact">
                <tbody>
                    <tr>
                        <td>Total Akun Bertipe Debit</td>
                        <td class="align-right"><?php echo e(format_rupiah($totalSaldoAwalDebit)); ?></td>
                    </tr>
                    <tr>
                        <td>Total Akun Bertipe Kredit</td>
                        <td class="align-right"><?php echo e(format_rupiah($totalSaldoAwalKredit)); ?></td>
                    </tr>
                    <tr class="grand-row">
                        <td>Selisih</td>
                        <td class="align-right"><?php echo e(format_rupiah(abs($totalSaldoAwalDebit - $totalSaldoAwalKredit))); ?></td>
                    </tr>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="helper-text">Simpan tahun buku baru terlebih dahulu, lalu isi saldo awal tiap akun.</p>
        <?php } ?>
    </article>
</section>

<section class="panel">
    <h3>Saldo Awal Per Akun</h3>
    <?php if (!empty($formTahunBuku['id'])) { ?>
        <form method="post">
            <input type="hidden" name="aksi" value="simpan_saldo_awal">
            <input type="hidden" name="tahun_buku_id" value="<?php echo (int) $formTahunBuku['id']; ?>">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Akun</th>
                        <th>Kategori</th>
                        <th>Tipe Saldo</th>
                        <th class="align-right">Saldo Awal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($akun as $barisAkun) { ?>
                        <tr>
                            <td><?php echo e($barisAkun['kode_akun']); ?></td>
                            <td><?php echo e($barisAkun['nama_akun']); ?></td>
                            <td><?php echo e($barisAkun['kategori']); ?></td>
                            <td><?php echo e($barisAkun['tipe_saldo']); ?></td>
                            <td>
                                <input type="number" class="align-right" name="saldo_awal[<?php echo (int) $barisAkun['id']; ?>]" value="<?php echo e((string) ($saldoAwalDipilih[(int) $barisAkun['id']] ?? 0)); ?>" min="0" step="0.01">
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="button-row">
                <p class="helper-text">Untuk akun bertipe debit seperti kas, piutang, peralatan, dan beban, isi nilainya di akun itu sendiri. Untuk utang, modal, dan pendapatan, isi juga sebagai nilai positif pada akun bertipe kreditnya.</p>
                <button type="submit" class="button primary">Simpan Saldo Awal</button>
            </div>
        </form>
    <?php } else { ?>
        <p class="helper-text">Belum ada tahun buku yang dipilih. Buat atau pilih tahun buku terlebih dahulu.</p>
    <?php } ?>
</section>
<?php
render_footer();