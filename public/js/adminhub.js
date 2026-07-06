document.addEventListener('DOMContentLoaded', function () {


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
    // Class 'dark' ditaruh di <html>, bukan <body> - sudah di-set oleh script anti-flash
    // di <head> (lihat layouts/app.blade.php), tidak perlu baca localStorage lagi di sini.
    var isDark = document.documentElement.classList.contains('dark');

    // Sinkronkan checkbox dan icon dengan state saat ini
    if (switchMode) switchMode.checked = isDark;
    if (themeIcon)  themeIcon.className = isDark ? 'bx bx-moon text-lg' : 'bx bx-sun text-lg';

    // Listener: update semua sekaligus saat user klik toggle
    if (switchMode) {
        switchMode.addEventListener('change', function () {
            var dark = this.checked;
            document.documentElement.classList.toggle('dark', dark);
            if (themeIcon) themeIcon.className = dark ? 'bx bx-moon text-lg' : 'bx bx-sun text-lg';
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


    // ── 6. Show/hide password ───────────────────────────────────────────────
    document.querySelectorAll('.password-wrap').forEach(function (wrap) {
        var input   = wrap.querySelector('input');
        var eyeBtn  = wrap.querySelector('.eye-btn');
        var eyeIcon = eyeBtn ? eyeBtn.querySelector('i') : null;

        if (!input || !eyeBtn || !eyeIcon) return;

        eyeBtn.addEventListener('click', function () {
            var isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';

            // Ganti ikon bx-hide ↔ bx-show
            eyeIcon.classList.toggle('bx-hide', !isHidden);
            eyeIcon.classList.toggle('bx-show',  isHidden);
        });
    });

});
