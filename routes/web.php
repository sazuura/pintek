<?php
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlatTerpasangController;
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

// Catatan: 'role:X' di tiap grup TETAP dipertahankan sebagai gerbang luar (persis
// perilaku lama - siapa boleh masuk prefix URL ini sama sekali tidak berubah).
// Middleware 'menu-akses:{slug}' di dalamnya adalah lapisan TAMBAHAN yang baca
// dari tabel role_menu_akses (diatur lewat halaman Sistem Settings) - dipakai
// untuk kontrol visibilitas/CRUD yang dinamis DI DALAM batas role yang sudah ada,
// bukan pengganti batas role itu sendiri. Menyatukan sepenuhnya (role baru bisa
// masuk prefix mana pun) baru bisa aman dilakukan setelah Fase 4 (konsolidasi
// route/view) selesai - lihat docs/plans/planning-role-akses-dinamis.md §4 & §9.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::middleware('menu-akses:dashboard')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    });
    Route::middleware('menu-akses:jadwal')->group(function () {
        Route::resource('jadwal', PenjadwalanController::class)->names('jadwal');
        Route::post('/jadwal/{id}/batalkan', [PenjadwalanController::class, 'batalkan'])->name('jadwal.batalkan');
    });
    Route::middleware('menu-akses:users')->group(function () {
        Route::resource('users', UserController::class)->names('users')->except(['show']);
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
            Route::post('/role-akses',              [RoleAksesController::class, 'store'])->name('role-akses.store');
            Route::delete('/role-akses/{role}',     [RoleAksesController::class, 'destroy'])->name('role-akses.destroy');
            Route::put('/role-akses/{role}/akses',  [RoleAksesController::class, 'updateAkses'])->name('role-akses.updateAkses');
        });
    });
});

Route::prefix('operator')->name('operator.')->middleware(['auth', 'role:operator'])->group(function () {
    Route::middleware('menu-akses:dashboard')->group(function () {
        Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');
    });
    Route::middleware('menu-akses:jadwal')->group(function () {
        Route::get('/jadwal', [OperatorController::class, 'jadwalIndex'])->name('jadwal.index');
    });
    Route::middleware('menu-akses:peralatan')->group(function () {
        Route::prefix('peralatan')->name('peralatan.')->group(function () {
            Route::get('/', [OperatorController::class, 'peralatanIndex'])->name('index');
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
    });
    Route::middleware('menu-akses:alat-terpasang')->group(function () {
        Route::resource('alat-terpasang', AlatTerpasangController::class)->names('alat-terpasang')->except(['show']);
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
            Route::post('/{id}/kembali', [PeminjamanController::class, 'inventarisKembali'])->name('kembali');
        });
    });
});

require __DIR__ . '/auth.php';
