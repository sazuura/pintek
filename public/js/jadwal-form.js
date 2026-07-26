// Logic form Jadwal Rapat (Tambah & Ubah) - dimuat lewat <script src> di kedua halaman, elemen yang diakses di bawah selalu ada.

// ── Platform hint & Link Zoom otomatis ─────────────────────────────────────
function platformPakaiZoom(v) {
    return v.includes('Zoom') || v === 'Hybrid';
}

// Field Keterangan dipakai bergantian: lokasi (Offline) vs link meeting (Online/Hybrid) - disimpan & dikembalikan saat platform ganti kategori.
var platformSebelumnyaOffline = document.getElementById('platform').value.includes('Offline');
var keteranganSebelumOffline = '';
var linkOtomatisSebelumOffline = false;

document.getElementById('platform').addEventListener('change', function () {
    var v = this.value;
    var hint = document.getElementById('ket-hint');
    var inp = document.getElementById('keterangan');
    var lokasiWrap = document.getElementById('lokasi-fisik-wrap');
    var zoomCheckbox = document.getElementById('link_otomatis');
    var pakaiZoom = platformPakaiZoom(v);

    var sekarangOffline = v.includes('Offline');
    if (sekarangOffline && !platformSebelumnyaOffline) {
        keteranganSebelumOffline = inp.value;
        linkOtomatisSebelumOffline = zoomCheckbox.checked;
        inp.value = '';
    } else if (!sekarangOffline && platformSebelumnyaOffline) {
        inp.value = keteranganSebelumOffline;
        if (pakaiZoom && linkOtomatisSebelumOffline) {
            zoomCheckbox.checked = true;
            syncZoomCheckboxUI(false);
        }
        linkOtomatisSebelumOffline = false;
    }
    platformSebelumnyaOffline = sekarangOffline;

    if (v.includes('Offline')) {
        hint.textContent = 'Masukkan lokasi rapat (Gedung, Ruangan, Lantai).';
        inp.placeholder = 'cth: Gedung A Lt.2 Ruang Rapat 1';
        lokasiWrap.style.display = 'none';
    } else if (v.includes('Online')) {
        hint.textContent = 'Keterangan tambahan (opsional), misalnya link meeting.';
        inp.placeholder = 'cth: https://zoom.us/j/xxxxxxx';
        lokasiWrap.style.display = 'none';
    } else if (v === 'Hybrid') {
        hint.textContent = 'Isi link meeting di sini (bisa dibuat otomatis lewat opsi di bawah).';
        inp.placeholder = 'cth: https://zoom.us/j/xxxxxxx';
        lokasiWrap.style.display = '';
    }

    var zoomWrap = document.getElementById('link-otomatis-wrap');
    zoomWrap.style.display = pakaiZoom ? '' : 'none';
    if (!pakaiZoom && zoomCheckbox.checked) {
        zoomCheckbox.checked = false;
        zoomCheckbox.dispatchEvent(new Event('change'));
    }
});

function syncZoomCheckboxUI(isFreshToggle) {
    var checkbox = document.getElementById('link_otomatis');
    var inp = document.getElementById('keterangan');
    var akunWrap = document.getElementById('zoom-akun-wrap');
    if (checkbox.checked) {
        if (isFreshToggle) {
            inp.value = '';
            inp.placeholder = 'Link akan dibuat otomatis setelah disimpan';
        }
        inp.readOnly = true;
        inp.style.backgroundColor = '#f3f4f6';
        akunWrap.style.display = '';
        refreshZoomAkunOptions();
    } else {
        inp.readOnly = false;
        inp.style.backgroundColor = '';
        akunWrap.style.display = 'none';
    }
}

document.getElementById('link_otomatis').addEventListener('change', function () {
    syncZoomCheckboxUI(true);
});

// ── Pilih Akun Zoom: disable opsi yang bentrok jadwal di tanggal+jam ini ───
function refreshZoomAkunOptions() {
    var select = document.getElementById('zoom_akun_pilihan');
    if (!select) return;
    var tanggal = document.getElementById('tanggal').value;
    var mulai   = document.getElementById('waktu_mulai').value;
    var selesai = document.getElementById('waktu_selesai').value;

    Array.from(select.options).forEach(function (opt) {
        if (!opt.value) return; // skip opsi "Otomatis"

        var daftarJadwal = [];
        try { daftarJadwal = JSON.parse(opt.dataset.jadwal || '[]'); } catch (e) { }

        var bentrok = !!(tanggal && mulai && selesai) && daftarJadwal.some(function (j) {
            return j.tanggal === tanggal && mulai < j.selesai && selesai > j.mulai;
        });

        opt.disabled = bentrok;
        var namaBersih = opt.textContent.replace(' (bentrok jadwal)', '');
        opt.textContent = bentrok ? namaBersih + ' (bentrok jadwal)' : namaBersih;

        if (bentrok && select.value === opt.value) {
            select.value = '';
        }
    });
}

['tanggal', 'waktu_mulai', 'waktu_selesai'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', refreshZoomAkunOptions);
});

// ── Operator: hide yang sudah dipilih, disable yang bentrok tanggal ────────
function getSelectedOperators() {
    return Array.from(document.querySelectorAll('.operator-select'))
        .map(function (s) { return s.value; })
        .filter(function (v) { return v !== ''; });
}

