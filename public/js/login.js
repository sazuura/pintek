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

});
