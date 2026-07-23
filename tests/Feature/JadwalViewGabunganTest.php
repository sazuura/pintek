<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Penjadwalan;
use App\Models\Role;
use App\Models\RoleMenuAkses;
use App\Models\User;
use Carbon\Carbon;
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
    public function jadwal_belum_selesai_tampil_di_atas_jadwal_yang_sudah_selesai_meski_lebih_dekat_tanggalnya(): void
    {
        // Rapat kemarin (sudah lewat waktu_selesai-nya) - tanggalnya PALING dekat
        // dengan hari ini, tapi statusnya sudah Selesai.
        Penjadwalan::create([
            'id_penjadwalan' => 'JDW010', 'judul_kegiatan' => 'Rapat Kemarin Sudah Selesai',
            'tanggal' => Carbon::yesterday()->toDateString(), 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'selesai',
        ]);
        // Rapat minggu depan - tanggalnya lebih jauh dari hari ini dibanding yang
        // kemarin, tapi belum selesai (masih Aktif) sehingga harus tampil lebih dulu.
        Penjadwalan::create([
            'id_penjadwalan' => 'JDW011', 'judul_kegiatan' => 'Rapat Minggu Depan Masih Aktif',
            'tanggal' => Carbon::now()->addDays(5)->toDateString(), 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'selesai',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.index'));

        $response->assertOk()->assertSeeInOrder([
            'Rapat Minggu Depan Masih Aktif',
            'Rapat Kemarin Sudah Selesai',
        ]);
    }

    /** @test */
    public function jadwal_dibatalkan_lama_tidak_menyempil_di_antara_jadwal_aktif(): void
    {
        // Dua rapat Aktif akan datang - harus tampil berurutan dari yang paling dekat.
        Penjadwalan::create([
            'id_penjadwalan' => 'JDW020', 'judul_kegiatan' => 'Rapat Aktif Lebih Dekat',
            'tanggal' => Carbon::now()->addDays(20)->toDateString(), 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'selesai',
        ]);
        Penjadwalan::create([
            'id_penjadwalan' => 'JDW021', 'judul_kegiatan' => 'Rapat Aktif Lebih Jauh',
            'tanggal' => Carbon::now()->addDays(26)->toDateString(), 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'selesai',
        ]);
        // Rapat yang dibatalkan 23 hari lalu - jaraknya di ANTARA dua rapat aktif di
        // atas kalau dihitung murni dari jarak tanggal absolut, tapi karena sudah
        // Dibatalkan, seharusnya tidak boleh menyempil di antara keduanya.
        Penjadwalan::create([
            'id_penjadwalan' => 'JDW022', 'judul_kegiatan' => 'Rapat Dibatalkan Lama',
            'tanggal' => Carbon::now()->subDays(23)->toDateString(), 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'dibatalkan',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.index'));

        $response->assertOk()->assertSeeInOrder([
            'Rapat Aktif Lebih Dekat',
            'Rapat Aktif Lebih Jauh',
            'Rapat Dibatalkan Lama',
        ]);
    }

    /** @test */
    public function jadwal_selesai_dan_dibatalkan_digabung_satu_riwayat_urut_dari_yang_paling_baru(): void
    {
        // Dibatalkan jauh lebih lama daripada rapat yang sudah Selesai - meski
        // beda status, keduanya tetap satu kelompok "riwayat" dan yang lebih baru
        // (Selesai, 2 hari lalu) harus tampil duluan daripada yang lebih lama
        // (Dibatalkan, 150 hari lalu) supaya tidak ada lompatan tanggal.
        Penjadwalan::create([
            'id_penjadwalan' => 'JDW030', 'judul_kegiatan' => 'Rapat Dibatalkan Sangat Lama',
            'tanggal' => Carbon::now()->subDays(150)->toDateString(), 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'dibatalkan',
        ]);
        Penjadwalan::create([
            'id_penjadwalan' => 'JDW031', 'judul_kegiatan' => 'Rapat Baru Saja Selesai',
            'tanggal' => Carbon::now()->subDays(2)->toDateString(), 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'selesai',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.index'));

        $response->assertOk()->assertSeeInOrder([
            'Rapat Baru Saja Selesai',
            'Rapat Dibatalkan Sangat Lama',
        ]);
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
    public function tidak_bisa_membuat_jadwal_pada_jam_yang_sudah_terlewati_hari_ini(): void
    {
        // Bekukan waktu supaya tidak flaky di jam berapapun test dijalankan.
        $this->travelTo(Carbon::parse('2026-07-23 14:00:00'));

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'judul_kegiatan' => 'Rapat Jam Lewat',
            'tanggal'        => '2026-07-23',   // hari ini (lolos after_or_equal:today)
            'waktu_mulai'    => '08:00',        // tapi jamnya sudah berlalu
            'waktu_selesai'  => '10:00',
            'platform'       => 'Offline',
            'operator_ids'   => [$this->operator->id_user],
        ]);

        $response->assertSessionHasErrors('waktu_selesai');
        $this->assertDatabaseMissing('penjadwalan', ['judul_kegiatan' => 'Rapat Jam Lewat']);

        $this->travelBack();
    }

    /** @test */
    public function operator_bisa_search_dan_filter_status_di_jadwal_saya(): void
    {
        // Jadwal kedua milik operator yang sama, statusnya dibatalkan - untuk
        // memastikan filter status benar-benar menyaring, bukan sekadar lolos.
        $batal = Penjadwalan::create([
            'id_penjadwalan' => 'JDW050', 'judul_kegiatan' => 'Rapat Batal Operator',
            'tanggal' => '2026-08-05', 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'dibatalkan',
        ]);
        $batal->operators()->sync([$this->operator->id_user]);

        // Search: cuma jadwal yang judulnya cocok yang tampil.
        $this->actingAs($this->operator)
            ->get(route('operator.jadwal.index', ['search' => 'Koordinasi']))
            ->assertOk()
            ->assertSee('Rapat Koordinasi Test')
            ->assertDontSee('Rapat Batal Operator');

        // Filter status dibatalkan: kebalikannya.
        $this->actingAs($this->operator)
            ->get(route('operator.jadwal.index', ['status' => 'dibatalkan']))
            ->assertOk()
            ->assertSee('Rapat Batal Operator')
            ->assertDontSee('Rapat Koordinasi Test');

        // Toolbar filternya sendiri harus tampil di halaman operator.
        $this->actingAs($this->operator)
            ->get(route('operator.jadwal.index'))
            ->assertSee('Semua Platform')
            ->assertSee('Semua Status');
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

    /**
     * Sebelum ini operator tidak pernah punya route jadwal.create/store sendiri - hanya
     * admin. Kalau hak akses tambah jadwal dinyalakan untuk role operator lewat Sistem
     * Settings, tombol "Tambah Jadwal" harus benar-benar mengarah ke route yang ada
     * (bukan ke prefix admin yang akan ditolak middleware role:admin).
     */
    /** @test */
    public function operator_yang_diberi_akses_tambah_jadwal_bisa_pakai_route_miliknya_sendiri(): void
    {
        $roleOperator = Role::where('slug', 'operator')->first();
        $menuJadwal   = Menu::where('slug', 'jadwal')->first();
        RoleMenuAkses::where('id_role', $roleOperator->id)->where('id_menu', $menuJadwal->id)
            ->update(['bisa_tambah' => true]);

        $this->actingAs($this->operator)
            ->get(route('operator.jadwal.index'))
            ->assertSee(route('operator.jadwal.create'), false);

        $this->actingAs($this->operator)
            ->post(route('operator.jadwal.store'), [
                'judul_kegiatan'  => 'Rapat Baru Operator',
                'tanggal'         => now()->addDay()->toDateString(),
                'waktu_mulai'     => '09:00',
                'waktu_selesai'   => '10:00',
                'platform'        => 'Offline',
                'operator_ids'    => [$this->operator->id_user],
            ])
            ->assertRedirect(route('operator.jadwal.index'));

        $this->assertDatabaseHas('penjadwalan', ['judul_kegiatan' => 'Rapat Baru Operator']);
    }
}
