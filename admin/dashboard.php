<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hrd') {
    header("location: ../index.php");
    exit;
}

define('KUOTA_TAHUNAN', 12);

$sql = "SELECT p.*, u.nik, u.nama,
               p.jumlah_hari AS durasi,
               (SELECT COALESCE(SUM(x.jumlah_hari), 0)
                  FROM pengajuan_cuti x
                 WHERE x.user_id = p.user_id
                   AND x.status = 'approved'
                   AND YEAR(x.tanggal_mulai) = YEAR(p.tanggal_mulai)) AS terpakai
        FROM pengajuan_cuti p
        JOIN users u ON u.id = p.user_id
        ORDER BY p.id DESC";
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

function analisisPengajuan($r)
{
    $catatan = [];
    $skor = 0;

    if ($r['durasi'] > $r['sisa']) {
        $catatan[] = "Durasi {$r['durasi']} hari melebihi sisa jatah ({$r['sisa']} hari).";
        $skor--;
    } else {
        $catatan[] = "Durasi {$r['durasi']} hari masih dalam sisa jatah ({$r['sisa']} hari).";
        $skor++;
    }
    if ($r['durasi'] > 14) {
        $catatan[] = "Durasi cuti panjang (lebih dari 14 hari), perlu pertimbangan khusus.";
        $skor--;
    }
    if (strtotime($r['tanggal_mulai']) < strtotime(date('Y-m-d', strtotime($r['created_at'])))) {
        $catatan[] = "Pengajuan dibuat setelah tanggal mulai cuti.";
        $skor--;
    }
    if (strlen(trim($r['alasan'])) < 10) {
        $catatan[] = "Alasan pengajuan terlalu singkat.";
        $skor--;
    }

    $rekom = $skor >= 1 ? 'Disarankan DISETUJUI' : ($skor == 0 ? 'Perlu dipertimbangkan' : 'Disarankan DITOLAK');
    return ['rekomendasi' => $rekom, 'catatan' => $catatan];
}

