<?php
namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenuAkses;
use Illuminate\Database\Seeder;

/**
 * Seed data awal untuk sistem role akses dinamis. Baris role_menu_akses di sini
 * SENGAJA dibuat untuk mereplikasi PERSIS kondisi akses yang sudah berjalan sekarang
 * (hasil audit routes/web.php + middleware role:X yang ada) - supaya begitu sistem
 * baru ini mulai dipakai (Fase 3+), tidak ada perubahan perilaku akses untuk role
 * admin/operator/inventaris yang sudah ada. Lihat docs/plans/planning-role-akses-dinamis.md.
 */
class RoleAksesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin'      => Role::updateOrCreate(['slug' => 'admin'], [
                'nama_role' => 'Admin', 'status' => 'aktif', 'is_terkunci' => true,
            ]),
            'operator'   => Role::updateOrCreate(['slug' => 'operator'], [
                'nama_role' => 'Operator', 'status' => 'aktif', 'is_terkunci' => true,
            ]),
            'inventaris' => Role::updateOrCreate(['slug' => 'inventaris'], [
                'nama_role' => 'Inventaris', 'status' => 'aktif', 'is_terkunci' => true,
            ]),
        ];

        $menuDefinisi = [
            ['slug' => 'dashboard',      'nama_menu' => 'Dashboard',              'icon' => 'bxs-dashboard', 'urutan' => 1],
            ['slug' => 'users',          'nama_menu' => 'Users',                  'icon' => 'bxs-group',     'urutan' => 2],
            ['slug' => 'jadwal',         'nama_menu' => 'Jadwal Rapat',           'icon' => 'bxs-calendar',  'urutan' => 3],
            ['slug' => 'peralatan',      'nama_menu' => 'Peralatan',              'icon' => 'bxs-wrench',    'urutan' => 4],
            ['slug' => 'peminjaman',     'nama_menu' => 'Peminjaman Peralatan',   'icon' => 'bx-package',    'urutan' => 5],
            ['slug' => 'alat-terpasang', 'nama_menu' => 'Alat Terpasang',         'icon' => 'bx-tv',         'urutan' => 6],
            ['slug' => 'laporan',        'nama_menu' => 'Laporan',                'icon' => 'bxs-file',      'urutan' => 7],
            ['slug' => 'pengaturan',     'nama_menu' => 'Pengaturan Sistem',      'icon' => 'bxs-cog',       'urutan' => 8],
        ];

        $menus = [];
        foreach ($menuDefinisi as $m) {
            $menus[$m['slug']] = Menu::updateOrCreate(['slug' => $m['slug']], $m);
        }

        // [role][menu] => [lihat, tambah, ubah, hapus] - hasil audit akses yang sudah
        // berjalan sekarang per role (lihat §3 planning doc untuk penjelasan tiap baris).
        $matrix = [
            'admin' => [
                'dashboard'  => [true, false, false, false],
                'jadwal'     => [true, true,  true,  true],
                'users'      => [true, true,  true,  true],
                'laporan'    => [true, false, false, false],
                'pengaturan' => [true, true,  true,  true],
            ],
            'operator' => [
                'dashboard'  => [true, false, false, false],
                'jadwal'     => [true, false, false, false],
                'peralatan'  => [true, false, false, false],
                'peminjaman' => [true, true,  true,  false],
            ],
            'inventaris' => [
                'dashboard'      => [true, false, false, false],
                'peralatan'      => [true, true,  true,  true],
                'alat-terpasang' => [true, true,  true,  true],
                'peminjaman'     => [true, false, true,  false],
                'laporan'        => [true, false, false, false],
            ],
        ];

        foreach ($matrix as $roleSlug => $menuAkses) {
            foreach ($menuAkses as $menuSlug => [$lihat, $tambah, $ubah, $hapus]) {
                RoleMenuAkses::updateOrCreate(
                    ['id_role' => $roles[$roleSlug]->id, 'id_menu' => $menus[$menuSlug]->id],
                    ['bisa_lihat' => $lihat, 'bisa_tambah' => $tambah, 'bisa_ubah' => $ubah, 'bisa_hapus' => $hapus]
                );
            }
        }
    }
}