function getTanggal() {
    var tglInput = document.getElementById('tanggal');
    return tglInput ? tglInput.value : '';
}

function refreshOperatorOptions() {
    var selected = getSelectedOperators();
    var tanggal = getTanggal();

    document.querySelectorAll('.operator-select').forEach(function (select) {
        var currentVal = select.value;
        Array.from(select.options).forEach(function (opt) {
            if (!opt.value) return;

            var jadwalDates = [];
            try { jadwalDates = JSON.parse(opt.dataset.jadwal || '[]'); } catch (e) { }

            var isSelectedElsewhere = selected.includes(opt.value) && opt.value !== currentVal;
            var isBentrok = tanggal && jadwalDates.includes(tanggal) && opt.value !== currentVal;

            // Sudah dipilih di baris lain → sembunyikan
            opt.hidden = isSelectedElsewhere;
            // Bentrok jadwal → disable tapi tetap tampil dengan badge keterangan
            opt.disabled = isBentrok && !isSelectedElsewhere;

            if (isBentrok && !isSelectedElsewhere) {
                opt.dataset.badge = 'Jadwal Bentrok';
            } else {
                delete opt.dataset.badge;
            }
        });
    });
    window.SearchableSelect && window.SearchableSelect.refreshAll();
}

var tglEl = document.getElementById('tanggal');
if (tglEl) {
    tglEl.addEventListener('change', refreshOperatorOptions);
}

function removeOperator(btn) {
    var list = document.getElementById('operator-list');
    if (list.children.length > 1) {
        btn.closest('.dynamic-item').remove();
        refreshOperatorOptions();
    }
    updateRemoveButtons();
}

function addOperator() {
    var list = document.getElementById('operator-list');
    var first = list.querySelector('.dynamic-item');
    var clone = first.cloneNode(true);

    clone.querySelectorAll('option').forEach(function (opt) {
        delete opt.dataset.badge;
        opt.hidden = false;
        opt.disabled = false;
    });

    var select = clone.querySelector('select');
    select.value = '';
    select.onchange = refreshOperatorOptions;
    window.SearchableSelect && window.SearchableSelect.reinitRow(clone);

    clone.querySelector('.btn-remove').disabled = false;
    clone.querySelector('.btn-remove').onclick = function () { removeOperator(this); };

    list.appendChild(clone);
    refreshOperatorOptions();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    var items = document.querySelectorAll('#operator-list .dynamic-item');
    items.forEach(function (item) {
        item.querySelector('.btn-remove').disabled = items.length <= 1;
    });
}

document.getElementById('add-operator').addEventListener('click', addOperator);

// ── Alat yang dibutuhkan: cuma cegah alat yang sama dipilih dobel ──────────
function refreshPeralatanOptions() {
    var selected = Array.from(document.querySelectorAll('.peralatan-select'))
        .map(function (s) { return s.value; })
        .filter(function (v) { return v !== ''; });

    document.querySelectorAll('.peralatan-select').forEach(function (select) {
        var currentVal = select.value;
        Array.from(select.options).forEach(function (opt) {
            if (!opt.value) return;
            opt.hidden = selected.includes(opt.value) && opt.value !== currentVal;
        });

        var jumlahInput = select.closest('.dynamic-item').querySelector('.peralatan-jumlah');
        if (currentVal) {
            jumlahInput.style.display = '';
            if (!jumlahInput.value) jumlahInput.value = 1;
        } else {
            jumlahInput.style.display = 'none';
            jumlahInput.value = '';
        }
    });
    window.SearchableSelect && window.SearchableSelect.refreshAll();
}

function removePeralatan(btn) {
    var list = document.getElementById('peralatan-list');
    var item = btn.closest('.dynamic-item');
    if (list.children.length > 1) {
        item.remove();
    } else {
        // Baris terakhir: reset ke kosong daripada dihapus, biar selalu ada minimal 1 baris template.
        item.querySelector('select').value = '';
    }
    refreshPeralatanOptions();
    updatePeralatanRemoveButtons();
}

function addPeralatan() {
    var list = document.getElementById('peralatan-list');
    var first = list.querySelector('.dynamic-item');
    var clone = first.cloneNode(true);
    clone.querySelectorAll('option').forEach(function (opt) { opt.hidden = false; });
    clone.querySelector('select').value = '';
    clone.querySelector('select').onchange = refreshPeralatanOptions;
    window.SearchableSelect && window.SearchableSelect.reinitRow(clone);
    clone.querySelector('.btn-remove').disabled = false;
    clone.querySelector('.btn-remove').onclick = function () { removePeralatan(this); };
    list.appendChild(clone);
    refreshPeralatanOptions();
    updatePeralatanRemoveButtons();
}

function updatePeralatanRemoveButtons() {
    document.querySelectorAll('#peralatan-list .btn-remove').forEach(function (btn) {
        btn.disabled = false;
    });
}

document.getElementById('add-peralatan').addEventListener('click', addPeralatan);

document.addEventListener('DOMContentLoaded', function () {
    refreshOperatorOptions();
    updateRemoveButtons();
    refreshPeralatanOptions();
    updatePeralatanRemoveButtons();
    syncZoomCheckboxUI(false);
});
