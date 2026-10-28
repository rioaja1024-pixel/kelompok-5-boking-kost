<?php
/**
 * ============================================================================
 * CONFIG: koneksi.php
 * ============================================================================
 * File inisialisasi OOP & Database Connection
 * 
 * Menerapkan prinsip PBO:
 * - Menggunakan Class Database (Singleton Pattern).
 * - Memuat class-class model (BaseModel, Kamar, FotoKamar, Transaksi, BookingKost).
 * - Menyediakan variabel $db (Object Database) dan $koneksi (mysqli) untuk
 *   kompatibilitas backward.
 * ============================================================================
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/models/BaseModel.php';
require_once __DIR__ . '/models/FotoKamar.php';
require_once __DIR__ . '/models/Kamar.php';
require_once __DIR__ . '/models/Transaksi.php';
require_once __DIR__ . '/models/BookingKost.php';

// CLASS & OBJECT: Mengambil instance objek Database
$db = Database::getInstance();

// Backward compatibility untuk script yang memerlukan variabel $koneksi
$koneksi = $db->getConnection();
?>
