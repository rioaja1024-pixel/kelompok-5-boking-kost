<?php
/**
 * ============================================================================
 * ABSTRACT CLASS: BaseModel (Superclass / Induk)
 * ============================================================================
 * PENERAPAN PRINSIP PBO (OOP):
 * 1. CLASS:
 *    - Abstract class yang berfungsi sebagai fondasi (kerangka dasar) bagi
 *      semua entitas model yang berinteraksi dengan database.
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Properti dilindungi menggunakan visibilitas PROTECTED ($db, $id, $createdAt)
 *      sehingga hanya dapat diakses oleh class ini dan class turunannya (subclass).
 *    - Getter dan Setter disediakan untuk mengakses serta memodifikasi atribut.
 * 3. INHERITANCE (PEWARISAN):
 *    - Class ini bertindak sebagai Superclass yang akan diwarisi (extends) oleh
 *      class-class model konkret seperti Kamar dan FotoKamar.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Mendefinisikan method-method abstract (simpan, getDetail, toArray)
 *      yang WAJIB di-override oleh setiap class turunan dengan implementasi
 *      yang berbeda-beda sesuai kebutuhan masing-masing entitas.
 * ============================================================================
 */

abstract class BaseModel {
    // ENKAPSULASI: Properti protected hanya dapat diakses oleh class ini dan subclass-nya
    protected Database $db;
    protected ?int $id = null;
    protected ?string $createdAt = null;

    /**
     * Konstruktor: Menerima objek Database melalui Dependency Injection
     * @param Database $db
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }

    // ========================================================================
    // ENKAPSULASI: GETTER & SETTER
    // ========================================================================

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(?int $id): void {
        $this->id = $id !== null ? (int)$id : null;
    }

    public function getCreatedAt(): ?string {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): void {
        $this->createdAt = $createdAt;
    }

    /**
     * Mendapatkan helper Database
     * @return Database
     */
    public function getDb(): Database {
        return $this->db;
    }

    // ========================================================================
    // POLIMORFISME (ABSTRACT METHODS)
    // Setiap class turunan wajib mengimplementasikan method-method ini
    // dengan perilaku unik sesuai entitasnya masing-masing.
    // ========================================================================

    /**
     * POLIMORFISME: Menyimpan data entitas ke database (Insert / Update)
     * @return bool
     */
    abstract public function simpan(): bool;

    /**
     * POLIMORFISME: Mendapatkan rincian detail lengkap dari entitas
     * @return array
     */
    abstract public function getDetail(): array;

    /**
     * POLIMORFISME: Mengonversi objek entitas ke format array asosiatif (siap JSON)
     * @return array
     */
    abstract public function toArray(): array;
}
?>

