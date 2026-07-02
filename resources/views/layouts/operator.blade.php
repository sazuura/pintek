<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Diskominfo</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="{{ asset('css/adminhub.css') }}">
</head>

<body>

    <section id="sidebar">
        <a href="{{ route('operator.dashboard') }}" class="brand" style="flex-direction:column; text-align:center;">
            <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height:60px; width:auto; margin-bottom:8px;">
            <span class="text">DISKOMINFOTIK</span>
        </a>

        <ul class="side-menu top">
            <li class="{{ request()->is('operator/dashboard') ? 'active' : '' }}">
                <a href="{{ route('operator.dashboard') }}">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            {{-- Presensi (di-comment sesuai kebutuhan) --}}
            {{-- <li class="{{ request()->is('operator/absensi*') ? 'active' : '' }}">
                <a href="{{ route('operator.absensi.index') }}">
                    <i class='bx bxs-check-circle'></i>
                    <span class="text">Presensi</span>
                </a>
            </li> --}}
            <li class="{{ request()->is('operator/peralatan*') ? 'active' : '' }}">
                <a href="{{ route('operator.peralatan.index') }}">
                    <i class='bx bxs-wrench'></i>
                    <span class="text">Peralatan</span>
                </a>
            </li>
            <li class="{{ request()->is('operator/peminjaman*') ? 'active' : '' }}">
                <a href="{{ route('operator.peminjaman.index') }}">
                    <i class='bx bxs-archive-in'></i>
                    <span class="text">Peminjaman</span>
                </a>
            </li>
        </ul>

        <ul class="side-menu">
            <li>
                <a href="{{ route('logout') }}" class="logout"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class='bx bxs-log-out-circle'></i>
                    <span class="text">Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                    @csrf
                </form>
            </li>
        </ul>
    </section>

    <section id="content">
        <div id="sidebar-overlay" onclick="closeSidebar()"></div>
        <nav style="display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center;">
                <i class='bx bx-menu'></i>
            </div>
            <div style="display:flex; align-items:center; gap:15px;">
                <img src="{{ asset('img/amanah.png') }}" alt="" style="height:40px;">
                <img src="{{ asset('img/jabaristimewa.png') }}" alt="" style="height:40px;">
                <img src="{{ asset('img/berakhlak.png') }}" alt="" style="height:40px;">
                <div class="theme-toggle">
                    <input type="checkbox" id="switch-mode">
                    <label for="switch-mode" class="toggle">
                        <span class="icon">🌞</span>
                    </label>
                </div>
                <a href="#" class="profile">
                    <span class="ms-2">Hallo, {{ Auth::user()->nama_user }}</span>
                </a>
            </div>
        </nav>
        <!-- Flash Notifications -->
        <x-flash />

        <!-- Main Content -->
        @yield('content')
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/adminhub.js') }}"></script>
    <script>
        document.getElementById('switch-mode').addEventListener('change', function () {
            document.querySelector('.icon').textContent = this.checked ? '🌙' : '🌞';
        });
    </script>
    @yield('scripts')
</body>

</html>