<?php
/**
 * ============================================================================
 * CLASS: Kamar
 * ============================================================================
 * PENERAPAN PRINSIP PBO (OOP):
 * 1. CLASS & OBJECT:
 *    - Merepresentasikan entitas unit Kamar Kost.
 *    - Objek dibuat dengan: $kamar = new Kamar($db);
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Atribut-atribut utama ($namaKamar, $harga, $gambar, $status, $fotos)
 *      dideklarasikan PRIVATE untuk mencegah manipulasi data secara langsung.
 *    - Akses data menggunakan metode GETTER dan SETTER.
 *    - Setter melakukan validasi data (misal: harga tidak boleh negatif,
 *      status hanya boleh 'kosong' atau 'terisi').
 * 3. INHERITANCE (PEWARISAN):
 *    - Menggunakan keyword 'extends BaseModel'.
 *    - Mewarisi properti $db, $id, $createdAt serta method-method dari BaseModel.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Meng-OVERRIDE method abstract dari BaseModel:
 *      a. simpan()    : Implementasi penyimpanan data kamar ke tabel 'kamar'.
 *      b. getDetail() : Implementasi pengembalian rincian kamar beserta foto slider.
 *      c. toArray()   : Mengubah objek kamar menjadi array representasi data.
 * ============================================================================
 */

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/FotoKamar.php';

class Kamar extends BaseModel {
    // ENKAPSULASI: Properti privat hanya dapat diakses melalui Getter & Setter
    private string $namaKamar = '';
    private int $harga        = 0;
    private ?string $gambar   = null;
    private string $status    = 'kosong'; // 'kosong' | 'terisi'
    private array $fotos      = [];

    /**
     * Konstruktor
     * @param Database $db
     */
    public function __construct(Database $db) {
        parent::__construct($db);
    }

    // ========================================================================
    // ENKAPSULASI: GETTER & SETTER DENGAN VALIDASI DATA
    // ========================================================================

    public function getNamaKamar(): string {
        return $this->namaKamar;
    }

    public function setNamaKamar(string $namaKamar): void {
        $this->namaKamar = trim($namaKamar);
    }

    public function getHarga(): int {
        return $this->harga;
    }

    /**
     * Setter harga dengan validasi PBO: Harga tidak boleh negatif
     */
    public function setHarga(int $harga): void {
        if ($harga < 0) {
            throw new InvalidArgumentException("Harga kamar tidak boleh negatif.");
        }
        $this->harga = $harga;
    }

    public function getGambar(): ?string {
        return $this->gambar;
    }

    public function setGambar(?string $gambar): void {
        $this->gambar = $gambar ? trim($gambar) : null;
    }

    public function getStatus(): string {
        return $this->status;
    }

    /**
     * Setter status dengan validasi PBO: Hanya menerima status 'kosong' atau 'terisi'
     */
    public function setStatus(string $status): void {
        $statusClean = strtolower(trim($status));
        if (!in_array($statusClean, ['kosong', 'terisi'])) {
            throw new InvalidArgumentException("Status kamar harus 'kosong' atau 'terisi'.");
        }
        $this->status = $statusClean;
    }

    public function getFotos(): array {
        return $this->fotos;
    }

    public function setFotos(array $fotos): void {
        $this->fotos = $fotos;
    }

    // ========================================================================
    // POLIMORFISME: IMPLEMENTASI & OVERRIDE METHOD DARI BASEMODEL
    // ========================================================================

