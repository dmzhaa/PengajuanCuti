<?php
session_start();
require_once '../koneksi.php';

// 1. hanya karyawan yang boleh masuk
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'karyawan') {
    header("location: ../index.php");
    exit;
}

function kembali($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
    header("location: ../karyawan/dashboard.php");
    exit;
}

// 2. cuma bsa diakses lewat submit form (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kembali('danger', 'Permintaan tidak valid.');
}

// 3. input form
$user_id     = (int) $_SESSION['id'];
$jenis_cuti  = trim($_POST['jenis_cuti'] ?? '');
$tgl_mulai   = $_POST['tgl_mulai'] ?? '';
$tgl_selesai = $_POST['tgl_selesai'] ?? '';
$alasan      = trim($_POST['alasan'] ?? '');

// 4. klo kosong ga ngisi apa2
if ($jenis_cuti === '' || $tgl_mulai === '' || $tgl_selesai === '' || $alasan === '') {
    kembali('warning', 'Semua field wajib diisi.');
}

// 5. tanggal & hitung durasi hari
$start = new DateTime($tgl_mulai);
$end   = new DateTime($tgl_selesai);

if ($end <= $start) {
    kembali('warning', 'Tanggal selesai harus setelah tanggal mulai.');
}

$jumlah_hari = $start->diff($end)->days;

// 6. cek sisa kuota tahunan (termasuk sama yang masih pending biar ga spam)
define('KUOTA_TAHUNAN', 12);
$tahun = $start->format('Y');

$stmtCek = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah_hari), 0) AS total_hari 
                                     FROM pengajuan_cuti 
                                     WHERE user_id = ? 
                                       AND status IN ('approved', 'pending') 
                                       AND YEAR(tanggal_mulai) = ?");
mysqli_stmt_bind_param($stmtCek, "is", $user_id, $tahun);
mysqli_stmt_execute($stmtCek);
$resCek = mysqli_stmt_get_result($stmtCek);
$rowCek = mysqli_fetch_assoc($resCek);

$terpakai = (int) $rowCek['total_hari'];
$sisa     = max(0, KUOTA_TAHUNAN - $terpakai);

if ($jumlah_hari > $sisa) {
    kembali('warning', "Pengajuan ditolak. Durasi ($jumlah_hari hari) melebihi sisa kuota tahun $tahun ($sisa hari).");
}

// 7. nyimpan pengajuan ke database
$stmt = mysqli_prepare($koneksi, "INSERT INTO pengajuan_cuti (user_id, jenis_cuti, tanggal_mulai, tanggal_selesai, jumlah_hari, alasan) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "isssis", $user_id, $jenis_cuti, $tgl_mulai, $tgl_selesai, $jumlah_hari, $alasan);

if (mysqli_stmt_execute($stmt)) {
    kembali('success', "Pengajuan cuti ($jumlah_hari hari) berhasil dikirim dan menunggu persetujuan HRD.");
}

kembali('danger', 'Gagal menyimpan pengajuan cuti.');