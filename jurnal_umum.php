<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'simpan_jurnal') {
    try {
        simpan_jurnal($_POST);
        atur_flash('success', 'Jurnal berhasil disimpan.');
        header('Location: jurnal_umum.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: jurnal_umum.php');
        exit;
    }
}

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'ubah_jurnal') {
    $id = (int) ($_POST['id'] ?? 0);

    try {
        ubah_jurnal($id, $_POST);
        atur_flash('success', 'Jurnal berhasil diperbarui.');
        header('Location: jurnal_umum.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: jurnal_umum.php?edit=' . $id);
        exit;
    }
}

if ($metodeRequest === 'POST' && ($_POST['aksi'] ?? '') === 'hapus_jurnal') {
    try {
        hapus_jurnal((int) ($_POST['id'] ?? 0));
        atur_flash('success', 'Jurnal berhasil dihapus.');
        header('Location: jurnal_umum.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', $exception->getMessage());
        header('Location: jurnal_umum.php');
        exit;
    }
}

$akun = ambil_daftar_akun();
$kontak = ambil_daftar_kontak();
$jurnalTerbaru = ambil_jurnal_terbaru(15);
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

$relasiEdit = $jurnalEdit['relasi'] ?? null;
$nilaiTanggal = $jurnalEdit['tanggal'] ?? date('Y-m-d');
$nilaiNomorBukti = $jurnalEdit['nomor_bukti'] ?? '';
$nilaiJenis = $jurnalEdit['jenis_transaksi'] ?? 'Umum';
$nilaiKontakId = $relasiEdit['kontak_id'] ?? 0;
$nilaiJatuhTempo = $relasiEdit['jatuh_tempo'] ?? '';
$nilaiNominalRelasi = $relasiEdit['nominal'] ?? 0;
$nilaiKeterangan = $jurnalEdit['keterangan'] ?? '';
$modeEdit = $jurnalEdit !== null;

render_header('Jurnal Umum', 'jurnal');
?>
<section class="panel">
    <h3><?php echo $modeEdit ? 'Edit Jurnal' : 'Buat Jurnal Baru'; ?></h3>
    <form method="post" class="form-grid journal-form">
        <input type="hidden" name="aksi" value="<?php echo $modeEdit ? 'ubah_jurnal' : 'simpan_jurnal'; ?>">
        <?php if ($modeEdit) { ?>
            <input type="hidden" name="id" value="<?php echo (int) $jurnalEdit['id']; ?>">
        <?php } ?>
        <label>
            <span>Tanggal</span>
            <input type="date" name="tanggal" value="<?php echo e($nilaiTanggal); ?>" required>
        </label>
        <label>
            <span>Nomor Bukti</span>
            <input type="text" name="nomor_bukti" placeholder="Contoh: JU-001" value="<?php echo e($nilaiNomorBukti); ?>">
        </label>
        <label>
            <span>Jenis Transaksi</span>
            <select name="jenis_transaksi" id="jenisTransaksi">
                <option value="Umum" <?php echo $nilaiJenis === 'Umum' ? 'selected' : ''; ?>>Umum</option>
                <option value="Kas" <?php echo $nilaiJenis === 'Kas' ? 'selected' : ''; ?>>Kas</option>
                <option value="Hutang" <?php echo $nilaiJenis === 'Hutang' ? 'selected' : ''; ?>>Hutang</option>
                <option value="Piutang" <?php echo $nilaiJenis === 'Piutang' ? 'selected' : ''; ?>>Piutang</option>
            </select>
        </label>
        <label>
            <span id="labelKontakTerkait">Kontak Terkait</span>
            <select name="kontak_id" id="kontakId">
                <option value="0">Tidak ada</option>
                <?php foreach ($kontak as $item) { ?>
                    <option value="<?php echo (int) $item['id']; ?>" data-jenis="<?php echo e($item['jenis']); ?>" <?php echo $nilaiKontakId === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['nama'] . ' - ' . $item['jenis']); ?></option>
                <?php } ?>
            </select>
        </label>
        <label>
            <span>Jatuh Tempo</span>
            <input type="date" name="jatuh_tempo" id="jatuhTempo" value="<?php echo e($nilaiJatuhTempo); ?>">
        </label>
        <label>
            <span>Nominal Hutang/Piutang</span>
            <input type="number" name="nominal_relasi" id="nominalRelasi" value="<?php echo e((string) $nilaiNominalRelasi); ?>" min="0" step="0.01">
        </label>
        <label class="full-width">
            <span>Keterangan</span>
            <textarea name="keterangan" rows="3" placeholder="Uraian transaksi"><?php echo e($nilaiKeterangan); ?></textarea>
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
                                        <option value="<?php echo (int) $item['id']; ?>" <?php echo (int) $baris['akun_id'] === (int) $item['id'] ? 'selected' : ''; ?>><?php echo e($item['kode_akun'] . ' - ' . $item['nama_akun']); ?></option>
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
                <a href="jurnal_umum.php" class="button ghost">Batal</a>
            <?php } ?>
        </div>
    </form>
    <p class="helper-text">Baris yang kosong boleh dibiarkan. Jurnal tetap harus minimal dua baris terisi dan total debit-kredit harus seimbang. Untuk transaksi hutang pembelian, pilih jenis `Hutang`, pilih supplier `Pemasok`, lalu isi nominal relasi agar masuk ke daftar tagihan. Jurnal yang hutang/piutangnya sudah pernah dibayar tidak bisa diubah atau dihapus.</p>
