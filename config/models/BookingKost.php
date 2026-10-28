<?php
/**
 * ============================================================================
 * CLASS: BookingKost (Subclass / Anak)
 * ============================================================================
 * PENERAPAN PRINSIP PBO (OOP):
 * 1. CLASS & OBJECT:
 *    - Merepresentasikan transaksi pemesanan kamar kost konkret.
 *    - Objek dibuat dengan: $booking = new BookingKost($db);
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Atribut khusus ($namaUnit, $kamarModel) bersifat PRIVATE.
 *    - Menggunakan Getter & Setter dengan validasi ketat.
 * 3. INHERITANCE (PEWARISAN):
 *    - Menggunakan keyword 'extends Transaksi'.
 *    - Mewarisi semua properti ($db, $namaPemesan, $durasi, $totalHarga, $metodePembayaran)
 *      dan method dari Superclass Transaksi.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Meng-OVERRIDE method abstract dari Transaksi:
 *      a. hitungTotal()        : Menghitung total biaya: durasi (bulan) x harga kamar.
 *      b. prosesTransaksi()    : Alur spesifik booking kost:
 *                                (Validasi -> Insert DB -> Ubah status kamar ke 'terisi').
 *      c. getStrukTransaksi()  : Menghasilkan struk rincian kamar kost.
 * ============================================================================
 */

require_once __DIR__ . '/Transaksi.php';
require_once __DIR__ . '/Kamar.php';

class BookingKost extends Transaksi {
    // ENKAPSULASI: Properti privat spesifik untuk booking kost
    private string $namaUnit = '';
    private ?Kamar $kamarModel = null;

    /**
     * Konstruktor
     * @param Database $db
     */
    public function __construct(Database $db) {
        parent::__construct($db);
    }

    // ========================================================================
    // ENKAPSULASI: GETTER & SETTER KHUSUS
    // ========================================================================

    public function getNamaUnit(): string {
        return $this->namaUnit;
    }

    public function setNamaUnit(string $namaUnit): void {
        $clean = trim($namaUnit);
        if (empty($clean)) {
            throw new InvalidArgumentException("Nama unit kamar harus dipilih.");
        }
        $this->namaUnit = $clean;
    }

    public function getKamarModel(): ?Kamar {
        if ($this->kamarModel === null && !empty($this->namaUnit)) {
            $kamar = new Kamar($this->db);
            $this->kamarModel = $kamar->getByNama($this->namaUnit);
        }
        return $this->kamarModel;
    }

    // ========================================================================
    // POLIMORFISME: OVERRIDE METHOD ABSTRACT DARI TRANSAKSI
    // ========================================================================

    /**
     * POLIMORFISME: Menghitung total biaya pemesanan kost berdasarkan
     * durasi bulan dikali harga tarif bulanan kamar.
     * @return int
     */
    public function hitungTotal(): int {
        $kamar = $this->getKamarModel();
        if ($kamar) {
            $this->totalHarga = $kamar->getHarga() * $this->durasi;
        }
        return $this->totalHarga;
    }

    /**
     * POLIMORFISME: Memproses alur transaksi booking kamar secara lengkap
     * @return array Respon transaksi
     */
    public function prosesTransaksi(): array {
        try {
            // 1. Validasi keberadaan kamar
            $kamar = $this->getKamarModel();
            if (!$kamar) {
                return [
                    'status'  => 'error',
                    'message' => "Unit kamar '{$this->namaUnit}' tidak ditemukan di sistem."
                ];
            }

            // 2. Validasi status ketersediaan kamar
            if ($kamar->getStatus() === 'terisi') {
                return [
                    'status'  => 'error',
                    'message' => "Maaf, unit {$this->namaUnit} saat ini sudah TERISI (tidak tersedia)."
                ];
            }

            // 3. Hitung atau pastikan total harga
            if ($this->totalHarga <= 0) {
                $this->hitungTotal();
            }

            // Escape data untuk keamanan query
            $unitEsc   = $this->db->escape($this->namaUnit);
            $namaEsc   = $this->db->escape($this->namaPemesan);
            $metodeEsc = $this->db->escape($this->metodePembayaran);
            $durasi    = $this->durasi;
            $total     = $this->totalHarga;

            // 4. Simpan riwayat transaksi ke tabel 'booking'
            $sqlBooking = "INSERT INTO booking (nama_unit, nama_pemesan, durasi, total_harga, metode_pembayaran) 
                           VALUES ('$unitEsc', '$namaEsc', $durasi, $total, '$metodeEsc')";
            
            $simpan = $this->db->query($sqlBooking);
            if (!$simpan) {
                return [
                    'status'  => 'error',
                    'message' => 'Gagal menyimpan transaksi booking: ' . $this->db->getLastError()
                ];
            }

            $this->id = $this->db->getLastInsertId();

            // 5. Ubah status kamar di tabel 'kamar' menjadi 'terisi'
            $kamar->updateStatus('terisi');

            // 6. Kembalikan data sukses
            return [
                'status'  => 'success',
                'message' => 'Pemesanan kamar ' . $this->namaUnit . ' berhasil disimpan!',
                'data'    => $this->getStrukTransaksi()
            ];

        } catch (Exception $e) {
            return [
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ];
        }
    }

    /**
     * POLIMORFISME: Menghasilkan rincian struk transaksi booking
     * @return array
     */
    public function getStrukTransaksi(): array {
        return [
            'id'                => $this->id,
            'unit'              => $this->namaUnit,
            'nama_unit'         => $this->namaUnit,
            'nama'              => $this->namaPemesan,
            'nama_pemesan'      => $this->namaPemesan,
            'durasi'            => $this->durasi,
            'total'             => $this->totalHarga,
            'total_harga'       => $this->totalHarga,
            'metode'            => $this->metodePembayaran,
            'metode_pembayaran' => $this->metodePembayaran,
            'created_at'        => $this->createdAt ?? date('Y-m-d H:i:s')
        ];
    }

    // ========================================================================
    // DOMAIN METHODS
    // ========================================================================

    /**
     * Mengambil seluruh riwayat data booking
     * @return array
     */
    public function getAllBooking(): array {
        $sql = "SELECT * FROM booking ORDER BY id DESC";
        $result = $this->db->query($sql);
        $list = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $list[] = [
                    'id'                => (int)$row['id'],
                    'nama_unit'         => $row['nama_unit'],
                    'nama_pemesan'      => $row['nama_pemesan'],
                    'durasi'            => (int)$row['durasi'],
                    'total_harga'       => (int)$row['total_harga'],
                    'metode_pembayaran' => $row['metode_pembayaran'] ?? 'Tunai / Cash',
                    'created_at'        => $row['created_at']
                ];
            }
        }

        return $list;
    }

    /**
     * Menghapus catatan booking dan mengembalikan status kamar menjadi 'kosong'
     * @param int $idBooking
     * @param bool $kembalikanKamar
     * @return bool
     */
    public function hapusBooking(int $idBooking, bool $kembalikanKamar = true): bool {
        $idBooking = (int)$idBooking;
        $sql = "SELECT nama_unit FROM booking WHERE id = $idBooking LIMIT 1";
        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $unit = $row['nama_unit'];
            $del = $this->db->query("DELETE FROM booking WHERE id = $idBooking");
            if ($del && $kembalikanKamar && !empty($unit)) {
                $unitEsc = $this->db->escape($unit);
                $this->db->query("UPDATE kamar SET status = 'kosong' WHERE nama_kamar = '$unitEsc'");
            }
            return (bool)$del;
        }
        return false;
    }
}
?>

