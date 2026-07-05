// Progressive enhancement: turns a <select class="searchable"> into a two-tier combobox
// (klik kotak trigger -> muncul search bar terpisah + daftar hasil di bawahnya), sambil
// tetap mempertahankan <select> asli sebagai sumber kebenaran (value, options, disabled
// state, validasi & submit form native).
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
    var TRIGGER_CLASSES = 'searchable-select-trigger w-full h-10 px-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-sm font-sans flex items-center justify-between gap-2 cursor-pointer transition-[border-color,box-shadow] duration-200 [&.open]:border-primary [&.open]:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
    var DROPDOWN_CLASSES = 'searchable-select-dropdown hidden absolute top-[calc(100%+4px)] left-0 right-0 z-30 bg-surface dark:bg-surface-dark border border-page-bg dark:border-page-bg-dark rounded-lg p-2 shadow-[0_8px_24px_rgba(0,0,0,0.12)] [&.open]:block';
    var SEARCH_INPUT_CLASSES = 'searchable-select-input w-full h-9 pl-8 pr-8 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-sm font-sans focus:outline-none focus:border-primary';

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

    function renderOptions(select, listEl, filter) {
        listEl.innerHTML = '';
        var f = (filter || '').trim().toLowerCase();
        var opts = Array.from(select.options).filter(function (o) { return o.value !== '' && !o.hidden; });
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
            item.className = 'searchable-select-item flex items-center justify-between gap-2 py-2 px-2.5 rounded-lg cursor-pointer [&:hover:not(.is-disabled)]:bg-page-bg dark:[&:hover:not(.is-disabled)]:bg-page-bg-dark [&.is-active]:bg-primary-50 dark:[&.is-active]:bg-[#0d2a40] [&.is-disabled]:opacity-50 [&.is-disabled]:cursor-not-allowed';
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
        if (opt && opt.value) {
            label.textContent = optionLabel(opt);
            label.classList.remove('text-text-muted');
            label.classList.add('text-text', 'dark:text-text-dark');
        } else {
            label.textContent = select.dataset.placeholder || 'Cari...';
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
        input.value = '';
        wrapper.querySelector('.searchable-select-clear').classList.add('hidden');
        renderOptions(select, listEl, '');
        input.focus();
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

        var wrapper = document.createElement('div');
        wrapper.className = 'searchable-select relative flex-1 min-w-0';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('searchable-select-native', 'hidden');

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = TRIGGER_CLASSES;
        var triggerLabel = document.createElement('span');
        triggerLabel.className = 'searchable-select-trigger-label truncate';
        trigger.appendChild(triggerLabel);
        var chevron = document.createElement('i');
        chevron.className = 'bx bx-chevron-down text-text-muted text-base shrink-0 transition-transform duration-200 [.open_&]:rotate-180';
        trigger.appendChild(chevron);
        wrapper.appendChild(trigger);

        var dropdown = document.createElement('div');
        dropdown.className = DROPDOWN_CLASSES;

        var searchWrap = document.createElement('div');
        searchWrap.className = 'relative mb-2';
        var searchIcon = document.createElement('i');
        searchIcon.className = 'bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none';
        searchWrap.appendChild(searchIcon);
        var input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.className = SEARCH_INPUT_CLASSES;
        input.placeholder = 'Cari...';
        searchWrap.appendChild(input);
        var clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'searchable-select-clear hidden absolute right-2 top-1/2 -translate-y-1/2 text-text-muted hover:text-text dark:hover:text-text-dark text-lg leading-none';
        clearBtn.innerHTML = '<i class="bx bx-x"></i>';
        searchWrap.appendChild(clearBtn);
        dropdown.appendChild(searchWrap);

        var listEl = document.createElement('div');
        listEl.className = 'searchable-select-list max-h-[220px] overflow-y-auto flex flex-col gap-0.5';
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

        select._searchableSelect = {
            refresh: function () {
                syncTriggerLabel(select);
                if (dropdown.classList.contains('open')) renderOptions(select, listEl, input.value);
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
        (root || document).querySelectorAll('select.searchable[data-enhanced]').forEach(function (select) {
            if (select._searchableSelect) select._searchableSelect.refresh();
        });
    }

    // Untuk baris hasil cloneNode(true): DOM ter-enhance ikut ter-copy tapi listener JS-nya
    // tidak, jadi bongkar dulu baru enhance ulang supaya event handler-nya segar.
    function reinitRow(container) {
        container.querySelectorAll('select.searchable').forEach(function (select) {
            unenhance(select);
            enhance(select);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select.searchable').forEach(enhance);
    });

    document.addEventListener('mousedown', function (e) {
        if (!e.target.closest('.searchable-select')) closeAllDropdowns();
    });

    window.SearchableSelect = { enhance: enhance, refreshAll: refreshAll, reinitRow: reinitRow };
})();
