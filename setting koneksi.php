<?php
$host = 'alamatsql';
$user = 'username';
$pass = 'password';
$db   = 'namadatabase';

$koneksi = new mysqli($host, $user, $pass);
if ($koneksi->connect_errno) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$koneksi->set_charset('utf8mb4');
$koneksi->query("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if (!$koneksi->select_db($db)) {
    die("Database gagal dipilih: " . $koneksi->error);
}
