<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hrd') {
    header("location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik        = trim($_POST['nik']);
    $nama       = trim($_POST['nama']);
    $password   = trim($_POST['password']);
    $role       = $_POST['role'];
    $kuota_cuti = (int) $_POST['kuota_cuti'];

    $cek = $koneksi->prepare("SELECT id FROM users WHERE nik = ?");
    $cek->bind_param("s", $nik);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal: NIK sudah terdaftar di sistem!'];
        header("location: ../admin/dashboard.php");
        exit;
    }

    $stmt = $koneksi->prepare("INSERT INTO users (nik, nama, password, role, kuota_cuti) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $nik, $nama, $password, $role, $kuota_cuti);

    if ($stmt->execute()) {
        $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Akun pengguna baru berhasil ditambahkan.'];
    } else {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Terjadi kesalahan saat menyimpan akun.'];
    }

    header("location: ../admin/dashboard.php");
    exit;
}