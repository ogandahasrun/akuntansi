<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodeRequest === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'simpan_kontak') {
            simpan_kontak($_POST);
            atur_flash('success', 'Kontak berhasil ditambahkan.');
        }

        if ($aksi === 'bayar_relasi') {
            catat_pembayaran_relasi((int) ($_POST['id_relasi'] ?? 0), $_POST['jumlah_bayar'] ?? 0);
            atur_flash('success', 'Pembayaran berhasil dicatat.');
        }

        header('Location: hutang_piutang.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: hutang_piutang.php');
        exit;
    }
}

$relasi = ambil_relasi_hutang_piutang();
$kontak = ambil_daftar_kontak();

render_header('Hutang dan Piutang', 'hutang_piutang');
?>
<section class="grid-two">
    <article class="panel">
        <h3>Tambah Kontak</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="simpan_kontak">
            <label>
                <span>Nama Kontak</span>
                <input type="text" name="nama" required>
            </label>
            <label>
                <span>Jenis Kontak</span>
                <select name="jenis">
                    <option value="Pelanggan">Pelanggan</option>
                    <option value="Pemasok">Pemasok</option>
                </select>
            </label>
            <label>
                <span>Telepon</span>
                <input type="text" name="telepon">
            </label>
            <label>
                <span>Alamat</span>
                <textarea name="alamat" rows="3"></textarea>
            </label>
            <button type="submit" class="button primary">Simpan Kontak</button>
        </form>
    </article>
    <article class="panel">
        <h3>Cara Pakai</h3>
        <p>Masukkan transaksi hutang atau piutang dari halaman jurnal umum. Di halaman ini Anda dapat memantau tagihan yang berjalan dan mencatat pembayaran secara ringkas.</p>
        <p class="helper-text">Agar arus kas tetap benar, pembayaran nyata tetap sebaiknya dicatat juga di jurnal umum.</p>
    </article>
</section>

<section class="panel">
    <div class="section-title">
        <h3>Daftar Hutang dan Piutang</h3>
        <span>Total kontak aktif: <?php echo count($kontak); ?></span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Kontak</th>
                <th>Jatuh Tempo</th>
                <th class="align-right">Nominal</th>
                <th class="align-right">Dibayar</th>
                <th class="align-right">Sisa</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($relasi)) { ?>
                <tr>
                    <td colspan="9" class="empty-state">Belum ada data hutang atau piutang.</td>
                </tr>
            <?php } ?>
            <?php foreach ($relasi as $baris) { ?>
                <tr>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($baris['jenis']); ?></td>
                    <td><?php echo e($baris['nama_kontak']); ?></td>
                    <td><?php echo e(format_tanggal_indonesia($baris['jatuh_tempo'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['nominal'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['dibayar'])); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['sisa'])); ?></td>
                    <td><?php echo e($baris['status']); ?></td>
                    <td>
                        <?php if ((float) $baris['sisa'] > 0) { ?>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="aksi" value="bayar_relasi">
                                <input type="hidden" name="id_relasi" value="<?php echo (int) $baris['id']; ?>">
                                <input type="number" name="jumlah_bayar" min="0" step="0.01" placeholder="Bayar" required>
                                <button type="submit" class="button small">Simpan</button>
                            </form>
                        <?php } else { ?>
                            <span class="badge success">Selesai</span>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<section class="panel">
    <h3>Kontak Aktif</h3>
    <table class="table compact">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Jenis</th>
                <th>Telepon</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($kontak)) { ?>
                <tr>
                    <td colspan="4" class="empty-state">Belum ada kontak.</td>
                </tr>
            <?php } ?>
            <?php foreach ($kontak as $item) { ?>
                <tr>
                    <td><?php echo e($item['nama']); ?></td>
                    <td><?php echo e($item['jenis']); ?></td>
                    <td><?php echo e($item['telepon']); ?></td>
                    <td><?php echo e($item['alamat']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
<?php
render_footer();