<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hrd') {
    header("location: ../index.php");
    exit;
}

define('KUOTA_TAHUNAN', 12);

$sql = "SELECT pengajuan_cuti.*, users.nik, users.nama,
               pengajuan_cuti.jumlah_hari AS durasi,
               (SELECT COALESCE(SUM(riwayat_cuti.jumlah_hari), 0)
                  FROM pengajuan_cuti AS riwayat_cuti
                 WHERE riwayat_cuti.user_id = pengajuan_cuti.user_id
                   AND riwayat_cuti.jenis_cuti = 'Cuti Tahunan'
                   AND riwayat_cuti.status = 'approved'
                   AND YEAR(riwayat_cuti.tanggal_mulai) = YEAR(pengajuan_cuti.tanggal_mulai)) AS terpakai
        FROM pengajuan_cuti
        JOIN users ON users.id = pengajuan_cuti.user_id
        ORDER BY pengajuan_cuti.id DESC";
$result = mysqli_query($koneksi, $sql);

$data = [];
$stat = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
while ($r = mysqli_fetch_assoc($result)) {
    $r['sisa'] = max(0, KUOTA_TAHUNAN - (int)$r['terpakai']);
    $data[] = $r;
    $stat['total']++;
    if (isset($stat[$r['status']])) {
        $stat[$r['status']]++;
    }
}