</section>

<section class="panel">
    <h3>Riwayat Jurnal</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nomor Bukti</th>
                <th>Jenis</th>
                <th>Keterangan</th>
                <th class="align-right">Total</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jurnalTerbaru)) { ?>
                <tr>
                    <td colspan="6" class="empty-state">Belum ada data jurnal.</td>
                </tr>
            <?php } ?>
            <?php foreach ($jurnalTerbaru as $baris) { ?>
                <tr>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($baris['nomor_bukti']); ?></td>
                    <td><?php echo e($baris['jenis_transaksi']); ?></td>
                    <td><?php echo e($baris['keterangan']); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['total_nominal'])); ?></td>
                    <td>
                        <div class="inline-actions">
                            <a href="jurnal_umum.php?edit=<?php echo (int) $baris['id']; ?>" class="button small">Edit</a>
                            <form method="post" onsubmit="return confirm('Hapus jurnal ini?');">
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

function aturFieldRelasi() {
    const jenis = document.getElementById('jenisTransaksi').value;
    const aktif = jenis === 'Hutang' || jenis === 'Piutang';
    const selectKontak = document.getElementById('kontakId');
    const labelKontak = document.getElementById('labelKontakTerkait');
    const targetJenis = jenis === 'Hutang' ? 'Pemasok' : (jenis === 'Piutang' ? 'Pelanggan' : '');

    labelKontak.textContent = jenis === 'Hutang'
        ? 'Supplier Pemasok'
        : (jenis === 'Piutang' ? 'Pelanggan' : 'Kontak Terkait');

    selectKontak.required = aktif;
    document.getElementById('jatuhTempo').required = aktif;
    document.getElementById('nominalRelasi').required = aktif;

    Array.from(selectKontak.options).forEach(function (option, index) {
        if (index === 0) {
            option.hidden = false;
            option.disabled = false;
            return;
        }

        const jenisKontak = option.getAttribute('data-jenis') || '';
        const cocok = targetJenis === '' || jenisKontak === targetJenis;
        option.hidden = !cocok;
        option.disabled = !cocok;
    });

    if (selectKontak.selectedOptions.length > 0 && selectKontak.selectedOptions[0].disabled) {
        selectKontak.value = '0';
    }
}

document.getElementById('jenisTransaksi').addEventListener('change', aturFieldRelasi);
aturFieldRelasi();
</script>
<?php
render_footer();