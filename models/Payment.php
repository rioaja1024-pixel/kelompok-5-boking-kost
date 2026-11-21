<?php
/**
 * ============================================================================
 * CLASS: Payment & Subclasses (Modul Pembayaran)
 * ============================================================================
 * PENERAPAN PRINSIP PBO (OOP):
 * 1. CLASS & OBJECT:
 *    - Abstract class Payment sebagai cetak biru sistem pembayaran.
 *    - Subclass konkret: CashPayment, QrisPayment, BankTransferPayment, EWalletPayment.
 *    - Factory class: PaymentFactory untuk instansiasi objek pembayaran dinamis.
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Properti dilindungi dengan visibilitas PROTECTED ($amount, $status, $transactionCode, $metode).
 *    - Akses atribut melalui method Getter & Setter dengan validasi nilai nominal.
 * 3. INHERITANCE (PEWARISAN):
 *    - Setiap jenis pembayaran (Cash, QRIS, Transfer, E-Wallet) mewarisi (extends)
 *      properti dan method dari superclass Payment.
 * 4. POLYMORPHISM (POLIMORFISME):
 *    - Setiap subclass meng-OVERRIDE method abstract:
 *      a. processPayment()           : Alur pemrosesan spesifik tiap saluran bayar.
 *      b. getPaymentInstructions()   : Petunjuk pembayaran yang berbeda sesuai jenisnya.
 * ============================================================================
 */

abstract class Payment {
    // ENKAPSULASI: Properti protected hanya dapat diakses oleh class ini & class turunannya
    protected int $amount = 0;
    protected string $status = 'pending'; // 'pending' | 'success' | 'failed'
    protected string $metode = '';
    protected ?string $transactionCode = null;

    /**
     * Konstruktor
     * @param int $amount
     * @param string $metode
     */
    public function __construct(int $amount, string $metode) {
        $this->setAmount($amount);
        $this->metode = $metode;
        $this->generateTransactionCode();
    }

    // ========================================================================
    // ENKAPSULASI: GETTER & SETTER DENGAN VALIDASI DATA
    // ========================================================================

    public function getAmount(): int {
        return $this->amount;
    }

