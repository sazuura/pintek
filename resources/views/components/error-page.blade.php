@props(['code', 'title', 'description'])
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} - {{ $title }} | {{ config('app.name', 'Diskominfotik') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">

    {{-- Anti-flash dark mode --}}
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>

<body class="flex justify-center items-center min-h-screen bg-page-bg dark:bg-page-bg-dark font-sans px-4">
    <div class="w-full max-w-[440px] bg-surface dark:bg-surface-dark rounded-2xl shadow-[0_8px_32px_rgba(0,0,0,0.10)] p-8 xs:p-10 text-center">
        <img src="{{ asset('img/logo.png') }}" alt="Logo Diskominfotik" width="284" height="284" class="h-[90px] w-auto object-contain mx-auto mb-4">

        <div class="text-6xl font-bold text-primary leading-none mb-3">{{ $code }}</div>
        <h1 class="text-lg font-semibold text-text dark:text-text-dark m-0 mb-2">{{ $title }}</h1>
        <p class="text-sm text-text-muted m-0 mb-7">{{ $description }}</p>

        <button type="button" onclick="kembali()"
            class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
            <i class="bx bx-arrow-back"></i> Kembali ke Halaman Sebelumnya
        </button>
    </div>

    <script>
        // Kembali ke halaman sebelumnya kalau ada riwayat navigasi dalam situs ini
        function kembali() {
            if (document.referrer && document.referrer.indexOf(window.location.origin) === 0) {
                window.history.back();
            } else {
                window.location.href = '{{ url('/') }}';
            }
        }
    </script>
</body>

</html>
