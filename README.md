# Sistem Pemesanan & Pengelolaan Kost Online Berbasis OOP (PBO)

> **Ujian Tengah Semester (UTS) — Pemrograman Berorientasi Objek (PBO)**  
> **Program Studi:** Informatika | **Semester:** 3 (Tiga)  
> **Dosen Pengampu:** Fathulloh, ST, M.Kom  

---

## 📌 1. Judul dan Informasi Mahasiswa

* **Nama:** Rio Permana,Muamar Renji Kordawi,Alinda Restu Desiyana gi
* **NIM:**  42425030,42425031,42425021
* **Kelas:** Informatika - Semester 3
* **Mata Kuliah:** Pemrograman Berorientasi Objek (PBO)
* **Dosen Pengampu:** Fathulloh, ST, M.Kom
* **Tema Aplikasi:** **Sistem Pemesanan & Pengelolaan Kost Online** *(Kategori: dll / Sistem Pemesanan & Manajemen Sewa)*

---

## 📖 2. Deskripsi Ringkas Aplikasi

### Latar Belakang
Proses pencarian dan pemesanan kamar kost umumnya masih dilakukan secara konvensional atau melalui pencatatan manual yang rawan kekeliruan, mulai dari ketidaktahuan calon penyewa mengenai ketersediaan unit kamar secara *real-time*, tidak tersedianya pilihan durasi sewa yang fleksibel (harian maupun bulanan), hingga bukti pemesanan yang tidak terdokumentasi rapi.

### Kegunaan Aplikasi
Aplikasi **Sistem Kost Online** ini dirancang untuk mendigitalkan proses reservasi kamar kost:
1. **Bagi Calon Penghuni Kost:** Memudahkan penjelajahan unit kamar dengan galeri foto interaktif (*multi-slide swipe*), mengecek status kamar secara instan (tersedia/terisi), memilih durasi sewa fleksibel (**Harian** atau **Bulanan**), serta melakukan booking dengan alur terpisah yang intuitif dan dilanjutkan pemilihan metode pembayaran multi-channel (QRIS, E-Wallet, Tunai/Cash) beserta konfirmasi WhatsApp otomatis.
2. **Bagi Pengelola/Pemilik Kost:** Mempermudah monitoring okupansi unit kamar, perubahan tarif/harga, manajemen album foto sudut ruangan, serta pencatatan transaksi masuk melalui Dashboard Admin terintegrasi yang aman.

---

## 🧱 3. Penerapan Konsep Pemrograman Berorientasi Objek (OOP)

Aplikasi dibangun di atas pondasi arsitektur **OOP PHP murni** dan database **MySQL** dengan menerapkan 4 pilar utama PBO:

### A. Class & Object
* **Representasi Entitas:** Setiap komponen sistem dimodelkan ke dalam class terpisah, antara lain:
  * `Database`: Pengelola koneksi basis data tunggal (*Design Pattern Singleton*).
  * `Kamar`: Entitas unit kamar kost, tarif, status ketersediaan, dan relasi galeri foto.
  * `FotoKamar`: Entitas album foto sudut ruangan (multi-slide carousel).
  * `Pemesanan`: Entitas transaksi booking penyewa kost.
  * `Payment`: Entitas abstrak pengolahan pembayaran.
* **Instansiasi Objek:** Objek dibuat secara dinamis saat menangani permintaan API, misalnya:  
  `$pemesanan = new Pemesanan($db);`  
  `$payment = PaymentFactory::create($metode, $total);`

### B. Encapsulation (Enkapsulasi)
* Semua atribut penting pada class dideklarasikan dengan hak akses `private` atau `protected` untuk mencegah manipulasi data secara ilegal dari luar class.
* Akses dan pengubahan data diatur melalui metode **Getter** dan **Setter** yang dilengkapi dengan **validasi logika bisnis ketat**:
  * `Pemesanan::$namaPemesan` (private): Setter memvalidasi bahwa nama tidak boleh kosong string/spasi.
  * `Pemesanan::$durasi` (private): Setter memvalidasi batas minimal durasi sewa (minimal 1 hari/bulan).
  * `Pemesanan::$tipeDurasi` (private): Setter memfilter tipe valid hanya `'harian'` atau `'bulanan'`.
  * `Kamar::$harga` (private): Setter memvalidasi bahwa tarif kamar tidak boleh negatif.
  * `Database::$instance` (private static): Konstruktor dibuat privat agar instance MySQL hanya dapat dibuat melalui method `getInstance()`.

