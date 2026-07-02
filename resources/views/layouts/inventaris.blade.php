<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Diskominfo</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="{{ asset('css/adminhub.css') }}">
    <style>
        /* Grid marketplace peralatan */
        .peralatan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
            padding: 1rem 0;
        }

        .peralatan-card {
            background: var(--light);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .07);
            transition: transform .2s, box-shadow .2s;
            display: flex;
            flex-direction: column;
        }

        .peralatan-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
        }

        .card-img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #bbb;
        }

        .card-img img {
            width: 100%;
            height: 140px;
            object-fit: cover;
        }

        .card-body {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }

        .card-title {
            font-size: .95rem;
            font-weight: 600;
            margin: 0;
        }

        .card-location {
            font-size: .78rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .card-stok {
            font-size: .82rem;
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: .5rem;
        }

        .stok-badge {
            font-size: .75rem;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .stok-badge.tersedia {
            background: #d4edda;
            color: #1a6b30;
        }

        .stok-badge.habis {
            background: #f8d7da;
            color: #842029;
        }

        .stok-badge.minim {
            background: #fff3cd;
            color: #856404;
        }

        /* Search & filter toolbar */
        .inv-toolbar {
            display: flex;
            gap: .75rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .inv-toolbar .search-box {
            flex: 1;
            min-width: 200px;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <section id="sidebar">
        <a href="{{ route('inventaris.dashboard') }}" class="brand" style="flex-direction:column; text-align:center;">
            <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height:60px; width:auto; margin-bottom:8px;">
            <span class="text">DISKOMINFOTIK</span>
        </a>

        <ul class="side-menu top">
            <li class="{{ request()->is('inventaris/dashboard') ? 'active' : '' }}">
                <a href="{{ route('inventaris.dashboard') }}">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li class="{{ request()->is('inventaris/peralatan*') ? 'active' : '' }}">
                <a href="{{ route('inventaris.peralatan.index') }}">
                    <i class='bx bxs-box'></i>
                    <span class="text">Peralatan</span>
                </a>
            </li>
            <li class="{{ request()->is('inventaris/peminjaman*') ? 'active' : '' }}">
                <a href="{{ route('inventaris.peminjaman.index') }}">
                    <i class='bx bxs-archive-in'></i>
                    <span class="text">Peminjaman</span>
                </a>
            </li>
        </ul>

        <ul class="side-menu">
            <li>
                <a href="{{ route('logout') }}" class="logout"
                    onclick="event.preventDefault(); document.getElementById('logout-form-inv').submit();">
                    <i class='bx bxs-log-out-circle'></i>
                    <span class="text">Logout</span>
                </a>
                <form id="logout-form-inv" action="{{ route('logout') }}" method="POST" style="display:none;">
                    @csrf
                </form>
            </li>
        </ul>
    </section>

    <!-- Content -->
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