<?php

namespace Tests\Feature;

use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PeralatanControllerTest extends TestCase
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
    public function inventaris_bisa_tambah_peralatan(): void
    {
        $this->actingAs($this->inventaris)
             ->post(route('inventaris.peralatan.store'), [
                 'kode_barang'    => 'GU/LAP/2024/001',
                 'nama_peralatan' => 'Laptop Zoom',
                 'gedung'         => 'Gedung Utama',
                 'lokasi_detail'  => 'Rak A Lt.2',
                 'stok'           => 5,
             ])
             ->assertRedirect(route('inventaris.peralatan.index'));

        $this->assertDatabaseHas('peralatan', [
            'kode_barang'    => 'GU/LAP/2024/001',
            'nama_peralatan' => 'Laptop Zoom',
            'gedung'         => 'Gedung Utama',
            'stok'           => 5,
        ]);
    }

    #[Test]
    public function id_peralatan_generate_otomatis(): void
    {
        $this->actingAs($this->inventaris)
             ->post(route('inventaris.peralatan.store'), [
                 'nama_peralatan' => 'Mikrofon',
                 'gedung'         => 'Gedung A',
                 'stok'           => 3,
             ]);

        $this->assertDatabaseHas('peralatan', ['id_peralatan' => 'PR-001']);
    }

    #[Test]
    public function kode_barang_harus_unik(): void
    {
        Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'kode_barang'    => 'GA/MIC/2024/001',
            'nama_peralatan' => 'Mikrofon',
            'gedung'         => 'Gedung A',
            'stok'           => 3,
        ]);

        $this->actingAs($this->inventaris)
             ->post(route('inventaris.peralatan.store'), [
                 'kode_barang'    => 'GA/MIC/2024/001',
                 'nama_peralatan' => 'Mikrofon Lain',
                 'gedung'         => 'Gedung A',
                 'stok'           => 2,
             ])
             ->assertSessionHasErrors('kode_barang');
    }

    #[Test]
    public function update_gagal_jika_rusak_melebihi_stok(): void
    {
        $peralatan = Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Speaker',
            'gedung'         => 'Gedung B',
            'stok'           => 3,
        ]);

        $this->actingAs($this->inventaris)
             ->put(route('inventaris.peralatan.update', $peralatan->id_peralatan), [
                 'nama_peralatan' => 'Speaker',
                 'gedung'         => 'Gedung B',
                 'stok'           => 3,
                 'rusak'          => 4,
             ])
             ->assertSessionHasErrors('rusak');
    }

    #[Test]
    public function inventaris_bisa_update_peralatan(): void
    {
        $peralatan = Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Webcam Lama',
            'gedung'         => 'Gedung A',
            'stok'           => 4,
        ]);

        $this->actingAs($this->inventaris)
             ->put(route('inventaris.peralatan.update', $peralatan->id_peralatan), [
                 'nama_peralatan' => 'Webcam Baru',
                 'gedung'         => 'Gedung A',
                 'stok'           => 4,
                 'rusak'          => 1,
             ])
             ->assertRedirect(route('inventaris.peralatan.index'));

        $this->assertDatabaseHas('peralatan', [
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Webcam Baru',
            'rusak'          => 1,
        ]);
    }

    #[Test]
    public function index_bisa_difilter_berdasarkan_gedung_dan_status(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 10]);
        Peralatan::create(['id_peralatan' => 'PR-B', 'nama_peralatan' => 'Layar', 'gedung' => 'Gedung B', 'stok' => 0]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.peralatan.index', ['gedung' => 'Gedung A']))
            ->assertOk();
        $response->assertSee('Proyektor');
        $response->assertDontSee('Layar');

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.peralatan.index', ['status' => 'tidak_tersedia']))
            ->assertOk();
        $response->assertSee('Layar');
        $response->assertDontSee('Proyektor');
    }

    #[Test]
    public function index_bisa_diurutkan_berdasarkan_nama_dan_stok(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Zebra Cam', 'gedung' => 'Gedung A', 'stok' => 1]);
        Peralatan::create(['id_peralatan' => 'PR-B', 'nama_peralatan' => 'Amplifier', 'gedung' => 'Gedung A', 'stok' => 10]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.peralatan.index', ['urutkan' => 'nama_asc']))
            ->assertOk();
        $this->assertTrue(
            strpos($response->getContent(), 'Amplifier') < strpos($response->getContent(), 'Zebra Cam')
        );

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.peralatan.index', ['urutkan' => 'stok_desc']))
            ->assertOk();
        $this->assertTrue(
            strpos($response->getContent(), 'Amplifier') < strpos($response->getContent(), 'Zebra Cam')
        );
    }

    #[Test]
    public function index_bisa_difilter_berdasarkan_kondisi(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Proyektor Mulus', 'gedung' => 'Gedung A', 'stok' => 10, 'rusak' => 0]);
        Peralatan::create(['id_peralatan' => 'PR-B', 'nama_peralatan' => 'Layar Sobek', 'gedung' => 'Gedung A', 'stok' => 5, 'rusak' => 2]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.peralatan.index', ['kondisi' => 'baik']))
            ->assertOk();
        $response->assertSee('Proyektor Mulus');
        $response->assertDontSee('Layar Sobek');

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.peralatan.index', ['kondisi' => 'rusak']))
            ->assertOk();
        $response->assertSee('Layar Sobek');
        $response->assertDontSee('Proyektor Mulus');
    }
}
