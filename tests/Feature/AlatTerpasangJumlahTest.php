<?php

namespace Tests\Feature;

use App\Models\AlatTerpasang;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlatTerpasangJumlahTest extends TestCase
{
    use RefreshDatabase;

    private User $inventaris;
    private Peralatan $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventaris = User::create([
            'id_user'   => 'US001',
            'nama_user' => 'Inventaris Test',
            'nohp'      => '081234567890',
            'email'     => 'inventaris@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'inventaris',
            'status'    => 'active',
        ]);

        $this->router = Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Router Cisco Wi-Fi Pod',
            'gedung'         => 'Gedung A',
            'stok'           => 10,
        ]);
    }

    /** @test */
    public function bisa_tambah_beberapa_unit_sekaligus(): void
    {
        $this->actingAs($this->inventaris)
            ->post(route('inventaris.alat-terpasang.store'), [
                'id_peralatan'   => [$this->router->id_peralatan],
                'jumlah'         => [4],
                'gedung'         => 'Gedung A',
                'tanggal_pasang' => now()->format('Y-m-d'),
                'kondisi'        => 'baik',
            ])
            ->assertRedirect(route('inventaris.alat-terpasang.index'));

        $this->assertSame(4, AlatTerpasang::where('id_peralatan', $this->router->id_peralatan)->count());
    }

    /** @test */
    public function bisa_tambah_beberapa_alat_berbeda_sekaligus(): void
    {
        $mouse = Peralatan::create([
            'id_peralatan'   => 'PR-002',
            'nama_peralatan' => 'Mouse Logitech',
            'gedung'         => 'Gedung A',
            'stok'           => 5,
        ]);

        $this->actingAs($this->inventaris)
            ->post(route('inventaris.alat-terpasang.store'), [
                'id_peralatan'   => [$this->router->id_peralatan, $mouse->id_peralatan],
                'jumlah'         => [4, 2],
                'gedung'         => 'Gedung A',
                'tanggal_pasang' => now()->format('Y-m-d'),
                'kondisi'        => 'baik',
            ])
            ->assertRedirect(route('inventaris.alat-terpasang.index'));

        $this->assertSame(4, AlatTerpasang::where('id_peralatan', $this->router->id_peralatan)->count());
        $this->assertSame(2, AlatTerpasang::where('id_peralatan', $mouse->id_peralatan)->count());
    }

    /** @test */
    public function gagal_jika_jumlah_melebihi_stok_yang_bisa_dipasang(): void
    {
        $this->actingAs($this->inventaris)
            ->post(route('inventaris.alat-terpasang.store'), [
                'id_peralatan'   => [$this->router->id_peralatan],
                'jumlah'         => [11], // stok cuma 10
                'gedung'         => 'Gedung A',
                'tanggal_pasang' => now()->format('Y-m-d'),
                'kondisi'        => 'baik',
            ])
            ->assertSessionHasErrors('jumlah.0');

        $this->assertSame(0, AlatTerpasang::where('id_peralatan', $this->router->id_peralatan)->count());
    }

    /** @test */
    public function jumlah_yang_bisa_dipasang_memperhitungkan_yang_sudah_terpasang(): void
    {
        AlatTerpasang::create([
            'id_alat_terpasang' => 'AT-001',
            'id_peralatan'      => $this->router->id_peralatan,
            'nama_alat'         => $this->router->nama_peralatan,
            'gedung'            => 'Gedung A',
            'tanggal_pasang'    => now(),
            'kondisi'           => 'baik',
        ]);

        // Sudah 1 terpasang dari stok 10, jadi maksimal yang masih bisa ditambah adalah 9.
        $this->actingAs($this->inventaris)
            ->post(route('inventaris.alat-terpasang.store'), [
                'id_peralatan'   => [$this->router->id_peralatan],
                'jumlah'         => [10],
                'gedung'         => 'Gedung A',
                'tanggal_pasang' => now()->format('Y-m-d'),
                'kondisi'        => 'baik',
            ])
            ->assertSessionHasErrors('jumlah.0');
    }

    /** @test */
    public function gagal_jika_alat_yang_sama_dipilih_dua_kali(): void
    {
        $this->actingAs($this->inventaris)
            ->post(route('inventaris.alat-terpasang.store'), [
                'id_peralatan'   => [$this->router->id_peralatan, $this->router->id_peralatan],
                'jumlah'         => [2, 3],
                'gedung'         => 'Gedung A',
                'tanggal_pasang' => now()->format('Y-m-d'),
                'kondisi'        => 'baik',
            ])
            ->assertSessionHasErrors('id_peralatan.1');
    }
}
