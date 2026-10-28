<?php
/**
 * ============================================================================
 * CLASS: Database
 * ============================================================================
 * PENERAPAN PRINSIP PBO (OOP):
 * 1. CLASS & OBJECT:
 *    - Class Database sebagai blueprint untuk koneksi database.
 *    - Objek dibuat menggunakan metode Singleton Pattern: Database::getInstance().
 * 2. ENCAPSULATION (ENKAPSULASI):
 *    - Properti kredensial database ($host, $user, $password, $database)
 *      dideklarasikan secara PRIVATE untuk data hiding (menyembunyikan detail sensitif).
 *    - Konstruktor dideklarasikan PRIVATE agar instansiasi hanya dapat dilakukan
 *      melalui metode publik getInstance() (mencegah multiple instance koneksi).
 *    - Akses ke koneksi database disediakan melalui metode publik getConnection().
 * ============================================================================
 */

class Database {
    // ENKAPSULASI: Properti privat yang tidak dapat diakses langsung dari luar class
    private string $host     = "localhost";
    private string $user     = "root";
    private string $password = "";
    private string $database = "db_booking";
    private ?mysqli $koneksi = null;

    // Instance tunggal untuk Singleton Pattern
    private static ?Database $instance = null;

    /**
     * Konstruktor privat: Mencegah pembuatan objek langsung dengan operator 'new Database()'
     */
    private function __construct() {
        $this->hubungkan();
    }

    /**
     * Mencegah cloning objek
     */
    private function __clone() {}

    /**
     * CLASS & OBJECT: Mendapatkan objek tunggal (Singleton) Database
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Membuka koneksi MySQLi
     */
    private function hubungkan(): void {
        try {
            $this->koneksi = new mysqli($this->host, $this->user, $this->password, $this->database);

            if ($this->koneksi->connect_error) {
                throw new Exception("Koneksi Database Gagal: " . $this->koneksi->connect_error);
            }

            // Set charset ke utf8mb4
            $this->koneksi->set_charset("utf8mb4");
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                "status"  => "error",
                "message" => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * ENKAPSULASI (Getter): Mendapatkan instance aktif mysqli
     * @return mysqli
     */
    public function getConnection(): mysqli {
        if ($this->koneksi === null || !$this->koneksi->ping()) {
            $this->hubungkan();
        }
        return $this->koneksi;
    }

    /**
     * Helper method untuk meng-escape string dari SQL Injection
     * @param string $string
     * @return string
     */
    public function escape(string $string): string {
        return $this->getConnection()->real_escape_string(trim($string));
    }

    /**
     * Menjalankan query MySQL
     * @param string $sql
     * @return mysqli_result|bool
     */
    public function query(string $sql) {
        return $this->getConnection()->query($sql);
    }

    /**
     * Mendapatkan ID terakhir hasil auto-increment insert
     * @return int
     */
    public function getLastInsertId(): int {
        return (int)$this->getConnection()->insert_id;
    }

    /**
     * Mendapatkan pesan error terakhir
     * @return string
     */
    public function getLastError(): string {
        return $this->getConnection()->error;
    }
}
?>

