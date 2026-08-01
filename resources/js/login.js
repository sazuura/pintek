document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.login-password-wrap').forEach(function (wrap) {
        const input  = wrap.querySelector('input');
        const eyeBtn = wrap.querySelector('.login-eye-btn');
        const eyeIcon = eyeBtn ? eyeBtn.querySelector('i') : null;

        if (!input || !eyeBtn || !eyeIcon) return;

        eyeBtn.addEventListener('click', function () {
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';

            // Ganti ikon bx-hide ↔ bx-show
            eyeIcon.classList.toggle('bx-hide', !isHidden);
            eyeIcon.classList.toggle('bx-show',  isHidden);
        });
    });

    // reCAPTCHA "normal" cuma tersedia ukuran tetap 304x78 dari Google
    var recaptchaWrap  = document.getElementById('recaptcha-wrap');
    var recaptchaScale = document.getElementById('recaptcha-scale');
    if (recaptchaWrap && recaptchaScale) {
        var NATIVE_WIDTH = 304, NATIVE_HEIGHT = 78, MAX_SCALE = 1.17;

        var applyScale = function () {
            var scale = Math.min(recaptchaWrap.clientWidth / NATIVE_WIDTH, MAX_SCALE);
            recaptchaScale.style.transform = 'scale(' + scale + ')';
            recaptchaWrap.style.height = Math.round(NATIVE_HEIGHT * scale) + 'px';
        };

        applyScale();
        window.addEventListener('resize', applyScale);
    }

});
