<!DOCTYPE html>
<html lang="id" class="overflow-x-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem') - Diskominfotik</title>

    {{--
    ANTI-FLASH - wajib jadi script PERTAMA di <head>, sebelum CSS apapun.

    Masalah: browser render body dengan warna default (putih) dulu,
    baru JS jalan dan tambahkan class .dark - hasilnya ada flash putih.

    Solusi: tambahkan class 'dark' ke <html> (bukan <body> via document.write).
    document.write('<body class="dark">') sebelumnya menyebabkan bug: parser HTML
    menganggap itu tag <body> KEDUA begitu tag <body> asli di bawah muncul, dan per
    spek HTML, atribut yang sudah ada (class) di body "duplikat" itu TIDAK digabung
    dengan atribut class asli - class asli (bg-page-bg, overflow-x-hidden, dst)
    malah hilang total, cuma tersisa "dark" saja. Makanya kalau dark mode aktif,
    body kehilangan bg-page-bg-nya sendiri (jadi putih) begitu pindah halaman, dan
    saat toggle balik ke light, class 'dark' dilepas dari body yang classnya sudah
    cuma "dark" itu - hasilnya body benar-benar tanpa class sama sekali.
    classList.add() di <html> tidak kena masalah ini karena bukan document.write
    (tidak menulis ulang tag), dan <html> adalah leluhur semua elemen jadi variant
    dark: (lihat @custom-variant di app.css) tetap kena ke semua turunannya.
    --}}
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>

        <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
        @vite(['resources/css/app.css'])

        @stack('styles')
</head>


    <body class="bg-page-bg dark:bg-page-bg-dark overflow-x-hidden font-sans">

        <section id="sidebar"
            class="group peer fixed top-0 left-0 w-[280px] h-full bg-surface dark:bg-surface-dark z-[2000] font-sans transition-[width,left] duration-300 overflow-x-hidden [scrollbar-width:none] [&.hide]:w-[60px] max-md:left-[-280px] max-md:[&.sidebar-open]:left-0 flex flex-col">
            <a href="{{ route(auth()->user()->role . '.dashboard') }}"
                class="text-2xl font-bold h-14 flex items-center text-primary sticky top-0 left-0 bg-surface dark:bg-surface-dark z-[500] p-0 box-content overflow-hidden shrink-0">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Diskominfotik"
                    class="min-w-[60px] h-8 object-contain flex justify-center px-3 shrink-0">
                <span class="text text-2xl group-[.hide]:hidden">DISKOMINFOTIK</span>
            </a>
            <ul class="side-menu top w-full mt-12">
                @yield('sidebar-menu')
            </ul>
            <ul class="side-menu w-full mt-auto mb-4">
                <li>
                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="relative flex items-center h-12 mx-3 my-0.5 pl-4 rounded-xl text-base text-red hover:bg-page-bg dark:hover:bg-page-bg-dark transition-all duration-300 whitespace-nowrap overflow-x-hidden group-[.hide]:mx-auto group-[.hide]:pl-0 group-[.hide]:w-10 group-[.hide]:justify-center">
                        <i class='bx bxs-log-out min-w-10 group-[.hide]:min-w-6 flex justify-center'></i>
                        <span class="text group-[.hide]:hidden">Logout</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </li>
            </ul>
        </section>

        <section id="content"
            class="relative w-[calc(100%-280px)] left-[280px] transition-[width,left] duration-300 peer-[.hide]:w-[calc(100%-60px)] peer-[.hide]:left-15 max-md:w-full max-md:left-0">
            <div id="sidebar-overlay"
                class="hidden fixed inset-0 bg-black/50 z-[1999] [&.show]:block"></div>
            <nav
                class="h-14 bg-surface dark:bg-surface-dark px-6 flex items-center justify-between gap-6 font-sans sticky top-0 left-0 z-[1000] max-md:px-4 max-md:gap-3 before:content-[''] before:absolute before:w-10 before:h-10 before:-bottom-10 before:left-0 before:rounded-full before:shadow-[-20px_-20px_0_var(--color-surface)] dark:before:shadow-[-20px_-20px_0_var(--color-surface-dark)]">
                <i id="sidebar-toggle"
                    class="bx bx-menu inline-flex items-center cursor-pointer text-text dark:text-text-dark text-[1.6rem] leading-none p-0 bg-transparent border-0"></i>
                <div class="flex items-center gap-4 ml-auto">
                    <div class="nav-badges flex items-center gap-2 max-md:hidden">
                        <img src="{{ asset('img/amanah.png') }}" alt="Bandung Barat Amanah" class="h-[34px] w-auto object-contain">
                        <img src="{{ asset('img/jabaristimewa.png') }}" alt="Jabar Istimewa" class="h-[34px] w-auto object-contain">
                        <img src="{{ asset('img/berakhlak.png') }}" alt="ASN BerAKHLAK" class="h-[34px] w-auto object-contain">
                    </div>
                    <div class="theme-toggle flex items-center">
                        <input type="checkbox" id="switch-mode" class="peer hidden">
                        <label for="switch-mode"
                            class="toggle w-9 h-9 rounded-lg bg-page-bg dark:bg-page-bg-dark flex justify-center items-center cursor-pointer transition-colors duration-200 text-text-muted hover:text-primary shrink-0">
                            <i class="bx bx-sun text-lg" id="theme-icon"></i>
                        </label>
                    </div>
                    <a href="#" class="profile">
                        <span class="text-sm text-text dark:text-text-dark">
                            Hallo, {{ Auth::user()->nama_user }}
                        </span>
                    </a>
                </div>
            </nav>

            <x-flash />

            @yield('content')
        </section>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="{{ asset('js/adminhub.js') }}"></script>
        <script src="{{ asset('js/content.js') }}"></script>
        <script src="{{ asset('js/searchable-select.js') }}"></script>
        <script src="{{ asset('js/live-search.js') }}"></script>
        @stack('scripts')
    </body>

</html>
