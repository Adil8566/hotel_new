<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "hotel";

$con = mysqli_connect($host, $user, $password, $database);

if (!$con) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($con, "utf8mb4");

/*
 * Fallback ringan untuk instalasi baru.
 * Struktur mengikuti database hotel yang diberikan pengguna,
 * khususnya tabel tbl_pembayaran: tanggal_bayar, metode, status.
 */
@mysqli_query($con, "
    CREATE TABLE IF NOT EXISTS tbl_pembayaran (
        id_pembayaran INT(11) NOT NULL AUTO_INCREMENT,
        id_booking INT(11) NOT NULL,
        tanggal_bayar DATETIME DEFAULT CURRENT_TIMESTAMP,
        jumlah_bayar DECIMAL(12,2) NOT NULL,
        metode VARCHAR(50) DEFAULT NULL,
        status ENUM('belum_lunas','lunas') DEFAULT 'belum_lunas',
        PRIMARY KEY (id_pembayaran),
        KEY id_booking (id_booking)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Kolom password dibuat longgar agar mendukung password_hash() maupun hash lama SHA1.
@mysqli_query($con, "ALTER TABLE tbl_pengguna MODIFY password VARCHAR(255) NOT NULL");
?>
