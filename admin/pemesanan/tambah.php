<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') {
    header('Location: ../../login.php'); exit;
}
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
normalize_hotel_statuses($con);

$id_tipe_get = (int) ($_GET['id_tipe'] ?? $_POST['id_tipe'] ?? 0);
if ($id_tipe_get <= 0) { header('Location: pemesanan.php'); exit; }

$stmt_tipe = mysqli_prepare($con, 'SELECT nama_tipe, harga FROM tbl_tipe_kamar WHERE id_tipe = ? LIMIT 1');
mysqli_stmt_bind_param($stmt_tipe, 'i', $id_tipe_get); mysqli_stmt_execute($stmt_tipe); mysqli_stmt_bind_result($stmt_tipe, $nama_tipe, $harga_tipe); $ada_tipe = mysqli_stmt_fetch($stmt_tipe); mysqli_stmt_close($stmt_tipe);
if (!$ada_tipe) { header('Location: pemesanan.php'); exit; }

$query_tamu = mysqli_query($con, 'SELECT id_tamu, nama, no_hp FROM tbl_tamu ORDER BY nama ASC');
$error = '';
$old = ['id_tamu'=>'','check_in'=>'','check_out'=>'','jumlah_tamu'=>1];

if (isset($_POST['btn-tambah'])) {
    $old['id_tamu'] = (int)($_POST['id_tamu'] ?? 0);
    $old['check_in'] = trim($_POST['check_in'] ?? '');
    $old['check_out'] = trim($_POST['check_out'] ?? '');
    $old['jumlah_tamu'] = max(1, (int)($_POST['jumlah_tamu'] ?? 1));

    $in = DateTime::createFromFormat('Y-m-d', $old['check_in']);
    $out = DateTime::createFromFormat('Y-m-d', $old['check_out']);

    if ($old['id_tamu'] <= 0 || !$in || !$out) {
        $error = 'Data pemesanan belum lengkap.';
    } elseif ($old['check_in'] < date('Y-m-d')) {
        $error = 'Tanggal check-in tidak boleh sebelum hari ini.';
    } elseif ($out <= $in) {
        $error = 'Tanggal check-out harus setelah check-in.';
    } else {
        // Jangan hanya mengandalkan status kamar. Booking masa depan boleh berada pada kamar yang secara fisik masih tersedia.
        $id_tipe = $id_tipe_get;
        $check_in = mysqli_real_escape_string($con, $old['check_in']);
        $check_out = mysqli_real_escape_string($con, $old['check_out']);
        $cari = mysqli_query($con, "
            SELECT k.id_kamar
            FROM tbl_kamar k
            WHERE k.id_tipe = {$id_tipe}
              AND LOWER(TRIM(k.status)) <> 'maintenance'
              AND NOT EXISTS (
                  SELECT 1 FROM tbl_booking b
                  WHERE b.id_kamar = k.id_kamar
                    AND LOWER(TRIM(b.status)) IN ('pending','dikonfirmasi','checkin')
                    AND b.check_in < '{$check_out}'
                    AND b.check_out > '{$check_in}'
              )
            ORDER BY k.nomor_kamar ASC
            LIMIT 1
        ");

        if (!$cari || mysqli_num_rows($cari) === 0) {
            $error = 'Tidak ada kamar yang tersedia pada tanggal tersebut.';
        } else {
            $k = mysqli_fetch_assoc($cari);
            $id_kamar = (int)$k['id_kamar'];
            $jumlah_malam = (int)$in->diff($out)->days;
            $total = (float)$harga_tipe * $jumlah_malam;
            $kode_booking = 'HTL-' . date('YmdHis') . '-' . random_int(100, 999);

            $stmt = mysqli_prepare($con, 'INSERT INTO tbl_booking (kode_booking, id_tamu, id_kamar, check_in, check_out, jumlah_tamu, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $status = 'pending';
            mysqli_stmt_bind_param($stmt, 'siissids', $kode_booking, $old['id_tamu'], $id_kamar, $old['check_in'], $old['check_out'], $old['jumlah_tamu'], $total, $status);
            if (mysqli_stmt_execute($stmt)) {
                $id_booking = mysqli_insert_id($con);
                mysqli_stmt_close($stmt);
                // Tidak mengubah status kamar menjadi terisi untuk booking yang belum check-in.
                header('Location: detail.php?id=' . $id_booking . '&pesan=tambah'); exit;
            }
            $error = 'Booking gagal disimpan: ' . mysqli_error($con);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Tambah Pemesanan</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets_mantis/css/mantis-hotel.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-light mantis-topbar"><div class="container-fluid"><a href="pemesanan.php" class="navbar-brand"><i class="fas fa-hotel me-2"></i>Hotel Admin</a><a href="../../logout.php" class="btn btn-outline-light btn-sm">Logout</a></div></nav>
<div class="d-flex"><?php include '../template/sidebar.php'; ?><main class="flex-grow-1 p-4"><div class="card shadow-sm border-0"><div class="card-header bg-white"><h5 class="mb-0">Tambah Pemesanan</h5></div><div class="card-body">
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-circle-exclamation me-1"></i><?= e($error); ?></div><?php endif; ?>
<div class="alert alert-info"><strong><?= e($nama_tipe); ?></strong> - <?= rupiah($harga_tipe); ?> / malam. Sistem akan memilih nomor kamar yang tidak bentrok dengan booking aktif pada rentang tanggal yang dipilih.</div>
<form method="POST">
<input type="hidden" name="id_tipe" value="<?= $id_tipe_get; ?>">
<div class="mb-3"><label class="form-label">Nama Tamu</label><select name="id_tamu" class="form-select" required><option value="">-- Pilih Tamu --</option><?php while($tamu = mysqli_fetch_assoc($query_tamu)): ?><option value="<?= (int)$tamu['id_tamu']; ?>" <?= (int)$old['id_tamu'] === (int)$tamu['id_tamu'] ? 'selected' : ''; ?>><?= e($tamu['nama']); ?> - <?= e($tamu['no_hp']); ?></option><?php endwhile; ?></select></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" value="<?= e($old['check_in']); ?>" min="<?= date('Y-m-d'); ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Check-out</label><input type="date" name="check_out" class="form-control" value="<?= e($old['check_out']); ?>" min="<?= date('Y-m-d', strtotime('+1 day')); ?>" required></div></div>
<div class="mb-3"><label class="form-label">Jumlah Tamu</label><input type="number" name="jumlah_tamu" class="form-control" value="<?= (int)$old['jumlah_tamu']; ?>" min="1" required></div>
<div class="alert alert-secondary small"><i class="fas fa-circle-info me-1"></i>Status awal booking adalah <strong>Pending</strong>. Kamar baru menjadi <strong>Terisi</strong> saat proses Check-in.</div>
<button type="submit" name="btn-tambah" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Booking</button><a href="pemesanan.php" class="btn btn-secondary ms-2">Kembali</a>
</form></div></div></main></div>
</body></html>