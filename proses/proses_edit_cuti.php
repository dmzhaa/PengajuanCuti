<?php
session_start();
require_once '../koneksi.php';

// cek proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'karyawan') {
    header("location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: ../karyawan/dashboard.php");
    exit;
}

$user_id     = $_SESSION['id'];
$id          = (int) ($_POST['id'] ?? 0);
$jenis_cuti  = trim($_POST['jenis_cuti'] ?? '');
$tgl_mulai   = $_POST['tgl_mulai'] ?? '';
$tgl_selesai = $_POST['tgl_selesai'] ?? '';
$alasan      = trim($_POST['alasan'] ?? '');

if ($id <= 0 || $jenis_cuti == '' || $tgl_mulai == '' || $tgl_selesai == '' || $alasan == '') {
    $_SESSION['flash'] = ['tipe' => 'warning', 'pesan' => 'Semua field wajib diisi.'];
    header("location: ../karyawan/dashboard.php");
    exit;
}

$mulai   = new DateTime($tgl_mulai);
$selesai = new DateTime($tgl_selesai);

if ($selesai <= $mulai) {
    $_SESSION['flash'] = ['tipe' => 'warning', 'pesan' => 'Tanggal selesai harus setelah tanggal mulai.'];
    header("location: ../karyawan/dashboard.php");
    exit;
}

$jumlah_hari = $mulai->diff($selesai)->days;

// cek sisa kuota (kecuali pengajuan yang sedang diedit ini)
$tahun   = $mulai->format('Y');
$q_kuota = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah_hari), 0) AS total FROM pengajuan_cuti WHERE user_id = ? AND id != ? AND status IN ('approved', 'pending') AND YEAR(tanggal_mulai) = ?");
mysqli_stmt_bind_param($q_kuota, "iis", $user_id, $id, $tahun);
mysqli_stmt_execute($q_kuota);
$data_kuota = mysqli_fetch_assoc(mysqli_stmt_get_result($q_kuota));

$sisa = 12 - (int)$data_kuota['total'];
if ($jumlah_hari > $sisa) {
    $_SESSION['flash'] = ['tipe' => 'warning', 'pesan' => "Sisa kuota cuti tahun $tahun tinggal $sisa hari."];
    header("location: ../karyawan/dashboard.php");
    exit;
}

// update hanya jika milik user ini dan status masih pending
$stmt = mysqli_prepare($koneksi, "UPDATE pengajuan_cuti SET jenis_cuti = ?, tanggal_mulai = ?, tanggal_selesai = ?, jumlah_hari = ?, alasan = ? WHERE id = ? AND user_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, "sssisii", $jenis_cuti, $tgl_mulai, $tgl_selesai, $jumlah_hari, $alasan, $id, $user_id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Pengajuan cuti berhasil diperbarui.'];
} else {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal mengubah pengajuan atau status sudah diproses HRD.'];
}

header("location: ../karyawan/dashboard.php");
exit;
?>