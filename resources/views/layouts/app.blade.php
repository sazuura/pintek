<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem') — Diskominfotik</title>

    {{--
    ANTI-FLASH — wajib jadi script PERTAMA di

    <head>, sebelum CSS apapun.

        Masalah: browser render body dengan warna default (putih) dulu,
        baru JS jalan dan tambahkan class .dark — hasilnya ada flash putih.

        Solusi: script ini jalan SEBELUM CSS dimuat dan SEBELUM body dirender.
        Langsung tambahkan class 'dark' ke

    <body> jika localStorage bilang dark.
        Ketika CSS akhirnya dimuat, body sudah punya class .dark → langsung gelap.
        Tidak ada momen putih sama sekali.
        --}}
        <script>
            if (localStorage.getItem('theme') === 'dark') {
                document.write('<body class="dark">');
            }
        </script>

        <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/adminhub.css') }}">

        @stack('styles')
</head>

{{--

<body> tanpa class — class 'dark' sudah ditulis via document.write di atas jika perlu.
    Jangan tambahkan class apapun di sini agar tidak konflik.
    --}}

    <body>

        <section id="sidebar">
            <a href="{{ route(auth()->user()->role . '.dashboard') }}" class="brand">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Diskominfotik" class="brand-logo">
                <span class="text">DISKOMINFOTIK</span>
            </a>
            <ul class="side-menu top">
                @yield('sidebar-menu')
            </ul>
            <ul class="side-menu">
                <li>
                    <a href="{{ route('logout') }}" class="logout"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class='bx bxs-log-out'></i>
                        <span class="text">Logout</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </section>

        <section id="content">
            <div id="sidebar-overlay"></div>
            <nav>
                <i class="bx bx-menu" id="sidebar-toggle"></i>
                <div style="display:flex; align-items:center; gap:16px; margin-left:auto;">
                    <div class="nav-badges">
                        <img src="{{ asset('img/amanah.png') }}" alt="Bandung Barat Amanah">
                        <img src="{{ asset('img/jabaristimewa.png') }}" alt="Jabar Istimewa">
                        <img src="{{ asset('img/berakhlak.png') }}" alt="ASN BerAKHLAK">
                    </div>
                    <div class="theme-toggle">
                        <input type="checkbox" id="switch-mode">
                        <label for="switch-mode" class="toggle">
                            <span class="icon" id="theme-icon">🌞</span>
                        </label>
                    </div>
                    <a href="#" class="profile">
                        <span style="font-size:14px; color:var(--dark);">
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
        @stack('scripts')
    </body>

</html>