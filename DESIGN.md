# Panduan Desain Antarmuka: Kost Online
**Dokumen Standar Desain UI/UX — Pastel Editorial (Muted Multi-Accent)**

---

## 1. Visi & Karakter Desain (Identity & Mood)

Aplikasi **Kost Online** dirancang dengan pendekatan **Pastel Editorial (Muted Multi-Accent)**: bersih, cerah, ceria, modern, namun tetap kalem dan tidak mencolok.

* **Karakter Utama**: Hangat, ramah, teratur, dan editorial seperti brosur hunian modern yang mengutamakan keterbacaan dan kenyamanan visual.
* **Prinsip Anti-Slop Utama**:
  * **Zero AI-Default Slop**: Tidak ada gradient biru-ungu atau neon, tidak ada efek glassmorphism berlebihan, tidak ada bayangan tebal yang membuat semua komponen mengambang, dan tidak ada penggunaan emoji sebagai ikon antarmuka.
  * **Purposeful Multi-Accent**: Setiap warna aksen pastel memiliki fungsi semantik yang jelas (status ketersediaan, highlight harga/promo, dan tag fasilitas), bukan sekadar hiasan acak.
  * **Content-Driven Composition**: Komposisi halaman fokus pada unit kamar yang nyata, hierarki tipografi yang lega, dan tata letak yang tidak kaku.

---

## 2. Palet Warna (Muted Multi-Accent / Pastel Editorial)

Palet menggunakan kanvas putih hangat yang bersih, dipadukan dengan biru laut tenang sebagai warna aksi utama dan tiga aksen pastel lembut untuk pembedaan informasi.

| Peran Warna | Nama Token | Kode HEX | Penggunaan & Kontras |
| :--- | :--- | :--- | :--- |
| **Latar Belakang Utama** | `var(--bg)` | `#FAFAF9` | Putih bersih lembut (*Warm Off-White*), kanvas utama halaman |
| **Permukaan Kartu** | `var(--white)` | `#FFFFFF` | Putih bersih untuk kartu kamar, modal, dan form |
| **Permukaan Sekunder** | `var(--surface)` | `#F4F4F2` | Latar halus untuk frame, input, dan area pendukung |
| **Garis Tepi (Border)** | `var(--border)` | `#E7E5E4` | Garis tepi tipis 1px (*Stone 200*) pemisah struktur |
| **Teks Utama** | `var(--text)` | `#18181B` | Arang gelap (*Zinc 900*), tajam dan mudah dibaca (rasio ~16.8:1) |
| **Teks Sekunder** | `var(--text-muted)` | `#52525B` | Abu-abu arang (*Zinc 600*), memenuhi standar WCAG AA (~7.2:1) |
| **Warna Utama (Primary)** | `var(--primary)` | `#2563EB` | Biru laut tenang (*Muted Ocean Blue*) untuk tombol booking & CTA |
| **Primary Hover** | `var(--primary-dk)` | `#1D4ED8` | Biru laut lebih dalam untuk state hover dan active |
| **Aksen Teal Pendukung** | `var(--teal)` | `#0D9488` | Teal kalem untuk aksen sekunder/konfirmasi positif |
| **Aksen Hijau Sage** | `var(--sage-bg)` / `var(--sage-fg)` | `#DCFCE7` / `#166534` | Status **Tersedia**, tag ketersediaan, dan ringkasan tagihan |
| **Aksen Peach / Terracotta** | `var(--peach-bg)` / `var(--peach-fg)` | `#FFEDD5` / `#9A3412` | Highlight harga, tarif harian, dan penanda edisi |
| **Aksen Lavender / Biru Muda** | `var(--lavender-bg)` / `var(--lavender-fg)` | `#E0E7FF` / `#3730A3` | Tag fasilitas kamar, label langkah booking, dan kategori |
| **Status Terisi** | `var(--terisi-bg)` / `var(--terisi-fg)` | `#FEE2E2` / `#991B1B` | Status kamar **Terisi** yang jelas namun tetap lembut |

> **Aturan Kontras & Warna**:
> 1. Seluruh teks normal wajib memenuhi rasio kontras minimal **4.5:1** (WCAG AA).
> 2. Dilarang menggunakan gradient biru-ke-ungu atau warna neon.

---

## 3. Sistem Tipografi (Typography Hierarchy)

Menggunakan pasangan tipografi editorial yang hangat namun tetap modern:

```css
--font-display: 'Fraunces', Georgia, serif;
--font-sans:    'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
```

### 3.1. Display & Judul (`Fraunces`)
* **Karakteristik**: Serif ekspresif yang memberi sentuhan editorial hangat dan berkarakter pada nama properti dan judul kamar.
* **Penggunaan**:
  * **Hero Main Heading (`h1`)**: `font-size: clamp(36px, 5vw, 62px); font-weight: 500; line-height: 1.08;`
  * **Section Heading (`h2`)**: `font-size: 26px; font-weight: 600; letter-spacing: -0.02em;`
  * **Nama Tipe Kamar**: `font-size: 21px; font-weight: 600;`

