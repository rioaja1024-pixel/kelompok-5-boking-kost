<?php
<<<<<<< HEAD
// Panggil koneksi database
require_once '../config/koneksi.php';

// Pastikan semua data dikirim dari form (nama, unit, durasi, total)
if (isset($_POST['nama']) && isset($_POST['unit']) && isset($_POST['durasi']) && isset($_POST['total'])) {
    
    $nama   = trim($_POST['nama']);
    $unit   = trim($_POST['unit']);
    $durasi = trim($_POST['durasi']);
    $total  = trim($_POST['total']);

    // Cek apakah ada inputan yang kosong
    // Catatan: gunakan strlen() bukan empty() karena empty("0") = true di PHP
    if (strlen($nama) === 0 || strlen($unit) === 0 || strlen($durasi) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap! Harap isi semua form.']);
        exit;
    }

    // 1. Simpan data ke tabel booking
    $query_booking = "INSERT INTO booking (nama, unit, durasi, total) VALUES ('$nama', '$unit', '$durasi', '$total')";
    $insert_booking = mysqli_query($koneksi, $query_booking);

    if ($insert_booking) {
        // 2. Kalau booking sukses, update status kamar jadi 'terisi'
        $query_update_kamar = "UPDATE kamar SET status = 'terisi' WHERE nama_kamar = '$unit'";
        mysqli_query($koneksi, $query_update_kamar);

        // Kirim respon sukses ke frontend
        echo json_encode(['status' => 'success', 'message' => 'Booking berhasil! Kamar sekarang berstatus terisi.']);
    } else {
        // Gagal menyimpan ke database
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ke database. ' . mysqli_error($koneksi)]);
    }

} else {
    // Ada variabel yang tidak terkirim dari script.js
    echo json_encode(['status' => 'error', 'message' => 'Error sistem: Data POST tidak lengkap.']);
=======
header('Content-Type: application/json');
require_once '../config/koneksi.php';

// Menerima request JSON dari fetch()
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['unit'], $data['nama'], $data['durasi'], $data['total_angka'])) {
    $unit = mysqli_real_escape_string($koneksi, $data['unit']);
    $nama = mysqli_real_escape_string($koneksi, $data['nama']);
    $durasi = (int)$data['durasi'];
    $total = (int)$data['total_angka'];

    $query = "INSERT INTO booking (nama_unit, nama_pemesan, durasi, total_harga) 
              VALUES ('$unit', '$nama', $durasi, $total)";

    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["status" => "success", "message" => "Pemesanan berhasil disimpan!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan data"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
>>>>>>> 63bbc246dda689808d6716536cf2a0652d77bb0b
}
?>