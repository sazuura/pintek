

var jadwalSudahDiajukan = {};
var jadwalReferensi     = [];
var jadwalGantiTerakhir = null;

function jumlahSudahDiajukan(nama) {
    return jadwalSudahDiajukan[nama] || 0;
}

function opsiTersedia(id) {
    var selects = document.querySelectorAll('.peralatan-select');
    for (var i = 0; i < selects.length; i++) {
        var opts = selects[i].options;
        for (var j = 0; j < opts.length; j++) {
            if (opts[j].value === String(id)) return true;
        }
    }
    return false;
}

function updateJadwalDuplikasiState(jadwalBerubah) {
    var select = document.getElementById('id_penjadwalan');
    var opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) {
        jadwalSudahDiajukan = {};
        jadwalReferensi = [];
    } else {
        try { jadwalSudahDiajukan = JSON.parse(opt.dataset.sudahDiajukan || '{}'); } catch (e) { jadwalSudahDiajukan = {}; }
        try { jadwalReferensi = JSON.parse(opt.dataset.referensi || '[]'); } catch (e) { jadwalReferensi = []; }
    }
    updateReferensiHint();
    if (jadwalBerubah) autoIsiDariReferensi();
    refreshPeralatanOptions();
}

var autoFillSedangJalan = false;

function autoIsiDariReferensi() {
    var list = document.getElementById('peralatan-list');
    autoFillSedangJalan = true;

    Array.from(list.querySelectorAll('.dynamic-item[data-auto-ref]')).forEach(function (row) {
        if (list.children.length > 1) {
            row.remove();
        } else {

            row.querySelector('select').value = '';
            row.querySelector('input[type=number]').value = '';
            delete row.dataset.autoRef;
        }
    });

    var bisaDiisi = jadwalReferensi
        .map(function (r) {
            var sisa = r.jumlah - jumlahSudahDiajukan(r.nama);
            return { id: r.id, nama: r.nama, jumlah: sisa };
        })
        .filter(function (r) { return r.jumlah > 0 && opsiTersedia(r.id); });
    var terpilih  = getSelectedPeralatan();

    bisaDiisi.forEach(function (r) {
        if (terpilih.indexOf(String(r.id)) !== -1) return;

        var kosong = Array.from(list.querySelectorAll('.dynamic-item')).find(function (row) {
            return row.querySelector('select').value === '';
        });
        var row, sel;
        if (kosong) {
            row = kosong;
            sel = row.querySelector('select');
            sel.value = r.id;
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        } else {

            row = addPeralatanRow(r.id, null);
            sel = row.querySelector('select');
        }

        if (String(sel.value) !== String(r.id)) {
            if (kosong) {
                sel.value = '';
                row.querySelector('input[type=number]').value = '';
            } else {
                row.remove();
            }
            return;
        }

        row.querySelector('input[type=number]').value = r.jumlah;
        row.dataset.autoRef = '1';
        terpilih.push(String(r.id));
    });

    autoFillSedangJalan = false;
    updateRemoveButtons();
    refreshPeralatanOptions();
    window.SearchableSelect && window.SearchableSelect.refreshAll();
}

document.getElementById('peralatan-list').addEventListener('change', function (e) {
    if (autoFillSedangJalan) return;
    var row = e.target.closest('.dynamic-item');
    if (row) delete row.dataset.autoRef;
});

function badgeReferensi(teks, variant) {
    var kelas = {
        info:    'bg-primary-50 dark:bg-[#0d2a40] text-primary',
        netral:  'bg-inactive dark:bg-inactive-dark text-inactive-text dark:text-inactive-text-dark',
        bahaya:  'bg-danger dark:bg-danger-dark text-danger-text',
    }[variant];
    return '<span class="shrink-0 inline-flex items-center py-[3px] px-2.5 rounded-full text-xs font-medium whitespace-nowrap ' + kelas + '">' + teks + '</span>';
}

function updateReferensiHint() {
    var box  = document.getElementById('referensi-hint');
    var list = document.getElementById('referensi-hint-list');
    if (jadwalReferensi.length === 0) {
        box.classList.add('hidden');
        return;
    }

    list.innerHTML = jadwalReferensi.map(function (r) {
        var sudah = jumlahSudahDiajukan(r.nama);
        var sisa  = r.jumlah - sudah;
        var badge;
        if (sisa <= 0) {
            badge = badgeReferensi('Lengkap', 'netral');
        } else if (!opsiTersedia(r.id)) {
            badge = badgeReferensi('Kurang ' + sisa + ' &middot; stok kosong', 'bahaya');
        } else {
            badge = badgeReferensi('Otomatis', 'info');
        }
        return '<div class="flex items-center justify-between gap-2">' +
            '<span class="text-[13px] text-text dark:text-text-dark truncate">' + escapeHtml(r.nama) +
                ' <span class="text-text-muted">(x' + r.jumlah + ')</span></span>' +
            badge +
        '</div>';
    }).join('');

    box.classList.remove('hidden');
}

function terapkanBatasKembaliDariJadwal(opt) {
    var kembali = document.getElementById('tanggal_kembali_rencana');
    if (opt && opt.value) {
        kembali.min = opt.dataset.tanggal;
        if (!kembali.value || kembali.value < opt.dataset.tanggal) {
            kembali.value = opt.dataset.tanggal;
        }
    } else {
        kembali.removeAttribute('min');
    }
}

