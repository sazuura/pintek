<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpLupaPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetController extends Controller
{
    private const OTP_TTL_MINUTES = 10;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        $throttleKey = 'forgot-password-send:' . $email;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $detik = RateLimiter::availableIn($throttleKey);
            return response()->json(['message' => "Terlalu banyak percobaan. Coba lagi dalam {$detik} detik."], 429);
        }

        $cooldownKey = 'forgot-password-cooldown:' . $email;
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $detik = RateLimiter::availableIn($cooldownKey);
            return response()->json(['message' => "Mohon tunggu {$detik} detik sebelum meminta kode baru."], 429);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            RateLimiter::hit($throttleKey, 900);
            return response()->json(['message' => 'Email tidak terdaftar.'], 422);
        }

        RateLimiter::hit($throttleKey, 900);
        RateLimiter::hit($cooldownKey, self::RESEND_COOLDOWN_SECONDS);

        $kode = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($kode), 'created_at' => now()]
        );

        Mail::to($user->email)->send(new OtpLupaPasswordMail($user->nama_user, $kode));

        return response()->json(['message' => 'Kode verifikasi sudah dikirim ke email terdaftar.']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string',
        ]);

        $error = $this->cekOtp($request->email, $request->otp);
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json(['message' => 'Kode terverifikasi.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'otp'      => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $error = $this->cekOtp($request->email, $request->otp);
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        RateLimiter::clear('forgot-password-verify:' . $request->email);

        return response()->json(['message' => 'Kata sandi berhasil diperbarui. Silakan login dengan kata sandi baru.']);
    }

    private function cekOtp(string $email, string $otp): ?string
    {
        $throttleKey = 'forgot-password-verify:' . $email;
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $detik = RateLimiter::availableIn($throttleKey);
            return "Terlalu banyak percobaan salah. Minta kode baru dalam {$detik} detik.";
        }

        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$row) {
            return 'Kode verifikasi tidak ditemukan. Silakan minta kode baru.';
        }
        if (now()->diffInMinutes(Carbon::parse($row->created_at)) > self::OTP_TTL_MINUTES) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return 'Kode verifikasi sudah kedaluwarsa. Silakan minta kode baru.';
        }
        if (!Hash::check($otp, $row->token)) {
            RateLimiter::hit($throttleKey, 900);
            return 'Kode verifikasi salah.';
        }

        RateLimiter::clear($throttleKey);
        return null;
    }
}
