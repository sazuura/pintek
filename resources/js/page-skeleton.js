

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

        klon.querySelectorAll('[id]').forEach(function (n) { n.removeAttribute('id'); });
        klon.classList.add('pointer-events-none', 'select-none');
        klon.setAttribute('aria-hidden', 'true');

        window.SkeletonUtil.batasiItem(klon);
        window.SkeletonUtil.mask(klon);
        asli.parentNode.insertBefore(klon, asli);
        klonList.push(klon);
    });

    setTimeout(function () {
        document.documentElement.classList.remove('skel-loading');
        klonList.forEach(function (k) { k.remove(); });

        if (window.Chart && window.Chart.instances) {
            Object.values(window.Chart.instances).forEach(function (c) { c.resize(); });
        }
    }, DURASI_MS);
})();
