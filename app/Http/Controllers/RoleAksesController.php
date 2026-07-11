<?php
namespace App\Http\Controllers;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenuAkses;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "Sistem Settings" - kelola role & hak akses menu secara dinamis (Gambar 1 & 2 di
 * docs/plans/planning-role-akses-dinamis.md). Perubahan di sini langsung berpengaruh
 * ke akses nyata lewat middleware 'menu-akses' (app/Http/Middleware/MenuAkses.php)
 * dan guard di masing-masing controller (PenjadwalanController, UserController, dst).
 */
class RoleAksesController extends Controller
{
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
            ->with(['children' => fn($q) => $q->orderBy('urutan')])
            ->orderBy('urutan')
            ->get();

        return view('dashboard.pengaturan.role-akses', compact('roles', 'menus'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->punyaAkses('pengaturan', 'tambah'), 403, 'Anda tidak memiliki akses untuk menambah role.');
        $data = $request->validate([
            'nama_role' => 'required|string|max:100|unique:roles,nama_role',
        ]);

        $slug = Str::slug($data['nama_role'], '_');
        if (Role::where('slug', $slug)->exists()) {
            return back()->withInput()->withErrors(['nama_role' => 'Role dengan nama serupa sudah ada.']);
        }

        $role = Role::create([
            'nama_role'   => $data['nama_role'],
            'slug'        => $slug,
            'status'      => 'aktif',
            'is_terkunci' => false,
        ]);

        // Siapkan baris akses kosong (semua false) untuk tiap menu yang sudah terdaftar,
        // supaya modal "Hak Akses Halaman" langsung punya baris untuk dicentang.
        foreach (Menu::pluck('id') as $idMenu) {
            RoleMenuAkses::create([
                'id_role' => $role->id,
                'id_menu' => $idMenu,
            ]);
        }

        return redirect()->route('admin.pengaturan.role-akses.index')
            ->with('success', "Role \"{$role->nama_role}\" berhasil ditambahkan.");
    }

    public function destroy(Role $role)
    {
        abort_if(!auth()->user()->punyaAkses('pengaturan', 'hapus'), 403, 'Anda tidak memiliki akses untuk menghapus role.');
        if ($role->is_terkunci) {
            return back()->with('error', "Role \"{$role->nama_role}\" adalah role bawaan sistem dan tidak dapat dihapus.");
        }

        if (\App\Models\User::where('role', $role->slug)->exists()) {
            return back()->with('error', "Role \"{$role->nama_role}\" masih dipakai oleh satu atau lebih user, tidak dapat dihapus.");
        }

        $nama = $role->nama_role;
        $role->delete();

        return back()->with('success', "Role \"{$nama}\" berhasil dihapus.");
    }

    public function updateAkses(Request $request, Role $role)
    {
        abort_if(!auth()->user()->punyaAkses('pengaturan', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah hak akses role.');
        $data = $request->validate([
            'akses'                => 'array',
            'akses.*.bisa_lihat'   => 'nullable|boolean',
            'akses.*.bisa_tambah'  => 'nullable|boolean',
            'akses.*.bisa_ubah'    => 'nullable|boolean',
            'akses.*.bisa_hapus'   => 'nullable|boolean',
        ]);

        foreach ($data['akses'] ?? [] as $idMenu => $flags) {
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
