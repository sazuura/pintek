<?php

namespace Tests\Feature;

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

    protected function setUp(): void
    {
        parent::setUp();

        // Mock WhatsAppService - kita tidak mau benar-benar kirim WA saat test
        $waMock = Mockery::mock(WhatsAppService::class);
        $waMock->shouldReceive('templateJadwalBaru')->andReturn('pesan test');
        $waMock->shouldReceive('templateJadwalDibatalkan')->andReturn('pesan test');
        $waMock->shouldReceive('kirim')->andReturn(true);
        $this->app->instance(WhatsAppService::class, $waMock);

        $this->service = $this->app->make(PenjadwalanService::class);

        // Buat data dasar
        $this->admin     = User::create($this->dataUser('US001', 'admin'));
        $this->operator1 = User::create($this->dataUser('US002', 'operator', '081111111111'));
        $this->operator2 = User::create($this->dataUser('US003', 'operator', '082222222222'));
    }

    /** @test */
    public function buat_jadwal_menyimpan_record_penjadwalan(): void
    {
        $this->service->buat(
            data:        $this->dataJadwal('2030-01-01'),
            operatorIds: [$this->operator1->id_user],
        );

        $this->assertDatabaseHas('penjadwalan', [
            'judul_kegiatan' => 'Rapat Test',
            'tanggal'        => '2030-01-01',
        ]);
    }

    /** @test */
    public function buat_jadwal_menugaskan_semua_operator_yang_dipilih(): void
    {
        $this->service->buat(
            data:        $this->dataJadwal('2030-01-02'),
            operatorIds: [$this->operator1->id_user, $this->operator2->id_user],
        );

        $jadwal = Penjadwalan::first();

        $this->assertDatabaseHas('jadwal_operator', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'id_user'        => $this->operator1->id_user,
        ]);
        $this->assertDatabaseHas('jadwal_operator', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'id_user'        => $this->operator2->id_user,
        ]);
    }

    /** @test */
    public function buat_jadwal_gagal_jika_operator_sudah_punya_jadwal_bentrok(): void
    {
        // Buat jadwal pertama
        $this->service->buat(
            data:        $this->dataJadwal('2030-01-05', '09:00', '11:00'),
            operatorIds: [$this->operator1->id_user],
        );

        // Coba buat jadwal kedua di waktu yang sama untuk operator yang sama
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/sudah memiliki jadwal/');

        $this->service->buat(
            data:        $this->dataJadwal('2030-01-05', '10:00', '12:00'), // overlap
            operatorIds: [$this->operator1->id_user],
        );
    }

    /** @test */
    public function id_penjadwalan_generate_sequential(): void
    {
        $this->service->buat(
            data: $this->dataJadwal('2030-02-01'),
            operatorIds: [$this->operator1->id_user],
        );
        $this->service->buat(
            data: $this->dataJadwal('2030-02-02'),
            operatorIds: [$this->operator2->id_user],
        );

        $ids = Penjadwalan::orderBy('id_penjadwalan')->pluck('id_penjadwalan')->toArray();
        $this->assertEquals(['JDW-001', 'JDW-002'], $ids);
    }

    /** @test */
    public function hapus_jadwal_menghapus_penugasan_operator_terkait(): void
    {
        $this->service->buat(
            data:        $this->dataJadwal('2030-03-01'),
            operatorIds: [$this->operator1->id_user],
        );

        $jadwal = Penjadwalan::first();
        $this->service->hapus($jadwal);

        $this->assertDatabaseMissing('penjadwalan', ['id_penjadwalan' => $jadwal->id_penjadwalan]);
        $this->assertDatabaseMissing('jadwal_operator', ['id_penjadwalan' => $jadwal->id_penjadwalan]);
    }

    /** @test */
    public function batalkan_jadwal_mengubah_status_dan_menyimpan_alasan(): void
    {
        $this->service->buat(
            data:        $this->dataJadwal('2030-04-01'),
            operatorIds: [$this->operator1->id_user],
        );

        $jadwal = Penjadwalan::first();
        $this->service->batalkan($jadwal, 'Kuorum tidak terpenuhi');

        $this->assertDatabaseHas('penjadwalan', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'status'         => 'dibatalkan',
            'alasan_batal'   => 'Kuorum tidak terpenuhi',
        ]);
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
        ];
    }
}
