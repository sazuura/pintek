<x-guest-layout>

    {{-- Session status (misal: "Link reset password sudah dikirim") --}}
    @if (session('status'))
        <div class="login-error" style="color:#2ecc71; margin-bottom:14px;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="login-field">
            <label class="login-label" for="email">Email</label>
            <input id="email" class="login-input" type="email" name="email" value="{{ old('email') }}" required
                autofocus autocomplete="username">
            @error('email')
                <span class="login-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Password dengan tombol show/hide --}}
        <div class="login-field">
            <label class="login-label" for="password">Password</label>
            <div class="login-password-wrap" x-data="{ show: false }">
                <input id="password" class="login-input" type="password" name="password" required
                    autocomplete="current-password">
                <button type="button" class="login-eye-btn" aria-label="Tampilkan password">
                    <i class="bx bx-hide"></i>
                </button>
            </div>
            @error('password')
                <span class="login-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="login-remember">
            <input type="checkbox" id="remember_me" name="remember">
            <label for="remember_me">Ingat saya</label>
        </div>

        {{-- reCAPTCHA --}}
        @if(class_exists(\Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class))
            <div class="login-captcha">
                {!! NoCaptcha::display() !!}
                @error('g-recaptcha-response')
                    <span class="login-error">{{ $message }}</span>
                @enderror
            </div>
        @endif

        {{-- Tombol login --}}
        <button type="submit" class="login-btn">
            <i class="bx bx-log-in"></i>
            Masuk
        </button>

    </form>

    {{-- reCAPTCHA script --}}
    @if(class_exists(\Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class))
        {!! NoCaptcha::renderJs() !!}
    @endif

</x-guest-layout>