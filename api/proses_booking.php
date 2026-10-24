<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/koneksi.php';

// Mendukung FormData ($_POST) maupun JSON raw payload
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

// Ambil field-field yang dikirimkan
$nama   = isset($input['nama']) ? trim($input['nama']) : (isset($input['nama_pemesan']) ? trim($input['nama_pemesan']) : '');
$unit   = isset($input['unit']) ? trim($input['unit']) : (isset($input['nama_unit']) ? trim($input['nama_unit']) : '');
$durasi = isset($input['durasi']) ? (int)$input['durasi'] : 1;
$total  = isset($input['total']) ? (int)$input['total'] : (isset($input['total_harga']) ? (int)$input['total_harga'] : 0);
$metode = isset($input['metode']) ? trim($input['metode']) : (isset($input['metode_pembayaran']) ? trim($input['metode_pembayaran']) : 'Tunai / Cash');

// Validasi input
if (empty($nama) || empty($unit)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Data tidak lengkap! Nama pemesan dan unit kamar harus diisi.'
    ]);
    exit;
}

if ($durasi <= 0) {
    $durasi = 1;
}

// Escape string untuk keamanan MySQL
$namaEsc   = mysqli_real_escape_string($koneksi, $nama);
$unitEsc   = mysqli_real_escape_string($koneksi, $unit);
$metodeEsc = mysqli_real_escape_string($koneksi, $metode);

// 1. Simpan data booking ke tabel booking dengan nama kolom yang sesuai skema database
$query_booking = "INSERT INTO booking (nama_unit, nama_pemesan, durasi, total_harga, metode_pembayaran) 
                  VALUES ('$unitEsc', '$namaEsc', $durasi, $total, '$metodeEsc')";

$insert_booking = mysqli_query($koneksi, $query_booking);

if ($insert_booking) {
    // 2. Update status kamar menjadi 'terisi'
    $query_update = "UPDATE kamar SET status = 'terisi' WHERE nama_kamar = '$unitEsc'";
    mysqli_query($koneksi, $query_update);

    echo json_encode([
        'status' => 'success',
        'message' => 'Pemesanan kamar ' . $unit . ' berhasil disimpan!',
        'data' => [
            'id' => mysqli_insert_id($koneksi),
            'unit' => $unit,
            'nama' => $nama,
            'durasi' => $durasi,
            'total' => $total,
            'metode' => $metode
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal menyimpan pemesanan ke database: ' . mysqli_error($koneksi)
    ]);
}
?>