function tgl($d) { return date('d/m/Y', strtotime($d)); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin HRD - Pengajuan Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body style="background-color: #f8fafc;">

    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #1e3a8a !important;">
        <div class="container py-1">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="#">
                <span class="brand-icon-admin me-2"><i class="bi bi-shield-check"></i></span>
                <div>
                    <div>Panel Admin / HRD</div>
                    <div class="small fw-normal text-light text-sm-custom">
                        NIK: <?php echo htmlspecialchars($_SESSION['nik']); ?> — <?php echo htmlspecialchars($_SESSION['nama']); ?>
                    </div>
                </div>
            </a>
            <a href="../logout.php" class="btn btn-outline-light btn-sm ms-auto rounded-pill px-3">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container mt-4 mb-4 px-3 px-md-4">

        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash']['tipe']; ?> alert-dismissible fade show small py-2" role="alert">
                <?php echo htmlspecialchars($_SESSION['flash']['pesan']); ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-blue h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Total Pengajuan</small>
                        <span class="fs-3 fw-bold text-primary"><?php echo $stat['total']; ?></span>
                        <small class="text-muted d-block text-xs">semua karyawan</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-yellow h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Pending</small>
                        <span class="fs-3 fw-bold text-warning"><?php echo $stat['pending']; ?></span>
                        <small class="text-muted d-block text-xs">menunggu keputusan</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-green h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Disetujui</small>
                        <span class="fs-3 fw-bold text-success"><?php echo $stat['approved']; ?></span>
                        <small class="text-muted d-block text-xs">pengajuan disetujui</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm stat-card stat-card-red h-100">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-sm-custom">Ditolak</small>
                        <span class="fs-3 fw-bold text-danger"><?php echo $stat['rejected']; ?></span>
                        <small class="text-muted d-block text-xs">pengajuan ditolak</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- tabel seluruh pengajuan -->
        <div class="card shadow-sm stat-card mb-4">
            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0 text-dark">Seluruh Pengajuan Cuti Karyawan</h5>
                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 10px 0 0 10px;">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="cari" class="form-control border-start-0" style="border-radius: 0 10px 10px 0;" placeholder="Cari nama / NIK...">
                    </div>
                </div>

                <div class="d-flex gap-2 mb-4 flex-wrap">
                    <button class="btn btn-filter-active btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="semua">Semua
                        <span class="badge rounded-circle ms-1"><?php echo $stat['total']; ?></span></button>
                    <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="pending">Pending
                        <span class="badge rounded-circle ms-1"><?php echo $stat['pending']; ?></span></button>
                    <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="approved">Disetujui
                        <span class="badge rounded-circle ms-1"><?php echo $stat['approved']; ?></span></button>
                    <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="rejected">Ditolak
                        <span class="badge rounded-circle ms-1"><?php echo $stat['rejected']; ?></span></button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-14">
                        <thead class="bg-table-header">
                            <tr class="text-muted text-xs text-uppercase">
                                <th class="ps-3">No</th>
                                <th>Karyawan</th>
                                <th>Jenis Cuti</th>
                                <th>Tanggal</th>
                                <th>Durasi</th>
                                <th>Sisa Jatah</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-body">
                            <?php if (count($data) == 0): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">Belum ada pengajuan cuti.</td></tr>
                            <?php endif; ?>

                            <?php foreach ($data as $i => $r):
                                $badgeMap = [
                                    'pending'  => ['badge-soft-pending', 'Pending'],
                                    'approved' => ['badge-soft-approved', 'Disetujui'],
                                    'rejected' => ['badge-soft-rejected', 'Ditolak']
                                ];
                                $badge = $badgeMap[$r['status']] ?? ['bg-secondary text-white', $r['status']];
                            ?>
                                <tr data-status="<?php echo $r['status']; ?>"
                                    data-cari="<?php echo htmlspecialchars(strtolower($r['nama'] . ' ' . $r['nik'])); ?>">
                                    <td class="ps-3 text-muted"><?php echo $i + 1; ?></td>
                                    
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($r['nama']); ?></div>
                                        <div class="text-muted text-xs">NIK: <?php echo htmlspecialchars($r['nik']); ?></div>
                                    </td>
                                    
                                    <td>
                                        <div><?php echo htmlspecialchars($r['jenis_cuti']); ?></div>
                                        <?php if (!empty($r['lampiran'])): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($r['lampiran']); ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none mt-1">
                                                <i class="bi bi-paperclip"></i> Lihat Bukti
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div><?php echo tgl($r['tanggal_mulai']); ?></div>
                                        <div class="text-muted text-xs"><?php echo tgl($r['tanggal_selesai']); ?></div>
                                    </td>
                                    
                                    <td>
                                        <span class="badge bg-soft-blue text-primary fw-semibold px-2 py-1"><?php echo $r['durasi']; ?> hr</span>
                                    </td>
                                    
                                    <td>
                                        <?php if ($r['jenis_cuti'] === 'Cuti Tahunan'): ?>
                                            <span class="fw-semibold <?php echo $r['sisa'] > 0 ? 'text-success' : 'text-primary'; ?>"><?php echo $r['sisa']; ?></span>
                                            <span class="text-muted">/ <?php echo KUOTA_TAHUNAN; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <span class="badge rounded-pill <?php echo $badge[0]; ?> fw-semibold px-3 py-1">
                                            &bull; <?php echo $badge[1]; ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if ($r['status'] === 'pending'): ?>

                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn-icon-aksi"
                                                    style="background-color: #dcfce7; color: #16a34a;"
                                                    title="Setujui"
                                                    data-bs-toggle="modal" data-bs-target="#modalStatus"
                                                    data-id="<?php echo $r['id']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($r['nama']); ?>"
                                                    data-jenis="<?php echo htmlspecialchars($r['jenis_cuti']); ?>"
                                                    data-alasan="<?php echo htmlspecialchars($r['alasan']); ?>"
                                                    data-lampiran="<?php echo htmlspecialchars($r['lampiran'] ?? ''); ?>"
                                                    data-aksi="approved"
                                                    data-catatan="<?php echo htmlspecialchars($r['catatan_hrd'] ?? ''); ?>">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>

                                                <button type="button" class="btn-icon-aksi"
                                                    style="background-color: #fee2e2; color: #dc2626;"
                                                    title="Tolak"
                                                    data-bs-toggle="modal" data-bs-target="#modalStatus"
                                                    data-id="<?php echo $r['id']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($r['nama']); ?>"
                                                    data-jenis="<?php echo htmlspecialchars($r['jenis_cuti']); ?>"
                                                    data-alasan="<?php echo htmlspecialchars($r['alasan']); ?>"
                                                    data-lampiran="<?php echo htmlspecialchars($r['lampiran'] ?? ''); ?>"
                                                    data-aksi="rejected"
                                                    data-catatan="<?php echo htmlspecialchars($r['catatan_hrd'] ?? ''); ?>">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>

                                        <?php else: ?>

                                            <button type="button" class="btn-icon-aksi"
                                                style="background-color: #fef3c7; color: #d97706;"
                                                title="Ubah Keputusan"
                                                data-bs-toggle="modal" data-bs-target="#modalStatus"
                                                data-id="<?php echo $r['id']; ?>"
                                                data-nama="<?php echo htmlspecialchars($r['nama']); ?>"
                                                data-jenis="<?php echo htmlspecialchars($r['jenis_cuti']); ?>"
                                                data-alasan="<?php echo htmlspecialchars($r['alasan']); ?>"
                                                data-lampiran="<?php echo htmlspecialchars($r['lampiran'] ?? ''); ?>"
                                                data-aksi="<?php echo $r['status']; ?>"
                                                data-catatan="<?php echo htmlspecialchars($r['catatan_hrd'] ?? ''); ?>">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>

                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- proses status -->
    <div class="modal fade" id="modalStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow" style="border-radius: 16px;" action="../proses/proses_approval.php" method="POST">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Proses Pengajuan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" id="st-id">

                    <div class="mb-2">
                        <small class="text-muted">Karyawan</small>
                        <div class="fw-semibold" id="st-nama"></div>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Jenis Cuti</small>
                        <div id="st-jenis"></div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Alasan</small>
                        <div id="st-alasan" class="bg-light rounded p-2 small"></div>
                    </div>

                    <div class="mb-3 d-none" id="st-lampiran-box">
                        <small class="text-muted d-block mb-1">Lampiran</small>
                        <a href="#" target="_blank" id="st-lampiran-link" class="badge bg-light text-primary border text-decoration-none py-1 px-2">
                            <i class="bi bi-paperclip"></i> Lihat Bukti
                        </a>
                    </div>

                    <label class="form-label small fw-semibold">Keputusan</label>

                    <div class="d-flex gap-3 mb-3 keputusan">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="aksi" id="aksi-setujui" value="approved" required>
                            <label class="form-check-label text-success fw-semibold" for="aksi-setujui">Setujui</label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="aksi" id="aksi-tolak" value="rejected">
                            <label class="form-check-label text-danger fw-semibold" for="aksi-tolak">Tolak</label>
                        </div>
                    </div>

                    <label class="form-label small fw-semibold">Catatan HRD (wajib jika ditolak)</label>
                    <textarea class="form-control" name="catatan" id="st-catatan" rows="3" placeholder="Tuliskan catatan untuk karyawan..."></textarea>
                </div>
                
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-custom-reset px-4 fw-semibold text-14" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-custom-submit px-4 fw-semibold text-14">Simpan Keputusan</button>
                </div>
  
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
        let filterAktif = 'semua';
        const rows = document.querySelectorAll('#tabel-body tr[data-status]');
        const inputCari = document.getElementById('cari');

        function terapkanFilter() {
            const kata = inputCari.value.trim().toLowerCase();
            rows.forEach(tr => {
                const cocokStatus = filterAktif === 'semua' || tr.dataset.status === filterAktif;
                const cocokCari = tr.dataset.cari.includes(kata);
                tr.style.display = (cocokStatus && cocokCari) ? '' : 'none';
            });
        }
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', () => { filterAktif = btn.dataset.filter; terapkanFilter(); });
        });
        inputCari.addEventListener('input', terapkanFilter);

        document.getElementById('modalStatus').addEventListener('show.bs.modal', e => {
            const b = e.relatedTarget;
            document.getElementById('st-id').value = b.dataset.id;
            document.getElementById('st-nama').textContent = b.dataset.nama;
            document.getElementById('st-jenis').textContent = b.dataset.jenis;
            document.getElementById('st-alasan').textContent = b.dataset.alasan;
            document.getElementById('st-catatan').value = b.dataset.catatan;
            document.getElementById('aksi-setujui').checked = b.dataset.aksi === 'approved';
            document.getElementById('aksi-tolak').checked = b.dataset.aksi === 'rejected';

            const boxLampiran = document.getElementById('st-lampiran-box');
            if (b.dataset.lampiran) {
                document.getElementById('st-lampiran-link').href = '../uploads/' + b.dataset.lampiran;
                boxLampiran.classList.remove('d-none');
            } else {
                boxLampiran.classList.add('d-none');
            }
        });
    </script>
</body>
</html>