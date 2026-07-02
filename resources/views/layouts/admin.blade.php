<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Diskominfo</title>

    <!-- Boxicons & Google Fonts -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- Custom CSS (AdminHub) -->
    <link rel="stylesheet" href="{{ asset('css/adminhub.css') }}">
</head>

<body>

    <!-- Sidebar -->
    <section id="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand" style="flex-direction: column; text-align: center;">
            <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height:60px; width:auto; margin-bottom:8px;">
            <span class="text">DISKOMINFOTIK</span>
        </a>

        <ul class="side-menu top">
            <li class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
                <a href="{{ url('/admin/dashboard') }}">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li class="{{ request()->is('admin/users*') ? 'active' : '' }}">
                <a href="{{ url('/admin/users') }}">
                    <i class='bx bxs-group'></i>
                    <span class="text">Users</span>
                </a>
            </li>
            <li class="{{ request()->is('admin/jadwal*') ? 'active' : '' }}">
                <a href="{{ url('/admin/jadwal') }}">
                    <i class='bx bxs-calendar'></i>
                    <span class="text">Jadwal</span>
                </a>
            </li>
            {{-- <li class="{{ request()->is('admin/absensi*') ? 'active' : '' }}">
                <a href="{{ url('/admin/absensi') }}">
                    <i class='bx bxs-check-circle'></i>
                    <span class="text">Presensi</span>
                </a>
            </li>
            <li class="{{ request()->is('admin/peralatan*') ? 'active' : '' }}">
                <a href="{{ url('/admin/peralatan') }}">
                    <i class='bx bxs-wrench'></i>
                    <span class="text">Peralatan</span>
                </a>
            </li> --}}
            <li class="{{ request()->is('admin/laporan*') ? 'active' : '' }}">
                <a href="{{ url('/admin/laporan') }}">
                    <i class='bx bxs-file'></i>
                    <span class="text">Laporan</span>
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
    <!-- End Sidebar -->


    <!-- Content -->
    <section id="content">
        <!-- Navbar -->
        <nav style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center;">
                <i class='bx bx-menu'></i>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="{{ asset('img/amanah.png') }}" alt="Bandung Barat Amanah" style="height:40px; width:auto;">
                <img src="{{ asset('img/jabaristimewa.png') }}" alt="Jabar Istimewa" style="height:40px; width:auto;">
                <img src="{{ asset('img/berakhlak.png') }}" alt="ASN BerAKHLAK" style="height:40px; width:auto;">
                <span></span>
                <div class="theme-toggle">
                    <input type="checkbox" id="switch-mode">
                    <label for="switch-mode" class="toggle">
                        <span class="icon">🌞</span>
                    </label>
                </div>
                <span></span>
                <a href="#" class="profile">
                    <span class="ms-2">Hallo, {{ Auth::user()->nama_user }}</span>
                </a>
            </div>
        </nav>
        <!-- End Navbar -->

        <!-- Flash Notifications -->
        <x-flash />

        <!-- Main Content -->
        @yield('content')

        <!-- Footer -->

    </section>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/adminhub.js') }}"></script>
    <script>
        const toggle = document.getElementById("switch-mode");
        const icon = document.querySelector(".icon");

        toggle.addEventListener("change", () => {
            if (toggle.checked) {
                icon.textContent = "🌙";
            } else {
                icon.textContent = "🌞";
            }
        });
    </script>
    @yield('scripts')
</body>

</html>