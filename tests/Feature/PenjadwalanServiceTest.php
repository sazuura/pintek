<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\JadwalPeralatan;
use App\Models\Peralatan;
use App\Models\Penjadwalan;
use App\Models\User;
use App\Services\PenjadwalanService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PenjadwalanServiceTest extends TestCase
{
    use RefreshDatabase;

    private PenjadwalanService $service;
    private User $admin;
    private User $operator1;
    private User $operator2;
    private Peralatan $peralatan;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock WhatsAppService — kita tidak mau benar-benar kirim WA saat test
        $waMock = Mockery::mock(WhatsAppService::class);
        $waMock->shouldReceive('templateJadwalBaru')->andReturn('pesan test');
        $waMock->shouldReceive('kirim')->andReturn(true);
        $this->app->instance(WhatsAppService::class, $waMock);

        $this->service = $this->app->make(PenjadwalanService::class);

        // Buat data dasar
        $this->admin    = User::create($this->dataUser('US001', 'admin'));
        $this->operator1 = User::create($this->dataUser('US002', 'operator', '081111111111'));
        $this->operator2 = User::create($this->dataUser('US003', 'operator', '082222222222'));
        $this->peralatan = Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Laptop',
            'gedung'         => 'Gedung A',
            'stok'           => 5,
        ]);
    }

    /** @test */
    public function buat_jadwal_menyimpan_record_penjadwalan(): void
    {
        $this->service->buat(
            data:         $this->dataJadwal('2030-01-01'),
            operatorIds:  [$this->operator1->id_user],
            peralatanIds: [],
            jumlahArr:    [],
        );

        $this->assertDatabaseHas('penjadwalan', [
            'judul_kegiatan' => 'Rapat Test',
            'tanggal'        => '2030-01-01',
        ]);
    }

    /** @test */
    public function buat_jadwal_membuat_absensi_pending_untuk_semua_operator(): void
    {
        $this->service->buat(
            data:         $this->dataJadwal('2030-01-02'),
            operatorIds:  [$this->operator1->id_user, $this->operator2->id_user],
            peralatanIds: [],
            jumlahArr:    [],
        );

        $jadwal = Penjadwalan::first();

        $this->assertDatabaseHas('absensi', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'id_user'        => $this->operator1->id_user,
            'status'         => 'pending',
        ]);
        $this->assertDatabaseHas('absensi', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'id_user'        => $this->operator2->id_user,
            'status'         => 'pending',
        ]);
    }

    /** @test */
    public function buat_jadwal_menyimpan_peralatan_yang_dipilih(): void
    {
        $this->service->buat(
            data:         $this->dataJadwal('2030-01-03'),
            operatorIds:  [$this->operator1->id_user],
            peralatanIds: [$this->peralatan->id_peralatan],
            jumlahArr:    [2],
        );

        $jadwal = Penjadwalan::first();

        $this->assertDatabaseHas('jadwal_peralatan', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'id_peralatan'   => 'PR-001',
            'jumlah'         => 2,
            'status_pemasangan' => 'belum_dipasang',
        ]);
    }

    /** @test */
    public function buat_jadwal_gagal_jika_stok_tidak_cukup(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak mencukupi/');

        $this->service->buat(
            data:         $this->dataJadwal('2030-01-04'),
            operatorIds:  [$this->operator1->id_user],
            peralatanIds: [$this->peralatan->id_peralatan],
            jumlahArr:    [99], // minta 99, stok hanya 5
        );
    }

    /** @test */
    public function buat_jadwal_gagal_jika_operator_sudah_punya_jadwal_bentrok(): void
    {
        // Buat jadwal pertama
        $this->service->buat(
            data:         $this->dataJadwal('2030-01-05', '09:00', '11:00'),
            operatorIds:  [$this->operator1->id_user],
            peralatanIds: [],
            jumlahArr:    [],
        );

        // Coba buat jadwal kedua di waktu yang sama untuk operator yang sama
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/sudah memiliki jadwal/');

        $this->service->buat(
            data:         $this->dataJadwal('2030-01-05', '10:00', '12:00'), // overlap
            operatorIds:  [$this->operator1->id_user],
            peralatanIds: [],
            jumlahArr:    [],
        );
    }

    /** @test */
    public function id_penjadwalan_generate_sequential(): void
    {
        $this->service->buat(
            data: $this->dataJadwal('2030-02-01'),
            operatorIds: [$this->operator1->id_user],
            peralatanIds: [], jumlahArr: [],
        );
        $this->service->buat(
            data: $this->dataJadwal('2030-02-02'),
            operatorIds: [$this->operator2->id_user],
            peralatanIds: [], jumlahArr: [],
        );

        $ids = Penjadwalan::orderBy('id_penjadwalan')->pluck('id_penjadwalan')->toArray();
        $this->assertEquals(['PJ001', 'PJ002'], $ids);
    }

    /** @test */
    public function hapus_jadwal_menghapus_absensi_dan_peralatan_terkait(): void
    {
        $this->service->buat(
            data:         $this->dataJadwal('2030-03-01'),
            operatorIds:  [$this->operator1->id_user],
            peralatanIds: [$this->peralatan->id_peralatan],
            jumlahArr:    [1],
        );

        $jadwal = Penjadwalan::first();
        $this->service->hapus($jadwal);

        $this->assertDatabaseMissing('penjadwalan',    ['id_penjadwalan' => $jadwal->id_penjadwalan]);
        $this->assertDatabaseMissing('absensi',        ['id_penjadwalan' => $jadwal->id_penjadwalan]);
        $this->assertDatabaseMissing('jadwal_peralatan',['id_penjadwalan'=> $jadwal->id_penjadwalan]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function dataUser(string $id, string $role, string $nohp = '080000000000'): array
    {
        return [
            'id_user'   => $id,
            'nama_user' => "User $id",
            'nohp'      => $nohp,
            'email'     => "$id@test.com",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'status'    => 'active',
        ];
    }

    private function dataJadwal(string $tanggal, string $mulai = '09:00', string $selesai = '11:00'): array
    {
        return [
            'judul_kegiatan' => 'Rapat Test',
            'tanggal'        => $tanggal,
            'waktu_mulai'    => $mulai,
            'waktu_selesai'  => $selesai,
            'platform'       => 'Online (Zoom)',
            'keterangan'     => 'Test keterangan',
            'id_pemateri'    => null,
        ];
    }
}
