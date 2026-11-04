<?php
/**
 * ============================================================================
 * CLASS: Pemesanan
 * ============================================================================
 * PENERAPAN PRINSIP PBO (OOP):
 * 1. CLASS & OBJECT:
 *    - Merepresentasikan entitas Pemesanan (Booking) Kamar Kost secara lengkap.
 *    - Berkolaborasi dengan objek Kamar dan objek Payment (Komposisi / Agregasi).
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Atribut-atribut ($namaPemesan, $namaUnit, $durasi, $totalHarga, $payment,
 *      $metodePembayaran, $statusPemesanan) bersifat PRIVATE.
 *    - Dilengkapi Getter dan Setter dengan validasi ketat logika bisnis
 *      (misal: nama tidak boleh kosong, durasi minimal 1 bulan, total harga valid).
 * 3. INHERITANCE (PEWARISAN):
 *    - Menggunakan keyword 'extends BaseModel' untuk mewarisi properti $db, $id,
 *      dan method dasar dari Superclass BaseModel.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Meng-OVERRIDE method abstract dari BaseModel:
 *      a. simpan()    : Menyimpan data pemesanan ke tabel MySQL 'booking'.
 *      b. getDetail() : Mengembalikan rincian struk lengkap pemesanan & info pembayaran.
 *      c. toArray()   : Mengonversi data pemesanan ke array representatif.
 * ============================================================================
 */

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Kamar.php';
require_once __DIR__ . '/Payment.php';

class Pemesanan extends BaseModel {
    // ENKAPSULASI: Properti privat yang terlindungi dari manipulasi luar
    private string $namaPemesan       = '';
    private string $namaUnit          = '';
    private ?Kamar $kamar             = null;
    private int $durasi               = 1;
    private string $tipeDurasi        = 'bulanan'; // 'harian' atau 'bulanan'
    private int $totalHarga           = 0;
    private ?Payment $payment         = null;
    private string $metodePembayaran  = 'Tunai / Cash';
    private string $statusPemesanan   = 'sukses';

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

    public function getNamaPemesan(): string {
        return $this->namaPemesan;
    }

    /**
     * Setter nama pemesan dengan validasi data
     */
    public function setNamaPemesan(string $namaPemesan): void {
        $clean = trim($namaPemesan);
        if (empty($clean)) {
            throw new InvalidArgumentException("Nama lengkap pemesan wajib diisi.");
        }
        $this->namaPemesan = $clean;
    }

    public function getNamaUnit(): string {
        return $this->namaUnit;
    }

    /**
     * Setter nama unit kamar
     */
    public function setNamaUnit(string $namaUnit): void {
        $clean = trim($namaUnit);
        if (empty($clean)) {
            throw new InvalidArgumentException("Unit kamar kost wajib dipilih.");
        }
        $this->namaUnit = $clean;
    }

    /**
     * Mendapatkan objek Kamar (Asosiasi Antar Objek)
     */
    public function getKamar(): ?Kamar {
        if ($this->kamar === null && !empty($this->namaUnit)) {
            $kamarModel = new Kamar($this->db);
            $this->kamar = $kamarModel->getByNama($this->namaUnit);
        }
        return $this->kamar;
    }

    public function setKamar(Kamar $kamar): void {
        $this->kamar = $kamar;
        $this->namaUnit = $kamar->getNamaKamar();
    }

    public function getDurasi(): int {
        return $this->durasi;
    }

    /**
     * Setter durasi sewa dengan validasi (minimal 1)
     */
    public function setDurasi(int $durasi): void {
        if ($durasi < 1) {
            throw new InvalidArgumentException("Durasi sewa minimal adalah 1.");
        }
        $this->durasi = $durasi;
    }

    public function getTipeDurasi(): string {
        return $this->tipeDurasi;
    }

    /**
     * Setter tipe durasi: 'harian' atau 'bulanan'
     */
    public function setTipeDurasi(string $tipe): void {
        $clean = strtolower(trim($tipe));
        $this->tipeDurasi = in_array($clean, ['harian', 'bulanan']) ? $clean : 'bulanan';
    }

    public function getTotalHarga(): int {
        return $this->totalHarga;
    }

    public function setTotalHarga(int $totalHarga): void {
        if ($totalHarga < 0) {
            throw new InvalidArgumentException("Total harga tidak boleh bernilai negatif.");
        }
        $this->totalHarga = $totalHarga;
    }

    public function getPayment(): ?Payment {
        return $this->payment;
    }

    /**
     * Setter objek Payment (Polimorfisme Pembayaran)
     */
    public function setPayment(Payment $payment): void {
        $this->payment = $payment;
        $this->metodePembayaran = $payment->getMetode();
    }

    public function getMetodePembayaran(): string {
        return $this->metodePembayaran;
    }

    public function setMetodePembayaran(string $metode): void {
        $clean = trim($metode);
        $this->metodePembayaran = !empty($clean) ? $clean : 'Tunai / Cash';
        // Buat objek Payment polimorfik jika belum ada
        if ($this->payment === null && $this->totalHarga >= 0) {
            $this->payment = PaymentFactory::create($this->metodePembayaran, $this->totalHarga);
        }
    }

    public function getStatusPemesanan(): string {
        return $this->statusPemesanan;
    }

    public function setStatusPemesanan(string $status): void {
        $this->statusPemesanan = $status;
    }

    // ========================================================================
    // POLIMORFISME: OVERRIDE METHOD ABSTRACT DARI BASEMODEL
    // ========================================================================

