<?php
/**
 * ============================================================================
 * API: admin_action.php (Backend Pengelola Admin berbasis OOP)
 * ============================================================================
 * Menyediakan endpoint operasional untuk Admin:
 * - Update status kamar
 * - Update harga kamar
 * - Reset status kamar
 * - Hapus data booking
 * - Hapus foto slider
 * - Statistik dashboard
 * Dilengkapi proteksi PIN Admin sederhana (Default PIN: 1234)
 * ============================================================================
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/koneksi.php';

const ADMIN_PIN = '1234';

// Ambil input JSON atau POST
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

// Cek autentikasi PIN admin sederhana
$reqPin = isset($input['pin']) ? trim($input['pin']) : (isset($_SERVER['HTTP_X_ADMIN_PIN']) ? trim($_SERVER['HTTP_X_ADMIN_PIN']) : '');
if ($reqPin !== ADMIN_PIN) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'PIN Admin tidak valid atau belum diisi.'
    ]);
    exit;
}

$action = isset($input['action']) ? trim($input['action']) : '';

try {
    $kamarModel   = new Kamar($db);
    $bookingModel = new BookingKost($db);
    $fotoModel    = new FotoKamar($db);

    switch ($action) {
        // 1. STATISTIK DASHBOARD ADMIN
        case 'stats':
            $daftarKamar = $kamarModel->getAll();
            $totalUnit   = count($daftarKamar);
            $kosongCount = 0;
            $terisiCount = 0;

            foreach ($daftarKamar as $k) {
                if (($k['status'] ?? 'kosong') === 'terisi') {
                    $terisiCount++;
                } else {
                    $kosongCount++;
                }
            }

            $daftarBooking = $bookingModel->getAllBooking();
            $totalBooking  = count($daftarBooking);
            $totalUang     = 0;
            foreach ($daftarBooking as $b) {
                $totalUang += (int)($b['total_harga'] ?? 0);
            }

            echo json_encode([
                'status' => 'success',
                'data'   => [
                    'total_unit'    => $totalUnit,
                    'unit_kosong'   => $kosongCount,
                    'unit_terisi'   => $terisiCount,
                    'total_booking' => $totalBooking,
                    'total_uang'    => $totalUang
                ]
            ]);
            break;

        // 2. TOGGLE STATUS KAMAR (KOSONG <-> TERISI)
        case 'toggle_status':
            $idKamar = isset($input['id_kamar']) ? (int)$input['id_kamar'] : 0;
            $status  = isset($input['status']) ? trim($input['status']) : '';

            $kamar = $kamarModel->getById($idKamar);
            if (!$kamar) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Kamar tidak ditemukan.']);
                exit;
            }

            $statusBaru = !empty($status) ? $status : ($kamar->getStatus() === 'terisi' ? 'kosong' : 'terisi');
            $kamar->updateStatus($statusBaru);

            echo json_encode([
                'status'  => 'success',
                'message' => "Status {$kamar->getNamaKamar()} berhasil diubah menjadi " . strtoupper($statusBaru) . "!",
                'data'    => [
                    'id'     => $idKamar,
                    'status' => $statusBaru
                ]
            ]);
            break;

        // 3. UPDATE HARGA KAMAR
        case 'update_harga':
            $idKamar = isset($input['id_kamar']) ? (int)$input['id_kamar'] : 0;
            $harga   = isset($input['harga']) ? (int)$input['harga'] : 0;

            if ($idKamar <= 0 || $harga <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID kamar dan harga baru harus valid.']);
                exit;
            }

            $kamar = $kamarModel->getById($idKamar);
            if (!$kamar) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Kamar tidak ditemukan.']);
                exit;
            }

            $kamar->updateHarga($harga);
            echo json_encode([
                'status'  => 'success',
                'message' => "Harga {$kamar->getNamaKamar()} berhasil diperbarui menjadi Rp " . number_format($harga, 0, ',', '.') . "/bln!"
            ]);
            break;

        // 4. RESET SEMUA STATUS KAMAR MENJADI TERSEDIA
        case 'reset_all_rooms':
            $kamarModel->resetSemuaKamar();
            echo json_encode([
                'status'  => 'success',
                'message' => 'Semua status unit kamar berhasil direset menjadi KOSONG / TERSEDIA!'
            ]);
            break;

        // 5. HAPUS DATA PEMESANAN / BOOKING
        case 'delete_booking':
            $idBooking = isset($input['id_booking']) ? (int)$input['id_booking'] : 0;
            $kembalikan = isset($input['kembalikan_kamar']) ? (bool)$input['kembalikan_kamar'] : true;

            if ($idBooking <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID booking tidak valid.']);
                exit;
            }

            $success = $bookingModel->hapusBooking($idBooking, $kembalikan);
            if ($success) {
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Data pemesanan berhasil dihapus dan unit kamar telah dikembalikan menjadi tersedia!'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Data booking tidak ditemukan atau gagal dihapus.']);
            }
            break;

        // 6. HAPUS FOTO SLIDER KAMAR
        case 'delete_foto':
            $idFoto = isset($input['id_foto']) ? (int)$input['id_foto'] : 0;
            if ($idFoto <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID foto tidak valid.']);
                exit;
            }

            $uploadDir = __DIR__ . '/../public/uploads/';
            $success = $fotoModel->hapusFoto($idFoto, $uploadDir);
            if ($success) {
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Foto berhasil dihapus dari slider!'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Foto tidak ditemukan atau gagal dihapus.']);
            }
            break;

        default:
            echo json_encode([
                'status'  => 'success',
                'message' => 'PIN Admin valid. Sesi terverifikasi.',
                'pin_ok'  => true
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
}
?>

