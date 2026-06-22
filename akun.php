<?php

require_once __DIR__ . '/fungsi.php';

$metodeRequest = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$akunEditId    = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$akunEdit      = $akunEditId > 0 ? ambil_akun_by_id($akunEditId) : null;

// ── Mutasi POST ──────────────────────────────────────────────────────────────

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

// ── Data ─────────────────────────────────────────────────────────────────────

// ambil_daftar_akun() TANPA hitung saldo per baris (performa).
// Saldo hanya bisa dilihat via Buku Besar.
$akun = ambil_daftar_akun();

// Kelompokkan per kategori untuk tampilan yang lebih rapi
$akunPerKategori = [];
foreach ($akun as $baris) {
    $akunPerKategori[$baris['kategori']][] = $baris;
}
ksort($akunPerKategori);

// Daftar akun calon induk (level 1, tanpa parent)
$akunCalonInduk = array_filter($akun, function ($a) use ($akunEditId) {
    return empty($a['parent_id']) && (int) $a['id'] !== $akunEditId;
});

if ($akunEdit && (int) $akunEdit['aktif'] !== 1) {
    $akunEdit = null;
}

$jumlahAkun = count($akun);

// Warna badge per kategori
$warnaKategori = [
    'Aset'       => ['bg' => 'rgba(17,100,102,0.12)',  'color' => '#0b4a4c'],
    'Kewajiban'  => ['bg' => 'rgba(201,92,55,0.12)',   'color' => '#7a2d12'],
    'Ekuitas'    => ['bg' => 'rgba(246,174,45,0.18)',  'color' => '#7a5800'],
    'Pendapatan' => ['bg' => 'rgba(35,123,75,0.12)',   'color' => '#185a35'],
    'Beban'      => ['bg' => 'rgba(163,53,53,0.12)',   'color' => '#7f2121'],
];

render_header('Daftar Akun', 'akun');
?>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Tambah / Edit Akun
     Dibuka via JS agar form tidak memenuhi halaman saat hanya lihat data.
     ══════════════════════════════════════════════════════════════════════════ -->
