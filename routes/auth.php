<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::post('forgot-password/send-otp', [PasswordResetController::class, 'sendOtp'])->name('password.send-otp');
    Route::post('forgot-password/verify-otp', [PasswordResetController::class, 'verifyOtp'])->name('password.verify-otp');
    Route::post('forgot-password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
