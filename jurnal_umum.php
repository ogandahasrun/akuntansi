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

$akun = ambil_akun_untuk_jurnal(); // Hanya akun leaf (tanpa sub-akun) yang boleh dipakai di jurnal
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

$relasiEdit      = $jurnalEdit['relasi'] ?? null;
$nilaiTanggal    = $jurnalEdit['tanggal'] ?? date('Y-m-d');
$nilaiNomorBukti = $jurnalEdit['nomor_bukti'] ?? '';
$nilaiJenis      = $jurnalEdit['jenis_transaksi'] ?? 'Umum';
$nilaiKontakId   = $relasiEdit['kontak_id'] ?? 0;
// Untuk Bayar Hutang, kontak_id bisa dari hutang_piutang_id yang terhubung
if ($nilaiKontakId === 0 && !empty($jurnalEdit['hutang_piutang_id'])) {
    $hpEdit = kueri_satu('SELECT kontak_id FROM hutang_piutang WHERE id = ' . (int) $jurnalEdit['hutang_piutang_id'] . ' LIMIT 1');
    $nilaiKontakId = (int) ($hpEdit['kontak_id'] ?? 0);
}
$nilaiJatuhTempo     = $relasiEdit['jatuh_tempo'] ?? '';
$nilaiNominalRelasi  = $relasiEdit['nominal'] ?? (float) ($jurnalEdit['nominal_bayar'] ?? 0);
$nilaiKeterangan     = $jurnalEdit['keterangan'] ?? '';
$nilaiHutangPiutangId = (int) ($jurnalEdit['hutang_piutang_id'] ?? 0);
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
            <input type="hidden" name="hutang_piutang_id" id="hutangPiutangId" value="<?php echo (int) $nilaiHutangPiutangId; ?>">
            <label>
                <span>Jenis Transaksi</span>
                <select name="jenis_transaksi" id="jenisTransaksi">
                    <option value="Umum"         <?php echo $nilaiJenis === 'Umum'          ? 'selected' : ''; ?>>Umum</option>
                    <option value="Kas"          <?php echo $nilaiJenis === 'Kas'           ? 'selected' : ''; ?>>Kas</option>
                    <option value="Hutang"       <?php echo $nilaiJenis === 'Hutang'        ? 'selected' : ''; ?>>Hutang (Catat Faktur Baru)</option>
                    <option value="Piutang"      <?php echo $nilaiJenis === 'Piutang'       ? 'selected' : ''; ?>>Piutang (Catat Faktur Baru)</option>
                    <option value="Bayar Hutang" <?php echo $nilaiJenis === 'Bayar Hutang'  ? 'selected' : ''; ?>>Bayar Hutang (Lunasi Faktur)</option>
                    <option value="Terima Piutang" <?php echo $nilaiJenis === 'Terima Piutang' ? 'selected' : ''; ?>>Terima Piutang (Lunasi Faktur)</option>
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
        <label id="wrapJatuhTempo">
            <span>Jatuh Tempo</span>
            <input type="date" name="jatuh_tempo" id="jatuhTempo" value="<?php echo e($nilaiJatuhTempo); ?>">
        </label>
        <label id="wrapNominalRelasi">
            <span id="labelNominalRelasi">Nominal Hutang/Piutang</span>
            <input type="number" name="nominal_relasi" id="nominalRelasi" value="<?php echo e((string) $nilaiNominalRelasi); ?>" min="0" step="0.01">
        </label>

        <!-- Panel khusus untuk Bayar Hutang / Terima Piutang -->
        <div id="wrapPilihFaktur" class="full-width" style="display:none">
            <div style="background:var(--bg-subtle,#f0f4ff);border:1px solid var(--border,#d0d8ef);border-radius:8px;padding:1rem;">
                <p style="margin:0 0 .6rem;font-weight:600">📋 Pilih Faktur yang Akan Dilunasi</p>
                <div id="loadingFaktur" style="display:none;color:var(--text-subtle)">Memuat daftar faktur…</div>
                <div id="emptyFaktur" style="display:none;color:var(--text-subtle)">Tidak ada faktur belum lunas untuk kontak ini.</div>
                <div id="daftarFaktur" style="display:flex;flex-direction:column;gap:.5rem"></div>
            </div>
        </div>
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
                <a href="jurnal_umum.php" class="button ghost">Batal</a>
            <?php } ?>
        </div>
    </form>
    <p class="helper-text">Baris yang kosong boleh dibiarkan. Jurnal tetap harus minimal dua baris terisi dan total debit-kredit harus seimbang. Pilih jenis <strong>Hutang</strong> atau <strong>Piutang</strong> untuk mencatat faktur baru yang masuk ke daftar tagihan. Pilih jenis <strong>Bayar Hutang</strong> atau <strong>Terima Piutang</strong> untuk melunasi faktur — status di halaman Hutang &amp; Piutang akan otomatis diperbarui.</p>
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

