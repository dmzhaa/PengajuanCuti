<?php
session_start();
require_once '../koneksi.php';

// gor karyawan sek olh mlebu
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

// gor iso diakses lewat submit form (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kembali('danger', 'Permintaan tidak valid.');
}

// input form
$user_id     = (int) $_SESSION['id'];
$jenis_cuti  = trim($_POST['jenis_cuti'] ?? '');
$tgl_mulai   = $_POST['tgl_mulai'] ?? '';
$tgl_selesai = $_POST['tgl_selesai'] ?? '';
$alasan      = trim($_POST['alasan'] ?? '');

// nek kosong ra ngisi opo2
if ($jenis_cuti === '' || $tgl_mulai === '' || $tgl_selesai === '' || $alasan === '') {
    kembali('warning', 'Semua field wajib diisi.');
}

// tanggal karo ngitung durasi dino
$start = new DateTime($tgl_mulai);
$end   = new DateTime($tgl_selesai);

if ($end <= $start) {
    kembali('warning', 'Tanggal selesai harus setelah tanggal mulai.');
}

$jumlah_hari = $start->diff($end)->days;

// ngecek sisa kuota tahunan (khusus cuti tahunan, termasuk karo seng ish pending ben ora spam)
if ($jenis_cuti === 'Cuti Tahunan') {
    define('KUOTA_TAHUNAN', 12);
    $tahun = $start->format('Y');

    $stmtCek = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah_hari), 0) AS total_hari 
                                         FROM pengajuan_cuti 
                                         WHERE user_id = ? 
                                           AND jenis_cuti = 'Cuti Tahunan'
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
}

// handle upload lampiran (wajib nek Cuti loro, opsional nggo liyane sih)
$nama_lampiran = null;
$ada_file      = isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] !== UPLOAD_ERR_NO_FILE;

if ($jenis_cuti === 'Cuti Sakit' && !$ada_file) {
    kembali('warning', 'Cuti Sakit wajib melampirkan surat dokter atau bukti pendukung.');
}

if ($ada_file) {
    if ($_FILES['lampiran']['error'] !== UPLOAD_ERR_OK) {
        kembali('danger', 'Terjadi kesalahan saat mengunggah file lampiran.');
    }

    $ekstensi_boleh = ['jpg', 'jpeg', 'png', 'pdf'];
    $nama_asli      = $_FILES['lampiran']['name'];
    $ukuran_file    = $_FILES['lampiran']['size'];
    $tmp_file       = $_FILES['lampiran']['tmp_name'];
    $ext            = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    if (!in_array($ext, $ekstensi_boleh)) {
        kembali('warning', 'Format file harus JPG, JPEG, PNG, atau PDF.');
    }

    if ($ukuran_file > 2 * 1024 * 1024) {
        kembali('warning', 'Ukuran file maksimal 2MB.');
    }

    $nama_lampiran = time() . '_' . uniqid() . '.' . $ext;
    $tujuan_upload = '../uploads/' . $nama_lampiran;

    if (!move_uploaded_file($tmp_file, $tujuan_upload)) {
        kembali('danger', 'Gagal menyimpan file lampiran ke server.');
    }
}

// nyimpen pengajuan ke database
$stmt = mysqli_prepare($koneksi, "INSERT INTO pengajuan_cuti (user_id, jenis_cuti, tanggal_mulai, tanggal_selesai, jumlah_hari, alasan, lampiran) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "isssiss", $user_id, $jenis_cuti, $tgl_mulai, $tgl_selesai, $jumlah_hari, $alasan, $nama_lampiran);

if (mysqli_stmt_execute($stmt)) {
    kembali('success', "Pengajuan cuti ($jumlah_hari hari) berhasil dikirim dan menunggu persetujuan HRD.");
}

// hapus file nek query gagal ben ora nyampah nang folder uploads
if ($nama_lampiran !== null && file_exists('../uploads/' . $nama_lampiran)) {
    unlink('../uploads/' . $nama_lampiran);
}

kembali('danger', 'Gagal menyimpan pengajuan cuti.');