document.getElementById('id_penjadwalan').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('keperluan').value = opt.dataset.judul;
        document.getElementById('tanggal_pinjam').value = opt.dataset.tanggal;
    }
    terapkanBatasKembaliDariJadwal(opt);
    var berubah = jadwalGantiTerakhir !== opt.value;
    jadwalGantiTerakhir = opt.value;
    updateJadwalDuplikasiState(berubah);
});

function getSelectedPeralatan() {
    return Array.from(document.querySelectorAll('.peralatan-select'))
        .map(function (s) { return s.value; }).filter(function (v) { return v !== ''; });
}

function refreshPeralatanOptions() {
    var selected = getSelectedPeralatan();
    document.querySelectorAll('.peralatan-select').forEach(function (select) {
        var currentVal = select.value;
        Array.from(select.options).forEach(function (opt) {
            if (!opt.value) return;
            opt.hidden = selected.includes(opt.value) && opt.value !== currentVal;

            var sudahDiajukan = jumlahSudahDiajukan(opt.dataset.nama) > 0;

            if (sudahDiajukan) {
                opt.dataset.badge = 'Sudah Diajukan';
                opt.dataset.badgeVariant = 'warning';
            } else {
                delete opt.dataset.badge;
                delete opt.dataset.badgeVariant;
            }
        });
    });
    window.SearchableSelect && window.SearchableSelect.refreshAll();
}

function removeItem(btn) {
    var list = document.getElementById('peralatan-list');
    if (list.children.length > 1) {
        btn.closest('.dynamic-item').remove();
        refreshPeralatanOptions();
    }
    updateRemoveButtons();
}

function updateRemoveButtons() {
    var items = document.querySelectorAll('#peralatan-list .dynamic-item');
    items.forEach(function (item) {
        item.querySelector('.btn-remove').disabled = items.length <= 1;
    });
}

document.getElementById('add-peralatan').addEventListener('click', function () {
    addPeralatanRow();
    refreshPeralatanOptions();
    updateRemoveButtons();
});

function addPeralatanRow(value, jumlah) {
    var list = document.getElementById('peralatan-list');
    var clone = list.querySelector('.dynamic-item').cloneNode(true);
    clone.querySelectorAll('option').forEach(function (opt) { opt.hidden = false; opt.disabled = false; delete opt.dataset.badge; delete opt.dataset.badgeVariant; });
    clone.querySelector('select').value = '';
    clone.querySelector('input[type=number]').value = '';
    clone.querySelector('select').onchange = refreshPeralatanOptions;
    clone.querySelector('.btn-remove').disabled = false;
    clone.querySelector('.btn-remove').onclick = function () { removeItem(this); };
    list.appendChild(clone);
    window.SearchableSelect && window.SearchableSelect.reinitRow(clone);

    var select = clone.querySelector('select');
    if (value) {
        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (jumlah) {
        clone.querySelector('input[type=number]').value = jumlah;
    }

    if (select._searchableSelect) select._searchableSelect.refresh();
    window.SearchableSelect && window.SearchableSelect.refreshAll();
    return clone;
}
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('id_penjadwalan');
    terapkanBatasKembaliDariJadwal(select.options[select.selectedIndex]);
    updateJadwalDuplikasiState();
});

function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

document.getElementById('form-peminjaman').addEventListener('submit', function (e) {
    e.preventDefault();
    lanjutkanSetelahCekDuplikat();
});

function lanjutkanSetelahCekDuplikat() {
    var namaBentrok = [];
    if (Object.keys(jadwalSudahDiajukan).length > 0) {
        document.querySelectorAll('.peralatan-select').forEach(function (select) {
            var opt = select.options[select.selectedIndex];
            if (!opt || jumlahSudahDiajukan(opt.dataset.nama) <= 0) return;
            namaBentrok.push(opt.dataset.nama || select.value);
        });
    }

    if (namaBentrok.length > 0) {
        document.getElementById('modalKonfirmasiDuplikatList').innerHTML = namaBentrok.map(function (n) {
            return '<li>' + escapeHtml(n) + '</li>';
        }).join('');
        bukaModalKonfirmasi('modalKonfirmasiDuplikat');
        return;
    }

    cekSpamLaluSubmit();
}

function konfirmasiTetapAjukan() {
    tutupModalKonfirmasi('modalKonfirmasiDuplikat');
    cekSpamLaluSubmit();
}

function cekSpamLaluSubmit() {
    var form = document.getElementById('form-peminjaman');
    var formData = new FormData(form);

    fetch(form.dataset.cekSpamUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData,
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var peringatan = data.peringatan || [];
            if (peringatan.length > 0) {
                document.getElementById('modalPeringatanSpamList').innerHTML = peringatan.map(function (p) {
                    return '<li>' + escapeHtml(p.nama) + ' (sudah diajukan ' + p.jumlah_sebelumnya + 'x untuk tanggal pinjam ini)</li>';
                }).join('');
                bukaModalKonfirmasi('modalPeringatanSpam');
            } else {
                form.submit();
            }
        })
        .catch(function () {

            form.submit();
        });
}

function konfirmasiTetapAjukanSpam() {
    tutupModalKonfirmasi('modalPeringatanSpam');
    document.getElementById('form-peminjaman').submit();
}

window.refreshPeralatanOptions = refreshPeralatanOptions;
window.removeItem = removeItem;
window.konfirmasiTetapAjukan = konfirmasiTetapAjukan;
window.konfirmasiTetapAjukanSpam = konfirmasiTetapAjukanSpam;
