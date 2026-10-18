<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/koneksi.php';

$query = "SELECT * FROM kamar";
$result = mysqli_query($koneksi, $query);

$kamar_list = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $kamar_list[] = $row;
    }
}

echo json_encode($kamar_list);
?>