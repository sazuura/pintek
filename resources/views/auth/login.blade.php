<x-guest-layout>

    {{-- Session status (misal: "Link reset password sudah dikirim") --}}
    @if (session('status'))
        <div class="text-xs text-[#2ecc71] mb-[14px]">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-[18px]">
            <label class="block text-[13px] font-medium text-text mb-1.5" for="email">Email</label>
            <input id="email"
                class="block w-full h-[42px] px-3 py-0 border border-[#ddd] rounded-lg bg-surface text-text font-sans text-sm box-border transition-[border-color,box-shadow] duration-200 focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.12)]"
                type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <span class="text-xs text-danger-text mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        {{-- Password dengan tombol show/hide --}}
        <div class="mb-[18px]">
            <label class="block text-[13px] font-medium text-text mb-1.5" for="password">Password</label>
            <div class="relative">
                <input id="password"
                    class="block w-full h-[42px] pl-3 pr-11 py-0 border border-[#ddd] rounded-lg bg-surface text-text font-sans text-sm box-border transition-[border-color,box-shadow] duration-200 focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.12)]"
                    type="password" name="password" required autocomplete="current-password">
                <button type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer text-text-muted text-lg flex items-center p-0 transition-colors duration-200 hover:text-primary"
                    aria-label="Tampilkan password">
                    <i class="bx bx-hide"></i>
                </button>
            </div>
            @error('password')
                <span class="text-xs text-danger-text mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center gap-2 mb-[18px] text-[13px] text-text-muted">
            <input type="checkbox" id="remember_me" name="remember"
                class="w-[15px] h-[15px] accent-primary cursor-pointer shrink-0">
            <label for="remember_me">Ingat saya</label>
        </div>

        {{-- reCAPTCHA --}}
        @if(class_exists(\Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class))
            <div class="mb-[18px]">
                {!! NoCaptcha::display() !!}
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

</x-guest-layout>
