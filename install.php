<?php

require_once __DIR__ . '/fungsi.php';

function proses_instalasi_database()
{
    global $koneksi;

    $sqlFile = __DIR__ . '/database.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception('Berkas database.sql tidak ditemukan.');
    }

    $sql = file_get_contents($sqlFile);

    if (!$koneksi->multi_query($sql)) {
        throw new Exception($koneksi->error);
    }

    do {
        $hasil = $koneksi->store_result();
        if ($hasil instanceof mysqli_result) {
            $hasil->free();
        }
    } while ($koneksi->more_results() && $koneksi->next_result());

    if ($koneksi->errno) {
        throw new Exception($koneksi->error);
    }

    /*
    if (function_exists('terapkan_template_akun_rumah_sakit')) {
        terapkan_template_akun_rumah_sakit(true);
    }
    */
}

if (PHP_SAPI === 'cli') {
    try {
        proses_instalasi_database();
        echo "Database berhasil dipasang." . PHP_EOL;
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, "Instalasi gagal: " . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['aksi']) && $_GET['aksi'] === 'pasang')) {
    try {
        proses_instalasi_database();
        atur_flash('success', 'Database berhasil dipasang. Silakan mulai menggunakan aplikasi.');
        header('Location: index.php');
        exit;
    } catch (Throwable $exception) {
        atur_flash('error', 'Instalasi gagal: ' . $exception->getMessage());
        header('Location: install.php');
        exit;
    }
}

render_header('Instalasi Database');
?>
<section class="panel install-panel">
    <h3>Persiapan Database</h3>
    <p>Aplikasi mendeteksi bahwa tabel belum tersedia. Tekan tombol di bawah untuk membuat seluruh struktur database dan data awal.</p>
    <form method="post">
        <button type="submit" class="button primary">Pasang Database Sekarang</button>
    </form>
</section>
<?php
render_footer();