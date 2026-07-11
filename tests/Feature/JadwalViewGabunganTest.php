<?php

namespace Tests\Feature;

use App\Models\Penjadwalan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi dashboard/jadwal/index.blade.php - hasil gabungan admin/jadwal/index.blade.php
 * dan operator/jadwal/index.blade.php (Fase 4, docs/plans/planning-role-akses-dinamis.md §5).
 * Satu file, tampilan menyesuaikan hak akses (bisaTambah/bisaUbah), bukan lagi 2 file terpisah.
 */
class JadwalViewGabunganTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private Penjadwalan $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'id_user' => 'US001', 'nama_user' => 'Admin Test', 'nohp' => '081200000001',
            'email' => 'admin@test.com', 'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active',
        ]);
        $this->operator = User::create([
            'id_user' => 'US002', 'nama_user' => 'Operator Test', 'nohp' => '081200000002',
            'email' => 'operator@test.com', 'password' => bcrypt('password'), 'role' => 'operator', 'status' => 'active',
        ]);

        $this->jadwal = Penjadwalan::create([
            'id_penjadwalan' => 'JDW001', 'judul_kegiatan' => 'Rapat Koordinasi Test',
            'tanggal' => '2026-08-01', 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'selesai',
        ]);
        $this->jadwal->operators()->sync([$this->operator->id_user]);
    }

    /** @test */
    public function admin_lihat_gaya_kelola_lengkap(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.index'));

        $response->assertOk()
            ->assertSee('Data Jadwal Rapat')
            ->assertSee('Tambah Jadwal')
            ->assertSee('Rapat Koordinasi Test')
            ->assertSee(route('admin.jadwal.create'), false);
    }

    /** @test */
    public function operator_lihat_gaya_baca_saja(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.jadwal.index'));

        $response->assertOk()
            ->assertSee('Jadwal Saya')
            ->assertDontSee('Tambah Jadwal')
            ->assertSee('Rapat Koordinasi Test');
    }

    /** @test */
    public function operator_hanya_lihat_jadwal_yang_ditugaskan_ke_dirinya(): void
    {
        Penjadwalan::create([
            'id_penjadwalan' => 'JDW002', 'judul_kegiatan' => 'Rapat Operator Lain',
            'tanggal' => '2026-08-02', 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'selesai',
        ]);

        $this->actingAs($this->operator)
            ->get(route('operator.jadwal.index'))
            ->assertSee('Rapat Koordinasi Test')
            ->assertDontSee('Rapat Operator Lain');
    }
}