// ====== State faktur yang dimuat dari server ======
let daftarFakturCache = [];
<?php if ($nilaiHutangPiutangId > 0) { ?>
const nilaiHutangPiutangIdAwal = <?php echo (int) $nilaiHutangPiutangId; ?>;
<?php } else { ?>
const nilaiHutangPiutangIdAwal = 0;
<?php } ?>

function formatRupiah(angka) {
    return 'Rp ' + parseFloat(angka).toLocaleString('id-ID', {minimumFractionDigits: 0});
}

function renderDaftarFaktur(daftar, selectedId) {
    const container = document.getElementById('daftarFaktur');
    const emptyMsg  = document.getElementById('emptyFaktur');
    container.innerHTML = '';

    if (!daftar || daftar.length === 0) {
        emptyMsg.style.display = '';
        return;
    }
    emptyMsg.style.display = 'none';

    daftar.forEach(function (faktur) {
        const isSelected = faktur.id === selectedId;
        const card = document.createElement('label');
        card.style.cssText = [
            'display:flex', 'gap:.75rem', 'align-items:flex-start',
            'padding:.75rem 1rem', 'border-radius:6px', 'cursor:pointer',
            'border:2px solid ' + (isSelected ? 'var(--accent,#4a6cf7)' : 'var(--border,#dde1f0)'),
            'background:' + (isSelected ? 'var(--accent-subtle,#eef1fd)' : '#fff'),
        ].join(';');

        const radio = document.createElement('input');
        radio.type    = 'radio';
        radio.name    = '__faktur_pilihan';
        radio.value   = faktur.id;
        radio.checked = isSelected;
        radio.style.marginTop = '2px';

        const info = document.createElement('div');
        info.innerHTML = [
            '<strong>' + faktur.label + '</strong>',
            '<small style="display:block;color:var(--text-subtle)">',
            'Sisa tagihan: <strong style="color:var(--accent)">' + formatRupiah(faktur.sisa) + '</strong>',
            '</small>',
        ].join('');

        card.appendChild(radio);
        card.appendChild(info);
        container.appendChild(card);

        radio.addEventListener('change', function () {
            if (this.checked) {
                pilihFaktur(faktur);
                // Update visual border
                container.querySelectorAll('label').forEach(function (l) {
                    l.style.border = '2px solid var(--border,#dde1f0)';
                    l.style.background = '#fff';
                });
                card.style.border    = '2px solid var(--accent,#4a6cf7)';
                card.style.background = 'var(--accent-subtle,#eef1fd)';
            }
        });
    });
}

function pilihFaktur(faktur) {
    document.getElementById('hutangPiutangId').value = faktur.id;
    document.getElementById('nominalRelasi').value   = faktur.sisa;
    document.getElementById('nominalRelasi').max     = faktur.sisa;
}

function muatFaktur(kontakId, jenis, selectedId) {
    if (!kontakId || kontakId === '0') {
        document.getElementById('daftarFaktur').innerHTML = '';
        document.getElementById('emptyFaktur').style.display = 'none';
        return;
    }

    const jenisHutang = jenis === 'Bayar Hutang' ? 'Hutang' : 'Piutang';
    document.getElementById('loadingFaktur').style.display = '';
    document.getElementById('daftarFaktur').innerHTML = '';
    document.getElementById('emptyFaktur').style.display = 'none';

    fetch('api_hutang.php?kontak_id=' + kontakId + '&jenis=' + jenisHutang)
        .then(function (res) { return res.json(); })
        .then(function (data) {
            daftarFakturCache = data;
            document.getElementById('loadingFaktur').style.display = 'none';
            renderDaftarFaktur(data, selectedId || 0);

            // Jika ada satu faktur dan belum ada yang dipilih, pilih otomatis
            if (data.length === 1 && !selectedId) {
                pilihFaktur(data[0]);
                const radio = document.querySelector('#daftarFaktur input[type=radio]');
                if (radio) radio.checked = true;
                const card = radio && radio.closest('label');
                if (card) {
                    card.style.border    = '2px solid var(--accent,#4a6cf7)';
                    card.style.background = 'var(--accent-subtle,#eef1fd)';
                }
            }
        })
        .catch(function () {
            document.getElementById('loadingFaktur').style.display = 'none';
            document.getElementById('emptyFaktur').style.display = '';
            document.getElementById('emptyFaktur').textContent = 'Gagal memuat faktur.';
        });
}

