<li class="{{ request()->is('operator/dashboard') ? 'active' : '' }}">
    <a href="{{ route('operator.dashboard') }}"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a>
</li>
<li class="{{ request()->is('operator/jadwal*') ? 'active' : '' }}">
    <a href="{{ route('operator.jadwal.index') }}"><i class='bx bxs-calendar'></i><span class="text">Jadwal
            Saya</span></a>
</li>
{{-- <li class="{{ request()->is('operator/absensi*') ? 'active' : '' }}">
    <a href="{{ route('operator.absensi.index') }}"><i class='bx bxs-check-circle'></i><span
            class="text">Presensi</span></a>
</li> --}}
<li class="{{ request()->is('operator/peralatan*') ? 'active' : '' }}">
    <a href="{{ route('operator.peralatan.index') }}"><i class='bx bxs-wrench'></i><span
            class="text">Peralatan</span></a>
</li>
<li class="{{ request()->is('operator/peminjaman*') ? 'active' : '' }}">
    <a href="{{ route('operator.peminjaman.index') }}"><i class='bx bxs-cart'></i><span
            class="text">Peminjaman</span></a>
</li>