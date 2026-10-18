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
    { id: "4", nama_kamar: "Kamar 4", harga: 550000, gambar: "kamar.jpg", status: "kosong" },
    { id: "5", nama_kamar: "Kamar 5", harga: 600000, gambar: "kamar4.jpg", status: "kosong" },
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
//  DURASI STEPPER (+ / -)
// =====================================================
function initDurasiStepper() {
    const inputDurasi = document.getElementById('durasiBulan');
    const btnKurang   = document.getElementById('btnKurang');
    const btnTambah   = document.getElementById('btnTambah');
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
    fetch('../api/get_kamar.php')
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
        opt.textContent = item.nama_kamar + ' - ' + hargaFmt + '/bln' + (isTerisi ? ' (🔴 Terisi)' : ' (🟢 Tersedia)');

        if (isTerisi) {
            opt.disabled = true;
        }

        selectUnit.appendChild(opt);
    });

    if (currentVal) {
        selectUnit.value = currentVal;
    }
}

// Render status grid (kotak indikator ketersediaan)
function renderStatusGrid(data) {
    const grid = document.getElementById('gridIndikatorKamar');
    const sub  = document.getElementById('jumlahKosong');
    if (!grid) return;

    grid.innerHTML = '';
    const kosong = data.filter(function (d) { return d.status !== 'terisi'; }).length;
    if (sub) sub.textContent = kosong + ' unit tersedia';

    data.forEach(function (item) {
        const isTerisi = item.status === 'terisi';
        const chip = document.createElement('div');
        chip.className = 'status-chip ' + (isTerisi ? 'terisi' : 'kosong');
        chip.innerHTML =
            '<span class="chip-name">' + item.nama_kamar + '</span>' +
            '<span class="chip-status">' + (isTerisi ? '🔴 Terisi' : '🟢 Kosong') + '</span>';
        
        chip.addEventListener('click', function () {
            const fotoSrc = (item.gambar && item.gambar.trim() !== '')
                ? 'uploads/' + item.gambar
                : 'https://placehold.co/300x200/e8e7ff/4f46e5?text=' + encodeURIComponent(item.nama_kamar);
            bukaDetail(item, fotoSrc);
        });

        grid.appendChild(chip);
    });
}

// Render katalog kamar dalam bentuk kartu
function renderKatalog(data) {
    const container = document.getElementById('katalogKamar');
    if (!container) return;
    container.innerHTML = '';

    data.forEach(function (item) {
        const isTerisi = item.status === 'terisi';
        const fotoSrc = (item.gambar && item.gambar.trim() !== '')
            ? 'uploads/' + item.gambar
            : 'https://placehold.co/300x200/e8e7ff/4f46e5?text=' + encodeURIComponent(item.nama_kamar);
        const hargaFmt = 'Rp ' + parseInt(item.harga).toLocaleString('id-ID');

        const card = document.createElement('div');
        card.className = 'kamar-card' + (isTerisi ? ' terisi' : '');
        card.dataset.nama   = item.nama_kamar;
        card.dataset.status = item.status || 'kosong';

        card.innerHTML =
            '<div class="card-img-wrap">' +
                '<img src="' + fotoSrc + '" alt="' + item.nama_kamar + '" ' +
                    'onerror="this.src=\'https://placehold.co/300x200/e8e7ff/4f46e5?text=No+Photo\'">' +
                '<span class="card-status-badge ' + (isTerisi ? 'terisi' : 'kosong') + '">' +
                    (isTerisi ? 'Terisi' : 'Tersedia') +
                '</span>' +
            '</div>' +
            '<div class="card-body">' +
                '<div class="card-name">' + item.nama_kamar + '</div>' +
                '<div class="card-loc">' +
                    '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">' +
                    '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>' +
                    '<circle cx="12" cy="9" r="2.5"/></svg>' +
                    ' Kost Nyaman' +
                '</div>' +
                '<div class="card-stars">★★★★★</div>' +
                '<div class="card-price">' + hargaFmt + '<span>/bln</span></div>' +
            '</div>';

        // Klik kartu -> buka popup detail kamar
        card.addEventListener('click', function () {
            bukaDetail(item, fotoSrc);
        });

        container.appendChild(card);
    });
}

