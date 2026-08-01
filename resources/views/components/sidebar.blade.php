@php
    // Sidebar dinamis tunggal / navbar
    $aBase = 'relative flex items-center h-12 mx-3 my-0.5 pl-4 text-base font-sans transition-all duration-300 whitespace-nowrap overflow-x-hidden group-[.hide]:mx-auto group-[.hide]:pl-0 group-[.hide]:w-10 group-[.hide]:justify-center';
    $aActive = "rounded-r-xl bg-primary-50 dark:bg-primary-950 text-primary font-semibold before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:h-6 before:w-1 before:rounded-r-full before:bg-primary";
    $aInactive = 'rounded-xl text-text dark:text-text-dark hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-primary';

    $userRole = auth()->user()->role;

    // Pola nama route: "{role}.{slug}.index", kecuali dashboard ("{role}.dashboard")
    $routeUntukMenu = function (string $slug) use ($userRole) {
        return match ($slug) {
            'dashboard'  => "{$userRole}.dashboard",
            'pengaturan' => 'admin.pengaturan.role-akses.index',
            default      => "{$userRole}.{$slug}.index",
        };
    };
    $polaAktifUntukMenu = function (string $slug) use ($userRole) {
        return match ($slug) {
            'dashboard'  => "{$userRole}.dashboard",
            'pengaturan' => 'admin.pengaturan.*',
            default      => "{$userRole}.{$slug}.*",
        };
    };

    $role = auth()->user()->roleAkses;
    $menuTerlihat = $role
        ? $role->aksesMenu()->where('bisa_lihat', true)->with('menu')->get()->pluck('menu')->filter()->sortBy('urutan')
        : collect();
@endphp
@foreach($menuTerlihat as $menu)
    @php
        $routeName = $routeUntukMenu($menu->slug);
        $routeAda  = \Illuminate\Support\Facades\Route::has($routeName);
    @endphp
    @if($routeAda)
        <li>
            <a href="{{ route($routeName) }}" class="{{ $aBase }} {{ request()->routeIs($polaAktifUntukMenu($menu->slug)) ? $aActive : $aInactive }}">
                <i class="bx {{ $menu->icon ?? 'bx-file' }} min-w-10 group-[.hide]:min-w-6 flex justify-center"></i><span class="text group-[.hide]:hidden">{{ $menu->nama_menu }}</span>
            </a>
        </li>
    @endif
@endforeach
