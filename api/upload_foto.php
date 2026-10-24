<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/koneksi.php';

// Validasi: pastikan id_kamar dan file foto tersedia
if (!isset($_POST['id_kamar']) || !isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap atau file bermasalah.']);
    exit;
}

$id_kamar = intval($_POST['id_kamar']);
$keterangan = isset($_POST['keterangan']) && trim($_POST['keterangan']) !== '' 
    ? mysqli_real_escape_string($koneksi, trim($_POST['keterangan'])) 
    : 'Foto Kamar';

// Validasi tipe file (hanya gambar)
$allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$file_type = mime_content_type($_FILES['foto']['tmp_name']);
if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.']);
    exit;
}

// Validasi ukuran file (maks 5MB)
if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar. Maksimal 5MB.']);
    exit;
}

// Buat nama file unik
$ext       = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
$nama_file = 'kamar_' . $id_kamar . '_' . time() . '.' . $ext;
$upload_dir = __DIR__ . '/../public/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$target    = $upload_dir . $nama_file;

// Pindahkan file ke folder uploads
if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
    // 1. Update kolom gambar utama di tabel kamar
    $query = "UPDATE kamar SET gambar = '$nama_file' WHERE id = $id_kamar";
    mysqli_query($koneksi, $query);

    // 2. Simpan juga ke tabel foto_kamar agar otomatis masuk ke slider
    $q_max = "SELECT COALESCE(MAX(urutan), 0) + 1 AS next_urutan FROM foto_kamar WHERE id_kamar = $id_kamar";
    $res_max = mysqli_query($koneksi, $q_max);
    $next_urutan = 1;
    if ($res_max && $row_max = mysqli_fetch_assoc($res_max)) {
        $next_urutan = (int)$row_max['next_urutan'];
    }

    $q_insert = "INSERT INTO foto_kamar (id_kamar, nama_file, keterangan, urutan) 
                 VALUES ($id_kamar, '$nama_file', '$keterangan', $next_urutan)";
    mysqli_query($koneksi, $q_insert);

    echo json_encode([
        'status' => 'success', 
        'message' => 'Foto berhasil diupload dan ditambahkan ke slider kamar!', 
        'file' => $nama_file
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file upload.']);
}
?>
