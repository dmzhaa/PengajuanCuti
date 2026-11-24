<?php
session_start();
require_once '../koneksi.php';

// cek login
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'karyawan') {
    header("location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION['id'];
$id      = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'ID pengajuan tidak valid.'];
    header("location: ../karyawan/dashboard.php");
    exit;
}

// jipuk jeneng file lampiran sek sakdurunge dihapus
$q_file = mysqli_prepare($koneksi, "SELECT lampiran FROM pengajuan_cuti WHERE id = ? AND user_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($q_file, "ii", $id, $user_id);
mysqli_stmt_execute($q_file);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($q_file));

// ngehapus gor nek gone user iki, karo status ish pending
$stmt = mysqli_prepare($koneksi, "DELETE FROM pengajuan_cuti WHERE id = ? AND user_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    if (!empty($data['lampiran']) && file_exists('../uploads/' . $data['lampiran'])) {
        unlink('../uploads/' . $data['lampiran']);
    }
    $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Pengajuan cuti berhasil dihapus.'];
} else {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal menghapus pengajuan atau status sudah diproses HRD.'];
}

header("location: ../karyawan/dashboard.php");
exit;