    /**
     * POLIMORFISME: Menyimpan atau memperbarui data kamar di database
     * @return bool
     */
    public function simpan(): bool {
        $nama   = $this->db->escape($this->namaKamar);
        $gambar = $this->gambar ? "'" . $this->db->escape($this->gambar) . "'" : "NULL";
        $status = $this->db->escape($this->status);

        if ($this->id !== null) {
            // Update kamar yang sudah ada
            $sql = "UPDATE kamar SET 
                        nama_kamar = '$nama', 
                        harga = {$this->harga}, 
                        gambar = $gambar, 
                        status = '$status' 
                    WHERE id = {$this->id}";
            return (bool)$this->db->query($sql);
        } else {
            // Insert kamar baru
            $sql = "INSERT INTO kamar (nama_kamar, harga, gambar, status) 
                    VALUES ('$nama', {$this->harga}, $gambar, '$status')";
            $result = $this->db->query($sql);
            if ($result) {
                $this->id = $this->db->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * POLIMORFISME: Mengembalikan data detail kamar lengkap beserta foto slider
     * @return array
     */
    public function getDetail(): array {
        // Muat foto relasi jika belum dimuat
        if (empty($this->fotos) && $this->id !== null) {
            $this->muatFotos();
        }

        return [
            'id'         => (string)$this->id,
            'nama_kamar' => $this->namaKamar,
            'harga'      => (string)$this->harga,
            'gambar'     => $this->gambar ?? '',
            'status'     => $this->status,
            'fotos'      => $this->fotos
        ];
    }

    /**
     * POLIMORFISME: Mengonversi objek kamar menjadi array representasi
     * @return array
     */
    public function toArray(): array {
        return $this->getDetail();
    }

    // ========================================================================
    // DOMAIN & REPOSITORY METHODS
    // ========================================================================

    /**
     * Memetakan data baris database ke atribut objek Kamar
     * @param array $row
     */
    private function hydrate(array $row): void {
        $this->setId((int)$row['id']);
        $this->setNamaKamar($row['nama_kamar']);
        $this->setHarga((int)$row['harga']);
        $this->setGambar($row['gambar'] ?? null);
        $this->setStatus($row['status'] ?? 'kosong');
        $this->muatFotos();
    }

    /**
     * Mengambil semua data kamar dari database beserta relasi foto
     * @return array Array of associative arrays
     */
    public function getAll(): array {
        $sql = "SELECT * FROM kamar ORDER BY id ASC";
        $result = $this->db->query($sql);
        $list = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $kamar = new Kamar($this->db);
                $kamar->hydrate($row);
                $list[] = $kamar->toArray();
            }
        }

        return $list;
    }

    /**
     * Mencari kamar berdasarkan nama
     * @param string $nama
     * @return Kamar|null
     */
    public function getByNama(string $nama): ?Kamar {
        $namaEsc = $this->db->escape($nama);
        $sql = "SELECT * FROM kamar WHERE nama_kamar = '$namaEsc' LIMIT 1";
        $result = $this->db->query($sql);

        if ($result && $row = $result->fetch_assoc()) {
            $this->hydrate($row);
            return $this;
        }

        return null;
    }

    /**
     * Mencari kamar berdasarkan ID
     * @param int $id
     * @return Kamar|null
     */
    public function getById(int $id): ?Kamar {
        $sql = "SELECT * FROM kamar WHERE id = $id LIMIT 1";
        $result = $this->db->query($sql);

        if ($result && $row = $result->fetch_assoc()) {
            $this->hydrate($row);
            return $this;
        }

        return null;
    }

    /**
     * Mengupdate status kamar
     * @param string $status 'kosong' | 'terisi'
     * @return bool
     */
    public function updateStatus(string $status): bool {
        $this->setStatus($status);
        if ($this->id !== null) {
            $statusEsc = $this->db->escape($this->status);
            $sql = "UPDATE kamar SET status = '$statusEsc' WHERE id = {$this->id}";
            return (bool)$this->db->query($sql);
        }
        return false;
    }

    /**
     * Mengupdate harga sewa kamar
     * @param int $harga
     * @return bool
     */
    public function updateHarga(int $harga): bool {
        $this->setHarga($harga);
        if ($this->id !== null) {
            $sql = "UPDATE kamar SET harga = {$this->harga} WHERE id = {$this->id}";
            return (bool)$this->db->query($sql);
        }
        return false;
    }

    /**
     * Mereset status semua kamar menjadi 'kosong'
     * @return bool
     */
    public function resetSemuaKamar(): bool {
        return (bool)$this->db->query("UPDATE kamar SET status = 'kosong'");
    }

    /**
     * Memuat daftar foto dari tabel foto_kamar
     * @return array
     */
    public function muatFotos(): array {
        if ($this->id === null) {
            return [];
        }

        $fotoModel = new FotoKamar($this->db);
        $this->fotos = $fotoModel->getByKamarId($this->id);

        // Jika foto di tabel foto_kamar kosong tetapi ada cover di kolom gambar, gunakan sebagai foto utama
        if (empty($this->fotos) && !empty($this->gambar)) {
            $this->fotos[] = [
                'id'         => 0,
                'file'       => $this->gambar,
                'keterangan' => 'Foto Utama'
            ];
        }

        return $this->fotos;
    }
}
?>

