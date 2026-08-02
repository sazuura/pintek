<?php

namespace Tests\Feature;

use App\Helpers\IdGenerator;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PeminjamanCekSpamTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Peralatan $mouseGedungA;
    private Peralatan $mouseGedungB;
    private string $tanggalPinjam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::create([
            'id_user' => 'US001', 'nama_user' => 'Operator Test', 'nohp' => '080000000001',
            'email' => 'operator@test.com', 'password' => bcrypt('password'), 'role' => 'operator', 'status' => 'active',
        ]);

        $this->mouseGedungA = Peralatan::create([
            'id_peralatan' => 'PR-MOUSE-A', 'nama_peralatan' => 'Mouse Wireless',
            'gedung' => 'Gedung A', 'stok' => 20,
        ]);
        $this->mouseGedungB = Peralatan::create([
            'id_peralatan' => 'PR-MOUSE-B', 'nama_peralatan' => 'Mouse Wireless',
            'gedung' => 'Gedung B', 'stok' => 20,
        ]);

        $this->tanggalPinjam = now()->addDay()->format('Y-m-d');
    }

    #[Test]
    public function tidak_ada_peringatan_kalau_belum_pernah_mengajukan(): void
    {
        $this->actingAs($this->operator)
            ->postJson(route('operator.peminjaman.cekSpam'), [
                'tanggal_pinjam' => $this->tanggalPinjam,
                'peralatan_ids'  => [$this->mouseGedungA->id_peralatan],
            ])
            ->assertOk()
            ->assertJson(['peringatan' => []]);
    }

    #[Test]
    public function ada_peringatan_setelah_alat_yang_sama_diajukan_dua_kali_sebelumnya(): void
    {
        $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan]);
        $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan]);

        $response = $this->actingAs($this->operator)
            ->postJson(route('operator.peminjaman.cekSpam'), [
                'tanggal_pinjam' => $this->tanggalPinjam,
                'peralatan_ids'  => [$this->mouseGedungA->id_peralatan],
            ])
            ->assertOk();

        $peringatan = $response->json('peringatan');
        $this->assertCount(1, $peringatan);
        $this->assertSame('Mouse Wireless', $peringatan[0]['nama']);
        $this->assertSame(2, $peringatan[0]['jumlah_sebelumnya']);
    }

    #[Test]
    public function peringatan_tetap_muncul_walau_gedung_alatnya_berbeda(): void
    {

        $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan]);
        $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan]);

        $response = $this->actingAs($this->operator)
            ->postJson(route('operator.peminjaman.cekSpam'), [
                'tanggal_pinjam' => $this->tanggalPinjam,
                'peralatan_ids'  => [$this->mouseGedungB->id_peralatan],
            ])
            ->assertOk();

        $this->assertCount(1, $response->json('peringatan'));
    }

    #[Test]
    public function pengajuan_yang_ditolak_tidak_dihitung(): void
    {
        $p1 = $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan]);
        $p1->update(['status' => 'ditolak']);
        $p2 = $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan]);
        $p2->update(['status' => 'dibatalkan']);

        $this->actingAs($this->operator)
            ->postJson(route('operator.peminjaman.cekSpam'), [
                'tanggal_pinjam' => $this->tanggalPinjam,
                'peralatan_ids'  => [$this->mouseGedungA->id_peralatan],
            ])
            ->assertOk()
            ->assertJson(['peringatan' => []]);
    }

    #[Test]
    public function tanggal_pinjam_berbeda_tidak_dihitung(): void
    {
        $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan], now()->addDays(5)->format('Y-m-d'));
        $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan], now()->addDays(5)->format('Y-m-d'));

        $this->actingAs($this->operator)
            ->postJson(route('operator.peminjaman.cekSpam'), [
                'tanggal_pinjam' => $this->tanggalPinjam,
                'peralatan_ids'  => [$this->mouseGedungA->id_peralatan],
            ])
            ->assertOk()
            ->assertJson(['peringatan' => []]);
    }

    #[Test]
    public function pengajuan_yang_sedang_diedit_tidak_menghitung_dirinya_sendiri(): void
    {
        $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan]);
        $sedangDiedit = $this->buatPeminjaman($this->operator, [$this->mouseGedungA->id_peralatan]);

        $this->actingAs($this->operator)
            ->postJson(route('operator.peminjaman.cekSpam'), [
                'tanggal_pinjam'          => $this->tanggalPinjam,
                'peralatan_ids'           => [$this->mouseGedungA->id_peralatan],
                'kecuali_id_peminjaman'   => $sedangDiedit->id_peminjaman,
            ])
            ->assertOk()
            ->assertJson(['peringatan' => []]);
    }

    private function buatPeminjaman(User $user, array $alatIds, ?string $tanggalPinjam = null): Peminjaman
    {
        $p = Peminjaman::create([
            'id_peminjaman'           => IdGenerator::next(Peminjaman::class, 'id_peminjaman', 'PMJ-'),
            'id_user'                 => $user->id_user,
            'tanggal_pinjam'          => $tanggalPinjam ?? $this->tanggalPinjam,
            'tanggal_kembali_rencana' => now()->addDays(3)->format('Y-m-d'),
            'keperluan'               => 'Rapat dinas',
            'status'                  => 'diajukan',
        ]);

        foreach ($alatIds as $id) {
            PeminjamanItem::create([
                'id_peminjaman' => $p->id_peminjaman,
                'id_peralatan'  => $id,
                'jumlah'        => 1,
            ]);
        }

        return $p;
    }
}
