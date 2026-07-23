<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Status akun hanya divalidasi sekali saat login (AuthenticatedSessionController) -
 * tanpa middleware ini, user yang dinonaktifkan admin di tengah sesi tetap bisa
 * memakai aplikasi sampai dia logout sendiri. Dicek di setiap request supaya
 * penonaktifan berlaku seketika (real time), bukan menunggu login berikutnya.
 */
class PastikanUserAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->status !== 'active') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.']);
        }

        return $next($request);
    }
}
