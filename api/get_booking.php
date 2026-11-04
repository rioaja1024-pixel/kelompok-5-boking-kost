<?php
/**
 * ============================================================================
 * API: get_booking.php (Berbasis Pemrograman Berorientasi Objek / PBO)
 * ============================================================================
 * Menggunakan class Pemesanan dan Payment untuk mengelola riwayat pemesanan.
 * ============================================================================
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/koneksi.php';

try {
    $pemesanan = new Pemesanan($db);

    // Jika request adalah GET, kembalikan daftar seluruh data pemesanan
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $dataBooking = $pemesanan->getAllPemesanan();
        echo json_encode([
            'status' => 'success',
            'data'   => $dataBooking
        ]);
        exit;
    }

    // Jika request adalah POST (fallback / backward compatibility)
    $unit   = $_POST['unit'] ?? '';
    $nama   = $_POST['nama'] ?? '';
    $durasi = isset($_POST['durasi']) ? (int)$_POST['durasi'] : 1;
    $total  = isset($_POST['total']) ? (int)$_POST['total'] : 0;
    $metode = $_POST['metode'] ?? 'Tunai / Cash';

    if (empty($unit) || empty($nama)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        exit;
    }

    // ENKAPSULASI: Setter dengan validasi
    $pemesanan->setNamaUnit($unit);
    $pemesanan->setNamaPemesan($nama);
    $pemesanan->setDurasi($durasi);
    $pemesanan->setTotalHarga($total);

    $payment = PaymentFactory::create($metode, $total);
    $pemesanan->setPayment($payment);

    // POLIMORFISME: Eksekusi proses pemesanan
    $result = $pemesanan->prosesPemesanan();
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
