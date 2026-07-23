// Progressive enhancement: mengubah HAMPIR SEMUA <select> di aplikasi (kecuali
// listbox beratribut "size", mis. picker bulan/tahun di dashboard) jadi dropdown
// custom (klik kotak trigger -> muncul daftar hasil di bawahnya), supaya tidak ada
// lagi tampilan dropdown bawaan browser/OS. <select> asli tetap dipertahankan sebagai
// sumber kebenaran (value, options, disabled state, validasi & submit form native) -
// cuma disembunyikan secara visual.
//
// Search bar di dalam dropdown otomatis muncul HANYA kalau jumlah pilihan (yang
// benar-benar bisa dipilih, bukan placeholder) lebih dari SEARCH_THRESHOLD - jadi
// select kecil seperti filter status/role tidak perlu kotak pencarian, sementara
// select dengan banyak data (daftar alat, operator, jadwal) otomatis dapat search.
//
// Data per <option> yang dikenali (semua opsional kecuali value/textContent):
//   data-subtitle       teks baris kedua di tiap item (mis. nomor HP, "Stok: 5")
//   data-badge          teks badge kecil di kanan item (mis. "Jadwal Bentrok")
//   data-badge-variant  'danger' | 'warning' (default 'danger' kalau data-badge ada)
//
// Class seperti "searchable-select", "open", "is-active", "is-disabled" TIDAK punya CSS
// sendiri -- murni dipakai buat query JS (closest(), classList) dan variant Tailwind
// arbitrary (mis. [&.open]:block) yang ditulis langsung di className konstanta di bawah.
(function () {
    var SELECT_SELECTOR = 'select:not([size])';
    var SEARCH_THRESHOLD = 7;

    var TRIGGER_CLASSES = 'searchable-select-trigger w-full h-10 px-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-surface dark:bg-surface-dark text-sm font-sans flex items-center justify-between gap-2 cursor-pointer transition-[border-color,box-shadow] duration-200 [&.open]:border-primary [&.open]:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
    var TRIGGER_ERROR_CLASSES = 'searchable-select-trigger w-full h-10 px-3 border border-danger-text rounded-lg bg-surface dark:bg-surface-dark text-sm font-sans flex items-center justify-between gap-2 cursor-pointer transition-[border-color,box-shadow] duration-200 [&.open]:shadow-[0_0_0_3px_rgba(231,76,60,0.15)]';
    // Class 'drop-up' ditambahkan openDropdown() saat ruang viewport di bawah trigger
    // tidak cukup - dropdown pindah membuka ke atas supaya tidak terpotong layar.
    var DROPDOWN_CLASSES = 'searchable-select-dropdown hidden absolute top-[calc(100%+4px)] left-0 right-0 z-30 bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 rounded-lg p-2 shadow-[0_8px_24px_rgba(0,0,0,0.12)] [&.open]:block [&.drop-up]:top-auto [&.drop-up]:bottom-[calc(100%+4px)] [&.drop-up]:shadow-[0_-8px_24px_rgba(0,0,0,0.12)]';
    var SEARCH_INPUT_CLASSES = 'searchable-select-input w-full h-9 pl-8 pr-8 border border-gray-300 dark:border-gray-700 rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-sm font-sans focus:outline-none focus:border-primary';

    function closeAllDropdowns(except) {
        document.querySelectorAll('.searchable-select-dropdown.open').forEach(function (dd) {
            if (dd === except) return;
            dd.classList.remove('open');
            var wrapper = dd.closest('.searchable-select');
            if (wrapper) {
                var trigger = wrapper.querySelector('.searchable-select-trigger');
                if (trigger) trigger.classList.remove('open');
            }
        });
    }

    function optionLabel(opt) {
        return (opt.textContent || '').replace(/\s+/g, ' ').trim();
    }

    var MIN_TRIGGER_WIDTH = 190;
    var MAX_TRIGGER_WIDTH = 340;
    var measureCtx = null;

    // Select filter di toolbar (bukan yang 'fill') dibuat menyesuaikan isi, bukan
    // selebar-lebarnya - tapi kalau opsinya berupa nama panjang (mis. "Clara Safitri
    // (Operator)"), lebar minimum bawaan (190px) bikin teks di trigger & tiap item
    // dropdown kepotong ellipsis. Di sini lebar dihitung dari opsi terpanjang supaya
    // select dengan data pendek (mis. "Semua Status") tetap ringkas, sementara yang
    // datanya panjang otomatis dilebarkan (dibatasi MAX supaya tidak berlebihan).
    function computeTriggerWidth(select) {
        if (!measureCtx) measureCtx = document.createElement('canvas').getContext('2d');
        measureCtx.font = '400 14px ui-sans-serif, system-ui, sans-serif';
        var longest = 0;
        selectableOptions(select).forEach(function (o) {
            var w = measureCtx.measureText(optionLabel(o)).width;
            if (w > longest) longest = w;
        });
        // padding trigger (px-3 kiri+kanan) + gap + lebar ikon chevron + sedikit buffer.
        var width = longest + 24 + 8 + 16 + 12;
        return Math.max(MIN_TRIGGER_WIDTH, Math.min(MAX_TRIGGER_WIDTH, Math.ceil(width)));
    }

    // Opsi yang dianggap "bisa dipilih & tampil di daftar": bukan disabled (placeholder
    // mis. "-- Pilih X --" selalu ditandai disabled) dan tidak hidden. Opsi dengan
    // value="" TETAP dimasukkan selama tidak disabled (mis. "Semua Status" di filter).
    function selectableOptions(select) {
        return Array.from(select.options).filter(function (o) { return !o.disabled && !o.hidden; });
    }

    function renderOptions(select, listEl, filter) {
        listEl.innerHTML = '';
        var f = (filter || '').trim().toLowerCase();
        var opts = selectableOptions(select);
        var matched = f ? opts.filter(function (o) {
            return optionLabel(o).toLowerCase().indexOf(f) !== -1 ||
                (o.dataset.subtitle || '').toLowerCase().indexOf(f) !== -1;
        }) : opts;

        if (!matched.length) {
            var empty = document.createElement('div');
            empty.className = 'searchable-select-empty py-6 px-3 text-[13px] text-text-muted text-center';
            empty.textContent = 'Tidak ada hasil';
            listEl.appendChild(empty);
            return;
        }

        matched.forEach(function (opt) {
            var item = document.createElement('div');
            item.className = 'searchable-select-item flex items-center justify-between gap-2 py-2 px-2.5 rounded-lg cursor-pointer [&:hover:not(.is-disabled)]:bg-page-bg dark:[&:hover:not(.is-disabled)]:bg-page-bg-dark [&.is-active]:bg-primary-50 dark:[&.is-active]:bg-[#0d2a40]/60 [&.is-disabled]:opacity-50 [&.is-disabled]:cursor-not-allowed';
            if (opt.disabled) item.classList.add('is-disabled');
            if (opt.value === select.value) item.classList.add('is-active');

            var text = document.createElement('div');
            text.className = 'min-w-0';
            var primary = document.createElement('div');
            primary.className = 'text-sm font-semibold text-text dark:text-text-dark truncate';
            primary.textContent = optionLabel(opt);
            text.appendChild(primary);
            if (opt.dataset.subtitle) {
                var subtitle = document.createElement('div');
                subtitle.className = 'text-xs text-text-muted truncate mt-0.5';
                subtitle.textContent = opt.dataset.subtitle;
                text.appendChild(subtitle);
            }
            item.appendChild(text);

            if (opt.dataset.badge) {
                var variant = opt.dataset.badgeVariant === 'warning'
                    ? 'bg-warning dark:bg-warning-dark text-warning-text'
                    : 'bg-danger dark:bg-danger-dark text-danger-text';
                var badge = document.createElement('span');
                badge.className = 'shrink-0 inline-flex items-center gap-1 py-[3px] px-2.5 rounded-full text-xs font-medium whitespace-nowrap ' + variant;
                badge.textContent = opt.dataset.badge;
                item.appendChild(badge);
            }

            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                if (opt.disabled) return;
                select.value = opt.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                closeDropdown(select);
            });
            listEl.appendChild(item);
        });
    }

    function syncTriggerLabel(select) {
        var wrapper = select.closest('.searchable-select');
        if (!wrapper) return;
        var label = wrapper.querySelector('.searchable-select-trigger-label');
        var opt = select.options[select.selectedIndex];
        // Opsi valid (termasuk yang value="" tapi tidak disabled, mis. "Semua Status")
        // ditampilkan apa adanya. Cuma placeholder (disabled) yang jatuh ke teks abu-abu.
        if (opt && !opt.disabled) {
            label.textContent = optionLabel(opt);
            label.classList.remove('text-text-muted');
            label.classList.add('text-text', 'dark:text-text-dark');
        } else {
            label.textContent = select.dataset.placeholder || 'Pilih...';
            label.classList.add('text-text-muted');
            label.classList.remove('text-text', 'dark:text-text-dark');
        }
    }

    function openDropdown(select) {
        var wrapper = select.closest('.searchable-select');
        var trigger = wrapper.querySelector('.searchable-select-trigger');
        var dropdown = wrapper.querySelector('.searchable-select-dropdown');
        var input = wrapper.querySelector('.searchable-select-input');
        var listEl = wrapper.querySelector('.searchable-select-list');

        closeAllDropdowns(dropdown);
        trigger.classList.add('open');
        dropdown.classList.add('open');
        if (input) {
            input.value = '';
            wrapper.querySelector('.searchable-select-clear').classList.add('hidden');
        }
        renderOptions(select, listEl, '');

        // Setelah isinya dirender (tinggi dropdown sudah final), cek sisa ruang
        // viewport: kalau di bawah trigger tidak muat dan ruang di atas lebih lega,
        // buka ke atas supaya daftarnya tidak terpotong tepi bawah layar.
        dropdown.classList.remove('drop-up');
        var trigRect = trigger.getBoundingClientRect();
        var ruangBawah = window.innerHeight - trigRect.bottom;
        var ruangAtas = trigRect.top;
        if (ruangBawah < dropdown.offsetHeight + 12 && ruangAtas > ruangBawah) {
            dropdown.classList.add('drop-up');
        }
        // Dua arah sama-sama sempit (mis. trigger di toolbar atas halaman pendek):
        // gulir container scroll seminimal mungkin supaya dropdown terlihat utuh.
        dropdown.scrollIntoView({ block: 'nearest', inline: 'nearest' });

        if (input) input.focus();
    }

    function closeDropdown(select) {
        var wrapper = select.closest('.searchable-select');
        wrapper.querySelector('.searchable-select-trigger').classList.remove('open');
        wrapper.querySelector('.searchable-select-dropdown').classList.remove('open');
        syncTriggerLabel(select);
    }

    function enhance(select) {
        if (select.dataset.enhanced) return;
        select.dataset.enhanced = '1';

        // Select yang aslinya dibuat untuk melebar mengisi baris flex (mis. baris
        // dinamis peralatan/operator, atau field form lewat <x-select>) ditandai lewat
        // class 'flex-1' / 'w-full' di elemen aslinya - wrapper ikut melebar. Selain
        // itu (mis. select filter di toolbar) wrapper dibuat menyesuaikan isi saja,
        // supaya tidak melebar aneh mengisi sisa ruang flex toolbar - tapi tetap diberi
        // lebar minimum supaya label seperti "Gedung B (Persandian)" tidak kepotong
        // ellipsis di kotak yang terlalu sempit.
        var fill = select.classList.contains('flex-1') || select.classList.contains('w-full');

        var wrapper = document.createElement('div');
        wrapper.className = 'searchable-select relative' + (fill ? ' flex-1 min-w-0' : ' inline-block');
        if (!fill) wrapper.style.minWidth = computeTriggerWidth(select) + 'px';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('searchable-select-native', 'hidden');

        // Kalau select aslinya ditandai error (mis. class '!border-danger-text' dari
        // $errors->has(...) di Blade), trigger-nya pakai border merah juga.
        var hasError = select.classList.contains('!border-danger-text') || select.classList.contains('border-danger-text');

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = hasError ? TRIGGER_ERROR_CLASSES : TRIGGER_CLASSES;
        var triggerLabel = document.createElement('span');
        triggerLabel.className = 'searchable-select-trigger-label truncate';
        trigger.appendChild(triggerLabel);
        var chevron = document.createElement('i');
        chevron.className = 'bx bx-chevron-down text-text-muted text-base shrink-0 transition-transform duration-200 [.open_&]:rotate-180';
        trigger.appendChild(chevron);
        wrapper.appendChild(trigger);

        var dropdown = document.createElement('div');
        dropdown.className = DROPDOWN_CLASSES;

        // Search bar cuma dibuat kalau opsinya banyak, ATAU select-nya memang ditandai
        // eksplisit class 'searchable' (mis. pilih operator/peralatan) - select kecil
        // lain (mis. filter status/role/kondisi) langsung tampil daftarnya tanpa kotak
        // pencarian.
        var showSearch = select.classList.contains('searchable') || selectableOptions(select).length > SEARCH_THRESHOLD;
        var input = null, clearBtn = null;
        if (showSearch) {
            var searchWrap = document.createElement('div');
            searchWrap.className = 'relative mb-2';
            var searchIcon = document.createElement('i');
            searchIcon.className = 'bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none';
            searchWrap.appendChild(searchIcon);
            input = document.createElement('input');
            input.type = 'text';
            input.autocomplete = 'off';
            input.className = SEARCH_INPUT_CLASSES;
            input.placeholder = 'Cari...';
            searchWrap.appendChild(input);
            clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'searchable-select-clear hidden absolute right-2 top-1/2 -translate-y-1/2 text-text-muted hover:text-text dark:hover:text-text-dark text-lg leading-none';
            clearBtn.innerHTML = '<i class="bx bx-x"></i>';
            searchWrap.appendChild(clearBtn);
            dropdown.appendChild(searchWrap);
        }

        var listEl = document.createElement('div');
        listEl.className = 'searchable-select-list custom-scrollbar max-h-[220px] overflow-y-auto flex flex-col gap-0.5';
        dropdown.appendChild(listEl);

        wrapper.appendChild(dropdown);

        syncTriggerLabel(select);

        trigger.addEventListener('click', function () {
            if (dropdown.classList.contains('open')) {
                closeDropdown(select);
            } else {
                openDropdown(select);
            }
        });
        if (input) {
            input.addEventListener('input', function () {
                clearBtn.classList.toggle('hidden', input.value === '');
                renderOptions(select, listEl, input.value);
            });
            clearBtn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.value = '';
                clearBtn.classList.add('hidden');
                renderOptions(select, listEl, '');
                input.focus();
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeDropdown(select);
            });
        }

        select._searchableSelect = {
            refresh: function () {
                syncTriggerLabel(select);
                if (dropdown.classList.contains('open')) renderOptions(select, listEl, input ? input.value : '');
            }
        };
    }

    function unenhance(select) {
        if (!select.dataset.enhanced) return;
        var wrapper = select.closest('.searchable-select');
        if (wrapper && wrapper.parentNode) {
            wrapper.parentNode.insertBefore(select, wrapper);
            wrapper.remove();
        }
        select.classList.remove('searchable-select-native', 'hidden');
        delete select.dataset.enhanced;
        delete select._searchableSelect;
    }

    // Re-render label trigger + (kalau lagi terbuka) daftar hasil tiap combobox yang sudah
    // di-enhance. Panggil ini setelah kode lain mengubah select.value / option.hidden/disabled
    // / data-badge / data-subtitle.
    function refreshAll(root) {
        (root || document).querySelectorAll(SELECT_SELECTOR + '[data-enhanced]').forEach(function (select) {
            if (select._searchableSelect) select._searchableSelect.refresh();
        });
    }

    // Untuk baris hasil cloneNode(true): DOM ter-enhance ikut ter-copy tapi listener JS-nya
    // tidak, jadi bongkar dulu baru enhance ulang supaya event handler-nya segar.
    function reinitRow(container) {
        container.querySelectorAll(SELECT_SELECTOR).forEach(function (select) {
            unenhance(select);
            enhance(select);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll(SELECT_SELECTOR).forEach(enhance);
    });

    document.addEventListener('mousedown', function (e) {
        if (!e.target.closest('.searchable-select')) closeAllDropdowns();
    });

    window.SearchableSelect = { enhance: enhance, refreshAll: refreshAll, reinitRow: reinitRow };
})();
