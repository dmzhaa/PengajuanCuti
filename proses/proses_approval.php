<?php
session_start();
require_once '../koneksi.php';

// Hanya HRD yang boleh memproses
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hrd') {
    header("location: ../index.php");
    exit;
}

function kembali($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
    header("location: ../admin/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kembali('danger', 'Permintaan tidak valid.');
}

$id      = (int) ($_POST['id'] ?? 0);
$aksi    = $_POST['aksi'] ?? '';
$catatan = trim($_POST['catatan'] ?? '');

$peta = ['approved' => 'approved', 'rejected' => 'rejected'];

if ($id <= 0 || !isset($peta[$aksi])) {
    kembali('danger', 'Data pengajuan atau keputusan tidak valid.');
}
if ($aksi === 'rejected' && $catatan === '') {
    kembali('warning', 'Catatan wajib diisi jika pengajuan ditolak.');
}

$status = $peta[$aksi];

$stmt = mysqli_prepare($koneksi, "UPDATE pengajuan_cuti SET status = ?, catatan_hrd = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "ssi", $status, $catatan, $id);

if (mysqli_stmt_execute($stmt)) {
    $label = ($status === 'approved') ? 'Disetujui' : 'Ditolak';
    kembali('success', "Pengajuan berhasil diubah menjadi $label.");
}
kembali('danger', 'Gagal menyimpan keputusan.');