<?php
/**
 * ============================================================================
 * ABSTRACT CLASS: Transaksi (Superclass / Induk)
 * ============================================================================
 * PENERAPAN PRINSIP PBO (OOP):
 * 1. CLASS:
 *    - Abstract class sebagai blueprint umum untuk semua jenis transaksi keuangan/pemesanan.
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Properti transaksi dilindungi dengan visibilitas PROTECTED ($id, $namaPemesan,
 *      $durasi, $totalHarga, $metodePembayaran, $createdAt).
 *    - Dilengkapi Getter dan Setter dengan aturan validasi logika bisnis
 *      (misal: durasi minimal 1, total harga tidak boleh negatif).
 * 3. INHERITANCE (PEWARISAN):
 *    - Diturunkan (extends) oleh subclass spesifik, contohnya: BookingKost.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Method abstract:
 *      a. hitungTotal()        : Setiap jenis transaksi memiliki formula kalkulasi tarif sendiri.
 *      b. prosesTransaksi()    : Alur eksekusi transaksi yang disesuaikan pada subclass.
 *      c. getStrukTransaksi()  : Format struk / invoice yang polimorfik.
 * ============================================================================
 */

abstract class Transaksi {
    // ENKAPSULASI: Properti protected
    protected Database $db;
    protected ?int $id                = null;
    protected string $namaPemesan      = '';
    protected int $durasi             = 1;
    protected int $totalHarga         = 0;
    protected string $metodePembayaran = 'Tunai / Cash';
    protected ?string $createdAt      = null;

    /**
     * Konstruktor
     * @param Database $db
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    // ========================================================================
    // ENKAPSULASI: GETTER & SETTER DENGAN VALIDASI
    // ========================================================================

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(?int $id): void {
        $this->id = $id !== null ? (int)$id : null;
    }

    public function getNamaPemesan(): string {
        return $this->namaPemesan;
    }

    public function setNamaPemesan(string $namaPemesan): void {
        $clean = trim($namaPemesan);
        if (empty($clean)) {
            throw new InvalidArgumentException("Nama pemesan tidak boleh kosong.");
        }
        $this->namaPemesan = $clean;
    }

    public function getDurasi(): int {
        return $this->durasi;
    }

    /**
     * Setter durasi dengan validasi PBO: minimal 1 bulan
     */
    public function setDurasi(int $durasi): void {
        if ($durasi < 1) {
            throw new InvalidArgumentException("Durasi sewa minimal adalah 1 bulan.");
        }
        $this->durasi = $durasi;
    }

    public function getTotalHarga(): int {
        return $this->totalHarga;
    }

    /**
     * Setter total harga dengan validasi PBO
     */
    public function setTotalHarga(int $totalHarga): void {
        if ($totalHarga < 0) {
            throw new InvalidArgumentException("Total harga tidak boleh bernilai negatif.");
        }
        $this->totalHarga = $totalHarga;
    }

    public function getMetodePembayaran(): string {
        return $this->metodePembayaran;
    }

    public function setMetodePembayaran(string $metode): void {
        $clean = trim($metode);
        $this->metodePembayaran = !empty($clean) ? $clean : 'Tunai / Cash';
    }

    public function getCreatedAt(): ?string {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): void {
        $this->createdAt = $createdAt;
    }

    // ========================================================================
    // POLIMORFISME (ABSTRACT METHODS)
    // ========================================================================

    /**
     * POLIMORFISME: Menghitung total harga transaksi
     * @return int
     */
    abstract public function hitungTotal(): int;

    /**
     * POLIMORFISME: Memproses transaksi secara penuh (validasi, insert DB, update state)
     * @return array Respon transaksi terstruktur
     */
    abstract public function prosesTransaksi(): array;

    /**
     * POLIMORFISME: Menghasilkan struk atau ringkasan transaksi
     * @return array
     */
    abstract public function getStrukTransaksi(): array;
}
?>

