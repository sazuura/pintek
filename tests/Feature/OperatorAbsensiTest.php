<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Penjadwalan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test alur presensi operator — validasi waktu dan status.
 */
class OperatorAbsensiTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Penjadwalan $jadwal;
    private Absensi $absensi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::create([
            'id_user'   => 'US001',
            'nama_user' => 'Operator Test',
            'nohp'      => '081234567890',
            'email'     => 'op@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'operator',
            'status'    => 'active',
        ]);

        // Jadwal hari ini, berlangsung 1 jam ke depan
        $this->jadwal = Penjadwalan::create([
            'id_penjadwalan' => 'PJ001',
            'judul_kegiatan' => 'Rapat Test',
            'tanggal'        => now('Asia/Jakarta')->toDateString(),
            'waktu_mulai'    => now('Asia/Jakarta')->subMinute()->format('H:i'),
            'waktu_selesai'  => now('Asia/Jakarta')->addHour()->format('H:i'),
            'platform'       => 'Online (Zoom)',
        ]);

        $this->absensi = Absensi::create([
            'id_penjadwalan' => 'PJ001',
            'id_user'        => 'US001',
            'tanggal'        => now('Asia/Jakarta')->toDateString(),
            'status'         => 'pending',
            'validated'      => false,
        ]);
    }

    /** @test */
    public function operator_bisa_isi_hadir_saat_jadwal_berlangsung(): void
    {
        $this->actingAs($this->operator)
             ->post(route('operator.absensi.store'), [
                 'id_absensi' => $this->absensi->id_absensi,
                 'status'     => 'hadir',
             ])
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertDatabaseHas('absensi', [
            'id_absensi' => $this->absensi->id_absensi,
            'status'     => 'hadir',
        ]);
    }

    /** @test */
    public function operator_tidak_bisa_hadir_untuk_jadwal_hari_lain(): void
    {
        // Ubah jadwal ke kemarin
        $this->jadwal->update([
            'tanggal'     => now()->subDay()->toDateString(),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '11:00',
        ]);
        $this->absensi->update(['tanggal' => now()->subDay()->toDateString()]);

        $this->actingAs($this->operator)
             ->post(route('operator.absensi.store'), [
                 'id_absensi' => $this->absensi->id_absensi,
                 'status'     => 'hadir',
             ])
             ->assertRedirect()
             ->assertSessionHas('error');

        // Status tetap pending
        $this->assertDatabaseHas('absensi', [
            'id_absensi' => $this->absensi->id_absensi,
            'status'     => 'pending',
        ]);
    }

    /** @test */
    public function operator_tidak_bisa_ajukan_izin_di_hari_h(): void
    {
        // Jadwal hari ini → tidak bisa izin hari H (harus H-1)
        $this->actingAs($this->operator)
             ->post(route('operator.absensi.store'), [
                 'id_absensi' => $this->absensi->id_absensi,
                 'status'     => 'izin',
                 'keterangan' => 'Ada urusan keluarga',
             ])
             ->assertRedirect()
             ->assertSessionHas('error');

        $this->assertDatabaseHas('absensi', [
            'id_absensi' => $this->absensi->id_absensi,
            'status'     => 'pending',
        ]);
    }

    /** @test */
    public function operator_tidak_bisa_ubah_absensi_yang_sudah_final(): void
    {
        $this->absensi->update(['status' => 'izin_disetujui']);

        $this->actingAs($this->operator)
             ->post(route('operator.absensi.store'), [
                 'id_absensi' => $this->absensi->id_absensi,
                 'status'     => 'hadir',
             ])
             ->assertRedirect()
             ->assertSessionHas('error');

        // Status tetap izin_disetujui
        $this->assertDatabaseHas('absensi', [
            'id_absensi' => $this->absensi->id_absensi,
            'status'     => 'izin_disetujui',
        ]);
    }

    /** @test */
    public function operator_tidak_bisa_isi_absensi_milik_operator_lain(): void
    {
        $operatorLain = User::create([
            'id_user'   => 'US002',
            'nama_user' => 'Operator Lain',
            'nohp'      => '089999999999',
            'email'     => 'lain@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'operator',
            'status'    => 'active',
        ]);

        $this->actingAs($operatorLain)
             ->post(route('operator.absensi.store'), [
                 'id_absensi' => $this->absensi->id_absensi, // milik US001
                 'status'     => 'hadir',
             ])
             ->assertForbidden();
    }
}