<div id="modal-akun" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-judul">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modal-judul"><?php echo $akunEdit ? 'Edit Akun' : 'Tambah Akun Baru'; ?></h3>
            <button type="button" class="modal-close" id="btn-tutup-modal" aria-label="Tutup">✕</button>
        </div>
        <form method="post" class="form-grid">
            <input type="hidden" name="aksi" value="<?php echo $akunEdit ? 'ubah_akun' : 'simpan_akun'; ?>">
            <?php if ($akunEdit) { ?>
                <input type="hidden" name="id" value="<?php echo (int) $akunEdit['id']; ?>">
            <?php } ?>

            <label>
                <span>Kode Akun</span>
                <input type="text" name="kode_akun" id="inp-kode-akun"
                       placeholder="Contoh: 2-1201"
                       value="<?php echo e($akunEdit['kode_akun'] ?? ''); ?>" required>
            </label>
            <label>
                <span>Nama Akun</span>
                <input type="text" name="nama_akun" id="inp-nama-akun"
                       placeholder="Contoh: Hutang - PT Kimia Farma"
                       value="<?php echo e($akunEdit['nama_akun'] ?? ''); ?>" required>
            </label>
            <label class="full-width">
                <span>Akun Induk <small style="color:var(--muted)">(opsional — untuk sub-rekening)</small></span>
                <select name="parent_id" id="inp-parent-id">
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
                <select name="kategori" id="inp-kategori">
                    <?php foreach (['Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban'] as $kategori) { ?>
                        <option value="<?php echo e($kategori); ?>"
                            <?php echo ($akunEdit['kategori'] ?? 'Aset') === $kategori ? 'selected' : ''; ?>>
                            <?php echo e($kategori); ?>
                        </option>
                    <?php } ?>
                </select>
            </label>
            <label>
                <span>Tipe Saldo</span>
                <select name="tipe_saldo" id="inp-tipe-saldo">
                    <option value="Debit"  <?php echo ($akunEdit['tipe_saldo'] ?? 'Debit') === 'Debit'  ? 'selected' : ''; ?>>Debit</option>
                    <option value="Kredit" <?php echo ($akunEdit['tipe_saldo'] ?? 'Debit') === 'Kredit' ? 'selected' : ''; ?>>Kredit</option>
                </select>
            </label>
            <label class="checkbox-row full-width">
                <input type="checkbox" name="is_kas" value="1"
                       <?php echo !empty($akunEdit['is_kas']) ? 'checked' : ''; ?>>
                <span>Tandai sebagai akun kas/bank</span>
            </label>
            <div class="button-row full-width" style="margin-top:4px">
                <button type="submit" class="button primary">
                    <?php echo $akunEdit ? 'Perbarui Akun' : 'Simpan Akun'; ?>
                </button>
                <a href="akun.php" class="button ghost">Batal</a>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     TOOLBAR: Statistik ringkas + Tombol Tambah + Filter
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="akun-toolbar">
    <!-- Stat chips -->
    <div class="akun-stats">
        <?php foreach ($akunPerKategori as $kat => $barisKat) {
            $w = $warnaKategori[$kat] ?? ['bg' => 'rgba(0,0,0,0.07)', 'color' => 'inherit'];
        ?>
        <span class="stat-chip" style="background:<?php echo $w['bg']; ?>;color:<?php echo $w['color']; ?>">
            <strong><?php echo count($barisKat); ?></strong> <?php echo e($kat); ?>
        </span>
        <?php } ?>
        <span class="stat-chip" style="background:rgba(0,0,0,0.06);color:var(--muted)">
            Total <strong><?php echo $jumlahAkun; ?></strong> akun
        </span>
    </div>

    <!-- Kontrol kanan -->
    <div class="akun-controls">
        <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="search" id="cari-akun" placeholder="Cari kode atau nama akun…"
                   autocomplete="off" spellcheck="false">
        </div>
        <select id="filter-kategori" title="Filter kategori">
            <option value="">Semua Kategori</option>
            <?php foreach (array_keys($akunPerKategori) as $kat) { ?>
                <option value="<?php echo e($kat); ?>"><?php echo e($kat); ?></option>
            <?php } ?>
        </select>
        <button type="button" class="button primary" id="btn-tambah-akun">
            + Tambah Akun
        </button>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     TABEL AKUN — dikelompokkan per kategori
     Kolom "Saldo" dihapus dari sini agar tidak ada N+1 query.
     Lihat saldo via Buku Besar.
     ══════════════════════════════════════════════════════════════════════════ -->
<div id="akun-container">
<?php foreach ($akunPerKategori as $kategori => $barisKat):
    $w = $warnaKategori[$kategori] ?? ['bg' => 'rgba(0,0,0,0.07)', 'color' => 'inherit'];
?>
<section class="panel akun-group" data-kategori="<?php echo e($kategori); ?>">
    <div class="akun-group-header">
        <span class="akun-group-badge" style="background:<?php echo $w['bg']; ?>;color:<?php echo $w['color']; ?>">
            <?php echo e($kategori); ?>
        </span>
        <span class="akun-group-count"><?php echo count($barisKat); ?> akun</span>
    </div>
    <table class="table akun-table">
        <thead>
            <tr>
                <th style="width:130px">Kode</th>
                <th>Nama Akun</th>
                <th style="width:80px">Tipe Saldo</th>
                <th style="width:80px">Kas/Bank</th>
                <th style="width:110px;text-align:right">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($barisKat as $baris):
            $adaSubAkun    = (int) ($baris['jumlah_sub_akun'] ?? 0) > 0;
            $adalahSubAkun = !empty($baris['parent_id']);
            $rowKelas      = $adaSubAkun ? 'row-induk' : ($adalahSubAkun ? 'row-sub' : '');
        ?>
            <tr class="akun-row <?php echo $rowKelas; ?>"
                data-kode="<?php echo e(strtolower($baris['kode_akun'])); ?>"
                data-nama="<?php echo e(strtolower($baris['nama_akun'])); ?>"
                data-kategori="<?php echo e($baris['kategori']); ?>">

                <td class="td-kode">
                    <?php if ($adalahSubAkun): ?>
                        <span class="sub-indent">└</span>
                    <?php endif; ?>
                    <code class="kode-akun"><?php echo e($baris['kode_akun']); ?></code>
                </td>

                <td class="td-nama">
                    <?php if ($adaSubAkun): ?>
                        <strong><?php echo e($baris['nama_akun']); ?></strong>
                        <span class="badge-induk">Induk</span>
                    <?php elseif ($adalahSubAkun): ?>
                        <span class="nama-sub"><?php echo e($baris['nama_akun']); ?></span>
                    <?php else: ?>
                        <?php echo e($baris['nama_akun']); ?>
                    <?php endif; ?>
                </td>

                <td>
                    <span class="badge-tipe <?php echo $baris['tipe_saldo'] === 'Debit' ? 'debit' : 'kredit'; ?>">
                        <?php echo e($baris['tipe_saldo']); ?>
                    </span>
                </td>

                <td><?php echo $baris['is_kas'] ? '<span class="kas-ya">✓ Ya</span>' : '<span style="color:var(--muted)">—</span>'; ?></td>

                <td>
                    <div class="inline-actions" style="justify-content:flex-end">
                        <a href="akun.php?edit=<?php echo (int) $baris['id']; ?>"
                           class="button small" title="Edit akun ini">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus akun ini?');"
                              style="margin:0">
                            <input type="hidden" name="aksi" value="hapus_akun">
                            <input type="hidden" name="id" value="<?php echo (int) $baris['id']; ?>">
                            <button type="submit" class="button small ghost"
                                    title="Hapus akun ini">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endforeach; ?>

