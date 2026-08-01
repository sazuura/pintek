// Utilitas skeleton loading bersama - dipakai baik oleh live-search.js (menyamarkan hasil
// pencarian selagi menunggu fetch) maupun page-skeleton.js (menyamarkan komponen [data-skel]
// sesaat setelah halaman pertama kali dibuka). Prinsipnya: tidak ada satu kotak abu-abu besar,
// tiap elemen (judul, badge, tombol ikon, foto, chart) diganti placeholder sesuai bentuknya sendiri.
(function () {
    var SKEL = 'bg-gray-200 dark:bg-gray-700 animate-pulse';
    var GAMBAR_KOSONG = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

    function elIkonSaja(el) {
        return (el.tagName === 'BUTTON' || el.tagName === 'A') &&
            !!el.querySelector('i.bx') && el.textContent.replace(/\s+/g, '') === '';
    }

    function elChipAksi(el) {
        var teks = el.textContent.trim();
        return (el.tagName === 'BUTTON' || el.tagName === 'A') &&
            !!el.querySelector('i.bx') && teks.length > 0 && teks.length <= 20;
    }

    function elBadge(el) {
        return el.tagName === 'SPAN' && el.classList.contains('rounded-full') && el.classList.contains('inline-flex');
    }

    function mask(el) {
        // Link/tombol apapun yang ikut tersamarkan dibuat tidak bisa diklik - hrefnya masih
        // menunjuk data lama/kadaluarsa selagi skeleton tampil.
        if (el.tagName === 'A' || el.tagName === 'BUTTON') el.classList.add('pointer-events-none');

        if (elBadge(el)) {
            el.textContent = '';
            el.className = 'inline-block w-16 h-5 rounded-full ' + SKEL;
            return;
        }
        if (elIkonSaja(el)) {
            el.innerHTML = '';
            el.className = 'inline-block w-7 h-7 rounded-lg pointer-events-none ' + SKEL;
            return;
        }
        if (elChipAksi(el)) {
            el.innerHTML = '';
            el.className = 'inline-block w-14 h-4 rounded pointer-events-none align-middle ' + SKEL;
            return;
        }
        if (el.tagName === 'IMG') {
            el.src = GAMBAR_KOSONG;
            el.classList.add('bg-gray-200', 'dark:bg-gray-700', 'animate-pulse');
            return;
        }
        if (el.tagName === 'CANVAS') {
            // Chart.js belum sempat menggambar apapun ke sini - kasih placeholder abu-abu
            // supaya area chart tidak jadi lubang kosong/transparan selagi skeleton tampil.
            el.classList.add('bg-gray-200', 'dark:bg-gray-700', 'animate-pulse', 'rounded');
            return;
        }
        if (el.tagName === 'I' && el.className.indexOf('bx') !== -1) {
            el.style.visibility = 'hidden';
            return;
        }

        var anakElemen = Array.prototype.slice.call(el.children);
        anakElemen.forEach(mask);

        // Teks langsung di elemen ini (bukan di dalam anak elemen) - misalnya "Gedung A"
        // pada <span><i class="bx bx-map"></i> Gedung A</span>, atau "Total Stok: " sebelum
        // <span>{{ $stok }}</span> - diganti jadi satu bar teks di posisi teks aslinya.
        var teksLangsung = '';
        var acuanNode = null;
        Array.prototype.forEach.call(el.childNodes, function (n) {
            if (n.nodeType === 3) {
                teksLangsung += n.textContent;
                if (!acuanNode && n.textContent.trim()) acuanNode = n;
            }
        });
        teksLangsung = teksLangsung.trim();
        if (!teksLangsung) return;

        Array.prototype.forEach.call(el.childNodes, function (n) {
            if (n.nodeType === 3) n.textContent = '';
        });

        var blokPenuh = anakElemen.length === 0 &&
            /^(DIV|P|H1|H2|H3|H4|H5|H6|TD|LI)$/.test(el.tagName);
        var bar = document.createElement('span');
        if (blokPenuh) {
            var lebarAcak = ['w-1/2', 'w-2/3', 'w-3/4', 'w-5/6'][Math.floor(Math.random() * 4)];
            bar.className = 'block ' + lebarAcak + ' h-3 rounded ' + SKEL;
        } else {
            var panjang = Math.min(14, Math.max(3, Math.round(teksLangsung.length * 0.75)));
            bar.className = 'inline-block align-middle rounded ml-1 ' + SKEL;
            bar.style.width = panjang + 'ch';
            bar.style.height = '0.75em';
        }
        if (acuanNode) el.insertBefore(bar, acuanNode); else el.appendChild(bar);
    }

    // Batasi jumlah baris/kartu yang ikut disamarkan - list yang panjang (mis. puluhan baris
    // tabel atau kartu grid) tidak perlu di-mask semuanya, cukup sejumlah yang kelihatan di
    // layar. Ini yang paling menentukan beban kerja main-thread skeleton, jadi item lebih dari
    // batas dibuang saja dari DOM sebelum proses mask jalan - bukan cuma disembunyikan.
    var MAKS_ITEM = 6;
    function batasiItem(root) {
        function potong(list) {
            Array.prototype.slice.call(list, MAKS_ITEM).forEach(function (n) { n.remove(); });
        }
        root.querySelectorAll('table tbody').forEach(function (tbody) {
            potong(tbody.querySelectorAll(':scope > tr:not(.accordion-detail)'));
        });
        var grid = root.querySelector('.grid');
        if (grid) potong(grid.children);
        potong(root.querySelectorAll('.mobile-card'));
    }

    window.SkeletonUtil = { mask: mask, batasiItem: batasiItem, MAKS_ITEM: MAKS_ITEM };
})();
