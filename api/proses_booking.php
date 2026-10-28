<?php
/**
 * ============================================================================
 * API: proses_booking.php (Berbasis Pemrograman Berorientasi Objek / PBO)
 * ============================================================================
 * PENERAPAN PRINSIP PBO:
 * 1. CLASS & OBJECT:
 *    - Instansiasi objek BookingKost ($booking = new BookingKost($db)).
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Data request dimasukkan ke dalam objek melalui method Setter:
 *      setNamaPemesan(), setNamaUnit(), setDurasi(), setTotalHarga(), setMetodePembayaran().
 *    - Validasi data berjalan secara otomatis di dalam setter.
 * 3. INHERITANCE (PEWARISAN):
 *    - BookingKost mewarisi properti dan method dari abstract class Transaksi.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Memanggil method polimorfik $booking->prosesTransaksi() yang meng-override
 *      method abstract dari Transaksi untuk memproses transaksi kost secara aman.
 * ============================================================================
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/koneksi.php';

// Mendukung FormData ($_POST) maupun JSON raw payload dari frontend
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
}

// Ekstraksi parameter
$nama   = isset($input['nama']) ? trim($input['nama']) : (isset($input['nama_pemesan']) ? trim($input['nama_pemesan']) : '');
$unit   = isset($input['unit']) ? trim($input['unit']) : (isset($input['nama_unit']) ? trim($input['nama_unit']) : '');
$durasi = isset($input['durasi']) ? (int)$input['durasi'] : 1;
$total  = isset($input['total']) ? (int)$input['total'] : (isset($input['total_harga']) ? (int)$input['total_harga'] : 0);
$metode = isset($input['metode']) ? trim($input['metode']) : (isset($input['metode_pembayaran']) ? trim($input['metode_pembayaran']) : 'Tunai / Cash');

// Validasi input awal
if (empty($nama) || empty($unit)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Data tidak lengkap! Nama pemesan dan pilihan unit kamar wajib diisi.'
    ]);
    exit;
}

try {
    // CLASS & OBJECT: Membuat objek BookingKost
    $booking = new BookingKost($db);

    // ENKAPSULASI: Memasukkan nilai menggunakan Setter dengan validasi
    $booking->setNamaUnit($unit);
    $booking->setNamaPemesan($nama);
    $booking->setDurasi($durasi > 0 ? $durasi : 1);
    if ($total > 0) {
        $booking->setTotalHarga($total);
    } else {
        $booking->hitungTotal(); // Menghitung otomatis jika total tidak dikirim
    }
    $booking->setMetodePembayaran($metode);

    // POLIMORFISME: Menjalankan eksekusi transaksi yang telah di-override
    $hasil = $booking->prosesTransaksi();

    if ($hasil['status'] === 'error') {
        http_response_code(400);
    }

    echo json_encode($hasil);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Validasi gagal: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
}
?>
