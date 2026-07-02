document.addEventListener('DOMContentLoaded', function () {

    // ═══════════════════════════════════════════════════
    // 1. ACCORDION
    //    Delegation ke document — cocok untuk <tr> yang
    //    di-render via @foreach di Blade.
    // ═══════════════════════════════════════════════════
    document.addEventListener('click', function (e) {
        var row = e.target.closest('tr.accordion-row');
        if (!row) return;

        // Jangan trigger jika klik tombol aksi / link / form
        if (e.target.closest('a, button, form, input, select')) return;

        var targetId = row.dataset.target;
        var detail   = targetId ? document.getElementById(targetId) : null;
        if (!detail) return;

        var isOpen = detail.classList.contains('open');

        // Tutup semua accordion yang sedang terbuka
        document.querySelectorAll('tr.accordion-detail.open').forEach(function (d) {
            d.classList.remove('open');
        });
        document.querySelectorAll('tr.accordion-row.open').forEach(function (r) {
            r.classList.remove('open');
        });

        // Buka yang diklik (jika sebelumnya tertutup)
        if (!isOpen) {
            detail.classList.add('open');
            row.classList.add('open');
        }
    });


    // ═══════════════════════════════════════════════════
    // 2. TAB VIEW
    //    Delegation ke document — tab bisa ada di mana
    //    saja di halaman.
    // ═══════════════════════════════════════════════════
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

        // Update tab aktif dalam group yang sama
        var allTabs = tabGroup
            ? tabGroup.querySelectorAll('.tab-btn')
            : document.querySelectorAll('.tab-btn[data-tab]');

        allTabs.forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');

        // Update panel aktif
        var panels = panelGroup
            ? panelGroup.querySelectorAll('.tab-panel')
            : document.querySelectorAll('.tab-panel');

        panels.forEach(function (p) { p.classList.remove('active'); });
        var target = document.getElementById(targetId);
        if (target) target.classList.add('active');

        // Simpan tab aktif per halaman
        var storageKey = 'activeTab_' + window.location.pathname;
        localStorage.setItem(storageKey, targetId);
    });

    // Restore tab tersimpan saat halaman dimuat
    var storageKey = 'activeTab_' + window.location.pathname;
    var savedTab   = localStorage.getItem(storageKey);
    if (savedTab) {
        var savedBtn = document.querySelector('.tab-btn[data-tab="' + savedTab + '"]');
        if (savedBtn) savedBtn.click();
    }


    // ═══════════════════════════════════════════════════
    // 3. SORT KOLOM
    //    Delegation ke document.
    // ═══════════════════════════════════════════════════
    document.addEventListener('click', function (e) {
        var th = e.target.closest('thead th.sortable');
        if (!th) return;

        var table  = th.closest('table');
        var tbody  = table.querySelector('tbody');
        var colIdx = Array.from(th.parentElement.children).indexOf(th);
        var asc    = th.dataset.sort !== 'asc';

        // Reset semua header di tabel ini
        table.querySelectorAll('thead th').forEach(function (h) {
            h.classList.remove('sorted');
            h.dataset.sort = '';
            var icon = h.querySelector('.sort-icon');
            if (icon) icon.textContent = '⇅';
        });

        // Set header aktif
        th.classList.add('sorted');
        th.dataset.sort = asc ? 'asc' : 'desc';
        var icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = asc ? '↑' : '↓';

        // Ambil baris data (bukan baris detail accordion)
        var rows = Array.from(tbody.querySelectorAll('tr')).filter(function (r) {
            return !r.classList.contains('accordion-detail');
        });

        rows.sort(function (a, b) {
            var aCell = a.children[colIdx];
            var bCell = b.children[colIdx];
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

        // Re-insert ke DOM, sertakan baris accordion detail pasangannya
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