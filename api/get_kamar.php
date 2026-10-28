<?php
/**
 * ============================================================================
 * API: get_kamar.php (Berbasis Pemrograman Berorientasi Objek / PBO)
 * ============================================================================
 * PENERAPAN PRINSIP PBO:
 * 1. CLASS & OBJECT:
 *    - Menggunakan objek Database ($db) dan objek model Kamar ($kamarModel).
 * 2. ENCAPSULATION:
 *    - Data kamar diakses melalui method publik objek tanpa query langsung di file API.
 * 3. POLYMORPHISM:
 *    - Objek Kamar meng-override method getDetail() dan toArray() dari BaseModel.
 * ============================================================================
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/koneksi.php';

try {
    // CLASS & OBJECT: Instansiasi objek Kamar dengan Dependency Injection $db
    $kamarModel = new Kamar($db);

    // Memanggil method objek Kamar untuk mengambil seluruh data kamar & slider foto
    $daftarKamar = $kamarModel->getAll();

    echo json_encode($daftarKamar);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal memuat data kamar: ' . $e->getMessage()
    ]);
}
?>
