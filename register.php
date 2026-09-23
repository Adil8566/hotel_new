<?php
session_start();
require_once 'database/koneksi.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $konfirmasi_password = (string) ($_POST['konfirmasi_password'] ?? '');

    if ($nama === '' || $username === '' || $password === '') {
        $error = 'Semua data wajib diisi.';
    } elseif ($password !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak sesuai.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $stmt = mysqli_prepare($con, 'SELECT id_pengguna FROM tbl_pengguna WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $username_ada = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($username_ada) {
            $error = 'Username sudah digunakan, silakan pilih username lain.';
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $peran = 'user';

            $insert = mysqli_prepare($con, 'INSERT INTO tbl_pengguna (username, password, nama, peran) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($insert, 'ssss', $username, $password_hashed, $nama, $peran);

            if (mysqli_stmt_execute($insert)) {
                $success = "Akun berhasil dibuat! Silakan <a href='login.php'>login di sini</a>.";
            } else {
                $error = 'Terjadi kesalahan sistem, gagal mendaftar.';
            }
            mysqli_stmt_close($insert);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Manajemen Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card shadow-sm border-0 p-4" style="width: 100%; max-width: 400px;">
    <div class="text-center mb-4">
        <h4 class="fw-bold">Buat Akun Baru</h4>
        <p class="text-muted small">Silakan isi data diri Anda untuk mendaftar</p>
    </div>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger py-2 small"><?= $error; ?></div>
    <?php } ?>

    <?php if (!empty($success)) { ?>
        <div class="alert alert-success py-2 small"><?= $success; ?></div>
    <?php } ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" required placeholder="Masukkan nama lengkap">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">Username</label>
            <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">Konfirmasi Password</label>
            <input type="password" name="konfirmasi_password" class="form-control" required placeholder="Ulangi password">
        </div>
        <button type="submit" name="register" class="btn btn-danger w-100 py-2">Daftar Sekarang</button>
    </form>

    <div class="text-center mt-3">
        <p class="text-muted small">Sudah punya akun? <a href="login.php" class="text-danger text-decoration-none fw-bold">Login</a></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>