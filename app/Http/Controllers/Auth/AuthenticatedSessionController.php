<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required|captcha'
        ],[
            'g-recaptcha-response.required' => 'Captcha wajib diisi.',
            'g-recaptcha-response.captcha' => 'Captcha tidak valid.'
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
            ]);
        }

        $flashSukses = 'Login berhasil. Selamat datang, ' . $user->nama_user . '!';

        return match($user->role) {
            'admin'      => redirect()->route('admin.dashboard')->with('success', $flashSukses),
            'operator'   => redirect()->route('operator.dashboard')->with('success', $flashSukses),
            'inventaris' => redirect()->route('inventaris.dashboard')->with('success', $flashSukses),
            default      => redirect('/'),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Anda berhasil logout.');
    }
}