<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/koneksi.php';

// Pastikan semua data dikirim dari form (nama, unit, durasi, total)
if (isset($_POST['nama']) && isset($_POST['unit']) && isset($_POST['durasi']) && isset($_POST['total'])) {
    
    $nama   = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $unit   = mysqli_real_escape_string($koneksi, trim($_POST['unit']));
    $durasi = (int)$_POST['durasi'];
    $total  = (int)$_POST['total'];
    $metode = isset($_POST['metode']) ? mysqli_real_escape_string($koneksi, trim($_POST['metode'])) : 'Cash';

    // Cek apakah ada inputan yang kosong
    if (strlen($nama) === 0 || strlen($unit) === 0 || $durasi <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap! Harap isi semua form.']);
        exit;
    }

    // 1. Simpan data ke tabel booking
    $query_booking = "INSERT INTO booking (nama, unit, durasi, total, metode_pembayaran) VALUES ('$nama', '$unit', $durasi, $total, '$metode')";
    $insert_booking = mysqli_query($koneksi, $query_booking);

    if ($insert_booking) {
        // 2. Kalau booking sukses, update status kamar jadi 'terisi'
        $query_update_kamar = "UPDATE kamar SET status = 'terisi' WHERE nama_kamar = '$unit'";
        mysqli_query($koneksi, $query_update_kamar);

        // Kirim respon sukses ke frontend
        echo json_encode(['status' => 'success', 'message' => 'Booking berhasil! Kamar sekarang berstatus terisi.']);
    } else {
        // Gagal menyimpan ke database
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ke database: ' . mysqli_error($koneksi)]);
    }

} else {
    // Ada variabel yang tidak terkirim dari script.js
    echo json_encode(['status' => 'error', 'message' => 'Error sistem: Data POST tidak lengkap.']);
}
?>