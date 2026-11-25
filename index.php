<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['role'])) {
    header("location: " . ($_SESSION['role'] == 'hrd' ? 'admin/dashboard.php' : 'karyawan/dashboard.php'));
    exit;
}

$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik      = trim($_POST['nik']);
    $password = trim($_POST['password']);

    $stmt = $koneksi->prepare("SELECT id, nik, nama, password, role FROM users WHERE nik = ?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $password === $user['password']) {
        $_SESSION['id']   = $user['id'];
        $_SESSION['nik']  = $user['nik'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];

        header("location: " . ($user['role'] == 'hrd' ? 'admin/dashboard.php' : 'karyawan/dashboard.php'));
        exit;
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pengajuan Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="d-flex align-items-center justify-content-center vh-100" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 450px; border-radius: 15px;">
        <div class="text-center mb-4">
            <h4 class="fw-bold">Pengajuan Cuti</h4>
            <p class="text-muted small mb-0">Silahkan masuk menggunakan NIK Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show text-center small py-2" role="alert">
                NIK atau Password salah!
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="nik" class="form-label small fw-semibold">NIK</label>
                <input type="text" class="form-control" id="nik" name="nik" placeholder="Masukkan NIK" required autofocus>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-semibold">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan Password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>