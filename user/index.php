<?php

session_start();

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['peran'] != 'user') {
    header("Location: ../login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Halaman User</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>

<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <span class="navbar-brand">
            Hotel
        </span>

        <a href="../logout.php"
           class="btn btn-danger">
            Logout
        </a>
    </div>
</nav>

<div class="container mt-4">

    <h3>
        Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>
    </h3>

    <p>
        Ini adalah halaman user.
    </p>

</div>

</body>
</html>