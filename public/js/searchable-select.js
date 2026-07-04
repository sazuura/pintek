// Progressive enhancement: turns a <select class="searchable"> into a searchable,
// scrollable combobox while keeping the original <select> as the source of truth
// (value, options, hidden/disabled state, native form submit & validation).
(function () {
    function closeAllDropdowns(except) {
        document.querySelectorAll('.searchable-select-dropdown.open').forEach(function (dd) {
            if (dd !== except) dd.classList.remove('open');
        });
    }

    function optionLabel(opt) {
        return (opt.dataset.origText || opt.textContent).replace(/\s+/g, ' ').trim();
    }

    function renderOptions(select, dropdown, filter) {
        dropdown.innerHTML = '';
        var f = (filter || '').trim().toLowerCase();
        var opts = Array.from(select.options).filter(function (o) { return o.value !== '' && !o.hidden; });
        var matched = f ? opts.filter(function (o) { return optionLabel(o).toLowerCase().indexOf(f) !== -1; }) : opts;

        if (!matched.length) {
            var empty = document.createElement('div');
            empty.className = 'searchable-select-empty';
            empty.textContent = 'Tidak ada hasil';
            dropdown.appendChild(empty);
            return;
        }

        matched.forEach(function (opt) {
            var item = document.createElement('div');
            item.className = 'searchable-select-item';
            item.textContent = optionLabel(opt);
            if (opt.disabled) item.classList.add('is-disabled');
            if (opt.value === select.value) item.classList.add('is-active');
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                if (opt.disabled) return;
                select.value = opt.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                dropdown.classList.remove('open');
            });
            dropdown.appendChild(item);
        });
    }

    function syncLabel(select, input) {
        var opt = select.options[select.selectedIndex];
        input.value = (opt && opt.value) ? optionLabel(opt) : '';
    }

    function enhance(select) {
        if (select.dataset.enhanced) return;
        select.dataset.enhanced = '1';

        var wrapper = document.createElement('div');
        wrapper.className = 'searchable-select';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('searchable-select-native');

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-input searchable-select-input';
        input.autocomplete = 'off';
        input.placeholder = select.dataset.placeholder || 'Cari...';
        wrapper.appendChild(input);

        var dropdown = document.createElement('div');
        dropdown.className = 'searchable-select-dropdown';
        wrapper.appendChild(dropdown);

        syncLabel(select, input);

        input.addEventListener('focus', function () {
            closeAllDropdowns(dropdown);
            renderOptions(select, dropdown, '');
            dropdown.classList.add('open');
        });
        input.addEventListener('input', function () {
            renderOptions(select, dropdown, input.value);
            dropdown.classList.add('open');
        });
        input.addEventListener('blur', function () {
            setTimeout(function () {
                dropdown.classList.remove('open');
                if (input.value.trim() === '' && select.value !== '') {
                    select.value = '';
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
                syncLabel(select, input);
            }, 150);
        });

        select._searchableSelect = {
            refresh: function () {
                syncLabel(select, input);
                if (dropdown.classList.contains('open')) renderOptions(select, dropdown, input.value);
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
        select.classList.remove('searchable-select-native');
        delete select.dataset.enhanced;
        delete select._searchableSelect;
    }

    // Re-render every enhanced combobox's visible label + (if open) its filtered
    // list. Call this after code mutates select.value / option.hidden / option.disabled.
    function refreshAll(root) {
        (root || document).querySelectorAll('select.searchable[data-enhanced]').forEach(function (select) {
            if (select._searchableSelect) select._searchableSelect.refresh();
        });
    }

    // For rows cloned via cloneNode(true): the clone carries over the enhanced
    // DOM but not the JS event listeners, so tear it down and rebuild fresh.
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
