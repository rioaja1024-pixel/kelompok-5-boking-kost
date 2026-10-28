<?php
/**
 * ============================================================================
 * API: get_booking.php (Berbasis Pemrograman Berorientasi Objek / PBO)
 * ============================================================================
 * PENERAPAN PRINSIP PBO:
 * 1. CLASS & OBJECT:
 *    - Menggunakan objek BookingKost ($booking = new BookingKost($db)).
 * 2. ENCAPSULATION:
 *    - Akses data transaksi melalui method publik getAllBooking() atau prosesTransaksi().
 * 3. INHERITANCE:
 *    - BookingKost mewarisi fungsionalitas dari Transaksi.
 * 4. POLYMORPHISM:
 *    - Menggunakan implementasi method transaksi polimorfik.
 * ============================================================================
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/koneksi.php';

try {
    $booking = new BookingKost($db);

    // Jika request adalah GET, kembalikan daftar seluruh data booking
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $dataBooking = $booking->getAllBooking();
        echo json_encode([
            'status' => 'success',
            'data'   => $dataBooking
        ]);
        exit;
    }

    // Jika request adalah POST (backward compatibility jika form lama memanggil get_booking.php)
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
    $booking->setNamaUnit($unit);
    $booking->setNamaPemesan($nama);
    $booking->setDurasi($durasi);
    $booking->setTotalHarga($total);
    $booking->setMetodePembayaran($metode);

    // POLIMORFISME: Eksekusi transaksi
    $result = $booking->prosesTransaksi();
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