    /**
     * POLIMORFISME: Menyimpan transaksi pemesanan ke tabel 'booking' di MySQL
     * @return bool
     */
    public function simpan(): bool {
        $namaEsc   = $this->db->escape($this->namaPemesan);
        $unitEsc   = $this->db->escape($this->namaUnit);
        $metodeEsc = $this->db->escape($this->metodePembayaran);
        $tipeEsc   = $this->db->escape($this->tipeDurasi);
        $durasi    = $this->durasi;
        $total     = $this->totalHarga;

        $sql = "INSERT INTO booking (nama_unit, nama_pemesan, durasi, tipe_durasi, total_harga, metode_pembayaran) 
                VALUES ('$unitEsc', '$namaEsc', $durasi, '$tipeEsc', $total, '$metodeEsc')";
        
        $result = $this->db->query($sql);
        if ($result) {
            $this->id = $this->db->getLastInsertId();
            $this->createdAt = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    /**
     * POLIMORFISME: Rincian detail pemesanan lengkap beserta info pembayaran
     * @return array
     */
    public function getDetail(): array {
        $paymentInfo = $this->payment ? $this->payment->processPayment() : null;

        return [
            'id'                => $this->id,
            'unit'              => $this->namaUnit,
            'nama_unit'         => $this->namaUnit,
            'nama'              => $this->namaPemesan,
            'nama_pemesan'      => $this->namaPemesan,
            'durasi'            => $this->durasi,
            'tipe_durasi'       => $this->tipeDurasi,
            'total'             => $this->totalHarga,
            'total_harga'       => $this->totalHarga,
            'metode'            => $this->metodePembayaran,
            'metode_pembayaran' => $this->metodePembayaran,
            'status_pemesanan'  => $this->statusPemesanan,
            'payment_detail'    => $paymentInfo,
            'created_at'        => $this->createdAt ?? date('Y-m-d H:i:s')
        ];
    }

    /**
     * POLIMORFISME: Array representation untuk respon JSON frontend
     * @return array
     */
    public function toArray(): array {
        return $this->getDetail();
    }

    // ========================================================================
    // DOMAIN & TRANSACTION METHODS
    // ========================================================================

    /**
     * Menghitung total harga berdasarkan tipe durasi (harian/bulanan)
     * @return int
     */
    public function hitungTotal(): int {
        $kamar = $this->getKamar();
        if ($kamar) {
            $hargaBulan = $kamar->getHarga();
            if ($this->tipeDurasi === 'harian') {
                $hargaHari = (int)ceil($hargaBulan / 30);
                $this->totalHarga = $hargaHari * $this->durasi;
            } else {
                $this->totalHarga = $hargaBulan * $this->durasi;
            }

            if ($this->payment) {
                $this->payment->setAmount($this->totalHarga);
            }
        }
        return $this->totalHarga;
    }

    /**
     * Memproses alur pemesanan secara menyeluruh:
     * 1. Validasi ketersediaan kamar
     * 2. Hitung total biaya
     * 3. Proses payment polimorfik
     * 4. Simpan ke database MySQL
     * 5. Ubah status kamar menjadi 'terisi'
     * @return array
     */
    public function prosesPemesanan(): array {
        try {
            // 1. Cek unit kamar
            $kamar = $this->getKamar();
            if (!$kamar) {
                return [
                    'status'  => 'error',
                    'message' => "Unit kamar '{$this->namaUnit}' tidak ditemukan di sistem."
                ];
            }

            // 2. Cek ketersediaan kamar
            if ($kamar->getStatus() === 'terisi') {
                return [
                    'status'  => 'error',
                    'message' => "Maaf, unit {$this->namaUnit} saat ini sudah TERISI (tidak tersedia)."
                ];
            }

            // 3. Pastikan total harga dihitung
            if ($this->totalHarga <= 0) {
                $this->hitungTotal();
            }

            // 4. Pastikan objek Payment diinisialisasi
            if ($this->payment === null) {
                $this->payment = PaymentFactory::create($this->metodePembayaran, $this->totalHarga);
            }

            // 5. Simpan ke tabel booking
            if (!$this->simpan()) {
                return [
                    'status'  => 'error',
                    'message' => 'Gagal mencatat pemesanan ke database: ' . $this->db->getLastError()
                ];
            }

            // 6. Update status kamar di MySQL menjadi 'terisi'
            $kamar->updateStatus('terisi');

            return [
                'status'  => 'success',
                'message' => 'Pemesanan kamar ' . $this->namaUnit . ' berhasil disimpan!',
                'data'    => $this->getDetail()
            ];

        } catch (Exception $e) {
            return [
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mengambil seluruh riwayat pemesanan dari database MySQL
     * @return array
     */
    public function getAllPemesanan(): array {
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
                    'tipe_durasi'       => $row['tipe_durasi'] ?? 'bulanan',
                    'total_harga'       => (int)$row['total_harga'],
                    'metode_pembayaran' => $row['metode_pembayaran'] ?? 'Tunai / Cash',
                    'created_at'        => $row['created_at']
                ];
            }
        }

        return $list;
    }

    /**
     * Membatalkan atau menghapus pemesanan serta mengembalikan unit kamar jadi kosong
     * @param int $idPemesanan
     * @param bool $kembalikanKamar
     * @return bool
     */
    public function batalkanPemesanan(int $idPemesanan, bool $kembalikanKamar = true): bool {
        $idPemesanan = (int)$idPemesanan;
        $sql = "SELECT nama_unit FROM booking WHERE id = $idPemesanan LIMIT 1";
        $res = $this->db->query($sql);

        if ($res && $row = $res->fetch_assoc()) {
            $unit = $row['nama_unit'];
            $del = $this->db->query("DELETE FROM booking WHERE id = $idPemesanan");
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

