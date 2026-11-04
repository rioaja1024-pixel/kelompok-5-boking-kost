<?php
/**
 * ============================================================================
 * API: proses_booking.php (Berbasis Pemrograman Berorientasi Objek / PBO)
 * ============================================================================
 * PENERAPAN PRINSIP PBO:
 * 1. CLASS & OBJECT:
 *    - Instansiasi objek Pemesanan: $pemesanan = new Pemesanan($db).
 *    - Instansiasi objek Payment polimorfik melalui PaymentFactory::create().
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Data request dimasukkan ke objek Pemesanan melalui Setter:
 *      setNamaPemesan(), setNamaUnit(), setDurasi(), setTotalHarga(), setPayment().
 *    - Aturan validasi dijalankan secara aman di dalam method Setter.
 * 3. INHERITANCE (PEWARISAN):
 *    - Pemesanan mewarisi BaseModel (properti $db, $id, $createdAt).
 *    - Objek Payment (CashPayment, QrisPayment, BankTransferPayment, EWalletPayment)
 *      mewarisi superclass abstract Payment.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Pemesanan meng-override method simpan(), getDetail(), toArray() dari BaseModel.
 *    - Payment mengeksekusi processPayment() dan getPaymentInstructions() sesuai
 *      metode pembayaran yang dipilih.
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
$nama        = isset($input['nama']) ? trim($input['nama']) : (isset($input['nama_pemesan']) ? trim($input['nama_pemesan']) : '');
$unit        = isset($input['unit']) ? trim($input['unit']) : (isset($input['nama_unit']) ? trim($input['nama_unit']) : '');
$durasi      = isset($input['durasi']) ? (int)$input['durasi'] : 1;
$tipe_durasi = isset($input['tipe_durasi']) ? trim($input['tipe_durasi']) : 'bulanan';
$total       = isset($input['total']) ? (int)$input['total'] : (isset($input['total_harga']) ? (int)$input['total_harga'] : 0);
$metode      = isset($input['metode']) ? trim($input['metode']) : (isset($input['metode_pembayaran']) ? trim($input['metode_pembayaran']) : 'Tunai / Cash');

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
    // 1. CLASS & OBJECT: Membuat instance objek Pemesanan
    $pemesanan = new Pemesanan($db);

    // 2. ENKAPSULASI: Memasukkan nilai menggunakan Setter dengan validasi otomatis
    $pemesanan->setNamaPemesan($nama);
    $pemesanan->setNamaUnit($unit);
    $pemesanan->setTipeDurasi($tipe_durasi);
    $pemesanan->setDurasi($durasi > 0 ? $durasi : 1);
    
    if ($total > 0) {
        $pemesanan->setTotalHarga($total);
    } else {
        $pemesanan->hitungTotal(); // Menghitung otomatis jika total belum terisi
    }

    // 3. POLYMORPHISM & FACTORY: Membuat objek Payment polimorfik sesuai metode yang dipilih
    $payment = PaymentFactory::create($metode, $pemesanan->getTotalHarga());
    $pemesanan->setPayment($payment);

    // 4. Memproses alur pemesanan secara utuh ke database MySQL
    $hasil = $pemesanan->prosesPemesanan();

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
