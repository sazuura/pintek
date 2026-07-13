<?php

namespace Tests\Feature;

use App\Helpers\IdGenerator;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Peralatan;
use App\Models\User;
use App\Services\PeminjamanService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PeminjamanServiceTest extends TestCase
{
    use RefreshDatabase;

    private PeminjamanService $service;
    private User $operator;
    private User $inventaris;
    private Peralatan $alat;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock WA - test tidak kirim WA sungguhan
        $waMock = Mockery::mock(WhatsAppService::class);
        $waMock->shouldReceive('templatePeminjamanBaru')->andReturn('pesan test');
        $waMock->shouldReceive('templatePeminjamanDiubah')->andReturn('pesan test');
        $waMock->shouldReceive('templatePeminjamanDibatalkan')->andReturn('pesan test');
        $waMock->shouldReceive('kirim')->andReturn(true);
        $this->app->instance(WhatsAppService::class, $waMock);

        $this->service = $this->app->make(PeminjamanService::class);

        $this->operator   = $this->buatUser('US001', 'operator');
        $this->inventaris = $this->buatUser('US002', 'inventaris', '081111111111');

        $this->alat = Peralatan::create([
            'id_peralatan' => 'PR-001', 'nama_peralatan' => 'Laptop',
            'gedung' => 'Gedung A', 'stok' => 5,
        ]);
    }

    /** @test */
    public function ajukan_menyimpan_header_dan_item_peminjaman(): void
    {
        $this->service->ajukan(
            header:       $this->dataHeader(),
            peralatanIds: [$this->alat->id_peralatan],
            jumlahArr:    [2],
        );

        $this->assertDatabaseHas('peminjaman', [
            'id_user'   => $this->operator->id_user,
            'status'    => 'diajukan',
            'keperluan' => 'Rapat dinas',
        ]);
        $this->assertDatabaseHas('peminjaman_item', [
            'id_peralatan' => 'PR-001',
            'jumlah'       => 2,
        ]);
    }

    /** @test */
    public function ajukan_langsung_mengurangi_stok_supaya_tidak_bisa_di_spam(): void
    {
        $alatSatuStok = Peralatan::create([
            'id_peralatan' => 'PR-MOUSE', 'nama_peralatan' => 'Mouse',
            'gedung' => 'Gedung A', 'stok' => 1,
        ]);

        $this->service->ajukan(
            header:       $this->dataHeader(),
            peralatanIds: [$alatSatuStok->id_peralatan],
            jumlahArr:    [1],
        );

        $this->assertSame(0, $alatSatuStok->fresh()->stok);

        // Stok sudah 0 - pengajuan kedua untuk alat yang sama (belum di-ACC/ditolak
        // sama sekali) harus gagal, bukan lolos terus seperti sebelum ada reservasi ini.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak mencukupi/');

        $this->service->ajukan(
            header:       $this->dataHeader(),
            peralatanIds: [$alatSatuStok->id_peralatan],
            jumlahArr:    [1],
        );
    }

    /** @test */
    public function ajukan_gagal_jika_stok_tidak_cukup(): void
    {
        try {
            $this->service->ajukan(
                header:       $this->dataHeader(),
                peralatanIds: [$this->alat->id_peralatan],
                jumlahArr:    [99], // lebih dari stok
            );
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertMatchesRegularExpression('/tidak mencukupi/', $e->getMessage());
        }

        // Gagal validasi harus rollback total - stok tidak boleh berkurang sedikit pun.
        $this->assertSame(5, $this->alat->fresh()->stok);
    }

    /** @test */
    public function setujui_mengubah_status_menjadi_disetujui(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);

        $this->service->setujui($peminjaman, $this->inventaris, 'OK disetujui');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman'      => $peminjaman->id_peminjaman,
            'status'             => 'disetujui',
            'catatan_inventaris' => 'OK disetujui',
        ]);
    }

    /** @test */
    public function tolak_mengubah_status_menjadi_ditolak(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);

        $this->service->tolak($peminjaman, $this->inventaris, 'Stok habis.');

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman'      => $peminjaman->id_peminjaman,
            'status'             => 'ditolak',
            'catatan_inventaris' => 'Stok habis.',
        ]);
    }

    /** @test */
    public function tolak_mengembalikan_stok_yang_sudah_direservasi(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]); // stok jadi 4

        $this->service->tolak($peminjaman, $this->inventaris, 'Stok habis.');

        $this->assertSame(5, $this->alat->fresh()->stok);
    }

    /** @test */
    public function batalkan_mengembalikan_stok_yang_sudah_direservasi(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]); // stok jadi 4

        $this->service->batalkan($peminjaman, 'Rapat dibatalkan.');

        $this->assertSame(5, $this->alat->fresh()->stok);
    }

    /** @test */
    public function ubah_mengganti_keperluan_dan_item_peminjaman(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor',
            'gedung' => 'Gedung A', 'stok' => 3,
        ]);

        $this->service->ubah(
            peminjaman:   $peminjaman,
            header:       ['keperluan' => 'Rapat dinas (diperbarui)'] + $this->dataHeader(),
            peralatanIds: [$alatKedua->id_peralatan],
            jumlahArr:    [2],
        );

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'keperluan'     => 'Rapat dinas (diperbarui)',
        ]);
        $this->assertDatabaseMissing('peminjaman_item', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'id_peralatan'  => 'PR-001',
        ]);
        $this->assertDatabaseHas('peminjaman_item', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'id_peralatan'  => 'PR-002',
            'jumlah'        => 2,
        ]);

        // Reservasi alat lama (PR-001) dilepas kembali, reservasi alat baru (PR-002) dibuat.
        $this->assertSame(5, $this->alat->fresh()->stok);
        $this->assertSame(1, $alatKedua->fresh()->stok);
    }

    /** @test */
    public function ubah_gagal_jika_status_bukan_menunggu(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'disetujui');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Menunggu/');

        $this->service->ubah(
            peminjaman:   $peminjaman,
            header:       $this->dataHeader(),
            peralatanIds: [$this->alat->id_peralatan],
            jumlahArr:    [1],
        );
    }

    /** @test */
    public function konfirmasi_kembali_mengisi_tanggal_kembali_aktual(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'disetujui'); // stok jadi 4

        $this->service->konfirmasiKembali($peminjaman, $this->inventaris);

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'status'        => 'dikembalikan',
        ]);
        $this->assertNotNull(Peminjaman::find($peminjaman->id_peminjaman)->tanggal_kembali_aktual);
        $this->assertSame(5, $this->alat->fresh()->stok);
    }

    /** @test */
    public function setujui_item_hanya_mengubah_status_item_itu_alat_lain_tetap_menunggu(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]);
        $items = $peminjaman->items()->orderBy('id_peralatan')->get();

        $this->service->setujuiItem($items[0]);

        $this->assertSame('disetujui', $items[0]->fresh()->status);
        $this->assertSame('diajukan', $items[1]->fresh()->status);
        // Masih ada 1 alat yang menunggu keputusan - status induk harus tetap "diajukan".
        $this->assertSame('diajukan', $peminjaman->fresh()->status);
    }

    /** @test */
    public function tolak_item_mengembalikan_stok_hanya_untuk_alat_itu(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]); // stok masing2 -1

        $itemLaptop = $peminjaman->items()->where('id_peralatan', $this->alat->id_peralatan)->first();
        $this->service->tolakItem($itemLaptop, 'Sedang dipakai unit lain.');

        $this->assertSame('ditolak', $itemLaptop->fresh()->status);
        $this->assertSame(5, $this->alat->fresh()->stok); // dikembalikan
        $this->assertSame(2, $alatKedua->fresh()->stok);  // alat kedua tetap tereservasi
        $this->assertSame('diajukan', $peminjaman->fresh()->status); // alat kedua masih menunggu
    }

    /** @test */
    public function status_induk_jadi_disetujui_begitu_semua_item_diputuskan_dan_ada_yang_disetujui(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]);
        $items = $peminjaman->items()->orderBy('id_peralatan')->get();

        $this->service->setujuiItem($items[0]);
        $this->service->tolakItem($items[1], 'Stok dipakai kegiatan lain.');

        $this->assertSame('disetujui', $peminjaman->fresh()->status);
    }

    /** @test */
    public function status_induk_jadi_ditolak_kalau_semua_item_ditolak(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]);
        $items = $peminjaman->items()->orderBy('id_peralatan')->get();

        $this->service->tolakItem($items[0], 'Ditolak.');
        $this->service->tolakItem($items[1], 'Ditolak juga.');

        $this->assertSame('ditolak', $peminjaman->fresh()->status);
    }

    /** @test */
    public function setujui_item_gagal_kalau_item_sudah_diputuskan_sebelumnya(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $item = $peminjaman->items()->first();
        $this->service->setujuiItem($item);

        $this->expectException(\RuntimeException::class);
        $this->service->setujuiItem($item->fresh());
    }

    /** @test */
    public function ubah_gagal_kalau_sudah_ada_item_yang_diputuskan_inventaris(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]);
        $item = $peminjaman->items()->where('id_peralatan', $this->alat->id_peralatan)->first();
        $this->service->setujuiItem($item);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/diproses inventaris/i');

        $this->service->ubah(
            peminjaman:   $peminjaman->fresh(),
            header:       $this->dataHeader(),
            peralatanIds: [$alatKedua->id_peralatan],
            jumlahArr:    [1],
        );
    }

    /** @test */
    public function batalkan_gagal_kalau_sudah_ada_item_yang_diputuskan_inventaris(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]);
        $item = $peminjaman->items()->where('id_peralatan', $this->alat->id_peralatan)->first();
        $this->service->setujuiItem($item);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/diproses inventaris/i');

        $this->service->batalkan($peminjaman->fresh(), 'Rapat dibatalkan.');
    }

    /** @test */
    public function konfirmasi_kembali_tidak_mengembalikan_stok_dua_kali_untuk_item_yang_sudah_ditolak(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]);
        $items = $peminjaman->items()->orderBy('id_peralatan')->get();

        $this->service->setujuiItem($items[0]);           // laptop disetujui, stok tetap tereservasi (4)
        $this->service->tolakItem($items[1], 'Ditolak.');  // proyektor ditolak, stok kembali ke 3

        $this->assertSame('disetujui', $peminjaman->fresh()->status);

        $this->service->konfirmasiKembali($peminjaman->fresh(), $this->inventaris);

        $this->assertSame(5, $this->alat->fresh()->stok);   // laptop baru dikembalikan di sini
        $this->assertSame(3, $alatKedua->fresh()->stok);    // proyektor TIDAK dobel dikembalikan
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buatUser(string $id, string $role, string $nohp = '080000000000'): User
    {
        return User::create([
            'id_user'   => $id,
            'nama_user' => "User $id",
            'nohp'      => $nohp,
            'email'     => "$id@test.com",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'status'    => 'active',
        ]);
    }

    private function dataHeader(): array
    {
        return [
            'id_user'                 => $this->operator->id_user,
            'tanggal_pinjam'          => now()->addDay()->format('Y-m-d'),
            'tanggal_kembali_rencana' => now()->addDays(3)->format('Y-m-d'),
            'keperluan'               => 'Rapat dinas',
            'status'                  => 'diajukan',
        ];
    }

    private function buatPeminjaman(array $alatIds, string $status = 'diajukan'): Peminjaman
    {
        $p = Peminjaman::create(array_merge($this->dataHeader(), [
            'id_peminjaman' => IdGenerator::next(Peminjaman::class, 'id_peminjaman', 'PMJ-'),
            'status'        => $status,
        ]));

        foreach ($alatIds as $id) {
            PeminjamanItem::create([
                'id_peminjaman' => $p->id_peminjaman,
                'id_peralatan'  => $id,
                'jumlah'        => 1,
            ]);
            // Samakan dengan perilaku ajukan() sungguhan (stok direservasi/dikurangi begitu
            // diajukan) supaya test tolak/batalkan/ubah/konfirmasiKembali yang memverifikasi
            // pengembalian stok berjalan dari kondisi awal yang realistis.
            Peralatan::whereKey($id)->decrement('stok', 1);
        }

        return $p;
    }
}
