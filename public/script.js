// =====================================================
//  CONFIG
// =====================================================
let hargaPerBulanDipilih = 0;
let unitDipilihData = null;
const NOMOR_WA = '6282138358117';

// =====================================================
//  INIT
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    muatDataKamar();
    initFormBooking();
    initModals();
    initFilterTabs();
    initSearchToggle();
    initDurasiStepper();
});

// =====================================================
//  SEARCH TOGGLE
// =====================================================
function initSearchToggle() {
    const btnSearch   = document.getElementById('btnSearch');
    const searchWrap  = document.getElementById('searchBarWrap');
    const searchInput = document.getElementById('searchInput');
    if (!btnSearch) return;

    btnSearch.addEventListener('click', function () {
        const isOpen = searchWrap.style.display !== 'none';
        searchWrap.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) searchInput.focus();
    });

    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        document.querySelectorAll('.kamar-card').forEach(function (card) {
            const name = (card.dataset.nama || '').toLowerCase();
            card.style.display = name.includes(query) ? '' : 'none';
        });
    });
}

// =====================================================
//  FILTER TABS
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
//  DURASI STEPPER
// =====================================================
function initDurasiStepper() {
    const inputDurasi = document.getElementById('durasiBulan');
    const btnKurang   = document.getElementById('btnKurang');
    const btnTambah   = document.getElementById('btnTambah');
    if (!inputDurasi) return;

    inputDurasi.addEventListener('input', hitungTotalHarga);

    if (btnKurang) {
        btnKurang.addEventListener('click', function () {
            var v = parseInt(inputDurasi.value) || 1;
            if (v > 1) { inputDurasi.value = v - 1; hitungTotalHarga(); }
        });
    }
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            var v = parseInt(inputDurasi.value) || 1;
            inputDurasi.value = v + 1;
            hitungTotalHarga();
        });
    }
}

// =====================================================
//  LOAD DATA KAMAR
// =====================================================
function muatDataKamar() {
    fetch('../api/get_kamar.php')
        .then(function (res) { return res.json(); })
        .then(function (data) {
            renderStatusGrid(data);
            renderKatalog(data);
        })
        .catch(function (err) { console.error('Gagal memuat kamar:', err); });
}

// Render status grid (kotak indikator)
function renderStatusGrid(data) {
    var grid = document.getElementById('gridIndikatorKamar');
    var sub  = document.getElementById('jumlahKosong');
    if (!grid) return;

    grid.innerHTML = '';
    var kosong = data.filter(function (d) { return d.status !== 'terisi'; }).length;
    if (sub) sub.textContent = kosong + ' unit tersedia';

    data.forEach(function (item) {
        var isTerisi = item.status === 'terisi';
        var chip = document.createElement('div');
        chip.className = 'status-chip ' + (isTerisi ? 'terisi' : 'kosong');
        chip.innerHTML =
            '<span class="chip-name">' + item.nama_kamar + '</span>' +
            '<span class="chip-status">' + (isTerisi ? '🔴 Terisi' : '🟢 Kosong') + '</span>';
        grid.appendChild(chip);
    });
}

