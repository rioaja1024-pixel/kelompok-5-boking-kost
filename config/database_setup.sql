-- =====================================================
-- DATABASE SETUP: db_booking
-- =====================================================

CREATE DATABASE IF NOT EXISTS `db_booking` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_booking`;

-- 1. TABEL KAMAR
CREATE TABLE IF NOT EXISTS `kamar` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kamar` VARCHAR(100) NOT NULL,
    `harga` INT NOT NULL,
    `gambar` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('kosong', 'terisi') DEFAULT 'kosong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL BOOKING
CREATE TABLE IF NOT EXISTS `booking` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_unit` VARCHAR(100) NOT NULL,
    `nama_pemesan` VARCHAR(100) NOT NULL,
    `durasi` INT NOT NULL DEFAULT 1,
    `total_harga` INT NOT NULL DEFAULT 0,
    `metode_pembayaran` VARCHAR(50) DEFAULT 'Tunai / Cash',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pastikan kolom metode_pembayaran ada jika tabel sudah pernah dibuat sebelumnya
ALTER TABLE `booking` ADD COLUMN IF NOT EXISTS `metode_pembayaran` VARCHAR(50) DEFAULT 'Tunai / Cash';

-- 3. TABEL FOTO_KAMAR (UNTUK SLIDER FOTO SUDUT RUANGAN)
CREATE TABLE IF NOT EXISTS `foto_kamar` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_kamar` INT NOT NULL,
    `nama_file` VARCHAR(255) NOT NULL,
    `keterangan` VARCHAR(100) DEFAULT '',
    `urutan` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`id_kamar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Query Bantuan: Untuk mengosongkan status semua kamar kembali (reset):
-- UPDATE kamar SET status = 'kosong';
-- DELETE FROM booking;

