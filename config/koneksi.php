<?php
/**
 * ============================================================================
 * BOOTSTRAP LOADER: config/koneksi.php
 * ============================================================================
 * Inisialisasi Database Connection & Autoloading Core Models (OOP)
 * 
 * Menerapkan prinsip PBO:
 * - Singleton Database Connection
 * - Core Domain Models: BaseModel, FotoKamar, Kamar, Payment, Pemesanan
 * ============================================================================
 */

require_once __DIR__ . '/Database.php';

// Inisialisasi koneksi singleton
$db = Database::getInstance();
$koneksi = $db->getConnection();

// Autoloading 5 Model Inti dari direktori models/
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/FotoKamar.php';
require_once __DIR__ . '/../models/Kamar.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Pemesanan.php';
?>
