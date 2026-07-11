<x-guest-layout>

    {{-- Session status (misal: "Link reset password sudah dikirim") --}}
    @if (session('status'))
        <div class="text-xs text-[#2ecc71] mb-[14px]">
            {{ session('status') }}
        </div>
    @endif

    <div id="lp-success-banner" class="hidden text-xs text-[#2ecc71] mb-[14px]"></div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-[18px]">
            <label class="block text-[13px] font-medium text-text mb-1.5" for="email">Email</label>
            <input id="email"
                class="block w-full h-[42px] px-3 py-0 border border-gray-300 rounded-lg bg-surface text-text font-sans text-sm box-border transition-[border-color,box-shadow] duration-200 focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.12)] {{ $errors->has('email') ? '!border-danger-text' : '' }}"
                type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <span class="text-xs text-danger-text mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        {{-- Password dengan tombol show/hide --}}
        <div class="mb-[18px]">
            <label class="block text-[13px] font-medium text-text mb-1.5" for="password">Password</label>
            <div class="relative login-password-wrap">
                <input id="password"
                    class="block w-full h-[42px] pl-3 pr-11 py-0 border border-gray-300 rounded-lg bg-surface text-text font-sans text-sm box-border transition-[border-color,box-shadow] duration-200 focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.12)] {{ $errors->has('password') ? '!border-danger-text' : '' }}"
                    type="password" name="password" required autocomplete="current-password">
                <button type="button"
                    class="login-eye-btn absolute right-3 top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer text-text-muted text-lg flex items-center p-0 transition-colors duration-200 hover:text-primary"
                    aria-label="Tampilkan password">
                    <i class="bx bx-hide"></i>
                </button>
            </div>
            @error('password')
                <span class="text-xs text-danger-text mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        {{-- Remember me + lupa password --}}
        <div class="mb-[18px] flex items-center justify-between">
            <x-checkbox name="remember">Ingat saya</x-checkbox>
            <button type="button" onclick="bukaLupaPassword()"
                class="bg-transparent border-0 p-0 text-[13px] text-primary font-medium cursor-pointer hover:underline">
                Lupa password?
            </button>
        </div>

        {{-- reCAPTCHA --}}
        {{-- Widget resminya cuma tersedia ukuran normal (304x78) atau compact - di-scale up
             (bukan resize) via CSS transform biar lebar visualnya sepadan dengan field lain.
             Skala dihitung dinamis lewat JS (bukan angka tetap) berdasarkan lebar kartu yang
             tersedia, supaya di layar HP sempit widget-nya ikut mengecil, bukan overflow/kepotong. --}}
        @if(class_exists(\Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class))
            <div class="mb-[18px]">
                <div id="recaptcha-wrap" class="w-full max-w-[356px] overflow-hidden">
                    <div id="recaptcha-scale" class="origin-top-left">
                        {!! NoCaptcha::display() !!}
                    </div>
                </div>
                @error('g-recaptcha-response')
                    <span class="text-xs text-danger-text mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        @endif

        {{-- Tombol login --}}
        <button type="submit"
            class="flex items-center justify-center w-full h-11 bg-primary hover:bg-primary-600 text-white border-0 rounded-lg font-sans text-[15px] font-semibold cursor-pointer gap-2 transition-colors duration-200">
            <i class="bx bx-log-in"></i>
            Masuk
        </button>

    </form>

    {{-- reCAPTCHA script --}}
    @if(class_exists(\Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class))
        {!! NoCaptcha::renderJs() !!}
    @endif

    {{-- Modal lupa password: 3 step (email -> kode OTP WhatsApp -> kata sandi baru) --}}
    @php
        $lpBadgeBase = 'w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 border border-gray-300 text-text-muted transition-colors duration-200';
        $lpLineBase = 'flex-1 h-[2px] bg-page-bg transition-colors duration-200';
        $lpInputClass = 'block w-full h-[42px] px-3 py-0 border border-gray-300 rounded-lg bg-surface text-text font-sans text-sm box-border transition-[border-color,box-shadow] duration-200 focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.12)]';
        $lpBtnPrimary = 'h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-primary text-white disabled:opacity-60 disabled:cursor-not-allowed';
        $lpBtnSecondary = 'h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg text-text';
    @endphp
    <div id="modalLupaPassword" class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
        <div class="bg-surface rounded-[14px] w-full max-w-[420px] max-h-[90vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
            <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg">
                <h3 class="m-0 text-[15px] font-semibold text-text">Atur Ulang Kata Sandi</h3>
                <button type="button" onclick="tutupLupaPassword()"
                    class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg hover:text-text">
                    <i class="bx bx-x"></i>
                </button>
            </div>

            <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-4">
                {{-- Stepper --}}
                <div class="flex items-center gap-1.5">
                    <span id="lp-badge-1" class="{{ $lpBadgeBase }}">1</span>
                    <div id="lp-line-1" class="{{ $lpLineBase }}"></div>
                    <span id="lp-badge-2" class="{{ $lpBadgeBase }}">2</span>
                    <div id="lp-line-2" class="{{ $lpLineBase }}"></div>
                    <span id="lp-badge-3" class="{{ $lpBadgeBase }}">3</span>
                </div>

                {{-- Step 1: Email --}}
                <div id="lp-step-1" class="flex flex-col gap-3">
                    <p class="text-[13px] text-text-muted m-0">Masukkan email akun kamu untuk melanjutkan.</p>
                    <div>
                        <label class="block text-[13px] font-medium text-text mb-1.5">Email</label>
                        <input type="email" id="lp-email" placeholder="Masukkan email akun" class="{{ $lpInputClass }}">
                        <span id="lp-email-error" class="hidden text-xs text-danger-text mt-1 block"></span>
                    </div>
                    <div class="flex justify-end gap-2.5 mt-1">
                        <button type="button" onclick="tutupLupaPassword()" class="{{ $lpBtnSecondary }}">Batal</button>
                        <button type="button" id="lp-lanjut-btn" onclick="lpKirimOtp(false)" class="{{ $lpBtnPrimary }}">Lanjutkan</button>
                    </div>
                </div>

                {{-- Step 2: Kode OTP --}}
                <div id="lp-step-2" class="hidden flex-col gap-3">
                    <p class="text-[13px] text-text-muted m-0">Kode verifikasi sudah dikirim ke WhatsApp yang terdaftar. Masukkan kode tersebut.</p>
                    <div>
                        <label class="block text-[13px] font-medium text-text mb-1.5">Kode OTP</label>
                        <input type="text" id="lp-otp" inputmode="numeric" maxlength="6" placeholder="6 digit kode" class="{{ $lpInputClass }}">
                        <span id="lp-otp-error" class="hidden text-xs text-danger-text mt-1 block"></span>
                    </div>
                    <button type="button" id="lp-resend-btn" onclick="lpKirimOtp(true)"
                        class="bg-transparent border-0 p-0 text-xs text-primary font-medium cursor-pointer self-start hover:underline">
                        Kirim ulang kode
                    </button>
                    <div class="flex justify-end gap-2.5 mt-1">
                        <button type="button" onclick="lpKeStep(1)" class="{{ $lpBtnSecondary }}">Kembali</button>
                        <button type="button" id="lp-verify-btn" onclick="lpVerifikasiOtp()" class="{{ $lpBtnPrimary }}">Verifikasi</button>
                    </div>
                </div>

                {{-- Step 3: Kata sandi baru --}}
                <div id="lp-step-3" class="hidden flex-col gap-3">
                    <p class="text-[13px] text-text-muted m-0">Kode terverifikasi. Masukkan kata sandi baru kamu.</p>
                    <div>
                        <label class="block text-[13px] font-medium text-text mb-1.5">Kata sandi baru</label>
                        <div class="relative login-password-wrap">
                            <input type="password" id="lp-password" placeholder="Min. 8 karakter" class="{{ $lpInputClass }} pr-11">
                            <button type="button"
                                class="login-eye-btn absolute right-3 top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer text-text-muted text-lg flex items-center p-0 transition-colors duration-200 hover:text-primary"
                                aria-label="Tampilkan password">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-text mb-1.5">Konfirmasi kata sandi baru</label>
                        <div class="relative login-password-wrap">
                            <input type="password" id="lp-password-confirm" placeholder="Ulangi kata sandi baru" class="{{ $lpInputClass }} pr-11">
                            <button type="button"
                                class="login-eye-btn absolute right-3 top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer text-text-muted text-lg flex items-center p-0 transition-colors duration-200 hover:text-primary"
                                aria-label="Tampilkan password">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                        <span id="lp-password-error" class="hidden text-xs text-danger-text mt-1 block"></span>
                    </div>
                    <div class="flex justify-end gap-2.5 mt-1">
                        <button type="button" onclick="tutupLupaPassword()" class="{{ $lpBtnSecondary }}">Batal</button>
                        <button type="button" id="lp-simpan-btn" onclick="lpSimpanPassword()" class="{{ $lpBtnPrimary }}">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var currentEmail = '';
    var currentOtp = '';
    var ACTIVE_BADGE = 'w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 bg-primary text-white transition-colors duration-200';
    var INACTIVE_BADGE = 'w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 border border-gray-300 text-text-muted transition-colors duration-200';

    window.bukaLupaPassword = function () {
        lpReset();
        document.getElementById('modalLupaPassword').classList.add('open');
    };

    window.tutupLupaPassword = function () {
        document.getElementById('modalLupaPassword').classList.remove('open');
    };

    function lpReset() {
        currentEmail = '';
        currentOtp = '';
        document.getElementById('lp-email').value = '';
        document.getElementById('lp-otp').value = '';
        document.getElementById('lp-password').value = '';
        document.getElementById('lp-password-confirm').value = '';
        lpClearErrors();
        lpKeStep(1);
    }

    function lpClearErrors() {
        ['lp-email-error', 'lp-otp-error', 'lp-password-error'].forEach(function (id) {
            var el = document.getElementById(id);
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function lpShowError(id, msg) {
        var el = document.getElementById(id);
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    window.lpKeStep = function (step) {
        [1, 2, 3].forEach(function (s) {
            document.getElementById('lp-step-' + s).classList.toggle('hidden', s !== step);
            document.getElementById('lp-badge-' + s).className = s <= step ? ACTIVE_BADGE : INACTIVE_BADGE;
        });
        document.getElementById('lp-line-1').classList.toggle('bg-primary', step >= 2);
        document.getElementById('lp-line-1').classList.toggle('bg-page-bg', step < 2);
        document.getElementById('lp-line-2').classList.toggle('bg-primary', step >= 3);
        document.getElementById('lp-line-2').classList.toggle('bg-page-bg', step < 3);
    };

    function lpSetLoading(btn, loading, label) {
        btn.disabled = loading;
        btn.textContent = loading ? 'Memproses...' : label;
    }

    function lpPost(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        });
    }

    window.lpKirimOtp = function (isResend) {
        var email = document.getElementById('lp-email').value.trim();
        lpClearErrors();
        if (!email) { lpShowError('lp-email-error', 'Email wajib diisi.'); return; }

        var btn = isResend ? document.getElementById('lp-resend-btn') : document.getElementById('lp-lanjut-btn');
        var originalLabel = btn.textContent;
        lpSetLoading(btn, true, originalLabel);

        lpPost('{{ route('password.send-otp') }}', { email: email })
            .then(function (result) {
                lpSetLoading(btn, false, originalLabel);
                if (!result.ok) {
                    lpShowError(isResend ? 'lp-otp-error' : 'lp-email-error', result.data.message || 'Gagal mengirim kode.');
                    return;
                }
                currentEmail = email;
                if (!isResend) lpKeStep(2);
            })
            .catch(function () {
                lpSetLoading(btn, false, originalLabel);
                lpShowError(isResend ? 'lp-otp-error' : 'lp-email-error', 'Terjadi kesalahan jaringan.');
            });
    };

    window.lpVerifikasiOtp = function () {
        var otp = document.getElementById('lp-otp').value.trim();
        lpClearErrors();
        if (!otp) { lpShowError('lp-otp-error', 'Kode wajib diisi.'); return; }

        var btn = document.getElementById('lp-verify-btn');
        var originalLabel = btn.textContent;
        lpSetLoading(btn, true, originalLabel);

        lpPost('{{ route('password.verify-otp') }}', { email: currentEmail, otp: otp })
            .then(function (result) {
                lpSetLoading(btn, false, originalLabel);
                if (!result.ok) {
                    lpShowError('lp-otp-error', result.data.message || 'Kode salah.');
                    return;
                }
                currentOtp = otp;
                lpKeStep(3);
            })
            .catch(function () {
                lpSetLoading(btn, false, originalLabel);
                lpShowError('lp-otp-error', 'Terjadi kesalahan jaringan.');
            });
    };

    window.lpSimpanPassword = function () {
        var password = document.getElementById('lp-password').value;
        var confirm = document.getElementById('lp-password-confirm').value;
        lpClearErrors();
        if (!password || password.length < 8) { lpShowError('lp-password-error', 'Kata sandi minimal 8 karakter.'); return; }
        if (password !== confirm) { lpShowError('lp-password-error', 'Konfirmasi kata sandi tidak cocok.'); return; }

        var btn = document.getElementById('lp-simpan-btn');
        var originalLabel = btn.textContent;
        lpSetLoading(btn, true, originalLabel);

        lpPost('{{ route('password.reset') }}', {
            email: currentEmail,
            otp: currentOtp,
            password: password,
            password_confirmation: confirm
        })
            .then(function (result) {
                lpSetLoading(btn, false, originalLabel);
                if (!result.ok) {
                    lpShowError('lp-password-error', result.data.message || 'Gagal menyimpan kata sandi.');
                    return;
                }
                tutupLupaPassword();
                var banner = document.getElementById('lp-success-banner');
                banner.textContent = result.data.message;
                banner.classList.remove('hidden');
            })
            .catch(function () {
                lpSetLoading(btn, false, originalLabel);
                lpShowError('lp-password-error', 'Terjadi kesalahan jaringan.');
            });
    };

    document.getElementById('modalLupaPassword').addEventListener('click', function (e) {
        if (e.target.id === 'modalLupaPassword') tutupLupaPassword();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tutupLupaPassword();
    });
})();
    </script>

</x-guest-layout>
