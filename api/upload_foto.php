<?php
/**
 * ============================================================================
 * API: upload_foto.php (Berbasis Pemrograman Berorientasi Objek / PBO)
 * ============================================================================
 * PENERAPAN PRINSIP PBO:
 * 1. CLASS & OBJECT:
 *    - Instansiasi objek Kamar ($kamarModel) dan objek FotoKamar ($fotoModel).
 * 2. ENCAPSULATION:
 *    - Validasi ekstensi MIME file, ukuran batas 5MB, pembuatan nama file unik,
 *      dan penyimpanan direktori diisolasi di dalam method objek prosesUpload().
 * 3. INHERITANCE:
 *    - FotoKamar dan Kamar sama-sama mewarisi BaseModel.
 * 4. POLYMORPHISM:
 *    - Method simpan() yang di-override dari BaseModel dieksekusi secara otomatis
 *      saat mencatat foto ke database.
 * ============================================================================
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/koneksi.php';

// Validasi request upload awal
if (!isset($_POST['id_kamar']) || !isset($_FILES['foto'])) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Data tidak lengkap! ID kamar dan file foto wajib diunggah.'
    ]);
    exit;
}

$idKamar    = (int)$_POST['id_kamar'];
$keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : 'Foto Kamar';

try {
    // 1. CLASS & OBJECT: Cek keberadaan kamar target
    $kamarModel = new Kamar($db);
    $kamar = $kamarModel->getById($idKamar);
    if (!$kamar) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => "Kamar dengan ID $idKamar tidak ditemukan."
        ]);
        exit;
    }

    // 2. ENKAPSULASI: Pemrosesan upload file melalui objek FotoKamar
    $fotoModel = new FotoKamar($db);
    $uploadDir = __DIR__ . '/../public/uploads/';

    $uploadResult = $fotoModel->prosesUpload($_FILES['foto'], $idKamar, $keterangan, $uploadDir);

    if ($uploadResult['success']) {
        // Perbarui juga gambar cover utama pada objek Kamar
        $kamar->setGambar($uploadResult['file']);
        $kamar->simpan();

        echo json_encode([
            'status'  => 'success',
            'message' => $uploadResult['message'],
            'file'    => $uploadResult['file']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $uploadResult['message']
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
?>
