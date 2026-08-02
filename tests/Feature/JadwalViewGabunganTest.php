<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Penjadwalan;
use App\Models\Peralatan;
use App\Models\Role;
use App\Models\RoleMenuAkses;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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

    #[Test]
    public function detail_jadwal_menampilkan_tabel_peminjaman_terkait_urut_dari_paling_lama(): void
    {
        $alat = Peralatan::create([
            'id_peralatan' => 'PR-500', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 5,
        ]);

        $baru = Peminjaman::create([
            'id_peminjaman' => 'PMJ-501', 'id_user' => $this->operator->id_user,
            'id_penjadwalan' => $this->jadwal->id_penjadwalan,
            'tanggal_pinjam' => '2026-08-01', 'tanggal_kembali_rencana' => '2026-08-02',
            'keperluan' => 'Pengajuan susulan alat kedua', 'status' => 'diajukan',
            'created_at' => now()->addMinute(),
        ]);
        PeminjamanItem::create(['id_peminjaman' => $baru->id_peminjaman, 'id_peralatan' => $alat->id_peralatan, 'jumlah' => 1]);

        $lama = Peminjaman::create([
            'id_peminjaman' => 'PMJ-500', 'id_user' => $this->operator->id_user,
            'id_penjadwalan' => $this->jadwal->id_penjadwalan,
            'tanggal_pinjam' => '2026-08-01', 'tanggal_kembali_rencana' => '2026-08-02',
            'keperluan' => 'Pengajuan alat pertama', 'status' => 'diajukan',
            'created_at' => now(),
        ]);
        PeminjamanItem::create(['id_peminjaman' => $lama->id_peminjaman, 'id_peralatan' => $alat->id_peralatan, 'jumlah' => 2]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.show', $this->jadwal->id_penjadwalan));

        $response->assertOk()
            ->assertSee('Peminjaman Terkait')
            ->assertSee('Jumlah Alat')
            ->assertSeeInOrder(['Pengajuan alat pertama', 'Pengajuan susulan alat kedua'], false);
    }

    #[Test]
    public function admin_lihat_gaya_kelola_lengkap(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.index'));

        $response->assertOk()
            ->assertSee('Data Jadwal Rapat')
            ->assertSee('Tambah Jadwal')
            ->assertSee('Rapat Koordinasi Test')
            ->assertSee(route('admin.jadwal.create'), false);
    }

    #[Test]
    public function jadwal_belum_selesai_tampil_di_atas_jadwal_yang_sudah_selesai_meski_lebih_dekat_tanggalnya(): void
    {

        Penjadwalan::create([
            'id_penjadwalan' => 'JDW010', 'judul_kegiatan' => 'Rapat Kemarin Sudah Selesai',
            'tanggal' => Carbon::yesterday()->toDateString(), 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'selesai',
        ]);

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

    #[Test]
    public function jadwal_dibatalkan_lama_tidak_menyempil_di_antara_jadwal_aktif(): void
    {

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

    #[Test]
    public function jadwal_selesai_dan_dibatalkan_digabung_satu_riwayat_urut_dari_yang_paling_baru(): void
    {

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

    #[Test]
    public function operator_lihat_gaya_baca_saja(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.jadwal.index'));

        $response->assertOk()
            ->assertSee('Jadwal Saya')
            ->assertDontSee('Tambah Jadwal')
            ->assertSee('Rapat Koordinasi Test');
    }

    #[Test]
    public function tidak_bisa_membuat_jadwal_pada_jam_yang_sudah_terlewati_hari_ini(): void
    {

        $this->travelTo(Carbon::parse('2026-07-23 14:00:00'));

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'judul_kegiatan' => 'Rapat Jam Lewat',
            'tanggal'        => '2026-07-23',
            'waktu_mulai'    => '08:00',
            'waktu_selesai'  => '10:00',
            'platform'       => 'Offline',
            'operator_ids'   => [$this->operator->id_user],
        ]);

        $response->assertSessionHasErrors('waktu_selesai');
        $this->assertDatabaseMissing('penjadwalan', ['judul_kegiatan' => 'Rapat Jam Lewat']);

        $this->travelBack();
    }

    #[Test]
    public function operator_bisa_search_dan_filter_status_di_jadwal_saya(): void
    {

        $batal = Penjadwalan::create([
            'id_penjadwalan' => 'JDW050', 'judul_kegiatan' => 'Rapat Batal Operator',
            'tanggal' => '2026-08-05', 'waktu_mulai' => '09:00', 'waktu_selesai' => '10:00',
            'platform' => 'Offline', 'status' => 'dibatalkan',
        ]);
        $batal->operators()->sync([$this->operator->id_user]);

        $this->actingAs($this->operator)
            ->get(route('operator.jadwal.index', ['search' => 'Koordinasi']))
            ->assertOk()
            ->assertSee('Rapat Koordinasi Test')
            ->assertDontSee('Rapat Batal Operator');

        $this->actingAs($this->operator)
            ->get(route('operator.jadwal.index', ['status' => 'dibatalkan']))
            ->assertOk()
            ->assertSee('Rapat Batal Operator')
            ->assertDontSee('Rapat Koordinasi Test');

        $this->actingAs($this->operator)
            ->get(route('operator.jadwal.index'))
            ->assertSee('Semua Platform')
            ->assertSee('Semua Status');
    }

    #[Test]
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

    #[Test]
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
                'tanggal'         => Carbon::parse('next monday')->toDateString(),
                'waktu_mulai'     => '09:00',
                'waktu_selesai'   => '10:00',
                'platform'        => 'Offline',
                'operator_ids'    => [$this->operator->id_user],
            ])
            ->assertRedirect(route('operator.jadwal.index'));

        $this->assertDatabaseHas('penjadwalan', ['judul_kegiatan' => 'Rapat Baru Operator']);
    }
}
