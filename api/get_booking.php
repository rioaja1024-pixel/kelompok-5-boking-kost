<?php
/**
 * ============================================================================
 * API: get_booking.php (Berbasis Pemrograman Berorientasi Objek / PBO)
 * ============================================================================
 * Menggunakan class Pemesanan untuk mengambil seluruh riwayat pemesanan kost.
 * ============================================================================
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/koneksi.php';

try {
    $pemesanan = new Pemesanan($db);
    $dataBooking = $pemesanan->getAllPemesanan();

    echo json_encode([
        'status' => 'success',
        'data'   => $dataBooking
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
