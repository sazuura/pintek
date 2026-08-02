<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

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
    public function admin_bisa_lihat_daftar_user(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.users.index'))
             ->assertOk();
    }

    #[Test]
    public function admin_bisa_tambah_user_operator(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.users.store'), [
                 'nama_user'     => 'Operator Baru',
                 'jenis_kelamin' => 'L',
                 'alamat'        => 'Jl. Contoh No. 1',
                 'nohp'          => '089999999999',
                 'email'         => 'opbaru@test.com',
                 'password'      => 'password',
                 'role'          => 'operator',
             ])
             ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'opbaru@test.com',
            'role'  => 'operator',
        ]);
    }

    #[Test]
    public function role_baru_yang_dibuat_lewat_settings_muncul_di_dropdown_dan_bisa_dipakai(): void
    {

        Role::create(['nama_role' => 'Resepsionis', 'slug' => 'resepsionis', 'status' => 'aktif', 'is_terkunci' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Resepsionis');

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'nama_user'     => 'Resepsionis Baru',
                'jenis_kelamin' => 'P',
                'alamat'        => 'Jl. Contoh No. 2',
                'nohp'          => '089999999998',
                'email'         => 'resepsionisbaru@test.com',
                'password'      => 'password',
                'role'          => 'resepsionis',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'resepsionisbaru@test.com',
            'role'  => 'resepsionis',
        ]);
    }

    #[Test]
    public function admin_bisa_tambah_user_inventaris(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.users.store'), [
                 'nama_user'     => 'Inventaris Baru',
                 'jenis_kelamin' => 'L',
                 'alamat'        => 'Jl. Contoh No. 1',
                 'nohp'          => '088888888888',
                 'email'         => 'inv@test.com',
                 'password'      => 'password',
                 'role'          => 'inventaris',
             ])
             ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'inv@test.com',
            'role'  => 'inventaris',
        ]);
    }

    #[Test]
    public function admin_bisa_nonaktifkan_user_lain(): void
    {
        $operator = User::create([
            'id_user'   => 'US002',
            'nama_user' => 'Operator Test',
            'nohp'      => '087777777777',
            'email'     => 'op@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'operator',
            'status'    => 'active',
        ]);

        $this->actingAs($this->admin)
             ->delete(route('admin.users.destroy', $operator->id_user))
             ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id_user' => 'US002',
            'status'  => 'inactive',
        ]);
    }

    #[Test]
    public function admin_tidak_bisa_nonaktifkan_dirinya_sendiri(): void
    {
        $this->actingAs($this->admin)
             ->delete(route('admin.users.destroy', $this->admin->id_user))
             ->assertRedirect()
             ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id_user' => 'US001',
            'status'  => 'active',
        ]);
    }

    #[Test]
    public function email_duplikat_tidak_bisa_disimpan(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.users.store'), [
                 'nama_user' => 'Duplikat',
                 'nohp'      => '086666666666',
                 'email'     => 'admin@test.com',
                 'password'  => 'password',
                 'role'      => 'operator',
             ])
             ->assertSessionHasErrors('email');
    }
}