### C. Inheritance (Pewarisan)
* **Superclass Abstrak `BaseModel` (`config/models/BaseModel.php`):**  
  Menyediakan properti bersama (`protected $db`, `protected $id`, `protected $createdAt`) dan metode dasar.
  * Diturunkan (*extends*) oleh class:
    * `class Kamar extends BaseModel`
    * `class FotoKamar extends BaseModel`
    * `class Pemesanan extends BaseModel`
* **Superclass Abstrak `Payment` (`config/models/Payment.php`):**  
  Menyediakan template pembayaran (`amount`, `status`, `transactionCode`).
  * Diturunkan (*extends*) oleh class spesifik:
    * `class CashPayment extends Payment`
    * `class QrisPayment extends Payment`
    * `class EWalletPayment extends Payment`
    * `class BankTransferPayment extends Payment`

### D. Polymorphism (Polimorfisme)
* **Method Overriding pada Model:**  
  Class `BaseModel` mendefinisikan method abstract:
  * `abstract public function simpan(): bool;`
  * `abstract public function getDetail(): array;`
  * `abstract public function toArray(): array;`  
  Masing-masing subclass (`Kamar`, `Pemesanan`, `FotoKamar`) mengimplementasikan logika penyimpanan dan serialisasi data secara spesifik sesuai entitasnya.
* **Polimorfisme Pemrosesan Pembayaran:**  
  Setiap turunan `Payment` meng-override method `processPayment()` dan `getPaymentInstructions()`:
  * `QrisPayment`: Menghasilkan instruksi scan QR code instan dan tautan simulasi gateway.
  * `EWalletPayment`: Menghasilkan nomor tagihan dompet digital (DANA, OVO, GoPay).
  * `CashPayment`: Menghasilkan instruksi bayar di lokasi saat serah terima kunci kost.

---

## 📊 Class Diagram (UML)

```mermaid
classDiagram
    direction TB

    %% Superclass / Abstract Classes
    class BaseModel {
        <<abstract>>
        #Database db
        #int id
        #string createdAt
        +__construct(Database db)
        +getId() int
        +setId(int id) void
        +getCreatedAt() string
        +setCreatedAt(string date) void
        +getDb() Database
        +simpan()* bool
        +getDetail()* array
        +toArray()* array
    }

    class Payment {
        <<abstract>>
        #int amount
        #string status
        #string metode
        #string transactionCode
        +__construct(int amount, string metode)
        +getAmount() int
        +setAmount(int amount) void
        +getStatus() string
        +setStatus(string status) void
        +getMetode() string
        +getTransactionCode() string
        -generateTransactionCode() void
        +processPayment()* array
        +getPaymentInstructions()* string
    }

    %% Database Singleton Class
    class Database {
        -string host
        -string user
        -string password
        -string database
        -mysqli koneksi
        -Database instance$
        -__construct()
        -__clone()
        +getInstance()$ Database
        +getConnection() mysqli
        +escape(string str) string
        +query(string sql) mixed
        +getLastInsertId() int
        +getLastError() string
    }

    %% Entity Models (Subclasses of BaseModel)
    class Kamar {
        -string namaKamar
        -int harga
        -string gambar
        -string status
        -array fotos
        +__construct(Database db)
        +getNamaKamar() string
        +setNamaKamar(string nama) void
        +getHarga() int
        +setHarga(int harga) void
        +getGambar() string
        +setGambar(string file) void
        +getStatus() string
        +setStatus(string status) void
        +getFotos() array
        +setFotos(array fotos) void
        +simpan() bool
        +getDetail() array
        +toArray() array
        +getAll() array
        +getByNama(string nama) Kamar
        +getById(int id) Kamar
        +updateStatus(string status) bool
        +updateHarga(int harga) bool
        +resetSemuaKamar() bool
    }

    class FotoKamar {
        -int idKamar
        -string fileFoto
        -string keterangan
        +__construct(Database db)
        +getIdKamar() int
        +setIdKamar(int idKamar) void
        +getFileFoto() string
        +setFileFoto(string file) void
        +getKeterangan() string
        +setKeterangan(string ket) void
        +simpan() bool
        +getDetail() array
        +toArray() array
        +getByKamarId(int idKamar) array
        +hapusFoto(int idFoto) bool
    }

    class Pemesanan {
        -string namaPemesan
        -string namaUnit
        -Kamar kamar
        -int durasi
        -string tipeDurasi
        -int totalHarga
        -Payment payment
        -string metodePembayaran
        -string statusPemesanan
        +__construct(Database db)
        +getNamaPemesan() string
        +setNamaPemesan(string nama) void
        +getNamaUnit() string
        +setNamaUnit(string unit) void
        +getDurasi() int
        +setDurasi(int durasi) void
        +getTipeDurasi() string
        +setTipeDurasi(string tipe) void
        +getTotalHarga() int
        +setTotalHarga(int total) void
        +setPayment(Payment payment) void
        +simpan() bool
        +getDetail() array
        +toArray() array
        +hitungTotal() int
        +prosesPemesanan() array
        +getAllPemesanan() array
        +batalkanPemesanan(int id) bool
    }

    %% Subclasses of Payment (Polymorphism)
    class CashPayment {
        +processPayment() array
        +getPaymentInstructions() string
    }

    class QrisPayment {
        -string qrString
        +processPayment() array
        +getPaymentInstructions() string
    }

    class EWalletPayment {
        -string provider
        +processPayment() array
        +getPaymentInstructions() string
    }

    class BankTransferPayment {
        -string bankName
        -string noRekening
        +processPayment() array
        +getPaymentInstructions() string
    }

    class PaymentFactory {
        +create(string metode, int amount)$ Payment
    }

    %% Relationships
    BaseModel <|-- Kamar : Inheritance
    BaseModel <|-- FotoKamar : Inheritance
    BaseModel <|-- Pemesanan : Inheritance

    Payment <|-- CashPayment : Inheritance
    Payment <|-- QrisPayment : Inheritance
    Payment <|-- EWalletPayment : Inheritance
    Payment <|-- BankTransferPayment : Inheritance

    Pemesanan o-- Kamar : Aggregation
    Pemesanan *-- Payment : Composition
    Kamar "1" *-- "many" FotoKamar : Composition
    PaymentFactory ..> Payment : Creates
    BaseModel --> Database : Uses
```

