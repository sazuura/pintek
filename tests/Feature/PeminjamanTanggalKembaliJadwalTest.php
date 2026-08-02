<?php

namespace Tests\Feature;

use App\Helpers\IdGenerator;
use App\Models\Penjadwalan;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PeminjamanTanggalKembaliJadwalTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Peralatan $alat;
    private Penjadwalan $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->operator = User::create([
            'id_user' => 'US001', 'nama_user' => 'Operator Test', 'nohp' => '080000000001',
            'email' => 'operator@test.com', 'password' => bcrypt('password'), 'role' => 'operator', 'status' => 'active',
        ]);

        $this->alat = Peralatan::create([
            'id_peralatan' => 'PR-001', 'nama_peralatan' => 'Laptop',
            'gedung' => 'Gedung A', 'stok' => 5,
        ]);

        $this->jadwal = Penjadwalan::create([
            'id_penjadwalan' => IdGenerator::next(Penjadwalan::class, 'id_penjadwalan', 'JDW-'),
            'judul_kegiatan' => 'Rapat Bersama Dinas Pertahanan',
            'tanggal'        => now()->addWeek()->format('Y-m-d'),
            'waktu_mulai'    => '09:00',
            'waktu_selesai'  => '11:00',
            'platform'       => 'Offline',
            'status'         => 'selesai',
        ]);
        $this->jadwal->operators()->attach($this->operator->id_user);
    }

    private function dataPengajuan(array $override = []): array
    {
        return array_merge([
            'id_penjadwalan'          => $this->jadwal->id_penjadwalan,
            'keperluan'               => 'Rapat Bersama Dinas Pertahanan',
            'tanggal_pinjam'          => now()->addDays(2)->format('Y-m-d'),
            'tanggal_kembali_rencana' => now()->addDays(3)->format('Y-m-d'),
            'peralatan_ids'           => [$this->alat->id_peralatan],
            'peralatan_jumlah'        => [1],
        ], $override);
    }

    #[Test]
    public function tanggal_kembali_sebelum_tanggal_rapat_ditolak(): void
    {
        $this->actingAs($this->operator)
            ->post(route('operator.peminjaman.store'), $this->dataPengajuan([
                'tanggal_kembali_rencana' => now()->addDays(3)->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('tanggal_kembali_rencana');

        $this->assertDatabaseMissing('peminjaman', [
            'id_penjadwalan' => $this->jadwal->id_penjadwalan,
        ]);
    }

    #[Test]
    public function tanggal_kembali_di_hari_yang_sama_dengan_rapat_diterima(): void
    {
        $this->actingAs($this->operator)
            ->post(route('operator.peminjaman.store'), $this->dataPengajuan([
                'tanggal_pinjam'          => $this->jadwal->tanggal->format('Y-m-d'),
                'tanggal_kembali_rencana' => $this->jadwal->tanggal->format('Y-m-d'),
            ]))
            ->assertSessionDoesntHaveErrors('tanggal_kembali_rencana');

        $this->assertDatabaseHas('peminjaman', [
            'id_penjadwalan' => $this->jadwal->id_penjadwalan,
        ]);
    }

    #[Test]
    public function tanggal_kembali_setelah_tanggal_rapat_diterima(): void
    {
        $this->actingAs($this->operator)
            ->post(route('operator.peminjaman.store'), $this->dataPengajuan([
                'tanggal_pinjam'          => now()->addDays(2)->format('Y-m-d'),
                'tanggal_kembali_rencana' => $this->jadwal->tanggal->addDay()->format('Y-m-d'),
            ]))
            ->assertSessionDoesntHaveErrors('tanggal_kembali_rencana');

        $this->assertDatabaseHas('peminjaman', [
            'id_penjadwalan' => $this->jadwal->id_penjadwalan,
        ]);
    }

    #[Test]
    public function tanpa_jadwal_terkait_validasi_tanggal_rapat_tidak_berlaku(): void
    {
        $this->actingAs($this->operator)
            ->post(route('operator.peminjaman.store'), $this->dataPengajuan([
                'id_penjadwalan'          => '',
                'tanggal_pinjam'          => now()->addDay()->format('Y-m-d'),
                'tanggal_kembali_rencana' => now()->addDay()->format('Y-m-d'),
            ]))
            ->assertSessionDoesntHaveErrors('tanggal_kembali_rencana');
    }
}
