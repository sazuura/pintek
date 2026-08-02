<?php
namespace App\Http\Controllers;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenuAkses;
use Illuminate\Http\Request;

class RoleAksesController extends Controller
{
    private const MENU_TERKUNCI = ['dashboard', 'laporan', 'pengaturan'];

    public function index()
    {
        $roles = Role::with(['aksesMenu.menu'])
            ->orderBy('id')
            ->get()
            ->map(function ($role) {

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