// =====================================================
//  MODAL DETAIL KAMAR
// =====================================================
function bukaDetail(item, fotoSrc) {
    unitDipilihData = item;

    const isTerisi = item.status === 'terisi';
    const hargaFmt = 'Rp ' + parseInt(item.harga).toLocaleString('id-ID') + '/bulan';

    const detailFoto = document.getElementById('detailFoto');
    const detailNama = document.getElementById('detailNama');
    const detailHarga = document.getElementById('detailHarga');
    const detailBadge = document.getElementById('detailBadge');
    const btnBook = document.getElementById('btnBookDetail');

    if (detailFoto) detailFoto.src = fotoSrc;
    if (detailNama) detailNama.textContent  = item.nama_kamar;
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

    const modalDetail = document.getElementById('modalDetail');
    if (modalDetail) modalDetail.style.display = 'flex';
}

function tutupDetail() {
    const modalDetail = document.getElementById('modalDetail');
    if (modalDetail) modalDetail.style.display = 'none';
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

    // Tutup modal detail dan scroll ke form booking
    tutupDetail();
    setTimeout(function () {
        const sec = document.getElementById('sectionBooking');
        if (sec) sec.scrollIntoView({ behavior: 'smooth' });
    }, 250);
}

// =====================================================
//  HITUNG TOTAL HARGA
// =====================================================
function hitungTotalHarga() {
    const durasiInput = document.getElementById('durasiBulan');
    let durasi = parseInt(durasiInput ? durasiInput.value : 1) || 1;
    if (durasi < 1) { 
        durasi = 1; 
        if (durasiInput) durasiInput.value = 1; 
    }

    const total = hargaPerBulanDipilih * durasi;
    const totalDisplay = document.getElementById('totalHargaDisplay');
    const totalVal = document.getElementById('totalHargaVal');

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
            total: totalVal,
            totalFormatted: totalDisplay
        };

        // Tampilkan modal pilihan metode pembayaran sebelum diproses
        bukaPembayaran(totalDisplay);
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
    formData.append('total', bookedData.total);
    formData.append('metode', metodeName);

    // Kirim request ke backend
    fetch('../api/proses_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(function (response) { return response.json(); })
    .then(function (res) {
        selesaikanBooking(bookedData, metodeName);
    })
    .catch(function (err) {
        console.warn('Gagal terhubung ke API backend, melanjutkan proses via konfirmasi WhatsApp:', err);
        // Tetap lanjutkan agar pemesan tidak macet
        selesaikanBooking(bookedData, metodeName);
    });
}

function selesaikanBooking(bookedData, metodeName) {
    pendingBookingData = null;

    // Tutup modal pembayaran
    tutupPembayaran();

    // Reset form booking
    const formBooking = document.getElementById('formBooking');
    if (formBooking) formBooking.reset();
    hargaPerBulanDipilih = 0;
    unitDipilihData = null;
    hitungTotalHarga();

    // Perbarui data kamar dari database
    muatDataKamar();

    // Tampilkan popup sukses booking dengan metode pembayaran
    tampilPopupSukses(
        bookedData.nama,
        bookedData.unit,
        bookedData.durasi,
        metodeName,
        bookedData.totalFormatted
    );
}

// =====================================================
//  MODAL SUKSES & WHATSAPP
// =====================================================
function tampilPopupSukses(nama, unit, durasi, metode, total) {
    const popupNama = document.getElementById('popupNama');
    const popupUnit = document.getElementById('popupUnit');
    const popupDurasi = document.getElementById('popupDurasi');
    const popupMetode = document.getElementById('popupMetode');
    const popupTotal = document.getElementById('popupTotal');

    if (popupNama) popupNama.textContent = nama;
    if (popupUnit) popupUnit.textContent = unit;
    if (popupDurasi) popupDurasi.textContent = durasi + ' Bulan';
    if (popupMetode) popupMetode.textContent = metode;
    if (popupTotal) popupTotal.textContent = total;

    // Susun pesan WhatsApp konfirmasi
    const pesan =
        'Halo, saya ingin konfirmasi booking kost:\n\n' +
        '📋 Unit Kamar: ' + unit + '\n' +
        '👤 Nama Pemesan: ' + nama + '\n' +
        '📅 Durasi Sewa: ' + durasi + ' Bulan\n' +
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

    // Tombol Pilih Unit Ini di modal detail
    const btnBookDetail = document.getElementById('btnBookDetail');
    if (btnBookDetail) {
        btnBookDetail.addEventListener('click', function () {
            if (!unitDipilihData || unitDipilihData.status === 'terisi') return;
            pilihKamar(unitDipilihData.nama_kamar, unitDipilihData.harga);
        });
    }
}