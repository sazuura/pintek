<?php

namespace Tests\Feature;

use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorPeralatanIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::create([
            'id_user' => 'US001', 'nama_user' => 'Operator Test', 'nohp' => '080000000001',
            'email' => 'operator@test.com', 'password' => bcrypt('password'), 'role' => 'operator', 'status' => 'active',
        ]);

        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Zebra Cam', 'gedung' => 'Gedung A', 'stok' => 10]); // tersedia
        Peralatan::create(['id_peralatan' => 'PR-B', 'nama_peralatan' => 'Amplifier', 'gedung' => 'Gedung A', 'stok' => 2]);  // kritis
        Peralatan::create(['id_peralatan' => 'PR-C', 'nama_peralatan' => 'Mixer', 'gedung' => 'Gedung A', 'stok' => 0]);      // tidak tersedia
    }

    /** @test */
    public function filter_status_tersedia_hanya_menampilkan_stok_di_atas_dua(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('operator.peralatan.index', ['status' => 'tersedia']))
            ->assertOk();

        $response->assertSee('Zebra Cam');
        $response->assertDontSee('Amplifier');
        $response->assertDontSee('Mixer');
    }

    /** @test */
    public function filter_status_kritis_hanya_menampilkan_stok_satu_sampai_dua(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('operator.peralatan.index', ['status' => 'kritis']))
            ->assertOk();

        $response->assertSee('Amplifier');
        $response->assertDontSee('Zebra Cam');
        $response->assertDontSee('Mixer');
    }

    /** @test */
    public function filter_status_tidak_tersedia_hanya_menampilkan_stok_nol_atau_kurang(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('operator.peralatan.index', ['status' => 'tidak_tersedia']))
            ->assertOk();

        $response->assertSee('Mixer');
        $response->assertDontSee('Zebra Cam');
        $response->assertDontSee('Amplifier');
    }

    /** @test */
    public function urutkan_nama_asc_mengurutkan_alfabetis(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('operator.peralatan.index', ['urutkan' => 'nama_asc']))
            ->assertOk();

        $posisiAmplifier = strpos($response->getContent(), 'Amplifier');
        $posisiMixer     = strpos($response->getContent(), 'Mixer');
        $posisiZebra     = strpos($response->getContent(), 'Zebra Cam');

        $this->assertTrue($posisiAmplifier < $posisiMixer);
        $this->assertTrue($posisiMixer < $posisiZebra);
    }

    /** @test */
    public function urutkan_stok_desc_mengurutkan_stok_terbanyak_dulu(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('operator.peralatan.index', ['urutkan' => 'stok_desc']))
            ->assertOk();

        $posisiZebra = strpos($response->getContent(), 'Zebra Cam');   // stok 10
        $posisiAmp   = strpos($response->getContent(), 'Amplifier');   // stok 2
        $posisiMixer = strpos($response->getContent(), 'Mixer');       // stok 0

        $this->assertTrue($posisiZebra < $posisiAmp);
        $this->assertTrue($posisiAmp < $posisiMixer);
    }

    /** @test */
    public function urutkan_gedung_tetap_bisa_dipilih_eksplisit(): void
    {
        $this->actingAs($this->operator)
            ->get(route('operator.peralatan.index', ['urutkan' => 'gedung']))
            ->assertOk()
            ->assertSee('Zebra Cam')
            ->assertSee('Amplifier')
            ->assertSee('Mixer');
    }
}
