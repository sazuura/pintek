<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenuAkses;
use App\Models\User;
use Database\Seeders\RoleAksesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    /** @test */
    public function seeder_mereplikasi_3_role_bawaan_dengan_slug_yang_sama_persis_dengan_users_role(): void
    {
        $this->assertDatabaseHas('roles', ['slug' => 'admin', 'is_terkunci' => true]);
        $this->assertDatabaseHas('roles', ['slug' => 'operator', 'is_terkunci' => true]);
        $this->assertDatabaseHas('roles', ['slug' => 'inventaris', 'is_terkunci' => true]);
        $this->assertSame(8, Menu::count());
    }

    /** @test */
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

    /** @test */
    public function admin_bisa_tambah_role_baru(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.pengaturan.role-akses.store'), [
                'nama_role' => 'Resepsionis',
            ])
            ->assertRedirect(route('admin.pengaturan.role-akses.index'));

        $this->assertDatabaseHas('roles', [
            'nama_role'   => 'Resepsionis',
            'slug'        => 'resepsionis',
            'is_terkunci' => false,
        ]);

        // Baris akses kosong (semua false) otomatis dibuat untuk tiap menu yang ada.
        $role = Role::where('slug', 'resepsionis')->first();
        $this->assertSame(8, RoleMenuAkses::where('id_role', $role->id)->count());
        $this->assertSame(0, RoleMenuAkses::where('id_role', $role->id)->where('bisa_lihat', true)->count());
    }

    /** @test */
    public function role_bawaan_tidak_bisa_dihapus(): void
    {
        $roleAdmin = Role::where('slug', 'admin')->first();

        $this->actingAs($this->admin)
            ->delete(route('admin.pengaturan.role-akses.destroy', $roleAdmin))
            ->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $roleAdmin->id]);
    }

    /** @test */
    public function role_bawaan_selain_admin_boleh_dihapus_kalau_tidak_dipakai_user(): void
    {
        // Cuma role admin yang benar-benar tidak boleh dihapus - operator & inventaris
        // (walau sama-sama is_terkunci=true dari seeder) sekarang boleh dihapus asal
        // tidak ada user yang masih memakainya (dicek terpisah, lihat test di bawah).
        $roleOperator = Role::where('slug', 'operator')->first();

        $this->actingAs($this->admin)
            ->delete(route('admin.pengaturan.role-akses.destroy', $roleOperator))
            ->assertRedirect();

        $this->assertDatabaseMissing('roles', ['id' => $roleOperator->id]);
    }

    /** @test */
    public function role_baru_yang_masih_dipakai_user_tidak_bisa_dihapus(): void
    {
        $role = Role::create(['nama_role' => 'Resepsionis', 'slug' => 'resepsionis', 'status' => 'aktif', 'is_terkunci' => false]);
        User::create([
            'id_user' => 'US002', 'nama_user' => 'Resep Test', 'nohp' => '081200000000',
            'email' => 'resep@test.com', 'password' => bcrypt('password'),
            'role' => 'resepsionis', 'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.pengaturan.role-akses.destroy', $role))
            ->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /** @test */
    public function role_baru_yang_tidak_dipakai_user_bisa_dihapus(): void
    {
        $role = Role::create(['nama_role' => 'Resepsionis', 'slug' => 'resepsionis', 'status' => 'aktif', 'is_terkunci' => false]);

        $this->actingAs($this->admin)
            ->delete(route('admin.pengaturan.role-akses.destroy', $role))
            ->assertRedirect();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function admin_bisa_ubah_hak_akses_menu_untuk_sebuah_role(): void
    {
        // Pakai menu "jadwal" (bukan "laporan") - laporan/dashboard/pengaturan sengaja
        // dikunci dari modal Hak Akses (lihat RoleAksesController::MENU_TERKUNCI) karena
        // terikat ke satu role spesifik, jadi updateAkses() tidak lagi memprosesnya.
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

    /** @test */
    public function menu_terkunci_dashboard_laporan_pengaturan_tidak_ikut_berubah_saat_updateakses(): void
    {
        // Kalau checkbox-nya disembunyikan dari modal tapi loop updateAkses() masih
        // memproses semua menu, akses dashboard/laporan/pengaturan role manapun akan
        // ke-reset ke false setiap kali admin menyimpan perubahan role LAIN - karena
        // menu terkunci memang tidak pernah dikirim dari form.
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

    /** @test */
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

    /** @test */
    public function mencabut_semua_centang_pada_suatu_menu_benar_benar_menonaktifkan_akses_di_database(): void
    {
        // Operator default punya bisa_tambah=true & bisa_ubah=true untuk menu peminjaman
        // (lihat RoleAksesSeeder). Submit form TANPA menyertakan menu ini di 'akses' sama
        // sekali - persis seperti browser saat semua checkbox baris itu dikosongkan (checkbox
        // yang tidak dicentang tidak pernah dikirim). Sebelumnya loop hanya mengikuti
        // $data['akses'], jadi menu yang hilang total dari request ini tidak pernah ter-update.
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

    /** @test */
    public function role_punya_akses_helper_mengecek_dengan_benar(): void
    {
        $roleAdmin = Role::where('slug', 'admin')->first();
        $roleOperator = Role::where('slug', 'operator')->first();

        $this->assertTrue($roleAdmin->punyaAkses('jadwal', 'hapus'));
        $this->assertFalse($roleOperator->punyaAkses('jadwal', 'hapus'));
        $this->assertTrue($roleOperator->punyaAkses('peminjaman', 'tambah'));
    }

    /** @test */
    public function mencabut_akses_tambah_lewat_settings_benar_benar_memblokir_operator_mengajukan_peminjaman(): void
    {
        $operator = User::create([
            'id_user' => 'US010', 'nama_user' => 'Operator Test', 'nohp' => '081211112222',
            'email' => 'operator@test.com', 'password' => bcrypt('password'),
            'role' => 'operator', 'status' => 'active',
        ]);

        // Sebelum dicabut: operator memang bisa akses halaman buat pengajuan.
        $this->actingAs($operator)
            ->get(route('operator.peminjaman.create'))
            ->assertOk();

        // Admin mencabut akses "tambah" untuk menu peminjaman milik role operator lewat Settings.
        $roleOperator = Role::where('slug', 'operator')->first();
        $menuPeminjaman = Menu::where('slug', 'peminjaman')->first();
        $this->actingAs($this->admin)->put(route('admin.pengaturan.role-akses.updateAkses', $roleOperator), [
            'akses' => [
                $menuPeminjaman->id => ['bisa_lihat' => '1', 'bisa_tambah' => '0', 'bisa_ubah' => '1', 'bisa_hapus' => '0'],
            ],
        ]);

        // Sekarang operator MASIH bisa lihat daftar (bisa_lihat tetap true)...
        $this->actingAs($operator)
            ->get(route('operator.peminjaman.index'))
            ->assertOk();

        // ...tapi submit pengajuan baru (aksi "tambah") ditolak 403, tanpa perlu deploy kode apa pun.
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

    /** @test */
    public function operator_tetap_tidak_bisa_masuk_prefix_admin_walau_akses_menu_dashboard_sama_sama_true(): void
    {
        // Regresi untuk bug yang sempat ketemu: menu "dashboard" dipakai bareng oleh
        // admin/operator/inventaris (semuanya bisa_lihat=true), jadi middleware
        // menu-akses SENDIRIAN tidak cukup untuk memisahkan prefix URL antar role -
        // gerbang 'role:X' di level grup tetap wajib dipertahankan (lihat routes/web.php).
        $operator = User::create([
            'id_user' => 'US011', 'nama_user' => 'Operator Test 2', 'nohp' => '081233334444',
            'email' => 'operator2@test.com', 'password' => bcrypt('password'),
            'role' => 'operator', 'status' => 'active',
        ]);

        $this->actingAs($operator)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($operator)->get('/inventaris/dashboard')->assertForbidden();
        $this->actingAs($operator)->get('/operator/dashboard')->assertOk();
    }
}
