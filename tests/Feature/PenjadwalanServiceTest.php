<?php

namespace Tests\Feature;

use App\Mail\JadwalBaruMail;
use App\Mail\JadwalDibatalkanMail;
use App\Models\Penjadwalan;
use App\Models\User;
use App\Services\PenjadwalanService;
use App\Services\ZoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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

        Mail::fake();

        $this->zoomMock = Mockery::mock(ZoomService::class);
        $this->app->instance(ZoomService::class, $this->zoomMock);

        $this->service = $this->app->make(PenjadwalanService::class);

        $this->admin     = User::create($this->dataUser('US001', 'admin'));
        $this->operator1 = User::create($this->dataUser('US002', 'operator', '081111111111'));
        $this->operator2 = User::create($this->dataUser('US003', 'operator', '082222222222'));
    }

    #[Test]
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
        Mail::assertSent(JadwalBaruMail::class, fn($mail) => $mail->hasTo($this->operator1->email));
    }

    #[Test]
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

    #[Test]
    public function buat_jadwal_gagal_jika_operator_sudah_punya_jadwal_bentrok(): void
    {

        $this->service->buat(
            data:        $this->dataJadwal('2030-01-05', '09:00', '11:00'),
            operatorIds: [$this->operator1->id_user],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/sudah memiliki jadwal/');

        $this->service->buat(
            data:        $this->dataJadwal('2030-01-05', '10:00', '12:00'),
            operatorIds: [$this->operator1->id_user],
        );
    }

    #[Test]
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

    #[Test]
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
        Mail::assertSent(JadwalDibatalkanMail::class, fn($mail) => $mail->hasTo($this->operator1->email));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function buat_jadwal_link_otomatis_kedua_akun_bentrok_tidak_generate(): void
    {

        Penjadwalan::create(array_merge($this->dataJadwal('2030-05-03', '09:00', '10:00'), [
            'id_penjadwalan' => 'JDW-901', 'link_otomatis' => true, 'zoom_account' => 'akun_1', 'zoom_meeting_id' => 'A1',
        ]));
        Penjadwalan::create(array_merge($this->dataJadwal('2030-05-03', '09:00', '10:00'), [
            'id_penjadwalan' => 'JDW-902', 'link_otomatis' => true, 'zoom_account' => 'akun_2', 'zoom_meeting_id' => 'A2',
        ]));

        $this->zoomMock->shouldReceive('buatMeeting')->never();

        $data = $this->dataJadwal('2030-05-03', '09:30', '10:30');
        $data['link_otomatis'] = true;

        $jadwal = $this->service->buat(data: $data, operatorIds: [$this->operator2->id_user]);

        $this->assertDatabaseHas('penjadwalan', [
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'link_otomatis'  => false,
        ]);
        $this->assertStringContainsString('sudah terpakai', $this->service->peringatanZoom());
    }

    #[Test]
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

    #[Test]
    public function buat_jadwal_akun_zoom_pilihan_manual_bentrok_tidak_fallback_ke_akun_lain(): void
    {

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

    #[Test]
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

    #[Test]
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

        $dataUbah = $this->dataJadwal('2030-05-04', '11:00', '12:00');
        $dataUbah['link_otomatis'] = true;
        $this->service->ubah($jadwal->fresh(), $dataUbah, [$this->operator1->id_user]);
    }

    #[Test]
    public function peralatan_sudah_diajukan_menghitung_total_jumlah_bukan_sekadar_nama(): void
    {

        $jadwal = $this->service->buat(
            data: $this->dataJadwal('2030-06-01'),
            operatorIds: [$this->operator1->id_user],
        );

        $alat = \App\Models\Peralatan::create([
            'id_peralatan' => 'PR-900', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 5,
        ]);

        $peminjaman = \App\Models\Peminjaman::create([
            'id_peminjaman' => 'PMJ-900', 'id_user' => $this->operator1->id_user,
            'id_penjadwalan' => $jadwal->id_penjadwalan,
            'tanggal_pinjam' => '2030-06-01', 'tanggal_kembali_rencana' => '2030-06-02',
            'keperluan' => 'Rapat', 'status' => 'diajukan',
        ]);
        \App\Models\PeminjamanItem::create([
            'id_peminjaman' => $peminjaman->id_peminjaman, 'id_peralatan' => $alat->id_peralatan, 'jumlah' => 1,
        ]);

        $hasil = $jadwal->fresh()->peralatanSudahDiajukan();

        $this->assertSame(['Proyektor' => 1], $hasil);
    }

    #[Test]
    public function peralatan_sudah_diajukan_menjumlahkan_lintas_beberapa_pengajuan(): void
    {
        $jadwal = $this->service->buat(
            data: $this->dataJadwal('2030-06-02'),
            operatorIds: [$this->operator1->id_user, $this->operator2->id_user],
        );

        $alat = \App\Models\Peralatan::create([
            'id_peralatan' => 'PR-901', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 5,
        ]);

        foreach ([['PMJ-901', $this->operator1, 1], ['PMJ-902', $this->operator2, 1]] as [$id, $user, $jumlah]) {
            $p = \App\Models\Peminjaman::create([
                'id_peminjaman' => $id, 'id_user' => $user->id_user,
                'id_penjadwalan' => $jadwal->id_penjadwalan,
                'tanggal_pinjam' => '2030-06-02', 'tanggal_kembali_rencana' => '2030-06-03',
                'keperluan' => 'Rapat', 'status' => 'diajukan',
            ]);
            \App\Models\PeminjamanItem::create([
                'id_peminjaman' => $p->id_peminjaman, 'id_peralatan' => $alat->id_peralatan, 'jumlah' => $jumlah,
            ]);
        }

        $hasil = $jadwal->fresh()->peralatanSudahDiajukan();

        $this->assertSame(['Proyektor' => 2], $hasil);
    }

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
