-- Grand Dian Hotel - Struktur kompatibel dengan database hotel
-- Gunakan database/hotel.sql untuk impor data lengkap.
-- File ini hanya fallback struktur tambahan jika tabel pembayaran belum ada.

CREATE TABLE IF NOT EXISTS tbl_pembayaran (
    id_pembayaran INT(11) NOT NULL AUTO_INCREMENT,
    id_booking INT(11) NOT NULL,
    tanggal_bayar DATETIME DEFAULT CURRENT_TIMESTAMP,
    jumlah_bayar DECIMAL(12,2) NOT NULL,
    metode VARCHAR(50) DEFAULT NULL,
    status ENUM('belum_lunas','lunas') DEFAULT 'belum_lunas',
    PRIMARY KEY (id_pembayaran),
    KEY id_booking (id_booking)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE tbl_pengguna MODIFY password VARCHAR(255) NOT NULL;

UPDATE tbl_booking SET status = LOWER(TRIM(status)) WHERE status IS NOT NULL;
UPDATE tbl_kamar SET status = LOWER(TRIM(status)) WHERE status IS NOT NULL;
