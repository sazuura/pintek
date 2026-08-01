// Skeleton loading sesaat setelah halaman pertama kali dibuka/di-refresh. Blade sudah
// merender HTML lengkap dari server (tidak ada jeda ambil data yang nyata), jadi ini murni
// efek visual: sembunyikan komponen [data-skel] asli (kelas skel-loading ditaruh di <html>
// oleh script anti-flash di layouts/app.blade.php, sebelum sempat kelihatan sekilas), tampilkan
// klon yang disamarkan lewat SkeletonUtil.mask, lalu setelah sebentar tukar balik ke aslinya.
(function () {
    var DURASI_MS = 550;

    var target = document.querySelectorAll('[data-skel]');
    if (!target.length) {
        document.documentElement.classList.remove('skel-loading');
        return;
    }

    var klonList = [];
    target.forEach(function (asli) {
        var klon = asli.cloneNode(true);
        klon.removeAttribute('data-skel');
        klon.removeAttribute('id');
        // ID yang sama persis dengan aslinya harus dibuang dari klon - kalau tidak,
        // document.getElementById() di script lain (mis. kalender dashboard) bisa salah
        // menangkap elemen klon ini alih-alih elemen asli, karena klon selalu disisipkan
        // lebih dulu (mendahului elemen asli) dalam urutan dokumen.
        klon.querySelectorAll('[id]').forEach(function (n) { n.removeAttribute('id'); });
        klon.classList.add('pointer-events-none', 'select-none');
        klon.setAttribute('aria-hidden', 'true');
        // Komponen dengan tabel/kartu panjang (mis. grid Data Peralatan puluhan item) tidak
        // perlu di-clone dan di-mask semuanya - buang dulu item selain beberapa yang pertama
        // biar kerja main-thread-nya ringan, baru sisanya di-mask.
        window.SkeletonUtil.batasiItem(klon);
        window.SkeletonUtil.mask(klon);
        asli.parentNode.insertBefore(klon, asli);
        klonList.push(klon);
    });

    setTimeout(function () {
        document.documentElement.classList.remove('skel-loading');
        klonList.forEach(function (k) { k.remove(); });

        // Canvas Chart.js yang ada di dalam komponen [data-skel] sempat di-instantiate selagi
        // display:none (ukurannya kebaca 0x0) - resize manual di sini supaya chart digambar
        // ulang dengan ukuran yang benar begitu kartunya kelihatan lagi.
        if (window.Chart && window.Chart.instances) {
            Object.values(window.Chart.instances).forEach(function (c) { c.resize(); });
        }
    }, DURASI_MS);
})();
