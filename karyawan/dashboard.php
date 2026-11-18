<?php
session_start();
require_once '../koneksi.php';

// Cek proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'karyawan') {
    header("location: ../index.php");
    exit;
}

define('KUOTA_TAHUNAN', 12);
$user_id = $_SESSION['id'];

$stmt = mysqli_prepare($koneksi, "SELECT * FROM pengajuan_cuti WHERE user_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$riwayat = [];
$terpakai = 0;
$pending_hari = 0;
$stat = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

while ($row = mysqli_fetch_assoc($result)) {
    $riwayat[] = $row;
    $stat['total']++;

    if (isset($stat[$row['status']])) {
        $stat[$row['status']]++;
    }

    if (date('Y', strtotime($row['tanggal_mulai'])) == date('Y')) {
        if ($row['status'] === 'approved') {
            $terpakai += (int)$row['jumlah_hari'];
        } elseif ($row['status'] === 'pending') {
            $pending_hari += (int)$row['jumlah_hari'];
        }
    }
}

$sisa_jatah = max(0, KUOTA_TAHUNAN - $terpakai);
function tgl($d) { return date('d/m/Y', strtotime($d)); }
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

    <div class="container mt-4 mb-4 px-3 px-md-4">

        <!-- Notifikasi Feedback dari proses_cuti.php -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash']['tipe']; ?> alert-dismissible fade show small py-2" role="alert">
                <?php echo htmlspecialchars($_SESSION['flash']['pesan']); ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- 1. Statistik Kuota Cuti -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-blue h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Kuota Tahunan</small>
                        <span class="fs-3 fw-bold text-primary"><?php echo KUOTA_TAHUNAN; ?></span>
                        <small class="text-muted d-block text-xs">hari / tahun</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-green h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Sisa Jatah</small>
                        <span class="fs-3 fw-bold text-success"><?php echo $sisa_jatah; ?></span>
                        <small class="text-muted d-block text-xs">hari tersisa</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-red h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Terpakai</small>
                        <span class="fs-3 fw-bold text-danger"><?php echo $terpakai; ?></span>
                        <small class="text-muted d-block text-xs">hari disetujui</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-yellow h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Pending</small>
                        <span class="fs-3 fw-bold text-warning"><?php echo $pending_hari; ?></span>
                        <small class="text-muted d-block text-xs">hari menunggu</small>
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
                            <input type="text" class="form-control bg-light" value="<?php echo $sisa_jatah; ?> Hari" readonly>
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
        <div class="card shadow-sm stat-card mb-4">
            <div class="card-body p-4">

                <!-- Header Tabel -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0 text-dark">Riwayat Pengajuan</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <!-- Tombol Filter -->
                        <button class="btn btn-filter-active btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="semua">Semua
                            <span class="badge rounded-circle ms-1"><?php echo $stat['total']; ?></span>
                        </button>

                        <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="pending">Pending
                            <span class="badge rounded-circle ms-1"><?php echo $stat['pending']; ?></span>
                        </button>

                        <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="approved">Disetujui
                            <span class="badge rounded-circle ms-1"><?php echo $stat['approved']; ?></span>
                        </button>

                        <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="rejected">Ditolak
                            <span class="badge rounded-circle ms-1"><?php echo $stat['rejected']; ?></span>
                        </button>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0 text-14">
                        <thead class="bg-table-header">
                            <tr class="text-muted text-xs text-uppercase">
                                <th class="ps-3">No</th>
                                <th>Jenis Cuti</th>
                                <th>Tanggal</th>
                                <th>Durasi</th>
                                <th>Alasan</th>
                                <th>Catatan HRD</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-body">
                            <?php if (count($riwayat) == 0): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat pengajuan cuti.</td></tr>
                            <?php endif; ?>

                            <?php foreach ($riwayat as $i => $r):
                                $badgeMap = [
                                    'pending'  => ['bg-soft-yellow', 'Pending'],
                                    'approved' => ['bg-soft-green', 'Disetujui'],
                                    'rejected' => ['bg-soft-red', 'Ditolak']
                                ];
                                $badge = $badgeMap[$r['status']] ?? ['bg-secondary', $r['status']];
                            ?>
                                <tr data-status="<?php echo $r['status']; ?>">
                                    <td class="ps-3 text-muted"><?php echo $i + 1; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($r['jenis_cuti']); ?></td>
                                    <td>
                                        <div><?php echo tgl($r['tanggal_mulai']); ?></div>
                                        <div class="text-muted text-xs"><?php echo tgl($r['tanggal_selesai']); ?></div>
                                    </td>
                                    <td><span class="badge bg-soft-blue text-primary fw-semibold"><?php echo $r['jumlah_hari']; ?> hr</span></td>
                                    <td><?php echo htmlspecialchars($r['alasan']); ?></td>
                                    <td class="text-muted small"><?php echo !empty($r['catatan_hrd']) ? htmlspecialchars($r['catatan_hrd']) : '-'; ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $badge[0]; ?> fw-semibold">
                                            <i class="bi bi-circle-fill icon-dot me-1"></i><?php echo $badge[1]; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div> <!-- Akhir Container Utama -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
        const rows = document.querySelectorAll('#tabel-body tr[data-status]');
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;
                rows.forEach(tr => {
                    tr.style.display = (filter === 'semua' || tr.dataset.status === filter) ? '' : 'none';
                });
            });
        });
    </script>

</body>

</html>