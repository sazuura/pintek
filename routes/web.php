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
    // Controller & view dashboard/peralatan/* sudah generik/role-agnostic (PeralatanController::index()
    // bahkan sudah ada branch khusus role admin untuk search gedung) - rute ini sebelumnya belum
    // pernah didaftarkan untuk admin, jadi menu Peralatan yang dicentang di Sistem Settings tidak
    // pernah benar-benar bisa diakses (sidebar skip diam-diam karena Route::has() gagal).
    Route::middleware('menu-akses:peralatan')->group(function () {
        Route::resource('peralatan', PeralatanController::class)->names('peralatan')->except(['show']);
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
    // "Alat Terpasang" kontennya generik (data inventaris, bukan spesifik-role) - sama seperti
    // peralatan, jadi aman dipasangkan lewat controller yang sama supaya benar-benar dinamis
    // kalau akses menu ini dinyalakan untuk role admin lewat Sistem Settings.
    Route::middleware('menu-akses:alat-terpasang')->group(function () {
        Route::resource('alat-terpasang', AlatTerpasangController::class)->names('alat-terpasang')->except(['show']);
    });
});

Route::prefix('operator')->name('operator.')->middleware(['auth', 'role:operator'])->group(function () {
    Route::middleware('menu-akses:dashboard')->group(function () {
        Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');
    });
    // Sama seperti jadwal & peralatan di bawah - "users" tidak pernah dipakai operator secara
    // default (seeder tidak memberi baris akses sama sekali), tapi begitu admin menyalakan akses
    // menu ini untuk role operator lewat Sistem Settings, route tujuannya harus benar-benar ada.
    Route::middleware('menu-akses:users')->group(function () {
        Route::resource('users', UserController::class)->names('users')->except(['show']);
    });
    Route::middleware('menu-akses:jadwal')->group(function () {
        Route::get('/jadwal', [OperatorController::class, 'jadwalIndex'])->name('jadwal.index');
        // Index tetap baca-saja lewat OperatorController (cuma jadwal milik sendiri), tapi
        // create/edit dsb dipasangkan ke PenjadwalanController yang sama dengan admin, supaya
        // kalau hak akses tambah/ubah jadwal dinyalakan untuk role operator lewat Sistem
        // Settings, rute tujuan tombolnya (yang dinamis lewat auth()->user()->role di view)
        // benar-benar ada - bukan cuma dicentang tapi tidak berfungsi.
        Route::get('/jadwal/create',    [PenjadwalanController::class, 'create'])->name('jadwal.create');
        Route::post('/jadwal',          [PenjadwalanController::class, 'store'])->name('jadwal.store');
        Route::get('/jadwal/{id}',      [PenjadwalanController::class, 'show'])->name('jadwal.show');
        Route::get('/jadwal/{id}/edit', [PenjadwalanController::class, 'edit'])->name('jadwal.edit');
        Route::put('/jadwal/{id}',      [PenjadwalanController::class, 'update'])->name('jadwal.update');
        Route::post('/jadwal/{id}/batalkan', [PenjadwalanController::class, 'batalkan'])->name('jadwal.batalkan');
    });
    Route::middleware('menu-akses:peralatan')->group(function () {
        Route::prefix('peralatan')->name('peralatan.')->group(function () {
            Route::get('/', [OperatorController::class, 'peralatanIndex'])->name('index');
            // Sama seperti jadwal di atas - index baca-saja tetap punya query khusus operator,
            // tapi create/edit/delete dipasangkan ke PeralatanController yang sama dengan
            // inventaris supaya hak akses tambah/ubah/hapus peralatan bisa benar-benar
            // dipakai kalau dinyalakan untuk role operator.
            Route::get('/create',    [PeralatanController::class, 'create'])->name('create');
            Route::post('/',         [PeralatanController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [PeralatanController::class, 'edit'])->name('edit');
            Route::put('/{id}',      [PeralatanController::class, 'update'])->name('update');
            Route::delete('/{id}',   [PeralatanController::class, 'destroy'])->name('destroy');
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
    Route::middleware('menu-akses:alat-terpasang')->group(function () {
        Route::resource('alat-terpasang', AlatTerpasangController::class)->names('alat-terpasang')->except(['show']);
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
            Route::post('/{id}/items/{idItem}/approve', [PeminjamanController::class, 'inventarisApproveItem'])->name('items.approve');
            Route::post('/{id}/items/{idItem}/reject',  [PeminjamanController::class, 'inventarisRejectItem'])->name('items.reject');
            Route::post('/{id}/kembali', [PeminjamanController::class, 'inventarisKembali'])->name('kembali');
        });
    });
});

require __DIR__ . '/auth.php';