    /**
     * Setter amount dengan validasi PBO: nominal tidak boleh negatif
     */
    public function setAmount(int $amount): void {
        if ($amount < 0) {
            throw new InvalidArgumentException("Nominal pembayaran tidak boleh bernilai negatif.");
        }
        $this->amount = $amount;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function setStatus(string $status): void {
        $clean = strtolower(trim($status));
        if (!in_array($clean, ['pending', 'success', 'failed'])) {
            throw new InvalidArgumentException("Status pembayaran harus pending, success, atau failed.");
        }
        $this->status = $clean;
    }

    public function getMetode(): string {
        return $this->metode;
    }

    public function getTransactionCode(): string {
        if ($this->transactionCode === null) {
            $this->generateTransactionCode();
        }
        return $this->transactionCode;
    }

    /**
     * Helper privat enkapsulasi: Membuat kode referensi transaksi unik
     */
    private function generateTransactionCode(): void {
        $this->transactionCode = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    // ========================================================================
    // POLIMORFISME (ABSTRACT METHODS)
    // ========================================================================

    /**
     * POLIMORFISME: Menjalankan eksekusi pembayaran sesuai metode
     * @return array
     */
    abstract public function processPayment(): array;

    /**
     * POLIMORFISME: Memberikan petunjuk langkah pembayaran kepada pengguna
     * @return string
     */
    abstract public function getPaymentInstructions(): string;
}

// ============================================================================
// SUBCLASS 1: CashPayment (Pewarisan dari Payment)
// ============================================================================
class CashPayment extends Payment {
    public function __construct(int $amount) {
        parent::__construct($amount, 'Tunai / Cash');
    }

    /**
     * POLIMORFISME: Implementasi spesifik pembayaran tunai
     */
    public function processPayment(): array {
        $this->status = 'pending'; // Dibayar langsung saat serah terima kunci
        return [
            'status'        => 'success',
            'metode'        => $this->metode,
            'kode_bayar'    => $this->getTransactionCode(),
            'instruksi'     => $this->getPaymentInstructions(),
            'status_bayar'  => $this->status
        ];
    }

    public function getPaymentInstructions(): string {
        return "Silakan siapkan uang tunai pas sebesar Rp " . number_format($this->amount, 0, ',', '.') . 
               " untuk diserahkan langsung kepada pemilik kost saat serah terima kunci kamar.";
    }
}

// ============================================================================
// SUBCLASS 2: QrisPayment (Pewarisan dari Payment)
// ============================================================================
class QrisPayment extends Payment {
    public function __construct(int $amount) {
        parent::__construct($amount, 'QRIS');
    }

    /**
     * POLIMORFISME: Implementasi spesifik pembayaran QRIS
     */
    public function processPayment(): array {
        $this->status = 'pending';
        return [
            'status'        => 'success',
            'metode'        => $this->metode,
            'kode_bayar'    => $this->getTransactionCode(),
            'instruksi'     => $this->getPaymentInstructions(),
            'status_bayar'  => $this->status
        ];
    }

    public function getPaymentInstructions(): string {
        return "Buka aplikasi m-Banking atau E-Wallet (GoPay, OVO, Dana, ShopeePay), " .
               "lalu scan barcode QRIS kost untuk menyelesaikan pembayaran sebesar Rp " . 
               number_format($this->amount, 0, ',', '.') . ".";
    }
}

// ============================================================================
// SUBCLASS 3: BankTransferPayment (Pewarisan dari Payment)
// ============================================================================
class BankTransferPayment extends Payment {
    private string $nomorRekening = '123-456-7890 (BCA an. Pemilik Kost)';

    public function __construct(int $amount) {
        parent::__construct($amount, 'Transfer Bank');
    }

    /**
     * POLIMORFISME: Implementasi spesifik transfer antar bank
     */
    public function processPayment(): array {
        $this->status = 'pending';
        return [
            'status'        => 'success',
            'metode'        => $this->metode,
            'kode_bayar'    => $this->getTransactionCode(),
            'nomor_rekening'=> $this->nomorRekening,
            'instruksi'     => $this->getPaymentInstructions(),
            'status_bayar'  => $this->status
        ];
    }

    public function getPaymentInstructions(): string {
        return "Transfer ke Rekening {$this->nomorRekening} sebesar Rp " . 
               number_format($this->amount, 0, ',', '.') . 
               ", sertakan kode referensi {$this->getTransactionCode()} pada berita transfer.";
    }
}

// ============================================================================
// SUBCLASS 4: EWalletPayment (Pewarisan dari Payment)
// ============================================================================
class EWalletPayment extends Payment {
    public function __construct(int $amount) {
        parent::__construct($amount, 'E-Wallet');
    }

    public function processPayment(): array {
        $this->status = 'pending';
        return [
            'status'        => 'success',
            'metode'        => $this->metode,
            'kode_bayar'    => $this->getTransactionCode(),
            'instruksi'     => $this->getPaymentInstructions(),
            'status_bayar'  => $this->status
        ];
    }

    public function getPaymentInstructions(): string {
        return "Kirim saldo E-Wallet ke nomor 0821-3835-8117 (DANA / OVO / GoPay) sebesar Rp " . 
               number_format($this->amount, 0, ',', '.') . " dan simpan bukti transaksi.";
    }
}

// ============================================================================
// FACTORY CLASS: PaymentFactory (Penerapan Factory Pattern)
// ============================================================================
class PaymentFactory {
    /**
     * CLASS & OBJECT: Memproduksi objek Payment yang tepat secara polimorfik
     * @param string $metode
     * @param int $amount
     * @return Payment
     */
    public static function create(string $metode, int $amount): Payment {
        $metodeClean = strtolower(trim($metode));

        if (str_contains($metodeClean, 'qris')) {
            return new QrisPayment($amount);
        } elseif (str_contains($metodeClean, 'transfer') || str_contains($metodeClean, 'bank')) {
            return new BankTransferPayment($amount);
        } elseif (str_contains($metodeClean, 'wallet') || str_contains($metodeClean, 'dana') || str_contains($metodeClean, 'ovo') || str_contains($metodeClean, 'gopay')) {
            return new EWalletPayment($amount);
        } else {
            return new CashPayment($amount);
        }
    }
}
?>

