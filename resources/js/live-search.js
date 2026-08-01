(function () {
    var DEBOUNCE_MS = 450;

    // Dipanggil tepat sebelum fetch dimulai - menyamarkan (lewat SkeletonUtil.mask, lihat
    // skeleton.js) baris tabel, kartu mobile, dan kartu grid yang SAAT INI sudah ter-render
    // (hasil pencarian sebelumnya, atau render awal dari server) supaya user melihat skeleton
    // alih-alih data lama yang diam/statis selagi menunggu hasil baru. Header tabel & pagination
    // sengaja tidak disentuh (query-nya cuma ambil baris tbody/kartu) supaya tidak ikut berkedip
    // tiap kali user mengetik. Begitu fetch selesai, seluruh region ditimpa HTML asli yang baru.
    function tampilkanSkeleton(region) {
        window.SkeletonUtil.batasiItem(region);
        var node = [];
        region.querySelectorAll('table tbody tr:not(.accordion-detail)').forEach(function (n) { node.push(n); });
        region.querySelectorAll('.mobile-card').forEach(function (n) { node.push(n); });
        var grid = region.querySelector('.grid');
        if (grid) Array.prototype.forEach.call(grid.children, function (n) { node.push(n); });
        node.forEach(window.SkeletonUtil.mask);
    }

    document.querySelectorAll('input[data-live-search]').forEach(function (input) {
        var form = input.form;
        var targetSelector = input.getAttribute('data-live-search');
        var region = targetSelector ? document.querySelector(targetSelector) : null;
        if (!form || !region) return;

        var timer = null;
        var reqId = 0;

        function refresh() {
            var myId = ++reqId;
            tampilkanSkeleton(region);
            var params = new URLSearchParams(new FormData(form));
            Array.from(params.keys()).forEach(function (k) {
                if (!params.get(k)) params.delete(k);
            });
            var qs = params.toString();
            var url = form.getAttribute('action') + (qs ? '?' + qs : '');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    if (myId !== reqId) return; // ada request lebih baru menyusul, abaikan respons basi ini
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var fresh = doc.querySelector(targetSelector);
                    if (fresh) region.innerHTML = fresh.innerHTML;
                    window.history.replaceState({}, '', url);
                })
                .catch(function () { form.submit(); }); // fallback: request gagal -> submit biasa (reload penuh)
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(refresh, DEBOUNCE_MS);
        });
    });
})();