<p id="no-results" style="display:none;text-align:center;padding:32px;color:var(--muted)">
    Tidak ada akun yang cocok dengan pencarian.
</p>
</div><!-- /#akun-container -->

<!-- Info: Saldo tidak ditampilkan di sini -->
<p style="text-align:right;color:var(--muted);font-size:0.82rem;margin-top:-8px">
    💡 Saldo per akun bisa dilihat di halaman
    <a href="buku_besar.php" style="color:var(--primary)">Buku Besar</a>.
</p>

<!-- ══════════════════════════════════════════════════════════════════════════
     STYLES khusus halaman akun
     ══════════════════════════════════════════════════════════════════════════ -->
<style>
/* ─── Toolbar ─────────────────────────────────────────────── */
.akun-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.akun-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 500;
}
.akun-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.search-wrap {
    position: relative;
}
.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    font-size: 0.9rem;
}
#cari-akun {
    width: 220px;
    padding-left: 36px;
    border-radius: 999px;
}
#filter-kategori {
    width: auto;
    min-width: 140px;
    border-radius: 999px;
    padding: 10px 14px;
}

/* ─── Grup akun ───────────────────────────────────────────── */
.akun-group {
    margin-bottom: 18px;
    padding: 0;
    overflow: hidden;
}
.akun-group-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px 12px;
    border-bottom: 1px solid rgba(222,207,183,0.6);
}
.akun-group-badge {
    font-weight: 700;
    font-size: 0.88rem;
    padding: 4px 12px;
    border-radius: 999px;
    letter-spacing: 0.03em;
}
.akun-group-count {
    font-size: 0.8rem;
    color: var(--muted);
}