// Render katalog card grid
function renderKatalog(data) {
    var container = document.getElementById('katalogKamar');
    if (!container) return;
    container.innerHTML = '';

    data.forEach(function (item) {
        var isTerisi = item.status === 'terisi';
        var fotoSrc = (item.gambar && item.gambar.trim() !== '')
            ? 'uploads/' + item.gambar
            : 'https://placehold.co/300x200/e8e7ff/4f46e5?text=' + encodeURIComponent(item.nama_kamar);
        var hargaFmt = 'Rp ' + parseInt(item.harga).toLocaleString('id-ID');

        var card = document.createElement('div');
        card.className   = 'kamar-card' + (isTerisi ? ' terisi' : '');
        card.dataset.nama   = item.nama_kamar;
        card.dataset.status = item.status || 'kosong';

        card.innerHTML =
            '<div class="card-img-wrap">' +
                '<img src="' + fotoSrc + '" alt="' + item.nama_kamar + '" ' +
                    'onerror="this.src=\'https://placehold.co/300x200/e8e7ff/4f46e5?text=No+Photo\'">' +
                '<span class="card-status-badge ' + (isTerisi ? 'terisi' : 'kosong') + '">' +
                    (isTerisi ? 'Terisi' : 'Tersedia') +
                '</span>' +
                '<span class="card-fav">♡</span>' +
            '</div>' +
            '<div class="card-body">' +
                '<div class="card-name">' + item.nama_kamar + '</div>' +
                '<div class="card-loc">' +
                    '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">' +
                    '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>' +
                    '<circle cx="12" cy="9" r="2.5"/></svg>' +
                    ' Bandung, Indonesia' +
                '</div>' +
                '<div class="card-stars">★★★★☆</div>' +
                '<div class="card-price">' + hargaFmt + '<span>/bln</span></div>' +
            '</div>';

        // Klik card → buka modal detail
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

    var isTerisi = item.status === 'terisi';
    var hargaFmt = 'Rp ' + parseInt(item.harga).toLocaleString('id-ID') + '/bulan';

    document.getElementById('detailFoto').src = fotoSrc;
    document.getElementById('detailNama').textContent  = item.nama_kamar;
    document.getElementById('detailHarga').textContent = hargaFmt;

    var badge = document.getElementById('detailBadge');
    badge.textContent = isTerisi ? '🔴 Terisi' : '🟢 Tersedia';
    badge.className   = 'detail-status-badge ' + (isTerisi ? 'terisi' : 'kosong');

    var btnBook = document.getElementById('btnBookDetail');
    if (isTerisi) {
        btnBook.disabled = true;
        btnBook.textContent = 'Kamar Penuh';
        btnBook.style.background = '#9ca3af';
        btnBook.style.cursor = 'not-allowed';
    } else {
        btnBook.disabled = false;
        btnBook.textContent = 'Book Now';
        btnBook.style.background = '';
        btnBook.style.cursor = '';
    }

    document.getElementById('modalDetail').style.display = 'flex';
}

function tutupDetail() {
    document.getElementById('modalDetail').style.display = 'none';
}

// =====================================================
//  FORM BOOKING
// =====================================================
function initFormBooking() {
    var formBooking = document.getElementById('formBooking');
    if (!formBooking) return;

    formBooking.addEventListener('submit', function (e) {
        e.preventDefault();

        var unitPilihan = document.getElementById('unitPilihan').value;
        if (!unitPilihan) {
            alert('Silakan pilih unit kamar terlebih dahulu!\nKlik kartu kamar lalu tekan "Book Now".');
            return;
        }

        var namaInput    = formBooking.querySelector('input[name="nama"]').value;
        var durasiInput  = document.getElementById('durasiBulan').value;
        var totalDisplay = document.getElementById('totalHargaDisplay').textContent;

        var formData = new FormData(formBooking);

        fetch('../api/proses_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(function (response) { return response.json(); })
        .then(function (res) {
            if (res.status === 'success') {
                formBooking.reset();
                document.getElementById('totalHargaDisplay').textContent = 'Rp 0';
                document.getElementById('totalHargaVal').value = '0';
                hargaPerBulanDipilih = 0;
                unitDipilihData = null;
                muatDataKamar();
                tampilPopup(namaInput, unitPilihan, durasiInput, totalDisplay);
            } else {
                alert('Error: ' + res.message);
            }
        })
        .catch(function (err) {
            console.error('Gagal mengirim data:', err);
            alert('Terjadi kesalahan koneksi. Cek console untuk detail.');
        });
    });
}

// =====================================================
//  PILIH KAMAR — dipanggil dari tombol Book Now di modal
// =====================================================
function pilihKamar(namaUnit, harga) {
    document.getElementById('unitPilihan').value = namaUnit;
    hargaPerBulanDipilih = parseInt(harga);
    document.getElementById('durasiBulan').value = 1;
    hitungTotalHarga();

    // Tutup modal dan scroll ke form
    tutupDetail();
    setTimeout(function () {
        var sec = document.getElementById('sectionBooking');
        if (sec) sec.scrollIntoView({ behavior: 'smooth' });
    }, 200);
}

// =====================================================
//  HITUNG TOTAL HARGA
// =====================================================
function hitungTotalHarga() {
    var durasiInput = document.getElementById('durasiBulan');
    var durasi = parseInt(durasiInput.value) || 1;
    if (durasi < 1) { durasi = 1; durasiInput.value = 1; }

    var total = hargaPerBulanDipilih * durasi;
    document.getElementById('totalHargaDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('totalHargaVal').value = total;
}

// =====================================================
//  MODAL SUKSES & WHATSAPP
// =====================================================
function initModals() {
    var btnTutup = document.getElementById('btnTutupModal');
    if (btnTutup) btnTutup.addEventListener('click', tutupPopup);

    var modalSukses = document.getElementById('modalSukses');
    if (modalSukses) {
        modalSukses.addEventListener('click', function (e) {
            if (e.target === modalSukses) tutupPopup();
        });
    }

    var modalDetail = document.getElementById('modalDetail');
    if (modalDetail) {
        modalDetail.addEventListener('click', function (e) {
            if (e.target === modalDetail) tutupDetail();
        });
    }

    // Tombol Book Now di dalam modal detail
    var btnBookDetail = document.getElementById('btnBookDetail');
    if (btnBookDetail) {
        btnBookDetail.addEventListener('click', function () {
            if (!unitDipilihData || unitDipilihData.status === 'terisi') return;
            pilihKamar(unitDipilihData.nama_kamar, unitDipilihData.harga);
        });
    }
}

function tampilPopup(nama, unit, durasi, total) {
    document.getElementById('popupNama').textContent   = nama;
    document.getElementById('popupUnit').textContent   = unit;
    document.getElementById('popupDurasi').textContent = durasi + ' Bulan';
    document.getElementById('popupTotal').textContent  = total;

    var pesan =
        'Halo, saya ingin konfirmasi booking kost:\n\n' +
        '📋 Unit   : ' + unit + '\n' +
        '👤 Nama   : ' + nama + '\n' +
        '📅 Durasi : ' + durasi + ' Bulan\n' +
        '💰 Total  : ' + total + '\n\n' +
        'Mohon informasi selanjutnya, terima kasih! 🙏';

    var urlWA = 'https://wa.me/' + NOMOR_WA + '?text=' + encodeURIComponent(pesan);
    document.getElementById('btnWhatsapp').href = urlWA;

    document.getElementById('modalSukses').style.display = 'flex';
}

function tutupPopup() {
    document.getElementById('modalSukses').style.display = 'none';
}