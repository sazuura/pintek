<li class="{{ request()->is('inventaris/dashboard') ? 'active' : '' }}">
    <a href="{{ route('inventaris.dashboard') }}"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a>
</li>
<li class="{{ request()->is('inventaris/peralatan*') ? 'active' : '' }}">
    <a href="{{ route('inventaris.peralatan.index') }}"><i class='bx bxs-wrench'></i><span class="text">Peralatan Saya</span></a>
</li>
<li class="{{ request()->is('inventaris/peminjaman*') ? 'active' : '' }}">
    <a href="{{ route('inventaris.peminjaman.index') }}"><i class='bx bxs-cart'></i><span class="text">Peminjaman</span></a>
</li>
