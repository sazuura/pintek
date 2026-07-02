<?php

namespace Tests\Feature;

use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeralatanControllerTest extends TestCase
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
    public function admin_bisa_tambah_peralatan(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.peralatan.store'), [
                 'kode_barang'    => 'GU/LAP/2024/001',
                 'nama_peralatan' => 'Laptop Zoom',
                 'gedung'         => 'Gedung Utama',
                 'lokasi_detail'  => 'Rak A Lt.2',
                 'stok'           => 5,
             ])
             ->assertRedirect(route('admin.peralatan.index'));

        $this->assertDatabaseHas('peralatan', [
            'kode_barang'    => 'GU/LAP/2024/001',
            'nama_peralatan' => 'Laptop Zoom',
            'gedung'         => 'Gedung Utama',
            'stok'           => 5,
        ]);
    }

    /** @test */
    public function id_peralatan_generate_otomatis(): void
    {
        $this->actingAs($this->admin)
             ->post(route('admin.peralatan.store'), [
                 'nama_peralatan' => 'Mikrofon',
                 'gedung'         => 'Gedung A',
                 'stok'           => 3,
             ]);

        $this->assertDatabaseHas('peralatan', ['id_peralatan' => 'PR-001']);
    }

    /** @test */
    public function kode_barang_harus_unik(): void
    {
        Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'kode_barang'    => 'GA/MIC/2024/001',
            'nama_peralatan' => 'Mikrofon',
            'gedung'         => 'Gedung A',
            'stok'           => 3,
        ]);

        $this->actingAs($this->admin)
             ->post(route('admin.peralatan.store'), [
                 'kode_barang'    => 'GA/MIC/2024/001', // duplikat
                 'nama_peralatan' => 'Mikrofon Lain',
                 'gedung'         => 'Gedung A',
                 'stok'           => 2,
             ])
             ->assertSessionHasErrors('kode_barang');
    }

    /** @test */
    public function update_gagal_jika_rusak_plus_perbaikan_melebihi_stok(): void
    {
        $peralatan = Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Speaker',
            'gedung'         => 'Gedung B',
            'stok'           => 3,
        ]);

        $this->actingAs($this->admin)
             ->put(route('admin.peralatan.update', $peralatan->id_peralatan), [
                 'nama_peralatan' => 'Speaker',
                 'gedung'         => 'Gedung B',
                 'stok'           => 3,
                 'rusak'          => 2,
                 'perbaikan'      => 2, // 2+2 > 3 → harus error
             ])
             ->assertSessionHasErrors('rusak');
    }

    /** @test */
    public function admin_bisa_update_peralatan(): void
    {
        $peralatan = Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Webcam Lama',
            'gedung'         => 'Gedung A',
            'stok'           => 4,
        ]);

        $this->actingAs($this->admin)
             ->put(route('admin.peralatan.update', $peralatan->id_peralatan), [
                 'nama_peralatan' => 'Webcam Baru',
                 'gedung'         => 'Gedung A',
                 'stok'           => 4,
                 'rusak'          => 1,
                 'perbaikan'      => 0,
             ])
             ->assertRedirect(route('admin.peralatan.index'));

        $this->assertDatabaseHas('peralatan', [
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Webcam Baru',
            'rusak'          => 1,
        ]);
    }
}
