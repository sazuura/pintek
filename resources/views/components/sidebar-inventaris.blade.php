@php
    // Desain item aktif: kotak rounded lembut warna primary-50 + garis vertikal biru
    // di tepi kiri (before pseudo-element), gantikan efek "cekungan" lama.
    $aBase = 'relative flex items-center h-12 mx-3 my-0.5 pl-4 text-base font-sans transition-all duration-300 whitespace-nowrap overflow-x-hidden group-[.hide]:mx-auto group-[.hide]:pl-0 group-[.hide]:w-10 group-[.hide]:justify-center';
    $aActive = "rounded-r-xl bg-primary-50 dark:bg-primary-950 text-primary font-semibold before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-6 before:w-1 before:rounded-r-full before:bg-primary";
    $aInactive = 'rounded-xl text-text dark:text-text-dark hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-primary';
@endphp
<li>
    <a href="{{ route('inventaris.dashboard') }}" class="{{ $aBase }} {{ request()->is('inventaris/dashboard') ? $aActive : $aInactive }}">
        <i class='bx bxs-dashboard min-w-10 flex justify-center'></i><span class="text group-[.hide]:hidden">Dashboard</span>
    </a>
</li>
<li>
    <a href="{{ route('inventaris.peralatan.index') }}" class="{{ $aBase }} {{ request()->is('inventaris/peralatan*') ? $aActive : $aInactive }}">
        <i class='bx bxs-wrench min-w-10 flex justify-center'></i><span class="text group-[.hide]:hidden">Peralatan Saya</span>
    </a>
</li>
<li>
    <a href="{{ route('inventaris.peminjaman.index') }}" class="{{ $aBase }} {{ request()->is('inventaris/peminjaman*') ? $aActive : $aInactive }}">
        <i class='bx bxs-cart min-w-10 flex justify-center'></i><span class="text group-[.hide]:hidden">Peminjaman</span>
    </a>
</li>
