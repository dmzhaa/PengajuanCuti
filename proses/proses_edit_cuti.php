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

// jipuk data lawas sek nggo ngecek lampiran & status pending
$q_lama = mysqli_prepare($koneksi, "SELECT lampiran FROM pengajuan_cuti WHERE id = ? AND user_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($q_lama, "ii", $id, $user_id);
mysqli_stmt_execute($q_lama);
$data_lama = mysqli_fetch_assoc(mysqli_stmt_get_result($q_lama));

if (!$data_lama) {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Pengajuan tidak ditemukan atau sudah diproses HRD.'];
    header("location: ../karyawan/dashboard.php");
    exit;
}

// cek sisa kuota (khusus cuti tahunan, kecuali pengajuan seng lagi diedit)
if ($jenis_cuti === 'Cuti Tahunan') {
    $tahun   = $mulai->format('Y');
    $q_kuota = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah_hari), 0) AS total FROM pengajuan_cuti WHERE user_id = ? AND id != ? AND jenis_cuti = 'Cuti Tahunan' AND status IN ('approved', 'pending') AND YEAR(tanggal_mulai) = ?");
    mysqli_stmt_bind_param($q_kuota, "iis", $user_id, $id, $tahun);
    mysqli_stmt_execute($q_kuota);
    $data_kuota = mysqli_fetch_assoc(mysqli_stmt_get_result($q_kuota));

    $sisa = 12 - (int)$data_kuota['total'];
    if ($jumlah_hari > $sisa) {
        $_SESSION['flash'] = ['tipe' => 'warning', 'pesan' => "Sisa kuota cuti tahun $tahun tinggal $sisa hari."];
        header("location: ../karyawan/dashboard.php");
        exit;
    }
}

$lampiran_lama = $data_lama['lampiran'];
$lampiran_baru = $lampiran_lama;
$ada_file      = isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] !== UPLOAD_ERR_NO_FILE;

// nek user upload file pengganti
if ($ada_file) {
    if ($_FILES['lampiran']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal mengunggah file lampiran.'];
        header("location: ../karyawan/dashboard.php");
        exit;
    }

    $boleh  = ['jpg', 'jpeg', 'png', 'pdf'];
    $nama   = $_FILES['lampiran']['name'];
    $ukuran = $_FILES['lampiran']['size'];
    $tmp    = $_FILES['lampiran']['tmp_name'];
    $ext    = strtolower(pathinfo($nama, PATHINFO_EXTENSION));

    if (!in_array($ext, $boleh)) {
        $_SESSION['flash'] = ['tipe' => 'warning', 'pesan' => 'Format file harus JPG, JPEG, PNG, atau PDF.'];
        header("location: ../karyawan/dashboard.php");
        exit;
    }

    if ($ukuran > 2 * 1024 * 1024) {
        $_SESSION['flash'] = ['tipe' => 'warning', 'pesan' => 'Ukuran file maksimal 2MB.'];
        header("location: ../karyawan/dashboard.php");
        exit;
    }

    $nama_file = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($tmp, '../uploads/' . $nama_file)) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal menyimpan file ke folder uploads.'];
        header("location: ../karyawan/dashboard.php");
        exit;
    }

    $lampiran_baru = $nama_file;
}

// nek diubah dadi cuti sakit tapi blas rung ono lampiran
if ($jenis_cuti === 'Cuti Sakit' && empty($lampiran_baru)) {
    $_SESSION['flash'] = ['tipe' => 'warning', 'pesan' => 'Cuti Sakit wajib melampirkan surat dokter atau bukti pendukung.'];
    header("location: ../karyawan/dashboard.php");
    exit;
}

// update gor nek gone user iki karo status ish pending
$stmt = mysqli_prepare($koneksi, "UPDATE pengajuan_cuti SET jenis_cuti = ?, tanggal_mulai = ?, tanggal_selesai = ?, jumlah_hari = ?, alasan = ?, lampiran = ? WHERE id = ? AND user_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, "sssissii", $jenis_cuti, $tgl_mulai, $tgl_selesai, $jumlah_hari, $alasan, $lampiran_baru, $id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    // nek ganti file anyar, hapus file lawase ben ora nyampah
    if ($ada_file && !empty($lampiran_lama) && file_exists('../uploads/' . $lampiran_lama)) {
        unlink('../uploads/' . $lampiran_lama);
    }
    $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => 'Pengajuan cuti berhasil diperbarui.'];
} else {
    if ($ada_file && !empty($lampiran_baru) && file_exists('../uploads/' . $lampiran_baru)) {
        unlink('../uploads/' . $lampiran_baru);
    }
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal mengubah pengajuan cuti.'];
}

header("location: ../karyawan/dashboard.php");
exit;
?>