document.addEventListener('DOMContentLoaded', function () {

    // ── 1. Active state ───────────────────────────────────────────────────────
    document.querySelectorAll('#sidebar .side-menu.top li a').forEach(function (link) {
        var linkPath = new URL(link.href, window.location.origin).pathname;
        if (linkPath !== '/' && window.location.pathname.startsWith(linkPath)) {
            link.closest('li').classList.add('active');
        }
    });


    // ── 2 + 3. Sidebar toggle ─────────────────────────────────────────────────
    var sidebar        = document.getElementById('sidebar');
    var toggleBtn      = document.getElementById('sidebar-toggle');
    var sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebar && toggleBtn) {

        function openDrawer() {
            sidebar.classList.add('sidebar-open');
            if (sidebarOverlay) sidebarOverlay.classList.add('show');
        }
        function closeDrawer() {
            sidebar.classList.remove('sidebar-open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('show');
        }
        function toggleDesktop() {
            var isMini = sidebar.classList.toggle('hide');
            localStorage.setItem('sidebarState', isMini ? 'mini' : 'full');
        }

        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                sidebar.classList.contains('sidebar-open') ? closeDrawer() : openDrawer();
            } else {
                toggleDesktop();
            }
        });

        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeDrawer);

        // Restore sidebar state saat load
        if (window.innerWidth > 768) {
            if (localStorage.getItem('sidebarState') === 'mini') {
                sidebar.classList.add('hide');
            }
        } else {
            closeDrawer();
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                closeDrawer();
                if (localStorage.getItem('sidebarState') === 'mini') {
                    sidebar.classList.add('hide');
                } else {
                    sidebar.classList.remove('hide');
                }
            } else {
                sidebar.classList.remove('hide');
                closeDrawer();
            }
        });
    }


    // ── 4. Dark mode ──────────────────────────────────────────────────────────
    var switchMode = document.getElementById('switch-mode');
    var themeIcon  = document.getElementById('theme-icon');
    var isDark     = document.body.classList.contains('dark');
    //   ^ baca dari body — sudah di-set oleh document.write di <head>,
    //     tidak perlu baca localStorage lagi, sudah sinkron.

    // Sinkronkan checkbox dan icon dengan state body saat ini
    if (switchMode) switchMode.checked    = isDark;
    if (themeIcon)  themeIcon.textContent = isDark ? '🌙' : '🌞';

    // Listener: update semua sekaligus saat user klik toggle
    if (switchMode) {
        switchMode.addEventListener('change', function () {
            var dark = this.checked;
            document.body.classList.toggle('dark', dark);
            if (themeIcon) themeIcon.textContent = dark ? '🌙' : '🌞';
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        });
    }


    // ── 5. Flash toast auto-dismiss ───────────────────────────────────────────
    document.querySelectorAll('.flash-toast, .toast').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

});
