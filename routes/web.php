<?php
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PeralatanController;
use App\Http\Controllers\PenjadwalanController;
use App\Http\Controllers\RoleAksesController;
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
    Route::middleware('menu-akses:dashboard')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    });
    Route::middleware('menu-akses:jadwal')->group(function () {
        Route::resource('jadwal', PenjadwalanController::class)->names('jadwal')->except(['destroy']);
        Route::post('/jadwal/{id}/batalkan', [PenjadwalanController::class, 'batalkan'])->name('jadwal.batalkan');
    });
    Route::middleware('menu-akses:users')->group(function () {
        Route::resource('users', UserController::class)->names('users')->except(['show']);
    });

    Route::middleware('menu-akses:peralatan')->group(function () {
        Route::resource('peralatan', PeralatanController::class)->names('peralatan')->except(['show']);
        Route::post('/peralatan/{id}/status', [PeralatanController::class, 'updateStatus'])->name('peralatan.status');
    });

    Route::middleware('menu-akses:peminjaman')->group(function () {
        Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
            Route::get('/',          [PeminjamanController::class, 'operatorIndex'])->name('index');
            Route::get('/create',    [PeminjamanController::class, 'operatorCreate'])->name('create');
            Route::post('/',         [PeminjamanController::class, 'operatorStore'])->name('store');
            Route::post('/cek-spam', [PeminjamanController::class, 'operatorCekSpam'])->name('cekSpam');
            Route::get('/{id}/edit', [PeminjamanController::class, 'operatorEdit'])->name('edit');
            Route::put('/{id}',      [PeminjamanController::class, 'operatorUpdate'])->name('update');
        });
        Route::post('/peminjaman/{id}/batalkan', [PeminjamanController::class, 'operatorBatalkan'])->name('peminjaman.batalkan');
    });
    Route::middleware('menu-akses:laporan')->group(function () {
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/',            [AdminController::class, 'laporanIndex'])->name('index');
            Route::get('/export/pdf',  [AdminController::class, 'laporanExportPdf'])->name('exportPdf');
            Route::get('/export/excel',[AdminController::class, 'laporanExportExcel'])->name('exportExcel');
        });
    });
    Route::middleware('menu-akses:pengaturan')->group(function () {
        Route::prefix('pengaturan')->name('pengaturan.')->group(function () {
            Route::get('/role-akses',              [RoleAksesController::class, 'index'])->name('role-akses.index');
            Route::put('/role-akses/{role}/akses',  [RoleAksesController::class, 'updateAkses'])->name('role-akses.updateAkses');
        });
    });

});

Route::prefix('operator')->name('operator.')->middleware(['auth', 'role:operator'])->group(function () {
    Route::middleware('menu-akses:dashboard')->group(function () {
        Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');
    });

    Route::middleware('menu-akses:users')->group(function () {
        Route::resource('users', UserController::class)->names('users')->except(['show']);
    });
    Route::middleware('menu-akses:jadwal')->group(function () {

        Route::get('/jadwal',           [PenjadwalanController::class, 'index'])->name('jadwal.index');
        Route::get('/jadwal/create',    [PenjadwalanController::class, 'create'])->name('jadwal.create');
        Route::post('/jadwal',          [PenjadwalanController::class, 'store'])->name('jadwal.store');
        Route::get('/jadwal/{id}',      [PenjadwalanController::class, 'show'])->name('jadwal.show');
        Route::get('/jadwal/{id}/edit', [PenjadwalanController::class, 'edit'])->name('jadwal.edit');
        Route::put('/jadwal/{id}',      [PenjadwalanController::class, 'update'])->name('jadwal.update');
        Route::post('/jadwal/{id}/batalkan', [PenjadwalanController::class, 'batalkan'])->name('jadwal.batalkan');
    });
    Route::middleware('menu-akses:peralatan')->group(function () {
        Route::prefix('peralatan')->name('peralatan.')->group(function () {

            Route::get('/',          [PeralatanController::class, 'index'])->name('index');
            Route::get('/create',    [PeralatanController::class, 'create'])->name('create');
            Route::post('/',         [PeralatanController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [PeralatanController::class, 'edit'])->name('edit');
            Route::put('/{id}',      [PeralatanController::class, 'update'])->name('update');
            Route::delete('/{id}',   [PeralatanController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/status', [PeralatanController::class, 'updateStatus'])->name('status');

        });
    });
    Route::middleware('menu-akses:peminjaman')->group(function () {
        Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
            Route::get('/',          [PeminjamanController::class, 'operatorIndex'])->name('index');
            Route::get('/create',    [PeminjamanController::class, 'operatorCreate'])->name('create');
            Route::post('/',         [PeminjamanController::class, 'operatorStore'])->name('store');
            Route::post('/cek-spam', [PeminjamanController::class, 'operatorCekSpam'])->name('cekSpam');
            Route::get('/{id}/edit', [PeminjamanController::class, 'operatorEdit'])->name('edit');
            Route::put('/{id}',      [PeminjamanController::class, 'operatorUpdate'])->name('update');
        });
        Route::post('/peminjaman/{id}/batalkan', [PeminjamanController::class, 'operatorBatalkan'])->name('peminjaman.batalkan');
    });
});

Route::prefix('inventaris')->name('inventaris.')->middleware(['auth', 'role:inventaris'])->group(function () {
    Route::middleware('menu-akses:dashboard')->group(function () {
        Route::get('/dashboard', [InventarisController::class, 'dashboard'])->name('dashboard');
    });
    Route::middleware('menu-akses:peralatan')->group(function () {
        Route::resource('peralatan', PeralatanController::class)->names('peralatan')->except(['show']);
        Route::post('/peralatan/{id}/status', [PeralatanController::class, 'updateStatus'])->name('peralatan.status');
    });
    Route::middleware('menu-akses:laporan')->group(function () {
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/',             [InventarisController::class, 'laporanIndex'])->name('index');
            Route::get('/export/pdf',   [InventarisController::class, 'laporanExportPdf'])->name('exportPdf');
            Route::get('/export/excel', [InventarisController::class, 'laporanExportExcel'])->name('exportExcel');
        });
    });
    Route::middleware('menu-akses:peminjaman')->group(function () {
        Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
            Route::get('/',              [PeminjamanController::class, 'inventarisIndex'])->name('index');
            Route::post('/{id}/approve', [PeminjamanController::class, 'inventarisApprove'])->name('approve');
            Route::post('/{id}/reject',  [PeminjamanController::class, 'inventarisReject'])->name('reject');
            Route::post('/{id}/items/{idItem}/approve', [PeminjamanController::class, 'inventarisApproveItem'])->name('items.approve');
            Route::post('/{id}/items/{idItem}/reject',  [PeminjamanController::class, 'inventarisRejectItem'])->name('items.reject');
            Route::post('/{id}/kembali', [PeminjamanController::class, 'inventarisKembali'])->name('kembali');
        });
    });
});

require __DIR__ . '/auth.php';
