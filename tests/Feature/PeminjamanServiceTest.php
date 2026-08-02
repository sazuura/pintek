<?php

namespace Tests\Feature;

use App\Helpers\IdGenerator;
use App\Mail\PeminjamanBaruMail;
use App\Mail\PeminjamanDiubahMail;
use App\Mail\PeminjamanDibatalkanMail;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Peralatan;
use App\Models\User;
use App\Services\PeminjamanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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

        Mail::fake();

        $this->service = $this->app->make(PeminjamanService::class);

        $this->operator   = $this->buatUser('US001', 'operator');
        $this->inventaris = $this->buatUser('US002', 'inventaris', '081111111111');

        $this->alat = Peralatan::create([
            'id_peralatan' => 'PR-001', 'nama_peralatan' => 'Laptop',
            'gedung' => 'Gedung A', 'stok' => 5,
        ]);
    }

    #[Test]
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
        Mail::assertSent(PeminjamanBaruMail::class, fn($mail) => $mail->hasTo($this->inventaris->email));
    }

    #[Test]
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak mencukupi/');

        $this->service->ajukan(
            header:       $this->dataHeader(),
            peralatanIds: [$alatSatuStok->id_peralatan],
            jumlahArr:    [1],
        );
    }

    #[Test]
    public function ajukan_gagal_jika_stok_tidak_cukup(): void
    {
        try {
            $this->service->ajukan(
                header:       $this->dataHeader(),
                peralatanIds: [$this->alat->id_peralatan],
                jumlahArr:    [99],
            );
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertMatchesRegularExpression('/tidak mencukupi/', $e->getMessage());
        }

        $this->assertSame(5, $this->alat->fresh()->stok);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function tolak_mengembalikan_stok_yang_sudah_direservasi(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);

        $this->service->tolak($peminjaman, $this->inventaris, 'Stok habis.');

        $this->assertSame(5, $this->alat->fresh()->stok);
    }

    #[Test]
    public function batalkan_mengembalikan_stok_yang_sudah_direservasi(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);

        $this->service->batalkan($peminjaman, 'Rapat dibatalkan.');

        $this->assertSame(5, $this->alat->fresh()->stok);
        Mail::assertSent(PeminjamanDibatalkanMail::class, fn($mail) => $mail->hasTo($this->inventaris->email));
    }

    #[Test]
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

        $this->assertSame(5, $this->alat->fresh()->stok);
        $this->assertSame(1, $alatKedua->fresh()->stok);
        Mail::assertSent(PeminjamanDiubahMail::class, fn($mail) => $mail->hasTo($this->inventaris->email));
    }

    #[Test]
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

    #[Test]
    public function konfirmasi_kembali_mengisi_tanggal_kembali_aktual(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'disetujui');

        $this->service->konfirmasiKembali($peminjaman, $this->inventaris);

        $this->assertDatabaseHas('peminjaman', [
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'status'        => 'dikembalikan',
        ]);
        $this->assertNotNull(Peminjaman::find($peminjaman->id_peminjaman)->tanggal_kembali_aktual);
        $this->assertSame(5, $this->alat->fresh()->stok);
    }

    #[Test]
    public function konfirmasi_kembali_gagal_kalau_tanggal_pinjam_masih_di_masa_depan(): void
    {
        $peminjaman = Peminjaman::create(array_merge($this->dataHeader(), [
            'id_peminjaman'           => IdGenerator::next(Peminjaman::class, 'id_peminjaman', 'PMJ-'),
            'status'                  => 'disetujui',
            'tanggal_pinjam'          => now()->addMonth()->format('Y-m-d'),
            'tanggal_kembali_rencana' => now()->addMonth()->addDays(3)->format('Y-m-d'),
        ]));
        PeminjamanItem::create([
            'id_peminjaman' => $peminjaman->id_peminjaman,
            'id_peralatan'  => $this->alat->id_peralatan,
            'jumlah'        => 1,
            'status'        => 'disetujui',
        ]);
        Peralatan::whereKey($this->alat->id_peralatan)->decrement('stok', 1);

        try {
            $this->service->konfirmasiKembali($peminjaman, $this->inventaris);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertMatchesRegularExpression('/belum bisa dikonfirmasi kembali/', $e->getMessage());
        }

        $this->assertSame('disetujui', $peminjaman->fresh()->status);
        $this->assertNull($peminjaman->fresh()->tanggal_kembali_aktual);
        $this->assertSame(4, $this->alat->fresh()->stok);
    }

    #[Test]
    public function badge_item_menampilkan_dibatalkan_saat_pengajuan_induknya_dibatalkan(): void
    {

        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'dibatalkan');

        $item = Peminjaman::with('items')->find($peminjaman->id_peminjaman)->items->first();

        $this->assertSame('diajukan', $item->status);
        $this->assertSame('Dibatalkan', $item->badge['label']);
        $this->assertSame('badge-danger', $item->badge['class']);
    }

    #[Test]
    public function badge_item_menampilkan_dikembalikan_saat_pengajuan_induknya_dikembalikan(): void
    {

        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'dikembalikan');
        $peminjaman->items()->update(['status' => 'disetujui']);

        $item = Peminjaman::with('items')->find($peminjaman->id_peminjaman)->items->first();

        $this->assertSame('disetujui', $item->status);
        $this->assertSame('Dikembalikan', $item->badge['label']);
        $this->assertSame('badge-info', $item->badge['class']);
    }

    #[Test]
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

        $this->assertSame('diajukan', $peminjaman->fresh()->status);
    }

    #[Test]
    public function tolak_item_mengembalikan_stok_hanya_untuk_alat_itu(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]);

        $itemLaptop = $peminjaman->items()->where('id_peralatan', $this->alat->id_peralatan)->first();
        $this->service->tolakItem($itemLaptop, 'Sedang dipakai unit lain.');

        $this->assertSame('ditolak', $itemLaptop->fresh()->status);
        $this->assertSame(5, $this->alat->fresh()->stok);
        $this->assertSame(2, $alatKedua->fresh()->stok);
        $this->assertSame('diajukan', $peminjaman->fresh()->status);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function setujui_item_gagal_kalau_item_sudah_diputuskan_sebelumnya(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $item = $peminjaman->items()->first();
        $this->service->setujuiItem($item);

        $this->expectException(\RuntimeException::class);
        $this->service->setujuiItem($item->fresh());
    }

    #[Test]
    public function setujui_item_gagal_kalau_pengajuan_sudah_dibatalkan(): void
    {

        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'dibatalkan');
        $item = $peminjaman->items()->first();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/sudah dibatalkan/');

        $this->service->setujuiItem($item);
    }

    #[Test]
    public function tolak_item_gagal_kalau_pengajuan_sudah_dibatalkan(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'dibatalkan');
        $item = $peminjaman->items()->first();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/sudah dibatalkan/');

        $this->service->tolakItem($item, 'Coba tolak setelah dibatalkan.');
    }

    #[Test]
    public function setujui_dan_tolak_bulk_gagal_kalau_pengajuan_sudah_dibatalkan(): void
    {
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan], 'dibatalkan');

        try {
            $this->service->setujui($peminjaman, $this->inventaris);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertMatchesRegularExpression('/sudah dibatalkan/', $e->getMessage());
        }

        try {
            $this->service->tolak($peminjaman, $this->inventaris, 'Coba tolak.');
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertMatchesRegularExpression('/sudah dibatalkan/', $e->getMessage());
        }

        $this->assertSame('dibatalkan', $peminjaman->fresh()->status);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function konfirmasi_kembali_tidak_mengembalikan_stok_dua_kali_untuk_item_yang_sudah_ditolak(): void
    {
        $alatKedua = Peralatan::create([
            'id_peralatan' => 'PR-002', 'nama_peralatan' => 'Proyektor', 'gedung' => 'Gedung A', 'stok' => 3,
        ]);
        $peminjaman = $this->buatPeminjaman([$this->alat->id_peralatan, $alatKedua->id_peralatan]);
        $items = $peminjaman->items()->orderBy('id_peralatan')->get();

        $this->service->setujuiItem($items[0]);
        $this->service->tolakItem($items[1], 'Ditolak.');

        $this->assertSame('disetujui', $peminjaman->fresh()->status);

        $this->service->konfirmasiKembali($peminjaman->fresh(), $this->inventaris);

        $this->assertSame(5, $this->alat->fresh()->stok);
        $this->assertSame(3, $alatKedua->fresh()->stok);
    }

    #[Test]
    public function operator_bisa_search_dan_filter_tanggal_di_riwayat_pengajuan(): void
    {
        $a = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $a->update(['keperluan' => 'Dokumentasi kegiatan lapangan', 'tanggal_pinjam' => '2026-07-01']);
        $b = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $b->update(['keperluan' => 'Backup jaringan Puskesmas', 'tanggal_pinjam' => '2026-07-20']);

        $this->actingAs($this->operator)
            ->get(route('operator.peminjaman.index', ['search' => 'Dokumentasi']))
            ->assertOk()
            ->assertSee('Dokumentasi kegiatan lapangan')
            ->assertDontSee('Backup jaringan Puskesmas');

        $this->actingAs($this->operator)
            ->get(route('operator.peminjaman.index', ['search' => 'Laptop']))
            ->assertOk()
            ->assertSee('Dokumentasi kegiatan lapangan')
            ->assertSee('Backup jaringan Puskesmas');

        $this->actingAs($this->operator)
            ->get(route('operator.peminjaman.index', ['start' => '2026-07-10', 'end' => '2026-07-31']))
            ->assertOk()
            ->assertSee('Backup jaringan Puskesmas')
            ->assertDontSee('Dokumentasi kegiatan lapangan');
    }

    #[Test]
    public function inventaris_bisa_search_pemohon_keperluan_dan_nama_alat(): void
    {
        $a = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $a->update(['keperluan' => 'Dokumentasi kegiatan lapangan']);

        $operatorLain = $this->buatUser('US003', 'operator');
        $b = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $b->update(['keperluan' => 'Backup jaringan Puskesmas', 'id_user' => $operatorLain->id_user]);

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.peminjaman.index', ['search' => 'User US003']))
            ->assertOk()
            ->assertSee('Backup jaringan Puskesmas')
            ->assertDontSee('Dokumentasi kegiatan lapangan');

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.peminjaman.index', ['search' => 'Dokumentasi']))
            ->assertOk()
            ->assertSee('Dokumentasi kegiatan lapangan')
            ->assertDontSee('Backup jaringan Puskesmas');

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.peminjaman.index', ['search' => 'Laptop']))
            ->assertOk()
            ->assertSee('Dokumentasi kegiatan lapangan')
            ->assertSee('Backup jaringan Puskesmas');
    }

    #[Test]
    public function inventaris_bisa_filter_rentang_tanggal_pinjam(): void
    {
        $a = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $a->update(['keperluan' => 'Dokumentasi kegiatan lapangan', 'tanggal_pinjam' => '2026-07-01']);
        $b = $this->buatPeminjaman([$this->alat->id_peralatan]);
        $b->update(['keperluan' => 'Backup jaringan Puskesmas', 'tanggal_pinjam' => '2026-07-20']);

        $this->actingAs($this->inventaris)
            ->get(route('inventaris.peminjaman.index', ['start' => '2026-07-10', 'end' => '2026-07-31']))
            ->assertOk()
            ->assertSee('Backup jaringan Puskesmas')
            ->assertDontSee('Dokumentasi kegiatan lapangan');
    }

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

            'tanggal_pinjam'          => now()->format('Y-m-d'),
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

            Peralatan::whereKey($id)->decrement('stok', 1);
        }

        return $p;
    }
}
