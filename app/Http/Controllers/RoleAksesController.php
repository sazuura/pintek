<?php
namespace App\Http\Controllers;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenuAkses;
use Illuminate\Http\Request;

/**
 * "Sistem Settings" - kelola role & hak akses menu secara dinamis (Gambar 1 & 2 di
 * docs/plans/planning-role-akses-dinamis.md). Perubahan di sini langsung berpengaruh
 * ke akses nyata lewat middleware 'menu-akses' (app/Http/Middleware/MenuAkses.php)
 * dan guard di masing-masing controller (PenjadwalanController, UserController, dst).
 */
class RoleAksesController extends Controller
{
    /**
     * Menu yang TIDAK ditampilkan di modal "Hak Akses Halaman" (Gambar 2) - beda dari
     * Peralatan/Alat Terpasang/Jadwal (satu controller/view dipakai lintas role, jadi
     * toggle role manapun benar-benar fungsional), tiga menu ini terikat ke SATU role
     * spesifik masing-masing (tiap role punya controller/tampilan sendiri, dan
     * Pengaturan malah cuma ada rute admin.pengaturan.* doang). Mencentang kombinasi
     * yang tidak ada implementasinya bikin menu kelihatan aktif tapi tidak pernah
     * nongol di sidebar (Route::has() gagal diam-diam), membingungkan.
     */
    private const MENU_TERKUNCI = ['dashboard', 'laporan', 'pengaturan'];

    public function index()
    {
        $roles = Role::with(['aksesMenu.menu'])
            ->orderBy('id')
            ->get()
            ->map(function ($role) {
                // Ringkasan C/R/U/D di Gambar 1: true kalau role ini punya kemampuan itu
                // di MINIMAL satu menu (rincian sebenarnya per-menu, lihat modal Gambar 2).
                $role->ringkasan = [
                    'lihat'  => $role->aksesMenu->contains(fn($a) => $a->bisa_lihat),
                    'tambah' => $role->aksesMenu->contains(fn($a) => $a->bisa_tambah),
                    'ubah'   => $role->aksesMenu->contains(fn($a) => $a->bisa_ubah),
                    'hapus'  => $role->aksesMenu->contains(fn($a) => $a->bisa_hapus),
                ];
                $role->jumlah_menu_terlihat = $role->aksesMenu->where('bisa_lihat', true)->count();
                return $role;
            });

        $menus = Menu::whereNull('id_parent')
            ->whereNotIn('slug', self::MENU_TERKUNCI)
            ->with(['children' => fn($q) => $q->orderBy('urutan')])
            ->orderBy('urutan')
            ->get();

        return view('dashboard.pengaturan.role-akses', compact('roles', 'menus'));
    }

    public function updateAkses(Request $request, Role $role)
    {
        abort_if(!auth()->user()->punyaAkses('pengaturan', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah hak akses role.');
        $request->validate([
            'akses'                => 'array',
            'akses.*.bisa_lihat'   => 'nullable|boolean',
            'akses.*.bisa_tambah'  => 'nullable|boolean',
            'akses.*.bisa_ubah'    => 'nullable|boolean',
            'akses.*.bisa_hapus'   => 'nullable|boolean',
        ]);

        // Loop ke semua menu yang MUNCUL DI MODAL (bukan cuma key yang ada di request)
        // karena checkbox yang tidak dicentang tidak pernah dikirim browser - kalau
        // loopnya cuma mengikuti $data['akses'], menu yang seluruh centangnya dikosongkan
        // akan terlewat dan nilainya di database tetap tersangkut true selamanya. Menu
        // terkunci (lihat MENU_TERKUNCI) sengaja DIKECUALIKAN dari loop ini juga - kalau
        // tidak, setiap kali admin menyimpan role APA PUN, akses dashboard/laporan/
        // pengaturan role itu (termasuk dashboard-nya sendiri!) ikut ke-reset ke false
        // karena memang tidak pernah dikirim dari form (checkbox-nya sudah disembunyikan).
        foreach (Menu::whereNotIn('slug', self::MENU_TERKUNCI)->pluck('id') as $idMenu) {
            RoleMenuAkses::updateOrCreate(
                ['id_role' => $role->id, 'id_menu' => $idMenu],
                [
                    'bisa_lihat'  => $request->boolean("akses.{$idMenu}.bisa_lihat"),
                    'bisa_tambah' => $request->boolean("akses.{$idMenu}.bisa_tambah"),
                    'bisa_ubah'   => $request->boolean("akses.{$idMenu}.bisa_ubah"),
                    'bisa_hapus'  => $request->boolean("akses.{$idMenu}.bisa_hapus"),
                ]
            );
        }

        return redirect()->route('admin.pengaturan.role-akses.index')
            ->with('success', "Hak akses \"{$role->nama_role}\" berhasil diperbarui.");
    }
}
