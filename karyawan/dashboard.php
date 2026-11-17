<?php
session_start();

// Cek proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'karyawan') {
    header("location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - Pengajuan Cuti</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- CSS Lokal -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body style="background-color: #f8fafc;">

    <!-- Navbar Topbar -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #1e3a8a !important;">
        <div class="container py-1">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="#">
                <i class="bi bi-calendar-range-fill me-2 fs-4"></i>
                <div>
                    <div>Dashboard Cuti Karyawan</div>
                    <div class="small fw-normal text-light text-sm-custom">
                        NIK: <?php echo $_SESSION['nik']; ?> — <?php echo $_SESSION['nama']; ?>
                    </div>
                </div>
            </a>
            <a href="../logout.php" class="btn btn-outline-light btn-sm ms-auto">
                <i class="bi bi-arrow-right-square me-1"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Container Utama -->
    <div class="container mt-4 mb-4 px-3 px-md-4">

        <!-- 1. Statistik Kuota Cuti -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-blue h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Kuota Tahunan</small>
                        <span class="fs-3 fw-bold text-primary">12</span>
                        <small class="text-muted d-block text-xs">hari / tahun</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-green h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Sisa Jatah</small>
                        <span class="fs-3 fw-bold text-success">12</span>
                        <small class="text-muted d-block text-xs">hari tersisa</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-red h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Terpakai</small>
                        <span class="fs-3 fw-bold text-danger">0</span>
                        <small class="text-muted d-block text-xs">hari disetujui</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-yellow h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Pending</small>
                        <span class="fs-3 fw-bold text-warning">0</span>
                        <small class="text-muted d-block text-xs">menunggu</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Formulir Pengajuan Cuti -->
        <div class="card shadow-sm stat-card form-card mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">
                    <span class="badge bg-warning text-dark me-2">Form</span> Pengajuan Cuti
                </h5>

                <form action="../proses/proses_cuti.php" method="POST">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jenis Cuti</label>
                            <select class="form-select" name="jenis_cuti" required>
                                <option value="" selected disabled>Pilih Jenis Cuti</option>
                                <option value="Cuti Tahunan">Cuti Tahunan</option>
                                <option value="Cuti Sakit">Cuti Sakit</option>
                                <option value="Cuti Darurat">Cuti Darurat</option>
                            <option value="Cuti Lainnya">Cuti Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Sisa Jatah Cuti</label>
                            <input type="text" class="form-control bg-light" value="12 Hari" readonly>
                            <small class="text-muted text-xs"></small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="tgl_mulai" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" class="form-control" name="tgl_selesai" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Alasan Pengajuan</label>
                        <textarea class="form-control" name="alasan" rows="3" placeholder="Tuliskan alasan dengan lengkap dan jujur!" required></textarea>
                    </div>

                    <div class="text-end">
                        <button type="reset" class="btn btn-custom-reset px-4 me-2 fw-semibold text-14">Reset</button>
                        <button type="submit" class="btn btn-custom-submit px-4 py-2 fw-semibold text-14">
                            <i class="bi bi-plus-lg me-1"></i>Ajukan Cuti</button>
                    </div>

                </form>
            </div>
        </div>

        <!-- 3. Tabel riwayat pengauan cuti -->
        <div class="card shadow-sm border rounded-4">
            <div class="card-body p-4">

                <!-- Header Tabel -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark">Riwayat Pengajuan</h5>
                    <div class="d-flex gap-2">
                        <!-- Tombol Filter -->
                        <button class="btn btn-filter-active btn-sm rounded-pill px-3 fw-semibold text-14">Semua
                            <span class="badge rounded-circle ms-1">0</span>
                        </button>

                        <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14">Pending
                            <span class="badge rounded-circle ms-1">0</span>
                        </button>

                        <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14">Disetujui
                            <span class="badge rounded-circle ms-1">0</span>
                        </button>

                        <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14">Ditolak
                            <span class="badge rounded-circle ms-1">0</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- Akhir Container Utama -->

    <!-- Bootstrap JS -->
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>

</body>

</html>