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

    function jamMenitSekarang() {
        var bagian = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', hour12: false
        }).formatToParts(new Date());
        var jam = 0, menit = 0;
        bagian.forEach(function (b) {
            if (b.type === 'hour') jam = parseInt(b.value, 10);
            if (b.type === 'minute') menit = parseInt(b.value, 10);
        });
        return { jam: jam, menit: menit };
    }

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

    function openPanel(trigger, panel) {
        closeAllPanels(panel);
        panel.classList.add('open');
        panel.classList.remove('drop-up');
        var rect = trigger.getBoundingClientRect();
        var ruangBawah = window.innerHeight - rect.bottom;
        if (ruangBawah < panel.offsetHeight + 12 && rect.top > ruangBawah) {
            panel.classList.add('drop-up');
        }

        panel.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    function buatWrapper(input, ikon) {
        var fill = input.classList.contains('w-full') || input.classList.contains('flex-1');
        var wrapper = document.createElement('div');
        wrapper.className = 'dtp-wrap relative' + (fill ? ' w-full' : ' inline-block');
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

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

        input.className = 'dtp-native absolute left-0 bottom-0 w-full h-px opacity-0 pointer-events-none';
        input.tabIndex = -1;

        return { wrapper: wrapper, trigger: trigger, label: label };
    }

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
            ui.label.classList.toggle('text-text-muted', !teks && input.required);
        }

        function render() {
            panel.innerHTML = '';
            var y = viewDate.getFullYear(), m = viewDate.getMonth();
            var min = parseTanggal(input.min), max = parseTanggal(input.max);
            var selected = parseTanggal(input.value);
            var today = new Date(); today.setHours(0, 0, 0, 0);
            var hariKerjaSaja = input.dataset.weekdaysOnly !== undefined;

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
                    var akhirPekan = hariKerjaSaja && (tgl.getDay() === 0 || tgl.getDay() === 6);
                    if ((min && tgl < min) || (max && tgl > max) || akhirPekan) {
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
            var iniAkhirPekan = hariKerjaSaja && (today.getDay() === 0 || today.getDay() === 6);
            if ((min && today < min) || (max && today > max) || iniAkhirPekan) hariIni.disabled = true, hariIni.classList.add('opacity-40', 'pointer-events-none');
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

    function enhanceTime(input) {
        if (input.dataset.dtp) return;
        input.dataset.dtp = '1';

        var ui = buatWrapper(input, 'bx-time-five');
        var panel = document.createElement('div');
        panel.className = PANEL_CLASSES + ' w-[184px]';
        ui.wrapper.appendChild(panel);

        var jamSementara = null;

        function nilai() {
            var m = /^(\d{2}):(\d{2})/.exec(input.value || '');
            return m ? { jam: +m[1], menit: +m[2] } : null;
        }

        function syncLabel() {
            var v = nilai();
            ui.label.textContent = v ? pad(v.jam) + ':' + pad(v.menit) : 'Pilih jam';
            ui.label.classList.toggle('text-text-muted', !v && input.required);
        }

        function render() {
            panel.innerHTML = '';
            var v = nilai();

            var sekarang = v ? null : jamMenitSekarang();

            var kolomWrap = document.createElement('div');
            kolomWrap.className = 'flex gap-1.5';

            var jamBox = document.createElement('div');
            jamBox.className = 'flex-1 min-w-0';
            var jamJudul = document.createElement('div');
            jamJudul.className = 'text-[11px] font-semibold uppercase text-text-muted text-center mb-1';
            jamJudul.textContent = 'Jam';
            jamBox.appendChild(jamJudul);
            var jamList = document.createElement('div');
            jamList.className = 'custom-scrollbar max-h-[200px] overflow-y-auto flex flex-col gap-0.5 px-1';
            var jamScrollTarget = sekarang ? Math.max(6, Math.min(20, sekarang.jam)) : null;
            var jamTombol = [];

            function updateJamHighlight(scroll) {
                var vNow = nilai();
                jamTombol.forEach(function (t) {
                    t.btn.classList.remove('!bg-primary', '!text-white', 'ring-1', 'ring-primary', 'text-primary');
                    if ((vNow && vNow.jam === t.angka) || (!vNow && jamSementara === t.angka)) {
                        ACTIVE_CLASSES.forEach(function (c) { t.btn.classList.add(c); });
                        if (scroll) jamList.scrollTop = t.btn.offsetTop - jamList.clientHeight / 2 + 16;
                    } else if (jamScrollTarget === t.angka) {

                        t.btn.classList.add('ring-1', 'ring-primary', 'text-primary');
                        if (scroll) jamList.scrollTop = t.btn.offsetTop - jamList.clientHeight / 2 + 16;
                    }
                });
            }

            for (var j = 6; j <= 20; j++) {
                (function (angka) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = TIME_ITEM_BASE;
                    btn.textContent = pad(angka);
                    btn.addEventListener('click', function () {
                        var cur = nilai();
                        if (cur) {

                            setValue(input, pad(angka) + ':' + pad(cur.menit));
                            jamSementara = null;
                        } else {

                            jamSementara = angka;
                        }
                        updateJamHighlight(false);
                    });
                    jamTombol.push({ btn: btn, angka: angka });
                    jamList.appendChild(btn);
                })(j);
            }
            updateJamHighlight(false);
            setTimeout(function () { updateJamHighlight(true); });
            jamBox.appendChild(jamList);
            kolomWrap.appendChild(jamBox);

            var menitBox = document.createElement('div');
            menitBox.className = 'flex-1 min-w-0';
            var menitJudul = document.createElement('div');
            menitJudul.className = 'text-[11px] font-semibold uppercase text-text-muted text-center mb-1';
            menitJudul.textContent = 'Menit';
            menitBox.appendChild(menitJudul);
            var menitList = document.createElement('div');
            menitList.className = 'custom-scrollbar max-h-[200px] overflow-y-auto flex flex-col gap-0.5 px-1';

            for (var mm = 0; mm < 60; mm++) {
                (function (angka) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = TIME_ITEM_BASE;
                    btn.textContent = pad(angka);
                    if (v && v.menit === angka) {
                        ACTIVE_CLASSES.forEach(function (c) { btn.classList.add(c); });
                        setTimeout(function () { menitList.scrollTop = btn.offsetTop - menitList.clientHeight / 2 + 16; });
                    } else if (sekarang && sekarang.menit === angka) {

                        btn.classList.add('ring-1', 'ring-primary', 'text-primary');
                        setTimeout(function () { menitList.scrollTop = btn.offsetTop - menitList.clientHeight / 2 + 16; });
                    }
                    btn.addEventListener('click', function () {

                        var v2 = nilai();
                        var jamUntukDipakai = v2 ? v2.jam : jamSementara;
                        if (jamUntukDipakai === null) return;
                        setValue(input, pad(jamUntukDipakai) + ':' + pad(angka));
                        jamSementara = null;
                        panel.classList.remove('open');
                    });
                    menitList.appendChild(btn);
                })(mm);
            }
            menitBox.appendChild(menitList);
            kolomWrap.appendChild(menitBox);

            panel.appendChild(kolomWrap);
        }

        ui.trigger.addEventListener('click', function () {
            if (panel.classList.contains('open')) { panel.classList.remove('open'); return; }
            jamSementara = null;
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
})();