---

## 🚀 4. Alur & Fitur Utama Program

### Alur Kerja Pengguna (User Flow)
```
1. Buka Beranda ───► Lihat Status Kamar (Kosong / Terisi)
          │
          ▼
2. Klik Kartu Kamar ───► Buka Modal Slider Foto & Detail Kamar
          │
          ▼ (Tekan "Pilih Unit Ini")
3. Modal Form Pemesanan Muncul (Langkah 1)
   ├── Pilih Tipe Sewa: Harian (🌙) atau Bulanan (📅)
   ├── Atur Durasi Sewa (+ / -)
   ├── Masukkan Nama Lengkap Pemesan
   └── Tekan "Lanjut ke Pembayaran"
          │
          ▼
4. Modal Pilihan Metode Pembayaran (Langkah 2)
   ├── Pilihan: E-Wallet / QRIS / Tunai
   └── Sistem OOP memproses validasi, simpan database, & ubah status kamar
          │
          ▼
5. Popup Sukses Booking
   └── Bukti Pemesanan Terbit + Tombol Teruskan ke WhatsApp Pemilik Kost
```

### Daftar Fitur Utama:
1. **Katalog Unit Kamar Dinamis:** Menampilkan seluruh daftar kamar kost lengkap dengan status ketersediaan (*badge* hijau untuk Tersedia, merah untuk Terisi) dan filter cepat.
2. **Interactive Photo Slider (Touch & Swipe):** Menampilkan berbagai sudut foto ruangan untuk setiap kamar yang dapat digeser menggunakan layar sentuh ponsel, *drag* mouse di PC, maupun tombol panah navigasi.
3. **Pemisahan Tampilan Form Booking:** Tampilan utama dibuat bersih; formulir pemesanan hanya muncul sebagai modal dialog saat pengguna ingin melakukan pemesanan.
4. **Pilihan Durasi Fleksibel (Harian & Bulanan):** Fitur pemilihan sewa harian (dengan tarif per hari otomatis terhitung proporsional) atau sewa bulanan.
5. **Multi-Metode Pembayaran (Polymorphic Payment):** Mendukung pembayaran via QRIS, E-Wallet, dan Cash dengan generator kode transaksi instan.
6. **Integrasi WhatsApp API:** Membantu penyewa mengirimkan konfirmasi reservasi langsung ke WhatsApp pengelola dengan draf pesan otomatis.
7. **Dashboard Admin Rahasia:** Pengelolaan data kamar, harga, galeri foto ruangan, dan histori booking melalui antarmuka khusus dengan autentikasi PIN.

