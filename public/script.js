// =====================================================
//  CONFIG & GLOBAL STATE
// =====================================================
let hargaPerBulanDipilih = 0;
let unitDipilihData = null;
let pendingBookingData = null; // Menyimpan data form sebelum metode pembayaran dipilih
const NOMOR_WA = '6282138358117';

// Data default awal agar tampilan selalu langsung siap dan tidak pernah kosong
const dataKamarDefault = [
    { id: "1", nama_kamar: "Kamar 1", harga: 500000, gambar: "kamar_1_1792231112.png", status: "kosong" },
    { id: "2", nama_kamar: "Kamar 2", harga: 500000, gambar: "kamar_2_1792231111.png", status: "kosong" },
    { id: "3", nama_kamar: "Kamar 3", harga: 550000, gambar: "kamar_3_1792231110.png", status: "kosong" },
    { id: "4", nama_kamar: "Kamar 4", harga: 550000, gambar: "kamar4.jpg", status: "kosong" },
    { id: "5", nama_kamar: "Kamar 5", harga: 600000, gambar: "kamar.jpg", status: "kosong" },
    { id: "6", nama_kamar: "Kamar 6", harga: 600000, gambar: "kamar6.jpg", status: "kosong" }
];

let daftarKamarData = [...dataKamarDefault];

// =====================================================
//  INIT
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    // 1. Render data default langsung agar layar tidak kosong dan pilihan langsung ada
    renderStatusGrid(daftarKamarData);
    renderKatalog(daftarKamarData);
    renderSelectOptions(daftarKamarData);

    // 2. Pasang event listener untuk semua interaksi
    initFormBooking();
    initModals();
    initFilterTabs();
    initSearchToggle();
    initDurasiStepper();
    initSelectKamar();
    initDetailSliderEvents();

    // 3. Ambil data terbaru dari database (jika lewat server http/localhost)
    muatDataKamar();
});

