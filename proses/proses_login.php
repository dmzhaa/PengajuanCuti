<?php
session_start();
require_once '../koneksi.php';

// ambil data dari form login
$nik = mysqli_real_escape_string($koneksi, $_POST['nik']);
$password = mysqli_real_escape_string($koneksi, $_POST['password']);

// cari user berdasarkan nik
$query = "SELECT * FROM users WHERE nik = '$nik'";
$result = mysqli_query($koneksi, $query);

// cek apakah nik ditemukan
if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    if ($password === $user['password']) {
        $_SESSION['id'] = $user['id'];
        $_SESSION['nik'] = $user['nik'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];

        if($user['role'] == 'hrd') {
            header("location: ../admin/dashboard.php");
            exit;
        } else {
            header("location: ../karyawan/dashboard.php");
            exit;
        }
    } else {
        header("location: ../index.php?=pesan=gagal");
        exit;
    }
} else {
    header("location: ../index.php?=pesan=gagal");
    exit;
}
?>
