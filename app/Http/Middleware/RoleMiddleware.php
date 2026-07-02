<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_if(!auth()->check(), 403, 'Silakan login terlebih dahulu.');
        abort_if(!in_array(auth()->user()->role, $roles), 403, 'Anda tidak memiliki akses ke halaman ini.');
        return $next($request);
    }
}
