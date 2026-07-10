<?php

namespace Tests\Feature;

use App\Models\Penjadwalan;
use App\Models\User;
use App\Services\PenjadwalanService;
use App\Services\WhatsAppService;
use App\Services\ZoomService;
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
    private $zoomMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock WhatsAppService - kita tidak mau benar-benar kirim WA saat test
        $waMock = Mockery::mock(WhatsAppService::class);
        $waMock->shouldReceive('templateJadwalBaru')->andReturn('pesan test');
        $waMock->shouldReceive('templateJadwalDiubah')->andReturn('pesan test');
        $waMock->shouldReceive('templateJadwalDibatalkan')->andReturn('pesan test');
        $waMock->shouldReceive('kirim')->andReturn(true);
        $this->app->instance(WhatsAppService::class, $waMock);

        // Mock ZoomService - default tidak pernah dipanggil, tiap test yang butuh
        // link_otomatis akan set expectation-nya sendiri.
        $this->zoomMock = Mockery::mock(ZoomService::class);
        $this->app->instance(ZoomService::class, $this->zoomMock);

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

    /** @test */
    public function buat_jadwal_dengan_link_otomatis_sukses(): void
    {
        $this->zoomMock->shouldReceive('buatMeeting')
            ->once()
            ->with('akun_1', Mockery::any())
            ->andReturn(['meeting_id' => 'MTG123', 'join_url' => 'https://zoom.us/j/123', 'password' => 'ab12cd']);

        $data = $this->dataJadwal('2030-05-01');
        $data['link_otomatis'] = true;

        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator1->id_user]);

        $this->assertDatabaseHas('penjadwalan', [
            'id_penjadwalan'  => $jadwal->id_penjadwalan,
            'link_otomatis'   => true,
            'zoom_meeting_id' => 'MTG123',
            'zoom_password'   => 'ab12cd',
            'zoom_account'    => 'akun_1',
            'keterangan'      => 'https://zoom.us/j/123',
        ]);
        $this->assertNull($this->service->peringatanZoom());
    }

    /** @test */
    public function buat_jadwal_dengan_link_otomatis_gagal_tetap_tersimpan(): void
    {
        $this->zoomMock->shouldReceive('buatMeeting')->once()->andReturn(null);

        $data = $this->dataJadwal('2030-05-02');
        $data['link_otomatis'] = true;

        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator1->id_user]);

        $this->assertDatabaseHas('penjadwalan', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'link_otomatis'  => false,
        ]);
        $this->assertNotNull($this->service->peringatanZoom());
        $this->assertStringContainsString('gagal dibuat', $this->service->peringatanZoom());
    }

    /** @test */
    public function buat_jadwal_link_otomatis_kedua_akun_bentrok_tidak_generate(): void
    {
        // Isi akun_1 dan akun_2 dengan jadwal otomatis di jam yang sama.
        Penjadwalan::create(array_merge($this->dataJadwal('2030-05-03', '09:00', '10:00'), [
            'id_penjadwalan' => 'JDW-901', 'link_otomatis' => true, 'zoom_account' => 'akun_1', 'zoom_meeting_id' => 'A1',
        ]));
        Penjadwalan::create(array_merge($this->dataJadwal('2030-05-03', '09:00', '10:00'), [
            'id_penjadwalan' => 'JDW-902', 'link_otomatis' => true, 'zoom_account' => 'akun_2', 'zoom_meeting_id' => 'A2',
        ]));

        $this->zoomMock->shouldReceive('buatMeeting')->never();

        $data = $this->dataJadwal('2030-05-03', '09:30', '10:30'); // overlap dgn keduanya
        $data['link_otomatis'] = true;

        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator2->id_user]);

        $this->assertDatabaseHas('penjadwalan', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'link_otomatis'  => false,
        ]);
        $this->assertStringContainsString('sudah terpakai', $this->service->peringatanZoom());
    }

    /** @test */
    public function buat_jadwal_dengan_akun_zoom_dipilih_manual(): void
    {
        $this->zoomMock->shouldReceive('buatMeeting')
            ->once()
            ->with('akun_2', Mockery::any())
            ->andReturn(['meeting_id' => 'MTG222', 'join_url' => 'https://zoom.us/j/222', 'password' => 'zz22']);

        $data = $this->dataJadwal('2030-05-06');
        $data['link_otomatis'] = true;

        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator1->id_user], akunPilihan: 'akun_2');

        $this->assertDatabaseHas('penjadwalan', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'zoom_account'   => 'akun_2',
        ]);
    }

    /** @test */
    public function buat_jadwal_akun_zoom_pilihan_manual_bentrok_tidak_fallback_ke_akun_lain(): void
    {
        // Akun 2 sudah kepakai di jam ini, akun 1 masih kosong - tapi karena admin
        // MEMAKSA pilih akun 2 secara manual, sistem TIDAK BOLEH otomatis pindah ke akun 1.
        Penjadwalan::create(array_merge($this->dataJadwal('2030-05-07', '09:00', '10:00'), [
            'id_penjadwalan' => 'JDW-903', 'link_otomatis' => true, 'zoom_account' => 'akun_2', 'zoom_meeting_id' => 'A3',
        ]));

        $this->zoomMock->shouldReceive('buatMeeting')->never();

        $data = $this->dataJadwal('2030-05-07', '09:30', '10:30');
        $data['link_otomatis'] = true;

        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator1->id_user], akunPilihan: 'akun_2');

        $this->assertDatabaseHas('penjadwalan', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'link_otomatis'  => false,
        ]);
        $this->assertStringContainsString('Akun 2 sudah terpakai', $this->service->peringatanZoom());
    }

    /** @test */
    public function ubah_jadwal_pindah_akun_zoom_manual_membuat_meeting_baru(): void
    {
        $this->zoomMock->shouldReceive('buatMeeting')
            ->once()
            ->with('akun_1', Mockery::any())
            ->andReturn(['meeting_id' => 'MTG-LAMA', 'join_url' => 'https://zoom.us/j/lama', 'password' => 'lama1']);

        $data = $this->dataJadwal('2030-05-08');
        $data['link_otomatis'] = true;
        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator1->id_user], akunPilihan: 'akun_1');

        $this->zoomMock->shouldReceive('hapusMeeting')->once()->with('akun_1', 'MTG-LAMA')->andReturn(true);
        $this->zoomMock->shouldReceive('buatMeeting')
            ->once()
            ->with('akun_2', Mockery::any())
            ->andReturn(['meeting_id' => 'MTG-BARU', 'join_url' => 'https://zoom.us/j/baru', 'password' => 'baru2']);

        $dataUbah = $this->dataJadwal('2030-05-08');
        $dataUbah['link_otomatis'] = true;
        $jadwalBaru = $this->service->ubah($jadwal->fresh(), $dataUbah, [$this->operator1->id_user], akunPilihan: 'akun_2');

        $this->assertSame('akun_2', $jadwalBaru->zoom_account);
        $this->assertSame('MTG-BARU', $jadwalBaru->zoom_meeting_id);
    }

    /** @test */
    public function ubah_jadwal_update_meeting_zoom_saat_waktu_berubah(): void
    {
        $this->zoomMock->shouldReceive('buatMeeting')
            ->once()
            ->andReturn(['meeting_id' => 'MTG555', 'join_url' => 'https://zoom.us/j/555', 'password' => 'xy99']);

        $data = $this->dataJadwal('2030-05-04', '09:00', '10:00');
        $data['link_otomatis'] = true;
        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator1->id_user]);

        $this->zoomMock->shouldReceive('updateMeeting')
            ->once()
            ->with('akun_1', 'MTG555', Mockery::any())
            ->andReturn(true);

        $dataUbah = $this->dataJadwal('2030-05-04', '11:00', '12:00'); // waktu berubah
        $dataUbah['link_otomatis'] = true;
        $this->service->ubah($jadwal->fresh(), $dataUbah, [$this->operator1->id_user]);
    }

    /** @test */
    public function hapus_jadwal_menghapus_meeting_zoom_jika_ada(): void
    {
        $this->zoomMock->shouldReceive('buatMeeting')
            ->once()
            ->andReturn(['meeting_id' => 'MTG777', 'join_url' => 'https://zoom.us/j/777', 'password' => 'zz11']);

        $data = $this->dataJadwal('2030-05-05');
        $data['link_otomatis'] = true;
        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator1->id_user]);

        $this->zoomMock->shouldReceive('hapusMeeting')->once()->with('akun_1', 'MTG777')->andReturn(true);

        $this->service->hapus($jadwal->fresh());
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
