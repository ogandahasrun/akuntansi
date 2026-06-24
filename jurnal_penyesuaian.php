<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'simpan_jurnal') {
    try {
        $_POST['jenis_transaksi'] = 'Penyesuaian'; // Pastikan tipenya Penyesuaian
        simpan_jurnal($_POST);
        atur_flash('success', 'Jurnal penyesuaian berhasil disimpan.');
        header('Location: jurnal_penyesuaian.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: jurnal_penyesuaian.php');
        exit;
    }
}

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'ubah_jurnal') {
    $id = (int) ($_POST['id'] ?? 0);

    try {
        $_POST['jenis_transaksi'] = 'Penyesuaian'; // Pastikan tipenya Penyesuaian
        ubah_jurnal($id, $_POST);
        atur_flash('success', 'Jurnal penyesuaian berhasil diperbarui.');
        header('Location: jurnal_penyesuaian.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: jurnal_penyesuaian.php?edit=' . $id);
        exit;
    }
}

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'hapus_jurnal') {
    try {
        hapus_jurnal((int) ($_POST['id'] ?? 0));
        atur_flash('success', 'Jurnal penyesuaian berhasil dihapus.');
        header('Location: jurnal_penyesuaian.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: jurnal_penyesuaian.php');
        exit;
    }
}

$akun = ambil_akun_untuk_jurnal();
$jurnalTerbaru = ambil_jurnal_penyesuaian(15);
$jurnalEditId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$jurnalEdit = $jurnalEditId > 0 ? ambil_jurnal_by_id($jurnalEditId) : null;

$detailJurnal = $jurnalEdit['detail'] ?? [];
$jumlahBarisForm = max(4, count($detailJurnal));
$barisForm = [];

for ($i = 0; $i < $jumlahBarisForm; $i++) {
    $barisForm[] = [
        'akun_id' => $detailJurnal[$i]['akun_id'] ?? '',
        'debit' => $detailJurnal[$i]['debit'] ?? 0,
        'kredit' => $detailJurnal[$i]['kredit'] ?? 0,
    ];
}

$nilaiTanggal    = $jurnalEdit['tanggal'] ?? date('Y-m-d');
$nilaiNomorBukti = $jurnalEdit['nomor_bukti'] ?? '';
$nilaiKeterangan     = $jurnalEdit['keterangan'] ?? '';
$modeEdit = $jurnalEdit !== null;

render_header('Jurnal Penyesuaian', 'jurnal_penyesuaian');
?>
<section class="panel">
    <h3><?php echo $modeEdit ? 'Edit Jurnal Penyesuaian' : 'Buat Jurnal Penyesuaian Baru'; ?></h3>
    <form method="post" class="form-grid journal-form">
        <input type="hidden" name="aksi" value="<?php echo $modeEdit ? 'ubah_jurnal' : 'simpan_jurnal'; ?>">
        <input type="hidden" name="jenis_transaksi" value="Penyesuaian">
        <?php if ($modeEdit) { ?>
            <input type="hidden" name="id" value="<?php echo (int) $jurnalEdit['id']; ?>">
        <?php } ?>
        <label>
            <span>Tanggal</span>
            <input type="date" name="tanggal" value="<?php echo e($nilaiTanggal); ?>" required>
        </label>
        <label>
            <span>Nomor Bukti</span>
            <input type="text" name="nomor_bukti" placeholder="Contoh: AJP-001" value="<?php echo e($nilaiNomorBukti); ?>">
        </label>
        
        <label class="full-width">
            <span>Keterangan Penyesuaian</span>
            <textarea name="keterangan" rows="3" placeholder="Uraian penyesuaian (misal: Penyusutan Inventaris Kantor Bulan Juni)" required><?php echo e($nilaiKeterangan); ?></textarea>
        </label>

        <div class="full-width">
            <table class="table" id="tabelJurnal">
                <thead>
                    <tr>
                        <th>Akun</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($barisForm as $baris) { ?>
                        <tr>
                            <td>
                                <select name="akun_id[]">
                                    <option value="">Pilih akun</option>
                                    <?php foreach ($akun as $item) { ?>
                                        <option value="<?php echo (int) $item['id']; ?>" <?php echo (int) $baris['akun_id'] === (int) $item['id'] ? 'selected' : ''; ?>>
                                            <?php
                                            $labelAkun = $item['kode_akun'] . ' - ';
                                            if (!empty($item['nama_induk'])) {
                                                $labelAkun .= '[' . $item['kode_induk'] . '] ';
                                            }
                                            $labelAkun .= $item['nama_akun'];
                                            echo e($labelAkun);
                                            ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td><input type="number" name="debit[]" value="<?php echo e((string) $baris['debit']); ?>" min="0" step="0.01"></td>
                            <td><input type="number" name="kredit[]" value="<?php echo e((string) $baris['kredit']); ?>" min="0" step="0.01"></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <button type="button" class="button ghost" id="tambahBaris">Tambah Baris</button>
        </div>

        <div class="button-row full-width">
            <button type="submit" class="button primary"><?php echo $modeEdit ? 'Perbarui Jurnal' : 'Simpan Jurnal'; ?></button>
            <?php if ($modeEdit) { ?>
                <a href="jurnal_penyesuaian.php" class="button ghost">Batal</a>
            <?php } ?>
        </div>
    </form>
    <p class="helper-text">Jurnal penyesuaian digunakan untuk mencocokkan pendapatan dan beban pada akhir periode akuntansi (misalnya penyusutan aset, biaya dibayar di muka, atau pendapatan akrual). Baris yang kosong boleh dibiarkan. Jurnal tetap harus minimal dua baris terisi dan total debit-kredit harus seimbang.</p>
</section>

<section class="panel">
    <h3>Riwayat Jurnal Penyesuaian</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nomor Bukti</th>
                <th>Keterangan</th>
                <th class="align-right">Total</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jurnalTerbaru)) { ?>
                <tr>
                    <td colspan="5" class="empty-state">Belum ada data jurnal penyesuaian.</td>
                </tr>
            <?php } ?>
            <?php foreach ($jurnalTerbaru as $baris) { ?>
                <tr>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($baris['nomor_bukti']); ?></td>
                    <td><?php echo e($baris['keterangan']); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['total_nominal'])); ?></td>
                    <td>
                        <div class="inline-actions">
                            <a href="jurnal_penyesuaian.php?edit=<?php echo (int) $baris['id']; ?>" class="button small">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus jurnal penyesuaian ini?');">
                                <input type="hidden" name="aksi" value="hapus_jurnal">
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

<script>
document.getElementById('tambahBaris').addEventListener('click', function () {
    const tbody = document.querySelector('#tabelJurnal tbody');
    const barisPertama = tbody.querySelector('tr');
    const barisBaru = barisPertama.cloneNode(true);
    barisBaru.querySelectorAll('input').forEach(function (input) {
        input.value = '0';
    });
    barisBaru.querySelectorAll('select').forEach(function (select) {
        select.selectedIndex = 0;
    });
    tbody.appendChild(barisBaru);
});
</script>
<?php
render_footer();
