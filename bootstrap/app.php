<?php

use App\Http\Middleware\MenuAkses;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'       => RoleMiddleware::class,
            'menu-akses' => MenuAkses::class,
        ]);

        // Guest yang belum login diarahkan ke halaman login, user yang sudah
        // login tapi buka halaman guest (mis. login) diarahkan ke /dashboard -
        // menggantikan App\Http\Middleware\Authenticate & RedirectIfAuthenticated
        // custom yang sudah dihapus (perilakunya identik, cukup lewat konfigurasi).
        $middleware->redirectTo(
            guests: fn () => route('login'),
            users: fn () => route('dashboard'),
        );

        // Password tidak boleh ikut ke-trim otomatis (mis. spasi di awal/akhir
        // yang sengaja diketik user), beda dari field lain yang aman di-trim.
        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
