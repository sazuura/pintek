<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    /** @test */
    public function admin_bisa_lihat_daftar_user(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.users.index'))
             ->assertOk();
    }

    /** @test */
    public function admin_bisa_tambah_user_operator(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.users.store'), [
                 'nama_user' => 'Operator Baru',
                 'nohp'      => '089999999999',
                 'email'     => 'opbaru@test.com',
                 'password'  => 'password',
                 'role'      => 'operator',
                 'gedung'    => null,
             ])
             ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'opbaru@test.com',
            'role'  => 'operator',
        ]);
    }

    /** @test */
    public function tambah_user_inventaris_wajib_isi_gedung(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.users.store'), [
                 'nama_user' => 'Inventaris Baru',
                 'nohp'      => '088888888888',
                 'email'     => 'inv@test.com',
                 'password'  => 'password',
                 'role'      => 'inventaris',
                 'gedung'    => '', // sengaja kosong
             ])
             ->assertSessionHasErrors('gedung');
    }

    /** @test */
    public function tambah_user_inventaris_berhasil_jika_gedung_diisi(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.users.store'), [
                 'nama_user' => 'Inventaris Baru',
                 'nohp'      => '088888888888',
                 'email'     => 'inv@test.com',
                 'password'  => 'password',
                 'role'      => 'inventaris',
                 'gedung'    => 'Gedung A',
             ])
             ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email'  => 'inv@test.com',
            'role'   => 'inventaris',
            'gedung' => 'Gedung A',
        ]);
    }

    /** @test */
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

    /** @test */
    public function admin_tidak_bisa_nonaktifkan_dirinya_sendiri(): void
    {
        $this->actingAs($this->admin)
             ->delete(route('admin.users.destroy', $this->admin->id_user))
             ->assertRedirect()
             ->assertSessionHas('error');

        // Status admin tetap active
        $this->assertDatabaseHas('users', [
            'id_user' => 'US001',
            'status'  => 'active',
        ]);
    }

    /** @test */
    public function email_duplikat_tidak_bisa_disimpan(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.users.store'), [
                 'nama_user' => 'Duplikat',
                 'nohp'      => '086666666666',
                 'email'     => 'admin@test.com', // email admin sudah ada
                 'password'  => 'password',
                 'role'      => 'operator',
             ])
             ->assertSessionHasErrors('email');
    }
}