### 3.2. Body, UI, & Label (`DM Sans`)
* **Karakteristik**: Sans-serif yang bersih, proporsional, dan nyaman dibaca pada tabel, formulir, maupun kartu mobile.
* **Penggunaan**:
  * **Lead Paragraph**: `font-size: 16px; line-height: 1.65; color: var(--text-muted);`
  * **Badge & Tag Pastel**: `font-size: 11px - 12px; font-weight: 600; letter-spacing: 0.03em;`
  * **Tombol / CTA**: `font-size: 13px; font-weight: 600; letter-spacing: 0.04em;`

---

## 4. Gaya Komponen (Component Styling)

### 4.1. Kartu Unit Kamar (`.kamar-card`)
* **Permukaan & Border**: Latar `#FFFFFF` dengan border tipis `1px solid #E7E5E4` dan sudut halus terukur (`border-radius: 8px`).
* **Elevasi**: Bayangan ringan `0 2px 10px rgba(24, 24, 27, 0.04)`; saat hover naik tipis `translateY(-3px)` dengan border sedikit lebih tegas.
* **Badge & Tag**:
  * Status **Tersedia** memakai pil Hijau Sage (`#DCFCE7` / `#166534`).
  * Highlight harga memakai tag Peach (`#FFEDD5` / `#9A3412`).
  * Tag fasilitas memakai sentuhan Lavender (`#E0E7FF` / `#3730A3`).

### 4.2. Tombol & Kontrol Interaktif (`Buttons & Focus`)
* **Tombol Booking Utama**: Biru Laut Tenang `#2563EB` dengan teks putih `#FFFFFF`, sudut `4px`, hover `#1D4ED8`.
* **Tombol Sekunder**: Latar `#FFFFFF`, border `1px solid #E7E5E4`, teks `#18181B`, hover `#F4F4F2`.
* **Aksesibilitas**: Semua elemen interaktif memiliki `cursor: pointer`, target sentuh minimal `44px` di mobile, serta indikator `:focus-visible` berwarna `#2563EB` (`outline: 2px solid #2563EB; outline-offset: 2px`).
* **Ikon**: Menggunakan ikon SVG sederhana berujung rapi (stroke `1.8px`), tanpa emoji dekoratif.

### 4.3. Modal Pemesanan & Pembayaran
* **Overlay**: Latar gelap arang `rgba(24, 24, 27, 0.55)` tanpa tumpukan efek glassmorphism.
* **Header & Step Badge**: Menggunakan tag Lavender (`#E0E7FF` / `#3730A3`) untuk penanda langkah.
* **Ringkasan Tagihan**: Menggunakan aksen Hijau Sage (`#DCFCE7` / `#166534`) atau kartu solid `#18181B` yang bersih tanpa gradient ungu.

---

## 5. Tata Letak & Hierarki Visual (Layout Rhythm)

1. **Top Navigation**: Sticky header bersih berlatar `#FFFFFF` dengan garis bawah `1px solid #E7E5E4`.
2. **Hero Section (Editorial Asimetris)**: Kolom kiri memuat tipografi besar `Fraunces`, deskripsi singkat, dua tombol aksi, dan strip spesifikasi; kolom kanan memuat frame foto kamar utama dengan kartu kutipan asimetris.
3. **Filter Bar**: Pemilih kategori unit dengan state aktif `#2563EB` yang jelas.
4. **Katalog Kamar**: Grid responsif (3 kolom desktop, 2 kolom tablet, 1 kolom mobile) dengan jarak lega dan tanpa horizontal overflow.
5. **Footer**: Latar `#18181B` (*Zinc 900*) yang kontras dan rapi dengan informasi lokasi serta tautan cepat yang aktif.

---

## 6. Checklist Kepatuhan Anti-Slop

- [x] **R-01**: Bebas dari gradient biru-ungu dan warna neon.
- [x] **R-02**: Bebas dari karakter *em dash* (`—`) pada teks UI.
- [x] **R-03**: Responsif di semua ukuran layar dengan target tap minimal 44px.
- [x] **R-04**: Bebas dari emoji dekoratif; menggunakan ikon SVG sederhana yang relevan.
- [x] **R-06**: Pasangan font `Fraunces` (judul) dan `DM Sans` (isi) yang terarah.
- [x] **R-25**: Semua kombinasi warna memenuhi standar kontras WCAG AA (minimal 4.5:1).
- [x] **R-32 & R-39**: Dukungan fokus keyboard `:focus-visible`, landmark semantik (`header`, `nav`, `main`, `footer`), dan `aria-label` lengkap.
