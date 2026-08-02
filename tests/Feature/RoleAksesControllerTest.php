<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Peralatan;
use App\Models\Role;
use App\Models\RoleMenuAkses;
use App\Models\User;
use Database\Seeders\RoleAksesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RoleAksesControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAksesSeeder::class);

        $this->admin = User::create([
            'id_user'   => 'US001',
            'nama_user' => 'Admin Test',
            'nohp'      => '081234567890',
            'email'     => 'admin@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'status'    => 'active',
        ]);
    }

    #[Test]
    public function seeder_mereplikasi_3_role_bawaan_dengan_slug_yang_sama_persis_dengan_users_role(): void
    {
        $this->assertDatabaseHas('roles', ['slug' => 'admin', 'is_terkunci' => true]);
        $this->assertDatabaseHas('roles', ['slug' => 'operator', 'is_terkunci' => true]);
        $this->assertDatabaseHas('roles', ['slug' => 'inventaris', 'is_terkunci' => true]);
        $this->assertSame(8, Menu::count());
    }

    #[Test]
    public function admin_bisa_lihat_halaman_sistem_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.pengaturan.role-akses.index'))
            ->assertOk()
            ->assertSee('Pengaturan Sistem')
            ->assertSee('Admin')
            ->assertSee('Operator')
            ->assertSee('Inventaris');
    }

    #[Test]
    public function admin_bisa_ubah_hak_akses_menu_untuk_sebuah_role(): void
    {

        $roleOperator = Role::where('slug', 'operator')->first();
        $menuJadwal   = Menu::where('slug', 'jadwal')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.role-akses.updateAkses', $roleOperator), [
                'akses' => [
                    $menuJadwal->id => ['bisa_lihat' => '1', 'bisa_tambah' => '1', 'bisa_ubah' => '0', 'bisa_hapus' => '0'],
                ],
            ])
            ->assertRedirect(route('admin.pengaturan.role-akses.index'));

        $this->assertDatabaseHas('role_menu_akses', [
            'id_role'    => $roleOperator->id,
            'id_menu'    => $menuJadwal->id,
            'bisa_lihat' => true,
            'bisa_tambah' => true,
        ]);
    }

    #[Test]
    public function menu_terkunci_dashboard_laporan_pengaturan_tidak_ikut_berubah_saat_updateakses(): void
    {

        $roleOperator = Role::where('slug', 'operator')->first();
        $menuDashboard = Menu::where('slug', 'dashboard')->first();

        $sebelum = RoleMenuAkses::where('id_role', $roleOperator->id)->where('id_menu', $menuDashboard->id)->first();
        $this->assertTrue((bool) $sebelum->bisa_lihat);

        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.role-akses.updateAkses', $roleOperator), [
                'akses' => [],
            ])
            ->assertRedirect(route('admin.pengaturan.role-akses.index'));

        $sesudah = RoleMenuAkses::where('id_role', $roleOperator->id)->where('id_menu', $menuDashboard->id)->first();
        $this->assertTrue((bool) $sesudah->bisa_lihat, 'Akses dashboard operator seharusnya tetap true, bukan ke-reset.');
    }

    #[Test]
    public function menu_terkunci_tidak_muncul_di_modal_hak_akses(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.pengaturan.role-akses.index'));

        $response->assertOk();
        $menus = $response->viewData('menus')->pluck('slug');
        $this->assertFalse($menus->contains('dashboard'));
        $this->assertFalse($menus->contains('laporan'));
        $this->assertFalse($menus->contains('pengaturan'));
        $this->assertTrue($menus->contains('jadwal'));
    }

    #[Test]
    public function mencabut_semua_centang_pada_suatu_menu_benar_benar_menonaktifkan_akses_di_database(): void
    {

        $roleOperator     = Role::where('slug', 'operator')->first();
        $menuPeminjaman   = Menu::where('slug', 'peminjaman')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.role-akses.updateAkses', $roleOperator), [
                'akses' => [],
            ])
            ->assertRedirect(route('admin.pengaturan.role-akses.index'));

        $this->assertDatabaseHas('role_menu_akses', [
            'id_role'    => $roleOperator->id,
            'id_menu'    => $menuPeminjaman->id,
            'bisa_lihat' => false,
            'bisa_tambah' => false,
            'bisa_ubah'   => false,
            'bisa_hapus'  => false,
        ]);
    }

    #[Test]
    public function role_punya_akses_helper_mengecek_dengan_benar(): void
    {
        $roleAdmin = Role::where('slug', 'admin')->first();
        $roleOperator = Role::where('slug', 'operator')->first();

        $this->assertTrue($roleAdmin->punyaAkses('jadwal', 'hapus'));
        $this->assertFalse($roleOperator->punyaAkses('jadwal', 'hapus'));
        $this->assertTrue($roleOperator->punyaAkses('peminjaman', 'tambah'));
    }

    #[Test]
    public function mencabut_akses_tambah_lewat_settings_benar_benar_memblokir_operator_mengajukan_peminjaman(): void
    {
        $operator = User::create([
            'id_user' => 'US010', 'nama_user' => 'Operator Test', 'nohp' => '081211112222',
            'email' => 'operator@test.com', 'password' => bcrypt('password'),
            'role' => 'operator', 'status' => 'active',
        ]);

        $this->actingAs($operator)
            ->get(route('operator.peminjaman.create'))
            ->assertOk();

        $roleOperator = Role::where('slug', 'operator')->first();
        $menuPeminjaman = Menu::where('slug', 'peminjaman')->first();
        $this->actingAs($this->admin)->put(route('admin.pengaturan.role-akses.updateAkses', $roleOperator), [
            'akses' => [
                $menuPeminjaman->id => ['bisa_lihat' => '1', 'bisa_tambah' => '0', 'bisa_ubah' => '1', 'bisa_hapus' => '0'],
            ],
        ]);

        $operator = $operator->fresh();

        $this->actingAs($operator)
            ->get(route('operator.peminjaman.index'))
            ->assertOk();

        $this->actingAs($operator)
            ->post(route('operator.peminjaman.store'), [
                'tanggal_pinjam'          => now()->addDay()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'keperluan'               => 'Test',
                'peralatan_ids'           => [],
                'peralatan_jumlah'        => [],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function operator_tetap_tidak_bisa_masuk_prefix_admin_walau_akses_menu_dashboard_sama_sama_true(): void
    {

        $operator = User::create([
            'id_user' => 'US011', 'nama_user' => 'Operator Test 2', 'nohp' => '081233334444',
            'email' => 'operator2@test.com', 'password' => bcrypt('password'),
            'role' => 'operator', 'status' => 'active',
        ]);

        $this->actingAs($operator)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($operator)->get('/inventaris/dashboard')->assertForbidden();
        $this->actingAs($operator)->get('/operator/dashboard')->assertOk();
    }

    #[Test]
    public function admin_bisa_ajukan_peminjaman_tapi_selalu_di_luar_rapat(): void
    {

        $alat = Peralatan::create([
            'id_peralatan' => 'PR-ADM', 'nama_peralatan' => 'Proyektor Admin Test',
            'gedung' => 'Gedung A', 'stok' => 3,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.peminjaman.create'))
            ->assertOk()
            ->assertDontSee('data-judul');

        $this->actingAs($this->admin)
            ->post(route('admin.peminjaman.store'), [
                'tanggal_pinjam'          => now()->addDay()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'keperluan'               => 'Dipinjam admin di luar rapat',
                'peralatan_ids'           => [$alat->id_peralatan],
                'peralatan_jumlah'        => [1],
            ])
            ->assertRedirect(route('admin.peminjaman.index'));

        $this->assertDatabaseHas('peminjaman', [
            'id_user'        => $this->admin->id_user,
            'keperluan'      => 'Dipinjam admin di luar rapat',
            'id_penjadwalan' => null,
        ]);

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.peminjaman.approve'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.peminjaman.items.approve'));
    }
}
