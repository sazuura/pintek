<?php

namespace Tests\Feature;

use App\Models\AlatTerpasang;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AlatTerpasangRingkasanTest extends TestCase
{
    use RefreshDatabase;

    private User $inventaris;

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
    }

    #[Test]
    public function ringkasan_menghitung_jumlah_terpasang_dan_tersimpan_per_alat(): void
    {
        $router = Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Router Cisco Wi-Fi Pod',
            'gedung'         => 'Gedung A',
            'stok'           => 10,
        ]);

        foreach (range(1, 4) as $i) {
            AlatTerpasang::create([
                'id_alat_terpasang' => "AT-00{$i}",
                'id_peralatan'      => $router->id_peralatan,
                'nama_alat'         => $router->nama_peralatan,
                'gedung'            => 'Gedung A',
                'tanggal_pasang'    => now(),
                'kondisi'           => 'baik',
            ]);
        }

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.alat-terpasang.index'))
            ->assertOk();

        $response->assertSee('Router Cisco Wi-Fi Pod');
        $response->assertSeeInOrder(['Router Cisco Wi-Fi Pod', '4', '6', '10']);
    }

    #[Test]
    public function ringkasan_tidak_muncul_kalau_belum_ada_alat_terpasang(): void
    {
        Peralatan::create([
            'id_peralatan'   => 'PR-002',
            'nama_peralatan' => 'Mouse Cadangan',
            'gedung'         => 'Gedung A',
            'stok'           => 5,
        ]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.alat-terpasang.index'))
            ->assertOk();

        $response->assertDontSee('Ringkasan Penempatan per Alat');
    }
}
