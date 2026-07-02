<li class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
    <a href="{{ route('admin.dashboard') }}"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a>
</li>
<li class="{{ request()->is('admin/users*') ? 'active' : '' }}">
    <a href="{{ route('admin.users.index') }}"><i class='bx bxs-group'></i><span class="text">Users</span></a>
</li>
<li class="{{ request()->is('admin/jadwal*') ? 'active' : '' }}">
    <a href="{{ route('admin.jadwal.index') }}"><i class='bx bxs-calendar'></i><span class="text">Jadwal</span></a>
</li>
{{-- <li class="{{ request()->is('admin/absensi*') ? 'active' : '' }}">
    <a href="{{ route('admin.absensi.index') }}"><i class='bx bxs-check-circle'></i><span
            class="text">Presensi</span></a>
</li>
<li class="{{ request()->is('admin/peralatan*') ? 'active' : '' }}">
    <a href="{{ route('admin.peralatan.index') }}"><i class='bx bxs-wrench'></i><span class="text">Peralatan</span></a>
</li> --}}
<li class="{{ request()->is('admin/laporan*') ? 'active' : '' }}">
    <a href="{{ route('admin.laporan.index') }}"><i class='bx bxs-file'></i><span class="text">Laporan</span></a>
</li>