<?php
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PeralatanController;
use App\Http\Controllers\PenjadwalanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));
Route::get('/dashboard', function () {
    return match (auth()->user()->role) {
        'admin'      => redirect()->route('admin.dashboard'),
        'inventaris' => redirect()->route('inventaris.dashboard'),
        default      => redirect()->route('operator.dashboard'),
    };
})->middleware('auth')->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('jadwal', PenjadwalanController::class)->names('jadwal');
    Route::get('/peralatan', [AdminController::class, 'peralatanIndex'])->name('peralatan.index');
    Route::resource('users', UserController::class)->names('users');
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::get('/',            [AdminController::class, 'absensiIndex'])->name('index');
        Route::get('/{id}',        [AdminController::class, 'absensiShow'])->name('show');
        Route::post('/{id}/status',[AdminController::class, 'absensiUpdateStatus'])->name('updateStatus');
    });
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/',            [AdminController::class, 'laporanIndex'])->name('index');
        Route::get('/export/pdf',  [AdminController::class, 'laporanExportPdf'])->name('exportPdf');
        Route::get('/export/excel',[AdminController::class, 'laporanExportExcel'])->name('exportExcel');
    });
    Route::post('/jadwal/{id}/batalkan', [PenjadwalanController::class, 'batalkan'])->name('jadwal.batalkan');
});

Route::prefix('operator')->name('operator.')->middleware(['auth', 'role:operator'])->group(function () {
    Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');
    Route::get('/jadwal', [OperatorController::class, 'jadwalIndex'])->name('jadwal.index');
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::get('/',       [OperatorController::class, 'absensiIndex'])->name('index');
        Route::post('/',      [OperatorController::class, 'absensiStore'])->name('store');
    });
    Route::prefix('peralatan')->name('peralatan.')->group(function () {
        Route::get('/',          [OperatorController::class, 'peralatanIndex'])->name('index');
        Route::patch('/{id}',    [OperatorController::class, 'peralatanKonfirmasi'])->name('konfirmasi');
    });
    Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
        Route::get('/',       [PeminjamanController::class, 'operatorIndex'])->name('index');
        Route::get('/create', [PeminjamanController::class, 'operatorCreate'])->name('create');
        Route::post('/',      [PeminjamanController::class, 'operatorStore'])->name('store');
    });
    Route::post('/peminjaman/{id}/batalkan', [PeminjamanController::class, 'operatorBatalkan'])->name('peminjaman.batalkan');
});

Route::prefix('inventaris')->name('inventaris.')->middleware(['auth', 'role:inventaris'])->group(function () {
    Route::get('/dashboard', [InventarisController::class, 'dashboard'])->name('dashboard');
    Route::resource('peralatan', PeralatanController::class)->names('peralatan');
    Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
        Route::get('/',              [PeminjamanController::class, 'inventarisIndex'])->name('index');
        Route::post('/{id}/approve', [PeminjamanController::class, 'inventarisApprove'])->name('approve');
        Route::post('/{id}/reject',  [PeminjamanController::class, 'inventarisReject'])->name('reject');
        Route::post('/{id}/kembali', [PeminjamanController::class, 'inventarisKembali'])->name('kembali');
    });
});

require __DIR__ . '/auth.php';
