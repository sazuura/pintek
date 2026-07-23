// Progressive enhancement: mengubah semua <input type="date"> dan <input type="time">
// jadi picker custom (kalender / daftar jam) bergaya Tailwind, menggantikan picker
// bawaan browser/OS yang tampilannya berbeda-beda tiap platform. Mengikuti pola yang
// sama dengan searchable-select.js:
//   - Input asli TETAP di DOM sebagai sumber kebenaran (value Y-m-d / HH:MM, atribut
//     min/max, required + validasi native, ikut tersubmit form) - cuma disembunyikan
//     visual (opacity-0, BUKAN display:none supaya validasi required tetap jalan).
//   - Setiap pemilihan men-set input.value lalu dispatch event 'input' + 'change'
//     (bubbles) supaya listener lain tetap jalan: cek bentrok jadwal via
//     getElementById('tanggal'), filter onchange="this.form.submit()", dsb.
//   - Class 'open' / 'drop-up' murni untuk query JS + variant Tailwind arbitrary.
(function () {
    var HARI  = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    var BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                        'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];

    var PANEL_CLASSES = 'dtp-panel hidden absolute top-[calc(100%+4px)] left-0 z-30 bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 rounded-lg p-3 shadow-[0_8px_24px_rgba(0,0,0,0.12)] [&.open]:block [&.drop-up]:top-auto [&.drop-up]:bottom-[calc(100%+4px)] [&.drop-up]:shadow-[0_-8px_24px_rgba(0,0,0,0.12)]';
    var NAV_BTN_CLASSES = 'w-7 h-7 rounded-lg border-none bg-transparent cursor-pointer inline-flex items-center justify-center text-text-muted hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-primary text-lg';
    var DAY_BASE = 'dtp-day h-8 rounded-lg border-none bg-transparent text-[13px] font-sans cursor-pointer inline-flex items-center justify-center text-text dark:text-text-dark hover:bg-page-bg dark:hover:bg-page-bg-dark';
    var TIME_ITEM_BASE = 'dtp-time-item w-full h-8 shrink-0 rounded-lg border-none bg-transparent text-[13px] font-sans cursor-pointer inline-flex items-center justify-center text-text dark:text-text-dark hover:bg-page-bg dark:hover:bg-page-bg-dark';
    var ACTIVE_CLASSES = ['!bg-primary', '!text-white'];

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function parseTanggal(v) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || '');
        return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
    }

    function formatTanggal(v) {
        var d = parseTanggal(v);
        if (!d) return '';
        return HARI[d.getDay()] + ', ' + d.getDate() + ' ' + BULAN_PENDEK[d.getMonth()] + ' ' + d.getFullYear();
    }

    function toVal(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function closeAllPanels(except) {
        document.querySelectorAll('.dtp-panel.open').forEach(function (p) {
            if (p !== except) p.classList.remove('open');
        });
    }

    function setValue(input, val) {
        input.value = val;
        input.dispatchEvent(new Event('input',  { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Buka panel + flip ke atas kalau ruang viewport di bawah trigger tidak cukup
    // (logika sama dengan searchable-select.js).
    function openPanel(trigger, panel) {
        closeAllPanels(panel);
        panel.classList.add('open');
        panel.classList.remove('drop-up');
        var rect = trigger.getBoundingClientRect();
        var ruangBawah = window.innerHeight - rect.bottom;
        if (ruangBawah < panel.offsetHeight + 12 && rect.top > ruangBawah) {
            panel.classList.add('drop-up');
        }
    }

    function buatWrapper(input, ikon) {
        var fill = input.classList.contains('w-full') || input.classList.contains('flex-1');
        var wrapper = document.createElement('div');
        wrapper.className = 'dtp-wrap relative' + (fill ? ' w-full' : ' inline-block');
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        // Trigger meniru seluruh class input aslinya (tinggi, lebar, border error,
        // dark mode) supaya tampilannya identik dengan field lain di form yang sama.
        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = input.className + ' flex items-center justify-between gap-2 cursor-pointer text-left';
        var label = document.createElement('span');
        label.className = 'dtp-label truncate';
        trigger.appendChild(label);
        var icon = document.createElement('i');
        icon.className = 'bx ' + ikon + ' text-text-muted text-base shrink-0 pointer-events-none';
        trigger.appendChild(icon);
        wrapper.appendChild(trigger);

        // Sembunyikan input asli TANPA display:none - tetap tervalidasi browser
        // (required) dan bubble errornya muncul menempel di posisi field ini.
        input.className = 'dtp-native absolute left-0 bottom-0 w-full h-px opacity-0 pointer-events-none';
        input.tabIndex = -1;

        return { wrapper: wrapper, trigger: trigger, label: label };
    }

    // ── DATE: panel kalender bulanan ──────────────────────────────────────────
    function enhanceDate(input) {
        if (input.dataset.dtp) return;
        input.dataset.dtp = '1';

        var ui = buatWrapper(input, 'bx-calendar');
        var panel = document.createElement('div');
        panel.className = PANEL_CLASSES + ' w-[272px]';
        ui.wrapper.appendChild(panel);

        var viewDate = parseTanggal(input.value) || new Date();

        function syncLabel() {
            var teks = formatTanggal(input.value);
            ui.label.textContent = teks || 'Pilih tanggal';
            ui.label.classList.toggle('text-text-muted', !teks);
        }

        function render() {
            panel.innerHTML = '';
            var y = viewDate.getFullYear(), m = viewDate.getMonth();
            var min = parseTanggal(input.min), max = parseTanggal(input.max);
            var selected = parseTanggal(input.value);
            var today = new Date(); today.setHours(0, 0, 0, 0);

            var header = document.createElement('div');
            header.className = 'flex items-center justify-between mb-2';
            var prev = document.createElement('button');
            prev.type = 'button';
            prev.className = NAV_BTN_CLASSES;
            prev.innerHTML = '<i class="bx bx-chevron-left"></i>';
            prev.addEventListener('click', function () { viewDate = new Date(y, m - 1, 1); render(); });
            var judul = document.createElement('div');
            judul.className = 'text-sm font-semibold text-text dark:text-text-dark';
            judul.textContent = BULAN[m] + ' ' + y;
            var next = document.createElement('button');
            next.type = 'button';
            next.className = NAV_BTN_CLASSES;
            next.innerHTML = '<i class="bx bx-chevron-right"></i>';
            next.addEventListener('click', function () { viewDate = new Date(y, m + 1, 1); render(); });
            header.appendChild(prev); header.appendChild(judul); header.appendChild(next);
            panel.appendChild(header);

            var grid = document.createElement('div');
            grid.className = 'grid grid-cols-7 gap-0.5';
            HARI.forEach(function (h) {
                var el = document.createElement('div');
                el.className = 'h-8 flex items-center justify-center text-[11px] font-semibold uppercase text-text-muted';
                el.textContent = h;
                grid.appendChild(el);
            });

            // Grid dimulai dari hari Minggu pada minggu berisi tanggal 1.
            var awal = new Date(y, m, 1 - new Date(y, m, 1).getDay());
            for (var i = 0; i < 42; i++) {
                (function (tgl) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = DAY_BASE;
                    btn.textContent = tgl.getDate();
                    if (tgl.getMonth() !== m) btn.classList.add('opacity-35');
                    if (tgl.getTime() === today.getTime()) btn.classList.add('ring-1', 'ring-primary', 'text-primary');
                    if (selected && tgl.getTime() === selected.getTime()) {
                        ACTIVE_CLASSES.forEach(function (c) { btn.classList.add(c); });
                    }
                    if ((min && tgl < min) || (max && tgl > max)) {
                        btn.disabled = true;
                        btn.classList.add('opacity-30', 'cursor-not-allowed', 'pointer-events-none');
                    }
                    btn.addEventListener('click', function () {
                        setValue(input, toVal(tgl));
                        panel.classList.remove('open');
                    });
                    grid.appendChild(btn);
                })(new Date(awal.getFullYear(), awal.getMonth(), awal.getDate() + i));
            }
            panel.appendChild(grid);

            var footer = document.createElement('div');
            footer.className = 'mt-2 pt-2 border-t border-page-bg dark:border-page-bg-dark text-center';
            var hariIni = document.createElement('button');
            hariIni.type = 'button';
            hariIni.className = 'border-none bg-transparent cursor-pointer text-[13px] font-medium text-primary hover:underline font-sans';
            hariIni.textContent = 'Hari Ini';
            if ((min && today < min) || (max && today > max)) hariIni.disabled = true, hariIni.classList.add('opacity-40', 'pointer-events-none');
            hariIni.addEventListener('click', function () {
                setValue(input, toVal(today));
                panel.classList.remove('open');
            });
            footer.appendChild(hariIni);
            panel.appendChild(footer);
        }

        ui.trigger.addEventListener('click', function () {
            if (panel.classList.contains('open')) { panel.classList.remove('open'); return; }
            viewDate = parseTanggal(input.value) || new Date();
            render();
            openPanel(ui.trigger, panel);
        });
        input.addEventListener('change', syncLabel);
        syncLabel();
    }

    // ── TIME: panel dua kolom (jam + menit) ───────────────────────────────────
    function enhanceTime(input) {
        if (input.dataset.dtp) return;
        input.dataset.dtp = '1';

        var ui = buatWrapper(input, 'bx-time-five');
        var panel = document.createElement('div');
        panel.className = PANEL_CLASSES + ' w-[172px]';
        ui.wrapper.appendChild(panel);

        function nilai() {
            var m = /^(\d{2}):(\d{2})/.exec(input.value || '');
            return m ? { jam: +m[1], menit: +m[2] } : null;
        }

        function syncLabel() {
            var v = nilai();
            ui.label.textContent = v ? pad(v.jam) + ':' + pad(v.menit) : 'Pilih jam';
            ui.label.classList.toggle('text-text-muted', !v);
        }

        function render() {
            panel.innerHTML = '';
            var v = nilai();

            var kolomWrap = document.createElement('div');
            kolomWrap.className = 'flex gap-1.5';

            // Kolom menit kelipatan 5; kalau nilai tersimpan menitnya "ganjil"
            // (mis. 13:37 dari data lama), tetap disisipkan supaya terlihat aktif.
            var daftarMenit = [];
            for (var mm = 0; mm < 60; mm += 5) daftarMenit.push(mm);
            if (v && daftarMenit.indexOf(v.menit) === -1) {
                daftarMenit.push(v.menit);
                daftarMenit.sort(function (a, b) { return a - b; });
            }

            [{ label: 'Jam',   n: 24, list: null },
             { label: 'Menit', n: null, list: daftarMenit }].forEach(function (kolom, idx) {
                var box = document.createElement('div');
                box.className = 'flex-1 min-w-0';
                var judul = document.createElement('div');
                judul.className = 'text-[11px] font-semibold uppercase text-text-muted text-center mb-1';
                judul.textContent = kolom.label;
                box.appendChild(judul);
                var list = document.createElement('div');
                list.className = 'custom-scrollbar max-h-[200px] overflow-y-auto flex flex-col gap-0.5 pr-0.5';

                var angkaList = kolom.list || Array.from({ length: kolom.n }, function (_, i) { return i; });
                angkaList.forEach(function (angka) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = TIME_ITEM_BASE;
                    btn.textContent = pad(angka);
                    var aktif = v && (idx === 0 ? v.jam === angka : v.menit === angka);
                    if (aktif) {
                        ACTIVE_CLASSES.forEach(function (c) { btn.classList.add(c); });
                        setTimeout(function () { list.scrollTop = btn.offsetTop - list.clientHeight / 2 + 16; });
                    }
                    btn.addEventListener('click', function () {
                        var cur = nilai() || { jam: 0, menit: 0 };
                        if (idx === 0) {
                            // Pilih jam: set nilai, panel tetap terbuka untuk pilih menit.
                            setValue(input, pad(angka) + ':' + pad(cur.menit));
                            render();
                        } else {
                            setValue(input, pad(cur.jam) + ':' + pad(angka));
                            panel.classList.remove('open');
                        }
                    });
                    list.appendChild(btn);
                });
                box.appendChild(list);
                kolomWrap.appendChild(box);
            });
            panel.appendChild(kolomWrap);
        }

        ui.trigger.addEventListener('click', function () {
            if (panel.classList.contains('open')) { panel.classList.remove('open'); return; }
            render();
            openPanel(ui.trigger, panel);
        });
        input.addEventListener('change', syncLabel);
        syncLabel();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[type="date"]').forEach(enhanceDate);
        document.querySelectorAll('input[type="time"]').forEach(enhanceTime);
    });

    document.addEventListener('mousedown', function (e) {
        if (!e.target.closest('.dtp-wrap')) closeAllPanels();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllPanels();
    });

    window.DateTimePicker = { enhanceDate: enhanceDate, enhanceTime: enhanceTime };
})();
