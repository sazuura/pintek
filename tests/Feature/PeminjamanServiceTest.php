<?php

namespace Tests\Feature;

use App\Helpers\IdGenerator;
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
    private User $inventaris;
    private Peralatan $alat;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock WA - test tidak kirim WA sungguhan
        $waMock = Mockery::mock(WhatsAppService::class);
        $waMock->shouldReceive('templatePeminjamanBaru')->andReturn('pesan test');
        $waMock->shouldReceive('kirim')->andReturn(true);
        $this->app->instance(WhatsAppService::class, $waMock);

        $this->service = $this->app->make(PeminjamanService::class);

        $this->operator   = $this->buatUser('US001', 'operator');
        $this->inventaris = $this->buatUser('US002', 'inventaris', '081111111111');

        $this->alat = Peralatan::create([
            'id_peralatan' => 'PR-001', 'nama_peralatan' => 'Laptop',
            'gedung' => 'Gedung A', 'stok' => 5,
        ]);
    }

    /** @test */
    public function ajukan_menyimpan_header_dan_item_peminjaman(): void
    {
        $this->service->ajukan(
            header:       $this->dataHeader(),
            peralatanIds: [$this->alat->id_peralatan],
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
            peralatanIds: [$this->alat->id_peralatan],
            jumlahArr:    [99], // lebih dari stok
        );
    }

    /** @test */
    public function setujui_mengubah_status_menjadi_disetujui(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);

        $this->service->setujui($peminjaman, $this->inventaris, 'OK disetujui');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman'      => $peminjaman->id_peminjaman,
            'status'             => 'disetujui',
            'catatan_inventaris' => 'OK disetujui',
        ]);
    }

    /** @test */
    public function tolak_mengubah_status_menjadi_ditolak(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);

        $this->service->tolak($peminjaman, $this->inventaris, 'Stok habis.');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman'      => $peminjaman->id_peminjaman,
            'status'             => 'ditolak',
            'catatan_inventaris' => 'Stok habis.',
        ]);
    }

    /** @test */
    public function konfirmasi_kembali_mengisi_tanggal_kembali_aktual(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'disetujui');

        $this->service->konfirmasiKembali($peminjaman, $this->inventaris);

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'status'        => 'dikembalikan',
        ]);
        $this->assertNotNull(Peminjaman::find($peminjaman->id_peminjaman)->tanggal_kembali_aktual);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buatUser(string $id, string $role, string $nohp = '080000000000'): User
    {
        return User::create([
            'id_user'   => $id,
            'nama_user' => "User $id",
            'nohp'      => $nohp,
            'email'     => "$id@test.com",
            'password'  => bcrypt('password'),
            'role'      => $role,
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
        $p = Peminjaman::create(array_merge($this->dataHeader(), [
            'id_peminjaman' => IdGenerator::next(Peminjaman::class, 'id_peminjaman', 'PMJ-'),
            'status'        => $status,
        ]));

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
