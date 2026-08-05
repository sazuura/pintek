document.addEventListener('DOMContentLoaded', function () {

    document.addEventListener('click', function (e) {
        var row = e.target.closest('tr.accordion-row');
        if (!row) return;

        if (e.target.closest('a, button, form, input, select')) return;

        var targetId = row.dataset.target;
        var detail   = targetId ? document.getElementById(targetId) : null;
        if (!detail) return;

        var isOpen = detail.classList.contains('open');

        document.querySelectorAll('tr.accordion-detail.open').forEach(function (d) {
            d.classList.remove('open');

            d.querySelectorAll('.inline-confirm-form').forEach(function (f) {
                f.style.display = 'none';
            });
        });
        document.querySelectorAll('tr.accordion-row.open').forEach(function (r) {
            r.classList.remove('open');
        });

        if (!isOpen) {
            detail.classList.add('open');
            row.classList.add('open');
        }
    });

    document.addEventListener('click', function (e) {
        var tab = e.target.closest('.tab-btn');
        if (!tab) return;

        var targetId  = tab.dataset.tab;
        var tabGroup  = tab.closest('.tab-group');
        var panelGroup = tabGroup
            ? tabGroup.dataset.panels
                ? document.getElementById(tabGroup.dataset.panels)
                : tabGroup.nextElementSibling
            : null;

        var allTabs = tabGroup
            ? tabGroup.querySelectorAll('.tab-btn')
            : document.querySelectorAll('.tab-btn[data-tab]');

        allTabs.forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');

        var panels = panelGroup
            ? panelGroup.querySelectorAll('.tab-panel')
            : document.querySelectorAll('.tab-panel');

        panels.forEach(function (p) { p.classList.remove('active'); });
        var target = document.getElementById(targetId);
        if (target) target.classList.add('active');

        var storageKey = 'activeTab_' + window.location.pathname;
        localStorage.setItem(storageKey, targetId);
    });

    var storageKey = 'activeTab_' + window.location.pathname;
    var savedTab   = localStorage.getItem(storageKey);
    if (savedTab) {
        var savedBtn = document.querySelector('.tab-btn[data-tab="' + savedTab + '"]');
        if (savedBtn) savedBtn.click();
    }

    document.addEventListener('click', function (e) {
        var th = e.target.closest('thead th.sortable');
        if (!th) return;

        var table  = th.closest('table');
        var tbody  = table.querySelector('tbody');
        var colIdx = Array.from(th.parentElement.children).indexOf(th);
        var asc    = th.dataset.sort !== 'asc';

        table.querySelectorAll('thead th').forEach(function (h) {
            h.classList.remove('sorted');
            h.dataset.sort = '';
            var icon = h.querySelector('.sort-icon');
            if (icon) icon.textContent = '⇅';
        });

        th.classList.add('sorted');
        th.dataset.sort = asc ? 'asc' : 'desc';
        var icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = asc ? '↑' : '↓';

        var rows = Array.from(tbody.children).filter(function (r) {
            return r.tagName === 'TR' && !r.classList.contains('accordion-detail');
        });

        rows.sort(function (a, b) {
            var aCell = a.children[colIdx];
            var bCell = b.children[colIdx];

            var aSortAttr = aCell ? aCell.dataset.sortValue : undefined;
            var bSortAttr = bCell ? bCell.dataset.sortValue : undefined;
            if (aSortAttr !== undefined && bSortAttr !== undefined) {
                var aNumAttr = parseFloat(aSortAttr);
                var bNumAttr = parseFloat(bSortAttr);
                if (!isNaN(aNumAttr) && !isNaN(bNumAttr)) {
                    return asc ? aNumAttr - bNumAttr : bNumAttr - aNumAttr;
                }
            }

            var aText = aCell ? aCell.textContent.trim().toLowerCase() : '';
            var bText = bCell ? bCell.textContent.trim().toLowerCase() : '';
            var aNum  = parseFloat(aText);
            var bNum  = parseFloat(bText);

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return asc ? aNum - bNum : bNum - aNum;
            }
            return asc
                ? aText.localeCompare(bText, 'id')
                : bText.localeCompare(aText, 'id');
        });

        rows.forEach(function (row) {
            tbody.appendChild(row);
            var detailId = row.dataset.target;
            if (detailId) {
                var detail = document.getElementById(detailId);
                if (detail) tbody.appendChild(detail);
            }
        });
    });

});

function bukaModalKonfirmasi(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('open');
}

function tutupModalKonfirmasi(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('open');
}

var mousedownTarget = null;

document.addEventListener('mousedown', function (e) {
    mousedownTarget = e.target;
});

window.bukaModalKonfirmasi = bukaModalKonfirmasi;
window.tutupModalKonfirmasi = tutupModalKonfirmasi;

document.addEventListener('click', function (e) {
    var closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
        var modal = closeBtn.closest('.modal-konfirmasi');
        if (modal) modal.classList.remove('open');
        return;
    }
    var backdrop = e.target.closest('.modal-konfirmasi.open');
    if (backdrop && e.target === backdrop && mousedownTarget === backdrop) {
        backdrop.classList.remove('open');
    }
    mousedownTarget = null;
});

document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.modal-konfirmasi.open').forEach(function (m) {
        m.classList.remove('open');
    });
});

document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn || !navigator.clipboard) return;
    var teks = btn.dataset.copy;
    if (!teks) return;

    navigator.clipboard.writeText(teks).then(function () {
        var icon = btn.querySelector('i');
        if (!icon) return;
        var kelasAsal = icon.className;
        icon.className = 'bx bx-check';
        setTimeout(function () { icon.className = kelasAsal; }, 1500);
    });
});