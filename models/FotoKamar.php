<?php
/**
 * ============================================================================
 * CLASS: FotoKamar
 * ============================================================================
 * PENERAPAN PRINSIP PBO (OOP):
 * 1. CLASS & OBJECT:
 *    - Merepresentasikan entitas foto-foto sudut kamar (slider foto kamar).
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Properti ($idKamar, $namaFile, $keterangan, $urutan) bersifat PRIVATE.
 *    - Akses & modifikasi melalui Getter dan Setter dengan validasi ketat
 *      (misal: validasi tipe file gambar dan ukuran maksimal file saat upload).
 * 3. INHERITANCE (PEWARISAN):
 *    - Menggunakan keyword 'extends BaseModel' untuk mewarisi properti $db, $id,
 *      dan method dasar dari Superclass BaseModel.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Meng-OVERRIDE method abstract dari BaseModel (simpan, getDetail, toArray)
 *      sesuai karakteristik entitas foto.
 * ============================================================================
 */

require_once __DIR__ . '/BaseModel.php';

class FotoKamar extends BaseModel {
    // ENKAPSULASI: Properti privat
    private int $idKamar      = 0;
    private string $namaFile   = '';
    private string $keterangan = 'Foto Kamar';
    private int $urutan        = 0;

    /**
     * Konstruktor
     * @param Database $db
     */
    public function __construct(Database $db) {
        parent::__construct($db);
    }

    // ========================================================================
    // ENKAPSULASI: GETTER & SETTER
    // ========================================================================

    public function getIdKamar(): int {
        return $this->idKamar;
    }

    public function setIdKamar(int $idKamar): void {
        if ($idKamar <= 0) {
            throw new InvalidArgumentException("ID Kamar harus lebih besar dari 0.");
        }
        $this->idKamar = $idKamar;
    }

    public function getNamaFile(): string {
        return $this->namaFile;
    }

    public function setNamaFile(string $namaFile): void {
        $this->namaFile = trim($namaFile);
    }

    public function getKeterangan(): string {
        return $this->keterangan;
    }

    public function setKeterangan(string $keterangan): void {
        $clean = trim($keterangan);
        $this->keterangan = !empty($clean) ? $clean : 'Foto Kamar';
    }

    public function getUrutan(): int {
        return $this->urutan;
    }

    public function setUrutan(int $urutan): void {
        $this->urutan = max(0, $urutan);
    }

    // ========================================================================
    // POLIMORFISME: OVERRIDE METHOD ABSTRACT DARI BASEMODEL
    // ========================================================================

    /**
     * POLIMORFISME: Menyimpan foto ke tabel foto_kamar
     * @return bool
     */
    public function simpan(): bool {
        if ($this->idKamar <= 0 || empty($this->namaFile)) {
            return false;
        }

        $idKamar    = $this->idKamar;
        $namaFile   = $this->db->escape($this->namaFile);
        $keterangan = $this->db->escape($this->keterangan);
        $urutan     = $this->urutan;

        if ($this->id !== null) {
            $sql = "UPDATE foto_kamar SET 
                        id_kamar = $idKamar, 
                        nama_file = '$namaFile', 
                        keterangan = '$keterangan', 
                        urutan = $urutan 
                    WHERE id = {$this->id}";
            return (bool)$this->db->query($sql);
        } else {
            $sql = "INSERT INTO foto_kamar (id_kamar, nama_file, keterangan, urutan) 
                    VALUES ($idKamar, '$namaFile', '$keterangan', $urutan)";
            $result = $this->db->query($sql);
            if ($result) {
                $this->id = $this->db->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * POLIMORFISME: Rincian detail foto
     * @return array
     */
    public function getDetail(): array {
        return [
            'id'         => $this->id ?? 0,
            'id_kamar'   => $this->idKamar,
            'file'       => $this->namaFile,
            'keterangan' => $this->keterangan,
            'urutan'     => $this->urutan
        ];
    }

    /**
     * POLIMORFISME: Array representation untuk JSON
     * @return array
     */
    public function toArray(): array {
        return [
            'id'         => (int)($this->id ?? 0),
            'file'       => $this->namaFile,
            'keterangan' => $this->keterangan
        ];
    }

    // ========================================================================
    // DOMAIN METHODS
    // ========================================================================

    /**
     * Mengambil semua foto untuk kamar tertentu
     * @param int $idKamar
     * @return array
     */
    public function getByKamarId(int $idKamar): array {
        $sql = "SELECT id, nama_file, keterangan, urutan 
                FROM foto_kamar 
                WHERE id_kamar = $idKamar 
                ORDER BY urutan ASC, id ASC";
        $result = $this->db->query($sql);
        $fotos = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $foto = new FotoKamar($this->db);
                $foto->setId((int)$row['id']);
                $foto->setIdKamar($idKamar);
                $foto->setNamaFile($row['nama_file']);
                $foto->setKeterangan($row['keterangan'] ?? 'Foto Kamar');
                $foto->setUrutan((int)($row['urutan'] ?? 0));

                $fotos[] = $foto->toArray();
            }
        }

        return $fotos;
    }

    /**
     * Menghitung nomor urutan foto berikutnya
     * @param int $idKamar
     * @return int
     */
    public function getNextUrutan(int $idKamar): int {
        $sql = "SELECT COALESCE(MAX(urutan), 0) + 1 AS next_urutan 
                FROM foto_kamar 
                WHERE id_kamar = $idKamar";
        $result = $this->db->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['next_urutan'];
        }
        return 1;
    }

    /**
     * Memproses upload file fisik gambar dan menyimpannya ke database
     * @param array $fileData Array $_FILES['foto']
     * @param int $idKamar
     * @param string $keterangan
     * @param string $uploadDir
     * @return array ['success' => bool, 'message' => string, 'file' => string|null]
     */
    public function prosesUpload(array $fileData, int $idKamar, string $keterangan, string $uploadDir): array {
        // Validasi file
        if (!isset($fileData['tmp_name']) || $fileData['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File tidak valid atau terjadi error upload.'];
        }

        // Validasi tipe file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $fileMime = mime_content_type($fileData['tmp_name']);
        if (!in_array($fileMime, $allowedTypes)) {
            return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.'];
        }

        // Validasi ukuran (maks 5MB)
        if ($fileData['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 5MB.'];
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $namaFile = 'kamar_' . $idKamar . '_' . time() . '.' . $ext;
        $target = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $namaFile;

        if (move_uploaded_file($fileData['tmp_name'], $target)) {
            $this->setIdKamar($idKamar);
            $this->setNamaFile($namaFile);
            $this->setKeterangan($keterangan);
            $this->setUrutan($this->getNextUrutan($idKamar));

            if ($this->simpan()) {
                return [
                    'success' => true,
                    'message' => 'Foto berhasil diunggah dan disimpan ke slider kamar!',
                    'file'    => $namaFile
                ];
            }
        }

        return ['success' => false, 'message' => 'Gagal memindahkan file yang diunggah.'];
    }

    /**
     * Menghapus foto dari database dan file fisik di uploads
     * @param int $idFoto
     * @param string $uploadDir
     * @return bool
     */
    public function hapusFoto(int $idFoto, string $uploadDir): bool {
        $idFoto = (int)$idFoto;
        $sql = "SELECT nama_file FROM foto_kamar WHERE id = $idFoto LIMIT 1";
        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $file = $row['nama_file'];
            $del = $this->db->query("DELETE FROM foto_kamar WHERE id = $idFoto");
            if ($del) {
                $target = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $file;
                if (!empty($file) && file_exists($target)) {
                    @unlink($target);
                }
                return true;
            }
        }
        return false;
    }
}
?>

