<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['peran'] ?? '') !== 'admin') { header('Location: ../../login.php'); exit; }
require_once '../../database/koneksi.php';
require_once '../../database/fungsi_hotel.php';
$dari=$_GET['dari']??date('Y-m-01'); $sampai=$_GET['sampai']??date('Y-m-t'); $status=$_GET['status']??'';
$where=["b.check_in <= '{$sampai}'","b.check_out >= '{$dari}'"]; if(valid_booking_status($status)) $where[]="LOWER(TRIM(b.status))='".mysqli_real_escape_string($con,$status)."'"; $ws=implode(' AND ',$where);
$list=mysqli_query($con,"SELECT b.kode_booking,t.nama,k.nomor_kamar,tk.nama_tipe,b.check_in,b.check_out,LOWER(TRIM(b.status)) status,b.total,COALESCE((SELECT SUM(p.jumlah_bayar) FROM tbl_pembayaran p WHERE p.id_booking=b.id_booking),0) terbayar FROM tbl_booking b INNER JOIN tbl_tamu t ON b.id_tamu=t.id_tamu INNER JOIN tbl_kamar k ON b.id_kamar=k.id_kamar INNER JOIN tbl_tipe_kamar tk ON k.id_tipe=tk.id_tipe WHERE {$ws} ORDER BY b.check_in ASC");
header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="laporan_hotel_'.date('Ymd_His').'.csv"'); echo "\xEF\xBB\xBF";
$out=fopen('php://output','w'); fputcsv($out,['Kode Booking','Nama Tamu','Nomor Kamar','Tipe Kamar','Check-in','Check-out','Status','Total','Terbayar','Sisa']); while($r=mysqli_fetch_assoc($list)){ $s=max(0,(float)$r['total']-(float)$r['terbayar']); fputcsv($out,[$r['kode_booking'],$r['nama'],$r['nomor_kamar'],$r['nama_tipe'],$r['check_in'],$r['check_out'],status_booking_label($r['status']),$r['total'],$r['terbayar'],$s]); } fclose($out); exit;
?>