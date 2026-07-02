<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Peralatan;
use App\Models\User;
use App\Services\PeminjamanService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PeminjamanServiceTest extends TestCase
{
    use RefreshDatabase;

    private PeminjamanService $service;
    private User $operator;
    private User $invGedungA;
    private User $invGedungB;
    private Peralatan $alatGedungA;
    private Peralatan $alatGedungB;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock WA — test tidak kirim WA sungguhan
        $waMock = Mockery::mock(WhatsAppService::class);
        $waMock->shouldReceive('templatePeminjamanBaru')->andReturn('pesan test');
        $waMock->shouldReceive('kirim')->andReturn(true);
        $this->app->instance(WhatsAppService::class, $waMock);

        $this->service = $this->app->make(PeminjamanService::class);

        // Users
        $this->operator   = $this->buatUser('US001', 'operator', null);
        $this->invGedungA = $this->buatUser('US002', 'inventaris', 'Gedung A', '081111111111');
        $this->invGedungB = $this->buatUser('US003', 'inventaris', 'Gedung B', '082222222222');

        // Peralatan di dua gedung berbeda
        $this->alatGedungA = Peralatan::create([
            'id_peralatan' => 'PR-001', 'nama_peralatan' => 'Laptop',
            'gedung' => 'Gedung A', 'stok' => 5,
        ]);
        $this->alatGedungB = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor',
            'gedung' => 'Gedung B', 'stok' => 3,
        ]);
    }

    /** @test */
    public function ajukan_menyimpan_header_dan_item_peminjaman(): void
    {
        $this->service->ajukan(
            header:       $this->dataHeader(),
            peralatanIds: [$this->alatGedungA->id_peralatan],
            jumlahArr:    [2],
        );

        $this->assertDatabaseHas('peminjaman', [
            'id_user'   => $this->operator->id_user,
            'status'    => 'diajukan',
            'keperluan' => 'Rapat dinas',
        ]);
        $this->assertDatabaseHas('peminjaman_item', [
            'id_peralatan' => 'PR-001',
            'jumlah'       => 2,
        ]);
    }

    /** @test */
    public function ajukan_gagal_jika_stok_tidak_cukup(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak mencukupi/');

        $this->service->ajukan(
            header:       $this->dataHeader(),
            peralatanIds: [$this->alatGedungA->id_peralatan],
            jumlahArr:    [99], // lebih dari stok
        );
    }

    /** @test */
    public function setujui_mengubah_status_menjadi_disetujui(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alatGedungA->id_peralatan]);

        $this->service->setujui($peminjaman, $this->invGedungA, 'OK disetujui');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman'      => $peminjaman->id_peminjaman,
            'status'             => 'disetujui',
            'catatan_inventaris' => 'OK disetujui',
        ]);
    }

    /** @test */
    public function tolak_mengubah_status_menjadi_ditolak(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alatGedungA->id_peralatan]);

        $this->service->tolak($peminjaman, $this->invGedungA, 'Stok habis.');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman'      => $peminjaman->id_peminjaman,
            'status'             => 'ditolak',
            'catatan_inventaris' => 'Stok habis.',
        ]);
    }

    /** @test */
    public function konfirmasi_kembali_mengisi_tanggal_kembali_aktual(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alatGedungA->id_peralatan], 'disetujui');

        $this->service->konfirmasiKembali($peminjaman, $this->invGedungA);

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'status'        => 'dikembalikan',
        ]);
        $this->assertNotNull(Peminjaman::find($peminjaman->id_peminjaman)->tanggal_kembali_aktual);
    }

    /** @test */
    public function inventaris_gedung_a_tidak_bisa_setujui_peminjaman_gedung_b(): void
    {
        // Peminjaman hanya berisi alat dari Gedung B
        $peminjaman = $this->buatPeminjaman([$this->alatGedungB->id_peralatan]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak berwenang/');

        // inventaris Gedung A coba setujui — harus ditolak
        $this->service->setujui($peminjaman, $this->invGedungA);
    }

    /** @test */
    public function inventaris_berwenang_jika_ada_item_dari_gedungnya(): void
    {
        // Peminjaman lintas 2 gedung
        $peminjaman = $this->buatPeminjaman([
            $this->alatGedungA->id_peralatan,
            $this->alatGedungB->id_peralatan,
        ]);

        // Inventaris Gedung A berwenang karena ada item dari Gedung A
        $this->service->setujui($peminjaman, $this->invGedungA);

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'status'        => 'disetujui',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buatUser(string $id, string $role, ?string $gedung, string $nohp = '080000000000'): User
    {
        return User::create([
            'id_user'   => $id,
            'nama_user' => "User $id",
            'nohp'      => $nohp,
            'email'     => "$id@test.com",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'gedung'    => $gedung,
            'status'    => 'active',
        ]);
    }

    private function dataHeader(): array
    {
        return [
            'id_user'                 => $this->operator->id_user,
            'tanggal_pinjam'          => now()->addDay()->format('Y-m-d'),
            'tanggal_kembali_rencana' => now()->addDays(3)->format('Y-m-d'),
            'keperluan'               => 'Rapat dinas',
            'status'                  => 'diajukan',
        ];
    }

    private function buatPeminjaman(array $alatIds, string $status = 'diajukan'): Peminjaman
    {
        $p = Peminjaman::create(array_merge($this->dataHeader(), ['status' => $status]));

        foreach ($alatIds as $id) {
            PeminjamanItem::create([
                'id_peminjaman' => $p->id_peminjaman,
                'id_peralatan'  => $id,
                'jumlah'        => 1,
            ]);
        }

        return $p;
    }
}
