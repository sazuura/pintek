<?php

namespace Tests\Feature;

use App\Models\Peralatan;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test bahwa inventaris hanya bisa lihat data gedungnya sendiri.
 * Ini adalah test untuk logika isolasi data antar gedung.
 */
class InventarisFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $invGedungA;
    private User $invGedungB;
    private User $operator;
    private Peralatan $alatA;
    private Peralatan $alatB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invGedungA = $this->buatUser('US001', 'inventaris', 'Gedung A');
        $this->invGedungB = $this->buatUser('US002', 'inventaris', 'Gedung B');
        $this->operator   = $this->buatUser('US003', 'operator', null);

        $this->alatA = Peralatan::create([
            'id_peralatan' => 'PR-001', 'nama_peralatan' => 'Laptop',
            'gedung' => 'Gedung A', 'stok' => 5,
        ]);
        $this->alatB = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor',
            'gedung' => 'Gedung B', 'stok' => 3,
        ]);
    }

    /** @test */
    public function inventaris_gedung_a_hanya_lihat_peralatan_gedung_a(): void
    {
        // Gedung A hanya boleh lihat PR-001
        $this->actingAs($this->invGedungA)
             ->get(route('inventaris.peralatan.index'))
             ->assertOk()
             ->assertSee('Laptop')       // milik Gedung A → tampil
             ->assertDontSee('Proyektor'); // milik Gedung B → tidak tampil
    }

    /** @test */
    public function inventaris_gedung_b_hanya_lihat_peralatan_gedung_b(): void
    {
        $this->actingAs($this->invGedungB)
             ->get(route('inventaris.peralatan.index'))
             ->assertOk()
             ->assertSee('Proyektor')  // milik Gedung B → tampil
             ->assertDontSee('Laptop'); // milik Gedung A → tidak tampil
    }

    /** @test */
    public function inventaris_gedung_a_hanya_lihat_peminjaman_yang_menyertakan_alatnya(): void
    {
        // Peminjaman 1: hanya alat Gedung A
        $p1 = Peminjaman::create([
            'id_user' => $this->operator->id_user,
            'tanggal_pinjam' => now()->addDay(),
            'tanggal_kembali_rencana' => now()->addDays(3),
            'keperluan' => 'Keperluan A',
            'status' => 'diajukan',
        ]);
        PeminjamanItem::create(['id_peminjaman' => $p1->id_peminjaman, 'id_peralatan' => 'PR-001', 'jumlah' => 1]);

        // Peminjaman 2: hanya alat Gedung B
        $p2 = Peminjaman::create([
            'id_user' => $this->operator->id_user,
            'tanggal_pinjam' => now()->addDay(),
            'tanggal_kembali_rencana' => now()->addDays(3),
            'keperluan' => 'Keperluan B',
            'status' => 'diajukan',
        ]);
        PeminjamanItem::create(['id_peminjaman' => $p2->id_peminjaman, 'id_peralatan' => 'PR-002', 'jumlah' => 1]);

        $this->actingAs($this->invGedungA)
             ->get(route('inventaris.peminjaman.index'))
             ->assertOk()
             ->assertSee('Keperluan A')    // ada alat Gedung A → tampil
             ->assertDontSee('Keperluan B'); // tidak ada alat Gedung A → tidak tampil
    }

    /** @test */
    public function inventaris_gedung_a_tidak_bisa_approve_peminjaman_gedung_b(): void
    {
        $peminjaman = Peminjaman::create([
            'id_user' => $this->operator->id_user,
            'tanggal_pinjam' => now()->addDay(),
            'tanggal_kembali_rencana' => now()->addDays(3),
            'keperluan' => 'Test',
            'status' => 'diajukan',
        ]);
        PeminjamanItem::create([
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'id_peralatan'  => 'PR-002', // Gedung B
            'jumlah'        => 1,
        ]);

        $this->actingAs($this->invGedungA)
             ->post(route('inventaris.peminjaman.approve', $peminjaman->id_peminjaman))
             ->assertRedirect()
             ->assertSessionHas('error');

        // Status tidak berubah
        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'status'        => 'diajukan',
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function buatUser(string $id, string $role, ?string $gedung): User
    {
        return User::create([
            'id_user'   => $id,
            'nama_user' => "User $id",
            'nohp'      => '0800000' . substr($id, -3),
            'email'     => "$id@test.com",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'gedung'    => $gedung,
            'status'    => 'active',
        ]);
    }
}
