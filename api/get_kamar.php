<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../config/koneksi.php';

$query = "SELECT * FROM kamar ORDER BY id ASC";
$result = mysqli_query($koneksi, $query);

$kamar_list = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id_kamar = (int)$row['id'];

        // Ambil daftar foto untuk kamar ini dari tabel foto_kamar
        $fotos = [];
        $q_foto = "SELECT id, nama_file, keterangan, urutan FROM foto_kamar WHERE id_kamar = $id_kamar ORDER BY urutan ASC, id ASC";
        $res_foto = mysqli_query($koneksi, $q_foto);
        if ($res_foto) {
            while ($f = mysqli_fetch_assoc($res_foto)) {
                $fotos[] = [
                    'id' => (int)$f['id'],
                    'file' => $f['nama_file'],
                    'keterangan' => $f['keterangan'] ? $f['keterangan'] : 'Foto Kamar'
                ];
            }
        }

        // Jika di tabel foto_kamar belum ada, gunakan foto cover utama dari kolom 'gambar'
        if (empty($fotos) && !empty($row['gambar'])) {
            $fotos[] = [
                'id' => 0,
                'file' => $row['gambar'],
                'keterangan' => 'Foto Utama'
            ];
        }

        $row['fotos'] = $fotos;
        $kamar_list[] = $row;
    }
}

echo json_encode($kamar_list);
?>