---

## 💻 5. Cara Menjalankan Program (How to Run)

### Prasyarat Sistem:
* Web Server Apache & PHP (Minimal PHP 7.4 atau PHP 8.x) — direkomendasikan menggunakan **XAMPP**.
* Database MySQL / MariaDB.
* Web Browser modern (Google Chrome, Microsoft Edge, atau Mozilla Firefox).

### Langkah-Langkah Menjalankan:
1. **Clone / Unduh Repository:**
   Letakkan folder proyek di dalam direktori `htdocs` server web lokal:
   ```bash
   # Jalankan di direktori c:/xampp/htdocs/
   git clone https://github.com/riopr325/KELOMPOK-BOKING-KOST-.git "projek kelompok"
   ```

2. **Jalankan XAMPP:**
   Buka aplikasi **XAMPP Control Panel**, kemudian klik **Start** pada modul **Apache** dan **MySQL**.

3. **Impor Database:**
   * Buka browser dan akses `http://localhost/phpmyadmin/`.
   * Buat database baru bernama: `db_booking`.
   * Buka menu **Import** dan pilih berkas `database.sql` yang ada di direktori root proyek ini, lalu klik **Go / Kirim**.

4. **Konfigurasi Database (Bila Diperlukan):**
   Pengaturan koneksi tersimpan di berkas `config/Database.php`:
   ```php
   private string $host     = 'localhost';
   private string $user     = 'root';
   private string $password = '';
   private string $database = 'db_booking';
   ```

5. **Akses Aplikasi Melalui Browser:**
   * **Halaman Utama Calon Penyewa:**
     ```
     http://localhost/projek%20kelompok/public/index.html
     ```
   * **Halaman Pengelola / Dashboard Admin:**
     ```
     http://localhost/projek%20kelompok/public/admin.html
     (Default PIN Admin: 1234)
     ```

---

## 📸 6. Tampilan Antarmuka (Screenshot)

| Tampilan Beranda & Katalog | Slider Foto Kamar Interaktif |
| :---: | :---: |
| Halaman utama dengan daftar kamar & ketersediaan real-time | Modal interaktif untuk melihat foto detail sudut ruangan |

| Form Pemesanan (Harian / Bulanan) | Pilihan Pembayaran & Bukti Sukses |
| :---: | :---: |
| Formulir terpisah dengan toggle sewa Harian / Bulanan | Pemilihan metode bayar & popup konfirmasi WhatsApp |

---

## 📁 7. Struktur Direktori Proyek

```text
projek kelompok/
├── api/
│   ├── admin_action.php       # Controller API aksi admin (OOP)
│   ├── get_booking.php        # API pemanggilan daftar booking
│   ├── get_kamar.php          # API katalog kamar & relasi foto
│   ├── proses_booking.php     # Controller API pemrosesan transaksi booking
│   └── upload_foto.php        # API upload foto kamar kost
├── config/
│   ├── Database.php           # Class Database (Singleton Pattern)
│   ├── koneksi.php            # Bootstrap autoloader class model
│   └── models/
│       ├── BaseModel.php      # Abstract Superclass Model
│       ├── BookingKost.php    # Legacy model transaksi kost
│       ├── FotoKamar.php      # Class model foto sudut kamar
│       ├── Kamar.php          # Class model entitas kamar
│       ├── Payment.php        # Abstract Payment & Subclasses Polymorphic
│       ├── Pemesanan.php      # Class model transaksi reservasi
│       └── Transaksi.php      # Abstract base transaction
├── public/
│   ├── uploads/               # Direktori penyimpanan foto kamar
│   ├── admin.html             # Antarmuka Dashboard Admin
│   ├── index.html             # Antarmuka Utama Reservasi Kost
│   ├── script.js              # Interaksi frontend & AJAX caller
│   └── style.css              # Tata letak & styling responsif
├── database.sql               # Skrip inisialisasi tabel basis data
└── README.md                  # Dokumentasi laporan UTS PBO
```

---

**Ujian Tengah Semester (UTS) — Pemrograman Berorientasi Objek (PBO)**  
*Dibuat dengan penuh dedikasi untuk memenuhi standar akademik teknik informatika.*
