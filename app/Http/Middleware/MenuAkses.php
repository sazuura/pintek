<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti dinamis untuk RoleMiddleware ('role:admin' dkk) - baca izin dari tabel
 * role_menu_akses (diatur lewat halaman Sistem Settings) alih-alih daftar role yang
 * ditulis literal di route. Lihat docs/plans/planning-role-akses-dinamis.md §4.
 *
 * Pemakaian: ->middleware('menu-akses:jadwal') untuk cek bisa_lihat (default),
 * atau ->middleware('menu-akses:jadwal,ubah') untuk cek kolom bisa_ubah, dst.
 */
class MenuAkses
{
    public function handle(Request $request, Closure $next, string $menuSlug, string $aksi = 'lihat'): Response
    {
        abort_if(!auth()->check(), 403, 'Silakan login terlebih dahulu.');
        abort_if(!auth()->user()->punyaAkses($menuSlug, $aksi), 403, 'Anda tidak memiliki akses ke halaman ini.');
        return $next($request);
    }
}
