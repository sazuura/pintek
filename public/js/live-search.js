// Progressive enhancement: input[data-live-search="#selector"] mem-filter halaman
// tanpa reload penuh. Setelah user berhenti mengetik sejenak (debounce), request GET
// dikirim lewat fetch(), lalu cuma bagian hasil (elemen yang cocok dengan selector di
// data-live-search) yang di-refresh via innerHTML - form & input pencarian sendiri
// TIDAK ikut diganti, jadi fokus/kursor di kotak pencarian tidak pernah hilang dan
// tidak ada flash reload tiap huruf dihapus/diketik.
(function () {
    var DEBOUNCE_MS = 450;

    document.querySelectorAll('input[data-live-search]').forEach(function (input) {
        var form = input.form;
        var targetSelector = input.getAttribute('data-live-search');
        var region = targetSelector ? document.querySelector(targetSelector) : null;
        if (!form || !region) return;

        var timer = null;
        var reqId = 0;

        function refresh() {
            var myId = ++reqId;
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
