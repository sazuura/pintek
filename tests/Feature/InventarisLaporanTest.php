<?php

namespace Tests\Feature;

use App\Models\AlatTerpasang;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    /** @test */
    public function halaman_laporan_bisa_diakses_dan_menampilkan_kedua_tab(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Router Cisco', 'gedung' => 'Gedung A', 'stok' => 10, 'rusak' => 2]);
        AlatTerpasang::create([
            'id_alat_terpasang' => 'AT-001',
            'id_peralatan'      => 'PR-A',
            'nama_alat'         => 'Router Cisco',
            'gedung'            => 'Gedung A',
            'tanggal_pasang'    => now(),
            'kondisi'           => 'baik',
        ]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.index'))
            ->assertOk();

        $response->assertSee('Router Cisco');
        $response->assertSee('Stok Peralatan');
        $response->assertSee('Alat Terpasang');
    }

    /** @test */
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

    /** @test */
    public function terpasang_bisa_difilter_berdasarkan_gedung(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Router A', 'gedung' => 'Gedung A', 'stok' => 5]);
        Peralatan::create(['id_peralatan' => 'PR-B', 'nama_peralatan' => 'Router B', 'gedung' => 'Gedung B', 'stok' => 5]);
        AlatTerpasang::create([
            'id_alat_terpasang' => 'AT-001', 'id_peralatan' => 'PR-A', 'nama_alat' => 'Router A',
            'gedung' => 'Gedung A', 'tanggal_pasang' => now(), 'kondisi' => 'baik',
        ]);
        AlatTerpasang::create([
            'id_alat_terpasang' => 'AT-002', 'id_peralatan' => 'PR-B', 'nama_alat' => 'Router B',
            'gedung' => 'Gedung B', 'tanggal_pasang' => now(), 'kondisi' => 'baik',
        ]);

        $response = $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.index', ['tab' => 'panel-terpasang', 'gedung' => 'Gedung A']))
            ->assertOk();

        $response->assertSee('Router A');
        $response->assertDontSee('Router B');
    }

    /** @test */
    public function export_pdf_stok_berhasil(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Router Cisco', 'gedung' => 'Gedung A', 'stok' => 10]);

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.exportPdf', ['tab' => 'panel-stok']))
            ->assertOk()
            ->assertSee('Router Cisco');
    }

    /** @test */
    public function export_pdf_terpasang_berhasil(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Router Cisco', 'gedung' => 'Gedung A', 'stok' => 10]);
        AlatTerpasang::create([
            'id_alat_terpasang' => 'AT-001', 'id_peralatan' => 'PR-A', 'nama_alat' => 'Router Cisco',
            'gedung' => 'Gedung A', 'tanggal_pasang' => now(), 'kondisi' => 'baik',
        ]);

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.exportPdf', ['tab' => 'panel-terpasang']))
            ->assertOk()
            ->assertSee('Router Cisco');
    }

    /** @test */
    public function export_excel_stok_dan_terpasang_berhasil(): void
    {
        Peralatan::create(['id_peralatan' => 'PR-A', 'nama_peralatan' => 'Router Cisco', 'gedung' => 'Gedung A', 'stok' => 10]);

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.exportExcel', ['tab' => 'panel-stok']))
            ->assertOk();

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.laporan.exportExcel', ['tab' => 'panel-terpasang']))
            ->assertOk();
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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