/* ─── Tabel ───────────────────────────────────────────────── */
.akun-table th,
.akun-table td {
    padding: 11px 14px;
}
.akun-table tbody tr {
    transition: background 0.15s;
}
.akun-table tbody tr:hover {
    background: rgba(246,174,45,0.07);
}
.row-induk {
    background: rgba(0,0,0,0.025);
}
.td-kode {
    white-space: nowrap;
}
.sub-indent {
    color: var(--muted);
    margin-right: 4px;
    font-size: 1rem;
}
.kode-akun {
    font-family: 'Courier New', monospace;
    font-size: 0.88rem;
    background: rgba(17,100,102,0.07);
    color: var(--primary);
    padding: 2px 7px;
    border-radius: 6px;
}
.badge-induk {
    font-size: 0.7rem;
    background: rgba(17,100,102,0.12);
    color: var(--primary);
    border-radius: 4px;
    padding: 1px 6px;
    margin-left: 6px;
    vertical-align: middle;
}
.nama-sub {
    padding-left: 1em;
    display: inline-block;
}
.badge-tipe {
    font-size: 0.78rem;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
}
.badge-tipe.debit  { background: rgba(17,100,102,0.10); color:#0b4a4c; }
.badge-tipe.kredit { background: rgba(201,92,55,0.10);  color:#7a2d12; }
.kas-ya { color: var(--success); font-weight: 600; }

/* ─── Modal ───────────────────────────────────────────────── */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,36,41,0.55);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: none; /* tersembunyi secara default */
    align-items: center;
    justify-content: center;
    padding: 20px;
}
/* Hanya tampilkan ketika class .modal-open ditambahkan via JS */
.modal-overlay.modal-open {
    display: flex;
    animation: fadeIn 0.18s ease;
}
@keyframes fadeIn { from { opacity:0 } to { opacity:1 } }
.modal-box {
    background: var(--panel-strong);
    border-radius: 22px;
    box-shadow: 0 24px 60px rgba(15,36,41,0.22);
    width: 100%;
    max-width: 560px;
    padding: 28px;
    animation: slideUp 0.2s ease;
}
@keyframes slideUp { from { transform:translateY(16px); opacity:0 } to { transform:translateY(0); opacity:1 } }
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
}
.modal-header h3 { margin: 0; }
.modal-close {
    background: transparent;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: var(--muted);
    padding: 4px 8px;
    border-radius: 8px;
    line-height: 1;
}
.modal-close:hover { background: rgba(0,0,0,0.07); }

/* ─── Row tersembunyi saat filter aktif ───────────────────── */
.akun-row.hidden { display: none; }
.akun-group.hidden { display: none; }
</style>

<!-- ══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT: Modal + Filter real-time (client-side, tanpa request server)
     ══════════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Modal ─────────────────────────────────────────────── */
    const modal       = document.getElementById('modal-akun');
    const btnTambah   = document.getElementById('btn-tambah-akun');
    const btnTutup    = document.getElementById('btn-tutup-modal');
    const editMode    = <?php echo $akunEdit ? 'true' : 'false'; ?>;

    function bukaModal() {
        modal.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
        // Fokus ke field pertama
        setTimeout(function () {
            const el = modal.querySelector('input[name="kode_akun"]');
            if (el) el.focus();
        }, 80);
    }

    function tutupModal() {
        modal.classList.remove('modal-open');
        document.body.style.overflow = '';
        // Jika sedang mode edit, kembali ke halaman bersih
        if (editMode) {
            window.location.href = 'akun.php';
        }
    }

    // Buka otomatis jika ada ?edit=
    if (editMode) {
        bukaModal();
    }

    btnTambah.addEventListener('click', bukaModal);
    btnTutup.addEventListener('click', tutupModal);

    // Tutup dengan klik di luar modal-box
    modal.addEventListener('click', function (e) {
        if (e.target === modal) tutupModal();
    });

    // Tutup dengan Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('modal-open')) tutupModal();
    });

    /* ── Filter real-time (client-side) ────────────────────── */
    const inputCari       = document.getElementById('cari-akun');
    const selectKategori  = document.getElementById('filter-kategori');
    const noResults       = document.getElementById('no-results');
    const groups          = document.querySelectorAll('.akun-group');

    let timeout = null;

    function applyFilter() {
        const kata      = inputCari.value.trim().toLowerCase();
        const katFilter = selectKategori.value.toLowerCase();
        let totalVisible = 0;

        groups.forEach(function (group) {
            const groupKat  = (group.dataset.kategori || '').toLowerCase();
            const katMatch  = !katFilter || groupKat === katFilter;
            const rows      = group.querySelectorAll('.akun-row');
            let groupVisible = 0;

            rows.forEach(function (row) {
                const kode = row.dataset.kode || '';
                const nama = row.dataset.nama || '';
                const match = katMatch && (!kata || kode.includes(kata) || nama.includes(kata));
                row.classList.toggle('hidden', !match);
                if (match) groupVisible++;
            });

            group.classList.toggle('hidden', groupVisible === 0);
            totalVisible += groupVisible;
        });

        noResults.style.display = totalVisible === 0 ? 'block' : 'none';
    }

    inputCari.addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(applyFilter, 120); // debounce ringan
    });

    selectKategori.addEventListener('change', applyFilter);
})();
</script>

<?php render_footer(); ?>