<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MenuAkses
{
    public function handle(Request $request, Closure $next, string $menuSlug, string $aksi = 'lihat'): Response
    {
        abort_if(!auth()->check(), 403, 'Silakan login terlebih dahulu.');
        abort_if(!auth()->user()->punyaAkses($menuSlug, $aksi), 403, 'Anda tidak memiliki akses ke halaman ini.');
        return $next($request);
    }
}