function tgl($d) { return date('d/m/Y', strtotime($d)); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin HRD - Pengajuan Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: #f8fafc;">

    <!-- Navbar -->
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
            <a href="../logout.php" class="btn btn-outline-light btn-sm ms-auto">
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

        <!-- 1. Statistik -->
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

        <!-- 2. Tabel seluruh pengajuan -->
        <div class="card shadow-sm border rounded-4">
            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0 text-dark">Seluruh Pengajuan Cuti Karyawan</h5>
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="cari" class="form-control form-control-sm" placeholder="Cari nama / NIK...">
                    </div>
                </div>

                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button class="btn btn-filter-active btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="semua">Semua
                        <span class="badge rounded-circle ms-1"><?php echo $stat['total']; ?></span></button>
                    <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="pending">Pending
                        <span class="badge rounded-circle ms-1"><?php echo $stat['pending']; ?></span></button>
                    <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="approved">Disetujui
                        <span class="badge rounded-circle ms-1"><?php echo $stat['approved']; ?></span></button>
                    <button class="btn btn-filter btn-sm rounded-pill px-3 fw-semibold text-14" data-filter="rejected">Ditolak
                        <span class="badge rounded-circle ms-1"><?php echo $stat['rejected']; ?></span></button>
                </div>

                <div class="table-responsive border rounded-3">
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
                                $ai = analisisPengajuan($r);
                                $badgeMap = [
                                    'pending'  => ['bg-soft-yellow', 'Pending'],
                                    'approved' => ['bg-soft-green', 'Disetujui'],
                                    'rejected' => ['bg-soft-red', 'Ditolak']
                                ];
                                $badge = $badgeMap[$r['status']] ?? ['bg-secondary', $r['status']];
                            ?>
                                <tr data-status="<?php echo $r['status']; ?>"
                                    data-cari="<?php echo htmlspecialchars(strtolower($r['nama'] . ' ' . $r['nik'])); ?>">
                                    <td class="ps-3 text-muted"><?php echo $i + 1; ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($r['nama']); ?></div>
                                        <div class="text-muted text-xs">NIK: <?php echo htmlspecialchars($r['nik']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['jenis_cuti']); ?></td>
                                    <td>
                                        <div><?php echo tgl($r['tanggal_mulai']); ?></div>
                                        <div class="text-muted text-xs"><?php echo tgl($r['tanggal_selesai']); ?></div>
                                    </td>
                                    <td><span class="badge bg-soft-blue text-primary fw-semibold"><?php echo $r['durasi']; ?> hr</span></td>
                                    <td>
                                        <span class="fw-semibold text-success"><?php echo $r['sisa']; ?></span>
                                        <span class="text-muted">/ <?php echo KUOTA_TAHUNAN; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $badge[0]; ?> fw-semibold">
                                            <i class="bi bi-circle-fill icon-dot me-1"></i><?php echo $badge[1]; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn-aksi btn-aksi-yellow" title="Proses / ubah status"
                                                data-bs-toggle="modal" data-bs-target="#modalStatus"
                                                data-id="<?php echo $r['id']; ?>"
                                                data-nama="<?php echo htmlspecialchars($r['nama']); ?>"
                                                data-jenis="<?php echo htmlspecialchars($r['jenis_cuti']); ?>"
                                                data-alasan="<?php echo htmlspecialchars($r['alasan']); ?>"
                                                data-status="<?php echo $r['status']; ?>"
                                                data-catatan="<?php echo htmlspecialchars($r['catatan_hrd'] ?? ''); ?>">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                            <button type="button" class="btn-aksi btn-aksi-purple" title="Analisis pengajuan"
                                                data-bs-toggle="modal" data-bs-target="#modalAI"
                                                data-nama="<?php echo htmlspecialchars($r['nama']); ?>"
                                                data-rekom="<?php echo htmlspecialchars($ai['rekomendasi']); ?>"
                                                data-catatan="<?php echo htmlspecialchars(json_encode($ai['catatan'])); ?>">
                                                <i class="bi bi-lightbulb"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal proses status -->
    <div class="modal fade" id="modalStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content rounded-4" action="../proses/proses_approval.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Proses Pengajuan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="st-id">
                    <div class="mb-2"><small class="text-muted">Karyawan</small>
                        <div class="fw-semibold" id="st-nama"></div></div>
                    <div class="mb-2"><small class="text-muted">Jenis Cuti</small>
                        <div id="st-jenis"></div></div>
                    <div class="mb-3"><small class="text-muted">Alasan</small>
                        <div id="st-alasan" class="bg-light rounded p-2 small"></div></div>

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
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-reset px-4 fw-semibold text-14" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-custom-submit px-4 fw-semibold text-14">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal analisis -->
    <div class="modal fade" id="modalAI" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-lightbulb me-2 text-purple"></i>Analisis Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><small class="text-muted">Karyawan</small>
                        <div class="fw-semibold" id="ai-nama"></div></div>
                    <div class="alert alert-light border fw-semibold" id="ai-rekom"></div>
                    <ul class="small mb-0" id="ai-catatan"></ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
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
            document.getElementById('aksi-setujui').checked = b.dataset.status === 'approved';
            document.getElementById('aksi-tolak').checked = b.dataset.status === 'rejected';
        });

        document.getElementById('modalAI').addEventListener('show.bs.modal', e => {
            const b = e.relatedTarget;
            document.getElementById('ai-nama').textContent = b.dataset.nama;
            document.getElementById('ai-rekom').textContent = b.dataset.rekom;
            const ul = document.getElementById('ai-catatan');
            ul.innerHTML = '';
            JSON.parse(b.dataset.catatan).forEach(t => {
                const li = document.createElement('li');
                li.textContent = t;
                ul.appendChild(li);
            });
        });
    </script>
</body>
</html>