function aturFieldRelasi() {
    const jenis         = document.getElementById('jenisTransaksi').value;
    const selectKontak  = document.getElementById('kontakId');
    const labelKontak   = document.getElementById('labelKontakTerkait');
    const wrapJT        = document.getElementById('wrapJatuhTempo');
    const wrapNR        = document.getElementById('wrapNominalRelasi');
    const wrapFaktur    = document.getElementById('wrapPilihFaktur');
    const labelNR       = document.getElementById('labelNominalRelasi');

    const adalahHutang   = jenis === 'Hutang' || jenis === 'Piutang';
    const adalahPelunasan = jenis === 'Bayar Hutang' || jenis === 'Terima Piutang';
    const aktif          = adalahHutang || adalahPelunasan;

    const targetJenis = (jenis === 'Hutang' || jenis === 'Bayar Hutang') ? 'Pemasok'
                      : (jenis === 'Piutang' || jenis === 'Terima Piutang') ? 'Pelanggan'
                      : '';

    labelKontak.textContent = (jenis === 'Hutang' || jenis === 'Bayar Hutang') ? 'Supplier'
                            : (jenis === 'Piutang' || jenis === 'Terima Piutang') ? 'Pelanggan'
                            : 'Kontak Terkait';

    // Tampilkan/sembunyikan field jatuh tempo dan nominal
    wrapJT.style.display = adalahHutang ? '' : 'none';
    wrapNR.style.display = aktif ? '' : 'none';
    labelNR.textContent  = adalahPelunasan ? 'Nominal yang Dibayarkan' : 'Nominal Hutang/Piutang';
    document.getElementById('nominalRelasi').readOnly = adalahPelunasan; // diisi otomatis dari faktur

    // Tampilkan/sembunyikan panel pilih faktur
    wrapFaktur.style.display = adalahPelunasan ? '' : 'none';

    if (!adalahPelunasan) {
        // Reset hutang_piutang_id jika bukan mode pelunasan
        document.getElementById('hutangPiutangId').value = 0;
    }

    selectKontak.required = aktif;
    const elJT = document.getElementById('jatuhTempo');
    const elNR = document.getElementById('nominalRelasi');
    elJT.required = adalahHutang;
    elNR.required = aktif;

    // Filter kontak sesuai jenis
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

    // Muat faktur jika mode pelunasan dan kontak sudah dipilih
    if (adalahPelunasan && selectKontak.value && selectKontak.value !== '0') {
        muatFaktur(selectKontak.value, jenis, nilaiHutangPiutangIdAwal);
    } else if (!adalahPelunasan) {
        document.getElementById('daftarFaktur').innerHTML = '';
        document.getElementById('emptyFaktur').style.display = 'none';
    }
}

document.getElementById('jenisTransaksi').addEventListener('change', function () {
    // Reset faktur yang dipilih saat jenis berubah
    document.getElementById('hutangPiutangId').value = 0;
    document.getElementById('nominalRelasi').value   = 0;
    aturFieldRelasi();
});

document.getElementById('kontakId').addEventListener('change', function () {
    const jenis = document.getElementById('jenisTransaksi').value;
    const adalahPelunasan = jenis === 'Bayar Hutang' || jenis === 'Terima Piutang';
    if (adalahPelunasan) {
        // Reset pilihan faktur saat kontak berubah
        document.getElementById('hutangPiutangId').value = 0;
        document.getElementById('nominalRelasi').value   = 0;
        muatFaktur(this.value, jenis, 0);
    }
});

atur_field_relasi_init();
function atur_field_relasi_init() {
    aturFieldRelasi();
    // Jika mode edit dengan jenis Bayar Hutang, muat faktur dan pilih yang sesuai
    const jenis = document.getElementById('jenisTransaksi').value;
    const adalahPelunasan = jenis === 'Bayar Hutang' || jenis === 'Terima Piutang';
    const kontakId = document.getElementById('kontakId').value;
    if (adalahPelunasan && kontakId && kontakId !== '0') {
        muatFaktur(kontakId, jenis, nilaiHutangPiutangIdAwal);
    }
}
</script>
<?php
render_footer();