// =====================================================
//  SEARCH TOGGLE & FILTER
// =====================================================
function initSearchToggle() {
    const btnSearch   = document.getElementById('btnSearch');
    const searchWrap  = document.getElementById('searchBarWrap');
    const searchInput = document.getElementById('searchInput');
    if (!btnSearch) return;

    btnSearch.addEventListener('click', function () {
        const isOpen = searchWrap.style.display !== 'none';
        searchWrap.style.display = isOpen ? 'none' : 'block';
        if (!isOpen && searchInput) searchInput.focus();
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            document.querySelectorAll('.kamar-card').forEach(function (card) {
                const name = (card.dataset.nama || '').toLowerCase();
                card.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }
}

// =====================================================
//  FILTER TABS (Semua / Tersedia / Terisi)
// =====================================================
function initFilterTabs() {
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');

            const filter = tab.dataset.filter;
            document.querySelectorAll('.kamar-card').forEach(function (card) {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// =====================================================
//  DURASI STEPPER (+ / -) & TIPE TOGGLE (Harian/Bulanan)
// =====================================================
function initDurasiStepper() {
    const inputDurasi  = document.getElementById('durasiBulan');
    const btnKurang    = document.getElementById('btnKurang');
    const btnTambah    = document.getElementById('btnTambah');
    const btnHarian    = document.getElementById('btnTipeHarian');
    const btnBulanan   = document.getElementById('btnTipeBulanan');
    const tipeDurasiEl = document.getElementById('tipeDurasi');

    if (!inputDurasi) return;

    inputDurasi.addEventListener('input', hitungTotalHarga);

    if (btnKurang) {
        btnKurang.addEventListener('click', function () {
            let v = parseInt(inputDurasi.value) || 1;
            if (v > 1) { 
                inputDurasi.value = v - 1; 
                hitungTotalHarga(); 
            }
        });
    }
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            let v = parseInt(inputDurasi.value) || 1;
            inputDurasi.value = v + 1;
            hitungTotalHarga();
        });
    }

    // Toggle tipe durasi Harian / Bulanan
    function setTipe(tipe) {
        if (tipeDurasiEl) tipeDurasiEl.value = tipe;
        const labelEl = document.getElementById('labelSatuanDurasi');
        if (labelEl) labelEl.textContent = tipe === 'harian' ? 'Hari' : 'Bulan';
        inputDurasi.value = 1;

        if (btnHarian)  btnHarian.classList.toggle('active', tipe === 'harian');
        if (btnBulanan) btnBulanan.classList.toggle('active', tipe === 'bulanan');
        hitungTotalHarga();
    }

    if (btnHarian)  btnHarian.addEventListener('click',  function () { setTipe('harian'); });
    if (btnBulanan) btnBulanan.addEventListener('click', function () { setTipe('bulanan'); });
}

// =====================================================
//  DROPDOWN PILIHAN UNIT
// =====================================================
function initSelectKamar() {
    const selectUnit = document.getElementById('unitPilihan');
    if (!selectUnit) return;

    selectUnit.addEventListener('change', function () {
        const namaUnit = this.value;
        if (!namaUnit) {
            hargaPerBulanDipilih = 0;
            unitDipilihData = null;
            hitungTotalHarga();
            return;
        }

        const selectedOpt = this.options[this.selectedIndex];
        let harga = selectedOpt ? parseInt(selectedOpt.dataset.harga) : 0;

        const kamar = daftarKamarData.find(function (k) { return k.nama_kamar === namaUnit; });
        if (kamar) {
            unitDipilihData = kamar;
            harga = parseInt(kamar.harga) || harga;
        }

        hargaPerBulanDipilih = harga;
        hitungTotalHarga();
    });
}

// =====================================================
//  LOAD DATA KAMAR DARI SERVER
// =====================================================
function muatDataKamar() {
    fetch('../api/get_kamar.php?_t=' + Date.now())
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP error ' + res.status);
            return res.json();
        })
        .then(function (data) {
            if (Array.isArray(data) && data.length > 0) {
                daftarKamarData = data;
                renderSelectOptions(data);
                renderStatusGrid(data);
                renderKatalog(data);
            }
        })
        .catch(function (err) { 
            console.warn('Mode lokal aktif: menggunakan daftar kamar bawaan.', err);
            // Tetap pastikan render dengan data bawaan
            renderSelectOptions(daftarKamarData);
            renderStatusGrid(daftarKamarData);
            renderKatalog(daftarKamarData);
        });
}

// Render opsi dropdown pada Form Booking
function renderSelectOptions(data) {
    const selectUnit = document.getElementById('unitPilihan');
    if (!selectUnit) return;

    const currentVal = selectUnit.value;
    selectUnit.innerHTML = '<option value="">-- Pilih Unit Kamar --</option>';

    data.forEach(function (item) {
        const isTerisi = item.status === 'terisi';
        const opt = document.createElement('option');
        opt.value = item.nama_kamar;
        opt.dataset.harga = item.harga;
        opt.dataset.status = item.status;

        const hargaFmt = 'Rp ' + parseInt(item.harga).toLocaleString('id-ID');
        opt.textContent = item.nama_kamar + ' - ' + hargaFmt + '/bln' + (isTerisi ? ' (Terisi)' : '');

        if (isTerisi) {
            opt.disabled = true;
        }

        selectUnit.appendChild(opt);
    });

    if (currentVal) {
        selectUnit.value = currentVal;
    }
}

// =====================================================
//  DATA FOTO MULTI-SLIDE PER KAMAR (FALLBACK & DEFAULT)
// =====================================================
const defaultFotoKamarMap = {
    1: [
        { file: 'kamar_1_1792312623.jpg', keterangan: 'Kamar Tidur Utama' },
        { file: 'kamar_1_1792231112.png', keterangan: 'Sudut Meja & Lemari' },
        { file: 'Kos Dekat Telkom University Ph_WA 0811 222 1614 Nyaman dan Murah.jpg', keterangan: 'Fasilitas & Lingkungan Kost' }
    ],
    2: [
        { file: 'kamar_2_1792312961.jpg', keterangan: 'Kamar Tidur & Kasur' },
        { file: 'kamar_2_1792231111.png', keterangan: 'Sudut Lemari & Meja' },
        { file: 'Kos Dekat Telkom University Ph_WA 0811 222 1614 Nyaman dan Murah.jpg', keterangan: 'Fasilitas & Lingkungan Kost' }
    ],
    3: [
        { file: 'kamar_3_1792312960.jpg', keterangan: 'Kamar Tidur Nyaman' },
        { file: 'kamar_3_1792231110.png', keterangan: 'Meja Belajar & Rak' },
        { file: 'Kos Dekat Telkom University Ph_WA 0811 222 1614 Nyaman dan Murah.jpg', keterangan: 'Fasilitas & Lingkungan Kost' }
    ],
    4: [
        { file: 'kamar_4_1792312958.jpg', keterangan: 'Tempat Tidur & Ventilasi' },
        { file: 'kamar4.jpg', keterangan: 'Interior & Meja Kerja' },
        { file: 'Kos Dekat Telkom University Ph_WA 0811 222 1614 Nyaman dan Murah.jpg', keterangan: 'Fasilitas & Lingkungan Kost' }
    ],
    5: [
        { file: 'kamar_5_1792312957.jpg', keterangan: 'Kamar Tidur Luas' },
        { file: 'kamar.jpg', keterangan: 'Sudut Kamar & Kasur' },
        { file: 'Kos Dekat Telkom University Ph_WA 0811 222 1614 Nyaman dan Murah.jpg', keterangan: 'Fasilitas & Lingkungan Kost' }
    ],
    6: [
        { file: 'kamar_6_1792312955.jpg', keterangan: 'Kamar Tidur Bersih' },
        { file: 'kamar6.jpg', keterangan: 'Area Meja & Lemari' },
        { file: 'Kos Dekat Telkom University Ph_WA 0811 222 1614 Nyaman dan Murah.jpg', keterangan: 'Fasilitas & Lingkungan Kost' }
    ]
};

// Helper untuk mendapatkan URL foto cover utama kamar
function getFotoKamar(item) {
    if (Array.isArray(item.fotos) && item.fotos.length > 0 && item.fotos[0].file) {
        return 'uploads/' + item.fotos[0].file;
    }
    let foto = (item.gambar && item.gambar.trim() !== '') ? item.gambar.trim() : '';
    if (!foto) {
        let nomor = parseInt(item.id) || 0;
        if (!nomor && item.nama_kamar) {
            let m = item.nama_kamar.match(/\d+/);
            if (m) nomor = parseInt(m[0]);
        }
        if (nomor && defaultFotoKamarMap[nomor] && defaultFotoKamarMap[nomor].length > 0) {
            foto = defaultFotoKamarMap[nomor][0].file;
        }
    }
    return foto ? ('uploads/' + foto) : 'https://placehold.co/300x200/e8e7ff/4f46e5?text=' + encodeURIComponent(item.nama_kamar);
}

// Helper untuk mendapatkan array seluruh foto kamar (untuk slider)
function getDaftarFotoKamar(item) {
    let list = [];

    // 1. Jika ada fotos dari response API database
    if (Array.isArray(item.fotos) && item.fotos.length > 0) {
        item.fotos.forEach(function (f) {
            if (f.file && f.file.trim() !== '') {
                list.push({
                    src: 'uploads/' + f.file.trim(),
                    keterangan: f.keterangan || 'Foto Kamar'
                });
            }
        });
        if (list.length > 0) return list;
    }

    // 2. Cek default mapping foto berdasarkan nomor kamar
    let nomor = parseInt(item.id) || 0;
    if (!nomor && item.nama_kamar) {
        let match = item.nama_kamar.match(/\d+/);
        if (match) nomor = parseInt(match[0]);
    }

    if (nomor && defaultFotoKamarMap[nomor]) {
        defaultFotoKamarMap[nomor].forEach(function (f) {
            list.push({
                src: 'uploads/' + f.file,
                keterangan: f.keterangan
            });
        });
    }

    // 3. Pastikan file gambar utama dari database ikut serta
    if (item.gambar && item.gambar.trim() !== '') {
        const utamaSrc = 'uploads/' + item.gambar.trim();
        const exists = list.some(function (f) { return f.src === utamaSrc; });
        if (!exists) {
            list.unshift({
                src: utamaSrc,
                keterangan: 'Foto Utama'
            });
        }
    }

    // 4. Fallback jika list masih kosong
    if (list.length === 0) {
        list.push({
            src: 'https://placehold.co/600x400/e8e7ff/4f46e5?text=' + encodeURIComponent(item.nama_kamar),
            keterangan: 'Tampak Ruangan'
        });
    }

    return list;
}

// Metadata tipe & fasilitas kamar (sesuai referensi desain modern hotel/kost)
const kamarMetaMap = {
    1: { tipe: 'Deluxe Room', guests: 2, baths: 1, size: '4x4 m' },
    2: { tipe: 'Standard Room', guests: 1, baths: 1, size: '3x4 m' },
    3: { tipe: 'Double Room', guests: 2, baths: 1, size: '4x4 m' },
    4: { tipe: 'VIP Room', guests: 2, baths: 1, size: '4x5 m' },
    5: { tipe: 'Single Room', guests: 1, baths: 1, size: '3x3 m' },
    6: { tipe: 'Single Room', guests: 1, baths: 1, size: '3x3 m' }
};

function getKamarMeta(item) {
    let nomor = parseInt(item.id) || 0;
    if (!nomor && item.nama_kamar) {
        let m = item.nama_kamar.match(/\d+/);
        if (m) nomor = parseInt(m[0]);
    }
    return kamarMetaMap[nomor] || { tipe: 'Kost Room', guests: 1, baths: 1, size: '3x4 m' };
}

// Render status & jumlah unit tersedia
function renderStatusGrid(data) {
    const sub = document.getElementById('jumlahKosong');
    if (sub && Array.isArray(data)) {
        const kosong = data.filter(function (d) { return d.status !== 'terisi'; }).length;
        sub.textContent = kosong + ' unit tersedia';
    }

    const grid = document.getElementById('gridIndikatorKamar');
    if (!grid) return; // Elemen sudah dihapus dari tampilan web

    grid.innerHTML = '';
    data.forEach(function (item) {
        const isTerisi = item.status === 'terisi';
        const chip = document.createElement('div');
        chip.className = 'status-chip ' + (isTerisi ? 'terisi' : 'kosong');
        chip.innerHTML =
            '<span class="chip-name">' + item.nama_kamar + '</span>' +
            '<span class="chip-status">' + (isTerisi ? '🔴 Terisi' : '🟢 Kosong') + '</span>';
        
        chip.addEventListener('click', function () {
            bukaDetail(item);
        });

        grid.appendChild(chip);
    });
}

// Render katalog kamar dalam bentuk kartu (Gaya Mewah / Hotel Elegance sesuai referensi)
function renderKatalog(data) {
    const container = document.getElementById('katalogKamar');
    if (!container) return;
    container.innerHTML = '';

    data.forEach(function (item) {
        const isTerisi = item.status === 'terisi';
        const fotoSrc  = getFotoKamar(item);
        const hargaFmt = 'Rp ' + parseInt(item.harga).toLocaleString('id-ID');
        const meta     = getKamarMeta(item);

        const card = document.createElement('div');
        card.className = 'kamar-card' + (isTerisi ? ' terisi' : '');
        card.dataset.nama   = item.nama_kamar;
        card.dataset.status = item.status || 'kosong';

        card.innerHTML =
            '<div class="card-img-wrap">' +
                '<img src="' + fotoSrc + '" alt="' + item.nama_kamar + '" ' +
                    'onerror="this.src=\'https://placehold.co/400x260/e8e7ff/4f46e5?text=No+Photo\'">' +
                '<div class="card-price-overlay">' +
                    '<span>From ' + hargaFmt + ' /bln</span>' +
                '</div>' +
                '<div class="card-status-pill ' + (isTerisi ? 'terisi' : 'kosong') + '">' +
                    (isTerisi ? 'Terisi' : 'Tersedia') +
                '</div>' +
            '</div>' +
            '<div class="card-body">' +
                '<h3 class="card-title-serif">' + meta.tipe + '</h3>' +
                '<div class="card-unit-name">' + item.nama_kamar + '</div>' +
                '<div class="card-meta-row">' +
                    '<span class="card-meta-item">' +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' +
                        ' ' + meta.guests + ' Orang' +
                    '</span>' +
                    '<span class="card-meta-item">' +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 12v7M20 12v7M2 19h20M4 14h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2z"/></svg>' +
                        ' ' + meta.baths + ' Km Mandi' +
                    '</span>' +
                    '<span class="card-meta-item">' +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>' +
                        ' ' + meta.size +
                    '</span>' +
                '</div>' +
                '<button type="button" class="btn-card-book ' + (isTerisi ? 'disabled' : '') + '">' +
                    (isTerisi ? 'TERISI' : 'BOOK NOW') +
                '</button>' +
            '</div>';

        // Klik tombol BOOK NOW -> langsung buka form booking untuk kamar ini
        const btnBook = card.querySelector('.btn-card-book');
        if (btnBook && !isTerisi) {
            btnBook.addEventListener('click', function (e) {
                e.stopPropagation();
                pilihKamar(item.nama_kamar, item.harga);
            });
        }

        // Klik kartu -> buka popup detail kamar dengan foto slider
        card.addEventListener('click', function () {
            bukaDetail(item);
        });

        container.appendChild(card);
    });
}

// =====================================================
//  SLIDER FOTO KAMAR DI MODAL DETAIL
// =====================================================
let currentSlideIndex = 0;
let activeSlideFotos  = [];

function setupDetailSlider(fotos, namaKamar) {
    activeSlideFotos = Array.isArray(fotos) && fotos.length > 0 ? fotos : [
        { src: 'https://placehold.co/600x400/e8e7ff/4f46e5?text=Foto+Kamar', keterangan: 'Kamar Kost' }
    ];
    currentSlideIndex = 0;

    const track = document.getElementById('detailSliderTrack');
    const dotsContainer = document.getElementById('sliderDots');
    const btnPrev = document.getElementById('sliderPrev');
    const btnNext = document.getElementById('sliderNext');

    if (!track) return;
    track.innerHTML = '';

    if (dotsContainer) dotsContainer.innerHTML = '';

    // Buat element untuk masing-masing slide
    activeSlideFotos.forEach(function (foto, idx) {
        const slide = document.createElement('div');
        slide.className = 'detail-slide';
        slide.innerHTML =
            '<img src="' + foto.src + '" alt="' + namaKamar + ' - Foto ' + (idx + 1) + '" class="detail-slide-img" ' +
                'onerror="this.src=\'https://placehold.co/600x400/e8e7ff/4f46e5?text=No+Photo\'">' +
            '<div class="detail-slide-caption">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>' +
                '<span>' + (foto.keterangan || ('Foto ' + (idx + 1))) + '</span>' +
            '</div>';
        track.appendChild(slide);

        // Buat dot navigasi
        if (dotsContainer) {
            const dot = document.createElement('span');
            dot.className = 'slider-dot' + (idx === 0 ? ' active' : '');
            dot.setAttribute('title', 'Lihat foto ' + (idx + 1));
            dot.addEventListener('click', function (e) {
                e.stopPropagation();
                pindahSlide(idx);
            });
            dotsContainer.appendChild(dot);
        }
    });

    // Jika hanya 1 foto, sembunyikan panah & dots
    const isSingle = activeSlideFotos.length <= 1;
    if (btnPrev) btnPrev.style.display = isSingle ? 'none' : 'flex';
    if (btnNext) btnNext.style.display = isSingle ? 'none' : 'flex';
    if (dotsContainer) dotsContainer.style.display = isSingle ? 'none' : 'flex';

    pindahSlide(0);
}

function pindahSlide(index) {
    if (!activeSlideFotos || activeSlideFotos.length === 0) return;

    const total = activeSlideFotos.length;
    // Looping navigasi (dari akhir kembali ke awal, atau sebaliknya)
    if (index >= total) {
        currentSlideIndex = 0;
    } else if (index < 0) {
        currentSlideIndex = total - 1;
    } else {
        currentSlideIndex = index;
    }

    const track = document.getElementById('detailSliderTrack');
    if (track) {
        track.style.transform = 'translateX(-' + (currentSlideIndex * 100) + '%)';
    }

    // Update Badge Counter
    const badgeText = document.getElementById('sliderBadgeText');
    if (badgeText) {
        const ket = activeSlideFotos[currentSlideIndex].keterangan;
        badgeText.textContent = (currentSlideIndex + 1) + ' / ' + total + (ket ? (' • ' + ket) : '');
    }

    // Update Active Dot
    const dots = document.querySelectorAll('.slider-dot');
    dots.forEach(function (d, idx) {
        if (idx === currentSlideIndex) {
            d.classList.add('active');
        } else {
            d.classList.remove('active');
        }
    });
}

function geserSlide(arah) {
    pindahSlide(currentSlideIndex + arah);
}

// Inisialisasi event geser touch (swipe smartphone), mouse drag, tombol panah, dan keyboard
function initDetailSliderEvents() {
    const btnPrev = document.getElementById('sliderPrev');
    const btnNext = document.getElementById('sliderNext');

    if (btnPrev) {
        btnPrev.addEventListener('click', function (e) {
            e.stopPropagation();
            geserSlide(-1);
        });
    }

    if (btnNext) {
        btnNext.addEventListener('click', function (e) {
            e.stopPropagation();
            geserSlide(1);
        });
    }

    const container = document.getElementById('detailSliderContainer');
    if (!container) return;

    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX   = 0;
    let isSwiping   = false;

    // Touch events untuk smartphone
    container.addEventListener('touchstart', function (e) {
        if (!e.touches || e.touches.length === 0) return;
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        touchEndX   = touchStartX;
        isSwiping   = true;
    }, { passive: true });

    container.addEventListener('touchmove', function (e) {
        if (!isSwiping || !e.touches || e.touches.length === 0) return;
        touchEndX = e.touches[0].clientX;
    }, { passive: true });

    container.addEventListener('touchend', function () {
        if (!isSwiping) return;
        isSwiping = false;
        const diffX = touchEndX - touchStartX;
        if (Math.abs(diffX) > 40) {
            if (diffX < 0) {
                geserSlide(1);  // Swipe ke kiri -> foto berikutnya
            } else {
                geserSlide(-1); // Swipe ke kanan -> foto sebelumnya
            }
        }
    });

    // Mouse drag events untuk desktop
    let isMouseDown = false;
    let mouseStartX = 0;

    container.addEventListener('mousedown', function (e) {
        isMouseDown = true;
        mouseStartX = e.clientX;
    });

    container.addEventListener('mouseup', function (e) {
        if (!isMouseDown) return;
        isMouseDown = false;
        const diffX = e.clientX - mouseStartX;
        if (Math.abs(diffX) > 45) {
            if (diffX < 0) {
                geserSlide(1);
            } else {
                geserSlide(-1);
            }
        }
    });

    container.addEventListener('mouseleave', function () {
        isMouseDown = false;
    });

    // Navigasi keyboard panah kiri/kanan saat modal detail terbuka
    window.addEventListener('keydown', function (e) {
        const modal = document.getElementById('modalDetail');
        if (modal && modal.style.display !== 'none') {
            if (e.key === 'ArrowLeft') {
                geserSlide(-1);
            } else if (e.key === 'ArrowRight') {
                geserSlide(1);
            } else if (e.key === 'Escape') {
                tutupDetail();
            }
        }
    });
}

// =====================================================
//  BUKA & TUTUP MODAL DETAIL KAMAR
// =====================================================
function bukaDetail(item) {
    unitDipilihData = item;

    const isTerisi = item.status === 'terisi';
    const hargaFmt = 'Rp ' + parseInt(item.harga).toLocaleString('id-ID') + '/bulan';

    const detailNama  = document.getElementById('detailNama');
    const detailHarga = document.getElementById('detailHarga');
    const detailBadge = document.getElementById('detailBadge');
    const btnBook     = document.getElementById('btnBookDetail');

    if (detailNama)  detailNama.textContent  = item.nama_kamar;
    if (detailHarga) detailHarga.textContent = hargaFmt;

    if (detailBadge) {
        detailBadge.textContent = isTerisi ? '🔴 Terisi' : '🟢 Tersedia';
        detailBadge.className   = 'detail-status-badge ' + (isTerisi ? 'terisi' : 'kosong');
    }

    if (btnBook) {
        if (isTerisi) {
            btnBook.disabled = true;
            btnBook.textContent = 'Kamar Penuh';
            btnBook.style.background = '#9ca3af';
            btnBook.style.cursor = 'not-allowed';
        } else {
            btnBook.disabled = false;
            btnBook.textContent = 'Pilih Unit Ini';
            btnBook.style.background = '';
            btnBook.style.cursor = 'pointer';
        }
    }

    // Siapkan slider dengan semua foto yang dimiliki kamar
    const daftarFoto = getDaftarFotoKamar(item);
    setupDetailSlider(daftarFoto, item.nama_kamar);

    const modalDetail = document.getElementById('modalDetail');
    if (modalDetail) modalDetail.style.display = 'flex';
}

function tutupDetail() {
    const modalDetail = document.getElementById('modalDetail');
    if (modalDetail) modalDetail.style.display = 'none';
}

// =====================================================
//  MODAL FORM BOOKING (POPUP TERSENDIRI)
// =====================================================
function bukaModalBooking() {
    const modalBooking = document.getElementById('modalBooking');
    if (modalBooking) {
        modalBooking.style.display = 'flex';
        // Pastikan kalkulasi harga terhitung
        hitungTotalHarga();
    }
}

function tutupModalBooking() {
    const modalBooking = document.getElementById('modalBooking');
    if (modalBooking) {
        modalBooking.style.display = 'none';
    }
}

// =====================================================
//  PILIH KAMAR DARI MODAL DETAIL
// =====================================================
function pilihKamar(namaUnit, harga) {
    const selectUnit = document.getElementById('unitPilihan');
    if (selectUnit) {
        selectUnit.value = namaUnit;
    }
    hargaPerBulanDipilih = parseInt(harga) || 0;
    
    const inputDurasi = document.getElementById('durasiBulan');
    if (inputDurasi && (!inputDurasi.value || parseInt(inputDurasi.value) < 1)) {
        inputDurasi.value = 1;
    }
    hitungTotalHarga();

    // Tutup modal detail kamar lalu buka modal popup pemesanan
    tutupDetail();
    setTimeout(function () {
        bukaModalBooking();
    }, 200);
}

// =====================================================
//  HITUNG TOTAL HARGA
// =====================================================
function hitungTotalHarga() {
    const durasiInput  = document.getElementById('durasiBulan');
    const tipeDurasiEl = document.getElementById('tipeDurasi');
    const infoBar      = document.getElementById('infoHargaSatuan');

    let durasi = parseInt(durasiInput ? durasiInput.value : 1) || 1;
    if (durasi < 1) { 
        durasi = 1; 
        if (durasiInput) durasiInput.value = 1; 
    }

    const tipe = tipeDurasiEl ? tipeDurasiEl.value : 'bulanan';
    const hargaHarian = hargaPerBulanDipilih > 0 ? Math.ceil(hargaPerBulanDipilih / 30) : 0;

    let total = 0;
    if (tipe === 'harian') {
        total = hargaHarian * durasi;
        if (infoBar && hargaHarian > 0) {
            infoBar.textContent = '💡 Tarif harian: Rp ' + hargaHarian.toLocaleString('id-ID') + ' / hari';
            infoBar.style.display = 'block';
        } else if (infoBar) {
            infoBar.style.display = 'none';
        }
    } else {
        total = hargaPerBulanDipilih * durasi;
        if (infoBar) infoBar.style.display = 'none';
    }

    const totalDisplay = document.getElementById('totalHargaDisplay');
    const totalVal     = document.getElementById('totalHargaVal');

    if (totalDisplay) {
        totalDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }
    if (totalVal) {
        totalVal.value = total;
    }
}

// =====================================================
//  FORM BOOKING - INTERCEPT UNTUK MEMILIH PEMBAYARAN
// =====================================================
function initFormBooking() {
    const formBooking = document.getElementById('formBooking');
    if (!formBooking) return;

    formBooking.addEventListener('submit', function (e) {
        e.preventDefault();

        const unitPilihan = document.getElementById('unitPilihan').value;
        if (!unitPilihan) {
            alert('Silakan pilih unit kamar kost terlebih dahulu!');
            return;
        }

        const namaInput    = formBooking.querySelector('input[name="nama"]').value.trim();
        const durasiInput  = document.getElementById('durasiBulan').value;
        const totalVal     = document.getElementById('totalHargaVal').value;
        const totalDisplay = document.getElementById('totalHargaDisplay').textContent;

        if (!namaInput) {
            alert('Silakan masukkan nama lengkap pemesan!');
            return;
        }

        // Simpan data sementara ke global state
        pendingBookingData = {
            nama: namaInput,
            unit: unitPilihan,
            durasi: durasiInput,
            tipe_durasi: document.getElementById('tipeDurasi') ? document.getElementById('tipeDurasi').value : 'bulanan',
            total: totalVal,
            totalFormatted: totalDisplay
        };

        // Tutup modal form booking, kemudian buka modal metode pembayaran
        tutupModalBooking();
        setTimeout(function () {
            bukaPembayaran(totalDisplay);
        }, 150);
    });
}

// =====================================================
//  MODAL METODE PEMBAYARAN
// =====================================================
function bukaPembayaran(totalFormatted) {
    const tagihanDisplay = document.getElementById('tagihanDisplay');
    if (tagihanDisplay) {
        tagihanDisplay.textContent = totalFormatted;
    }

    const modalPembayaran = document.getElementById('modalPembayaran');
    if (modalPembayaran) {
        modalPembayaran.style.display = 'flex';
    }
}

function tutupPembayaran() {
    const modalPembayaran = document.getElementById('modalPembayaran');
    if (modalPembayaran) {
        modalPembayaran.style.display = 'none';
    }
}

// Dipanggil saat salah satu tombol metode pembayaran diklik
function pilihMetode(metodeName) {
    if (!pendingBookingData) {
        alert('Data booking tidak ditemukan. Silakan isi form kembali.');
        tutupPembayaran();
        return;
    }

    const bookedData = Object.assign({}, pendingBookingData);

    // Siapkan FormData untuk dikirim ke backend
    const formData = new FormData();
    formData.append('nama', bookedData.nama);
    formData.append('unit', bookedData.unit);
    formData.append('durasi', bookedData.durasi);
    formData.append('tipe_durasi', bookedData.tipe_durasi || 'bulanan');
    formData.append('total', bookedData.total);
    formData.append('metode', metodeName);

    // Kirim request ke backend
    fetch('../api/proses_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(function (response) { return response.json(); })
    .then(function (res) {
        if (res.status === 'success') {
            selesaikanBooking(bookedData, metodeName);
        } else {
            alert('Perhatian: ' + (res.message || 'Gagal menyimpan booking ke server.'));
            selesaikanBooking(bookedData, metodeName);
        }
    })
    .catch(function (err) {
        console.warn('Gagal terhubung ke API backend, melanjutkan proses via konfirmasi WhatsApp:', err);
        // Tetap lanjutkan agar pemesan tidak macet
        selesaikanBooking(bookedData, metodeName);
    });
}

function selesaikanBooking(bookedData, metodeName) {
    pendingBookingData = null;

    // Tutup modal booking dan pembayaran
    tutupModalBooking();
    tutupPembayaran();

    // Reset form booking
    const formBooking = document.getElementById('formBooking');
    if (formBooking) formBooking.reset();
    hargaPerBulanDipilih = 0;
    unitDipilihData = null;
    // Reset tipe durasi ke default bulanan
    const tipeDurasiEl = document.getElementById('tipeDurasi');
    if (tipeDurasiEl) tipeDurasiEl.value = 'bulanan';
    const labelSatuan = document.getElementById('labelSatuanDurasi');
    if (labelSatuan) labelSatuan.textContent = 'Bulan';
    const btnHarian  = document.getElementById('btnTipeHarian');
    const btnBulanan = document.getElementById('btnTipeBulanan');
    if (btnHarian)  btnHarian.classList.remove('active');
    if (btnBulanan) btnBulanan.classList.add('active');
    const infoBar = document.getElementById('infoHargaSatuan');
    if (infoBar) infoBar.style.display = 'none';
    hitungTotalHarga();

    // Update status kamar langsung di memori tampilan
    const kmr = daftarKamarData.find(function (k) { return k.nama_kamar === bookedData.unit; });
    if (kmr) {
        kmr.status = 'terisi';
    }
    renderSelectOptions(daftarKamarData);
    renderStatusGrid(daftarKamarData);
    renderKatalog(daftarKamarData);

    // Sinkronkan kembali dengan data fresh dari database
    muatDataKamar();

    // Tampilkan popup sukses booking dengan metode pembayaran
    tampilPopupSukses(
        bookedData.nama,
        bookedData.unit,
        bookedData.durasi,
        bookedData.tipe_durasi || 'bulanan',
        metodeName,
        bookedData.totalFormatted
    );
}

// =====================================================
//  MODAL SUKSES & WHATSAPP
// =====================================================
function tampilPopupSukses(nama, unit, durasi, tipeDurasi, metode, total) {
    const popupNama   = document.getElementById('popupNama');
    const popupUnit   = document.getElementById('popupUnit');
    const popupDurasi = document.getElementById('popupDurasi');
    const popupMetode = document.getElementById('popupMetode');
    const popupTotal  = document.getElementById('popupTotal');

    const satuanLabel = tipeDurasi === 'harian' ? 'Hari' : 'Bulan';
    const tipeLabel   = tipeDurasi === 'harian' ? 'Harian' : 'Bulanan';

    if (popupNama)   popupNama.textContent   = nama;
    if (popupUnit)   popupUnit.textContent   = unit;
    if (popupDurasi) popupDurasi.textContent = durasi + ' ' + satuanLabel + ' (' + tipeLabel + ')';
    if (popupMetode) popupMetode.textContent = metode;
    if (popupTotal)  popupTotal.textContent  = total;

    // Susun pesan WhatsApp konfirmasi
    const pesan =
        'Halo, saya ingin konfirmasi booking kost:\n\n' +
        '📋 Unit Kamar: ' + unit + '\n' +
        '👤 Nama Pemesan: ' + nama + '\n' +
        '📅 Durasi Sewa: ' + durasi + ' ' + satuanLabel + ' (' + tipeLabel + ')\n' +
        '💳 Metode Bayar: ' + metode + '\n' +
        '💰 Total Tagihan: ' + total + '\n\n' +
        'Mohon konfirmasi pesanan saya. Terima kasih! 🙏';

    const btnWhatsapp = document.getElementById('btnWhatsapp');
    if (btnWhatsapp) {
        btnWhatsapp.href = 'https://wa.me/' + NOMOR_WA + '?text=' + encodeURIComponent(pesan);
    }

    const modalSukses = document.getElementById('modalSukses');
    if (modalSukses) {
        modalSukses.style.display = 'flex';
    }
}

function tutupPopupSukses() {
    const modalSukses = document.getElementById('modalSukses');
    if (modalSukses) {
        modalSukses.style.display = 'none';
    }
}

// =====================================================
//  GLOBAL MODAL EVENT LISTENERS
// =====================================================
function initModals() {
    // Tombol tutup modal sukses
    const btnTutupModal = document.getElementById('btnTutupModal');
    if (btnTutupModal) {
        btnTutupModal.addEventListener('click', tutupPopupSukses);
    }

    // Klik background overlay untuk menutup
    const modalSukses = document.getElementById('modalSukses');
    if (modalSukses) {
        modalSukses.addEventListener('click', function (e) {
            if (e.target === modalSukses) tutupPopupSukses();
        });
    }

    const modalDetail = document.getElementById('modalDetail');
    if (modalDetail) {
        modalDetail.addEventListener('click', function (e) {
            if (e.target === modalDetail) tutupDetail();
        });
    }

    const modalPembayaran = document.getElementById('modalPembayaran');
    if (modalPembayaran) {
        modalPembayaran.addEventListener('click', function (e) {
            if (e.target === modalPembayaran) tutupPembayaran();
        });
    }

    const modalBooking = document.getElementById('modalBooking');
    if (modalBooking) {
        modalBooking.addEventListener('click', function (e) {
            if (e.target === modalBooking) tutupModalBooking();
        });
    }

    // Tombol Pilih Unit Ini di modal detail
    const btnBookDetail = document.getElementById('btnBookDetail');
    if (btnBookDetail) {
        btnBookDetail.addEventListener('click', function () {
            if (!unitDipilihData || unitDipilihData.status === 'terisi') return;
            pilihKamar(unitDipilihData.nama_kamar, unitDipilihData.harga);
        });
    }
}
