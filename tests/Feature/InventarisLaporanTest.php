<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InventarisLaporanTest extends TestCase
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
    public function halaman_laporan_bisa_diakses_dan_menampilkan_tab(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Router Cisco', 'gedung' => 'Gedung A', 'stok' => 10, 'rusak' => 2]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.index'))
            ->assertOk();

        $response->assertSee('Router Cisco');
        $response->assertSee('Stok Peralatan');
    }

    #[Test]
    public function stok_bisa_difilter_berdasarkan_kondisi(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Proyektor Mulus', 'gedung' => 'Gedung A', 'stok' => 10, 'rusak' => 0]);
        Peralatan::create(['id_peralatan' => 'PR-B', 'nama_peralatan' => 'Layar Sobek', 'gedung' => 'Gedung A', 'stok' => 5, 'rusak' => 2]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.index', ['tab' => 'panel-stok', 'kondisi' => 'rusak']))
            ->assertOk();

        $response->assertSee('Layar Sobek');
        $response->assertDontSee('Proyektor Mulus');
    }


    #[Test]
    public function export_pdf_stok_berhasil(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Router Cisco', 'gedung' => 'Gedung A', 'stok' => 10]);

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.exportPdf', ['tab' => 'panel-stok']))
            ->assertOk()
            ->assertSee('Router Cisco');
    }


    #[Test]
    public function export_excel_stok_berhasil(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Router Cisco', 'gedung' => 'Gedung A', 'stok' => 10]);

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.exportExcel', ['tab' => 'panel-stok']))
            ->assertOk();
    }

    #[Test]
    public function riwayat_peminjaman_bisa_difilter_berdasarkan_rentang_tanggal(): void
    {
        $peralatan = Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Kamera DSLR', 'gedung' => 'Gedung A', 'stok' => 5]);

        $lama = Peminjaman::create([
            'id_peminjaman'           => 'PMJ-001',
            'id_user'                 => $this->inventaris->id_user,
            'tanggal_pinjam'          => '2026-01-10',
            'tanggal_kembali_rencana' => '2026-01-12',
            'keperluan'               => 'Rapat lama',
            'status'                  => 'disetujui',
        ]);
        PeminjamanItem::create(['id_peminjaman' => $lama->id_peminjaman, 'id_peralatan' => $peralatan->id_peralatan, 'jumlah' => 1]);

        $baru = Peminjaman::create([
            'id_peminjaman'           => 'PMJ-002',
            'id_user'                 => $this->inventaris->id_user,
            'tanggal_pinjam'          => '2026-07-10',
            'tanggal_kembali_rencana' => '2026-07-12',
            'keperluan'               => 'Rapat baru',
            'status'                  => 'disetujui',
        ]);
        PeminjamanItem::create(['id_peminjaman' => $baru->id_peminjaman, 'id_peralatan' => $peralatan->id_peralatan, 'jumlah' => 1]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.index', [
                'tab'   => 'panel-peminjaman',
                'start' => '2026-07-01',
                'end'   => '2026-07-31',
            ]))
            ->assertOk();

        $response->assertSee('Rapat baru');
        $response->assertDontSee('Rapat lama');
    }

    #[Test]
    public function export_pdf_dan_excel_riwayat_peminjaman_berhasil(): void
    {
        $peralatan = Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Kamera DSLR', 'gedung' => 'Gedung A', 'stok' => 5]);
        $p = Peminjaman::create([
            'id_peminjaman'           => 'PMJ-001',
            'id_user'                 => $this->inventaris->id_user,
            'tanggal_pinjam'          => now()->format('Y-m-d'),
            'tanggal_kembali_rencana' => now()->addDay()->format('Y-m-d'),
            'keperluan'               => 'Rapat dinas',
            'status'                  => 'disetujui',
        ]);
        PeminjamanItem::create(['id_peminjaman' => $p->id_peminjaman, 'id_peralatan' => $peralatan->id_peralatan, 'jumlah' => 1]);

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.exportPdf', ['tab' => 'panel-peminjaman']))
            ->assertOk()
            ->assertSee('Kamera DSLR');

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.exportExcel', ['tab' => 'panel-peminjaman']))
            ->assertOk();
    }

    #[Test]
    public function operator_tidak_bisa_akses_laporan_inventaris(): void
    {
        $operator = User::create([
            'id_user'   => 'US002',
            'nama_user' => 'Operator Test',
            'nohp'      => '081234567891',
            'email'     => 'operator@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'operator',
            'status'    => 'active',
        ]);

        $this->actingAs($operator)
            ->get(route('inventaris.laporan.index'))
            ->assertForbidden();
    }
}
