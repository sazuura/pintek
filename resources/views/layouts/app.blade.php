<!DOCTYPE html>
<html lang="id" class="overflow-x-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem') - Diskominfotik</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">

    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
        document.documentElement.classList.add('skel-loading');
    </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://unpkg.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
        @vite(['resources/css/app.css'])

        @stack('styles')
</head>

    <body class="bg-page-bg dark:bg-page-bg-dark overflow-x-hidden font-sans">

        <section id="sidebar"
            class="group peer fixed top-0 left-0 w-[280px] h-full bg-surface dark:bg-surface-dark z-[2000] font-sans transition-[width,left] duration-300 overflow-x-hidden [scrollbar-width:none] [&.hide]:w-[60px] max-tablet:left-[-280px] max-tablet:[&.sidebar-open]:left-0 flex flex-col">
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
                        onclick="event.preventDefault(); bukaModalKonfirmasi('modalKonfirmasiLogout');"
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

        <x-modal-konfirmasi id="modalKonfirmasiLogout" title="Konfirmasi Logout" icon="bxs-log-out" icon-class="text-danger-text">
            <div class="bg-danger dark:bg-danger-dark rounded-[10px] py-3.5 px-4">
                <div class="text-[13px] font-semibold text-danger-text">
                    <i class="bx bx-error"></i> Yakin ingin logout dari sistem?
                </div>
            </div>
            <div class="flex justify-end gap-2.5 mt-3">
                <button type="button" data-modal-close
                    class="h-9 px-3.5 rounded-lg bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                <button type="button" onclick="document.getElementById('logout-form').submit();"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white">
                    <i class="bx bxs-log-out"></i> Logout
                </button>
            </div>
        </x-modal-konfirmasi>

        <section id="content"
            class="relative w-[calc(100%-280px)] left-[280px] transition-[width,left] duration-300 peer-[.hide]:w-[calc(100%-60px)] peer-[.hide]:left-15 max-tablet:w-full max-tablet:left-0">
            <div id="sidebar-overlay"
                class="hidden fixed inset-0 bg-black/50 z-[1999] [&.show]:block"></div>
            <nav
                class="h-14 bg-surface dark:bg-surface-dark px-6 flex items-center justify-between gap-6 font-sans sticky top-0 left-0 z-[1000] max-tablet:px-4 max-tablet:gap-3 before:content-[''] before:absolute before:w-10 before:h-10 before:-bottom-10 before:left-0 before:rounded-full before:shadow-[-20px_-20px_0_var(--color-surface)] dark:before:shadow-[-20px_-20px_0_var(--color-surface-dark)]">
                <i id="sidebar-toggle"
                    class="bx bx-menu inline-flex items-center cursor-pointer text-text dark:text-text-dark text-[1.6rem] leading-none p-0 bg-transparent border-0 shrink-0"></i>
                <div class="flex items-center gap-4 max-tablet:gap-2.5 ml-auto min-w-0">
                    <div class="nav-badges flex items-center gap-2 shrink-0 max-tablet:hidden">
                        <img src="{{ asset('img/amanah.png') }}" alt="Bandung Barat Amanah" width="251" height="120" loading="lazy" class="h-[34px] w-auto object-contain">
                        <img src="{{ asset('img/jabaristimewa.png') }}" alt="Jabar Istimewa" width="233" height="120" loading="lazy" class="h-[34px] w-auto object-contain">
                        <img src="{{ asset('img/berakhlak.png') }}" alt="ASN BerAKHLAK" width="621" height="120" loading="lazy" class="h-[34px] w-auto object-contain">
                    </div>
                    <div class="theme-toggle flex items-center shrink-0">
                        <input type="checkbox" id="switch-mode" class="peer hidden">
                        <label for="switch-mode"
                            class="toggle w-9 h-9 rounded-lg bg-page-bg dark:bg-page-bg-dark flex justify-center items-center cursor-pointer transition-colors duration-200 text-text-muted hover:text-primary shrink-0">
                            <i class="bx bx-sun text-lg" id="theme-icon"></i>
                        </label>
                    </div>
                    <a href="#" class="profile min-w-0 max-w-[160px] max-tablet:max-w-[110px]">
                        <span class="text-sm text-text dark:text-text-dark block truncate">
                            Hallo, {{ Auth::user()->nama_user }}
                        </span>
                    </a>
                </div>
            </nav>

            <x-flash />

            @yield('content')
        </section>

        @vite(['resources/js/app.js'])
        @stack('scripts')
    </body>

</html>
