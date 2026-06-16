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

$akun = ambil_daftar_akun();
$kontak = ambil_daftar_kontak();
$jurnalTerbaru = ambil_jurnal_terbaru(15);

render_header('Jurnal Umum', 'jurnal');
?>
<section class="panel">
    <h3>Buat Jurnal Baru</h3>
    <form method="post" class="form-grid journal-form">
        <input type="hidden" name="aksi" value="simpan_jurnal">
        <label>
            <span>Tanggal</span>
            <input type="date" name="tanggal" value="<?php echo e(date('Y-m-d')); ?>" required>
        </label>
        <label>
            <span>Nomor Bukti</span>
            <input type="text" name="nomor_bukti" placeholder="Contoh: JU-001">
        </label>
        <label>
            <span>Jenis Transaksi</span>
            <select name="jenis_transaksi" id="jenisTransaksi">
                <option value="Umum">Umum</option>
                <option value="Kas">Kas</option>
                <option value="Hutang">Hutang</option>
                <option value="Piutang">Piutang</option>
            </select>
        </label>
        <label>
            <span>Kontak Terkait</span>
            <select name="kontak_id" id="kontakId">
                <option value="0">Tidak ada</option>
                <?php foreach ($kontak as $item) { ?>
                    <option value="<?php echo (int) $item['id']; ?>"><?php echo e($item['nama'] . ' - ' . $item['jenis']); ?></option>
                <?php } ?>
            </select>
        </label>
        <label>
            <span>Jatuh Tempo</span>
            <input type="date" name="jatuh_tempo" id="jatuhTempo">
        </label>
        <label>
            <span>Nominal Hutang/Piutang</span>
            <input type="number" name="nominal_relasi" id="nominalRelasi" value="0" min="0" step="0.01">
        </label>
        <label class="full-width">
            <span>Keterangan</span>
            <textarea name="keterangan" rows="3" placeholder="Uraian transaksi"></textarea>
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
                    <?php for ($i = 0; $i < 4; $i++) { ?>
                        <tr>
                            <td>
                                <select name="akun_id[]">
                                    <option value="">Pilih akun</option>
                                    <?php foreach ($akun as $item) { ?>
                                        <option value="<?php echo (int) $item['id']; ?>"><?php echo e($item['kode_akun'] . ' - ' . $item['nama_akun']); ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td><input type="number" name="debit[]" value="0" min="0" step="0.01"></td>
                            <td><input type="number" name="kredit[]" value="0" min="0" step="0.01"></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <button type="button" class="button ghost" id="tambahBaris">Tambah Baris</button>
        </div>

        <button type="submit" class="button primary">Simpan Jurnal</button>
    </form>
    <p class="helper-text">Baris yang kosong boleh dibiarkan. Jurnal tetap harus minimal dua baris terisi dan total debit-kredit harus seimbang. Untuk hutang atau piutang, pilih kontak terkait dan isi nominal relasi agar masuk juga ke daftar tagihan.</p>
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
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jurnalTerbaru)) { ?>
                <tr>
                    <td colspan="5" class="empty-state">Belum ada data jurnal.</td>
                </tr>
            <?php } ?>
            <?php foreach ($jurnalTerbaru as $baris) { ?>
                <tr>
                    <td><?php echo e(format_tanggal_indonesia($baris['tanggal'])); ?></td>
                    <td><?php echo e($baris['nomor_bukti']); ?></td>
                    <td><?php echo e($baris['jenis_transaksi']); ?></td>
                    <td><?php echo e($baris['keterangan']); ?></td>
                    <td class="align-right"><?php echo e(format_rupiah($baris['total_nominal'])); ?></td>
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
    document.getElementById('kontakId').required = aktif;
    document.getElementById('jatuhTempo').required = aktif;
    document.getElementById('nominalRelasi').required = aktif;
}

document.getElementById('jenisTransaksi').addEventListener('change', aturFieldRelasi);
aturFieldRelasi();
</script>
<?php
render_footer();