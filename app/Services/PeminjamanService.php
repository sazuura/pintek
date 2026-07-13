<?php
namespace App\Services;

use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Helpers\IdGenerator;

class PeminjamanService
{
    public function __construct(private WhatsAppService $wa){}

    public function ajukan(array $header, array $peralatanIds, array $jumlahArr): Peminjaman
    {
        $peminjaman = DB::transaction(function () use ($header, $peralatanIds, $jumlahArr) {
            // Stok langsung dikurangi (direservasi) begitu diajukan - bukan menunggu di-ACC
            // inventaris - supaya operator tidak bisa terus-menerus mengajukan alat yang
            // sama selama stoknya masih terbaca penuh (mis. stok mouse 1 tapi diajukan
            // berkali-kali sebelum ada yang di-ACC/ditolak). Kalau nanti ditolak/dibatalkan,
            // stok dikembalikan lagi lewat tambahStokKembali().
            $this->kurangiStok($peralatanIds, $jumlahArr);

            $idPeminjaman = IdGenerator::next(Peminjaman::class, 'id_peminjaman', 'PMJ-');
            $peminjaman = Peminjaman::create(array_merge($header, [
                'id_peminjaman' => $idPeminjaman
            ]));
            $this->simpanItem($peminjaman, $peralatanIds, $jumlahArr);
            return $peminjaman;
        });

        $this->kirimNotifKeInventaris($peminjaman);
        return $peminjaman;
    }

    public function ubah(Peminjaman $peminjaman, array $header, array $peralatanIds, array $jumlahArr): Peminjaman
    {
        if (!$peminjaman->isMenunggu()) {
            throw new \RuntimeException('Hanya pengajuan berstatus "Menunggu" yang bisa diubah.');
        }
        $this->pastikanBelumAdaItemDiputuskan($peminjaman);

        $peminjaman = DB::transaction(function () use ($peminjaman, $header, $peralatanIds, $jumlahArr) {
            // Kembalikan dulu reservasi stok dari item LAMA, baru validasi & reservasi
            // untuk item BARU - supaya kalau alatnya sama dengan sebelumnya, stok tidak
            // dianggap "kurang" gara-gara masih menghitung reservasi lama yang sebentar
            // lagi toh akan diganti.
            $peminjaman->loadMissing('items');
            $this->tambahStokKembali($peminjaman->items);
            $this->kurangiStok($peralatanIds, $jumlahArr);

            $peminjaman->update($header);
            $peminjaman->items()->delete();
            $this->simpanItem($peminjaman, $peralatanIds, $jumlahArr);
            return $peminjaman->fresh();
        });

        $this->kirimNotifPerubahanKeInventaris($peminjaman);
        return $peminjaman;
    }

    /**
     * Setujui SATU item alat saja - alat lain dalam pengajuan yang sama tidak ikut
     * berubah statusnya. Stok tidak disentuh (sudah direservasi sejak diajukan()).
     */
    public function setujuiItem(PeminjamanItem $item): void
    {
        if ($item->status !== 'diajukan') {
            throw new \RuntimeException('Alat ini sudah diputuskan sebelumnya.');
        }
        DB::transaction(function () use ($item) {
            $item->update(['status' => 'disetujui']);
            $this->rekomputeStatusPeminjaman($item->peminjaman);
        });
    }

    /**
     * Tolak SATU item alat saja - stok reservasi khusus item ini dilepas kembali,
     * alat lain dalam pengajuan yang sama tidak ikut terdampak.
     */
    public function tolakItem(PeminjamanItem $item, string $alasan): void
    {
        if ($item->status !== 'diajukan') {
            throw new \RuntimeException('Alat ini sudah diputuskan sebelumnya.');
        }
        DB::transaction(function () use ($item, $alasan) {
            Peralatan::whereKey($item->id_peralatan)->increment('stok', $item->jumlah);
            $item->update(['status' => 'ditolak']);
            $item->peminjaman->update(['catatan_inventaris' => $alasan]);
            $this->rekomputeStatusPeminjaman($item->peminjaman);
        });
    }

    /**
     * Setujui semua item yang masih "diajukan" sekaligus - dipakai tombol cepat di
     * dashboard/kartu mobile yang tidak butuh kontrol per-alat.
     */
    public function setujui(Peminjaman $peminjaman, User $inventaris, ?string $catatan = null): void
    {
        DB::transaction(function () use ($peminjaman, $catatan) {
            $peminjaman->loadMissing('items');
            foreach ($peminjaman->items->where('status', 'diajukan') as $item) {
                $item->update(['status' => 'disetujui']);
            }
            if ($catatan !== null) {
                $peminjaman->update(['catatan_inventaris' => $catatan]);
            }
            $this->rekomputeStatusPeminjaman($peminjaman);
        });
    }

    /** Tolak semua item yang masih "diajukan" sekaligus (tombol cepat, lihat setujui()). */
    public function tolak(Peminjaman $peminjaman, User $inventaris, string $alasan): void
    {
        DB::transaction(function () use ($peminjaman, $alasan) {
            $peminjaman->loadMissing('items');
            foreach ($peminjaman->items->where('status', 'diajukan') as $item) {
                Peralatan::whereKey($item->id_peralatan)->increment('stok', $item->jumlah);
                $item->update(['status' => 'ditolak']);
            }
            $peminjaman->update(['catatan_inventaris' => $alasan]);
            $this->rekomputeStatusPeminjaman($peminjaman);
        });
    }

    /**
     * Turunkan status peminjaman induk dari status per-item alatnya: masih ada yang
     * "diajukan" -> induk tetap "diajukan" (menunggu); semua sudah diputuskan & ada
     * yang disetujui -> "disetujui"; semua ditolak -> "ditolak".
     */
    private function rekomputeStatusPeminjaman(Peminjaman $peminjaman): void
    {
        $peminjaman->loadMissing('items');
        $items = $peminjaman->items;

        if ($items->contains(fn($i) => $i->status === 'diajukan')) {
            $status = 'diajukan';
        } elseif ($items->contains(fn($i) => $i->status === 'disetujui')) {
            $status = 'disetujui';
        } else {
            $status = 'ditolak';
        }

        if ($peminjaman->status !== $status) {
            $peminjaman->update(['status' => $status]);
        }
    }

    /**
     * Item yang statusnya sudah "ditolak" sebelumnya stoknya SUDAH dikembalikan saat
     * ditolak - kalau ikut dikembalikan lagi di sini stoknya akan double count.
     */
    private function tambahStokKembaliUntukItemBelumDikembalikan($items): void
    {
        foreach ($items as $item) {
            if ($item->status === 'ditolak') continue;
            Peralatan::whereKey($item->id_peralatan)->increment('stok', $item->jumlah);
        }
    }

    /** Operator tidak boleh ubah/batalkan pengajuan begitu ada alat yang sudah diputuskan inventaris. */
    private function pastikanBelumAdaItemDiputuskan(Peminjaman $peminjaman): void
    {
        $peminjaman->loadMissing('items');
        if ($peminjaman->items->contains(fn($i) => $i->status !== 'diajukan')) {
            throw new \RuntimeException('Sudah ada alat yang diproses inventaris, pengajuan ini tidak bisa diubah/dibatalkan lagi.');
        }
    }

    public function konfirmasiKembali(Peminjaman $peminjaman, User $inventaris): void
    {
        // PERBAIKAN: Validasi pembatasan gedung user dihapus.
        // Stok dikembalikan di sini karena alatnya baru sungguhan bebas dipakai lagi
        // setelah fisiknya dikembalikan (beda dari tolak/batalkan yang mengembalikan stok
        // begitu reservasinya dilepas, karena alatnya memang tidak jadi dipakai sama sekali).
        DB::transaction(function () use ($peminjaman) {
            $peminjaman->loadMissing('items');
            $this->tambahStokKembaliUntukItemBelumDikembalikan($peminjaman->items);
            $peminjaman->update([
                'status' => 'dikembalikan',
                'tanggal_kembali_aktual' => now()->toDateString(),
            ]);
        });
    }

    public function batalkan(Peminjaman $peminjaman, string $alasan): void
    {
        if (!$peminjaman->isMenunggu()) {
            throw new \RuntimeException('Hanya pengajuan berstatus "Menunggu" yang bisa dibatalkan.');
        }
        $this->pastikanBelumAdaItemDiputuskan($peminjaman);

        DB::transaction(function () use ($peminjaman, $alasan) {
            $peminjaman->loadMissing('items');
            $this->tambahStokKembali($peminjaman->items);
            $peminjaman->update([
                'status' => 'dibatalkan',
                'alasan_batal' => $alasan,
                'dibatalkan_at' => now(),
            ]);
        });

        $peminjaman->load(['items.peralatan', 'user']);

        // Kirim ke semua user ber-role inventaris yang aktif
        $daftarInventaris = User::where('role', 'inventaris')->where('status', 'active')->get();
        [$daftarPeralatan, $gedungLabel] = $this->kelompokkanPerGedung($peminjaman->items);

        foreach ($daftarInventaris as $inventaris) {
            if (!$inventaris->nohp) continue;

            $pesan = $this->wa->templatePeminjamanDibatalkan(
                namaInventaris: $inventaris->nama_user,
                namaOperator: $peminjaman->user->nama_user,
                gedung: $gedungLabel,
                tanggalPinjam: $peminjaman->tanggal_pinjam->format('d/m/Y'),
                tanggalKembali: $peminjaman->tanggal_kembali_rencana->format('d/m/Y'),
                keperluan: $peminjaman->keperluan,
                daftarPeralatan: $daftarPeralatan,
                alasan: $alasan
            );
            $this->wa->kirim($inventaris->nomor_wa, $pesan);
        }
    }

    /**
     * Validasi ketersediaan stok SEKALIGUS langsung mereservasi (mengurangi) stoknya.
     * Baris peralatan dikunci (lockForUpdate) selama transaksi berjalan supaya dua
     * pengajuan yang masuk bersamaan untuk alat yang sama tidak bisa lolos validasi
     * berdasarkan angka stok yang sama-sama sudah basi (race condition).
     */
    private function kurangiStok(array $peralatanIds, array $jumlahArr): void
    {
        foreach ($peralatanIds as $i => $id) {
            if (empty($id) || empty($jumlahArr[$i])) continue;
            $jumlah = (int) $jumlahArr[$i];
            $alat = Peralatan::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($jumlah > $alat->stok) {
                throw new \RuntimeException(
                    "Stok {$alat->nama_peralatan} tidak mencukupi. Tersedia: {$alat->stok}, diminta: {$jumlah}."
                );
            }
            $alat->decrement('stok', $jumlah);
        }
    }

    /**
     * Kembalikan stok yang sebelumnya direservasi item peminjaman (dipanggil saat
     * pengajuan ditolak/dibatalkan/diedit/dikembalikan) - alat itemnya sendiri bisa
     * saja sudah tidak ada (dihapus inventaris), makanya query diabaikan diam-diam
     * kalau tidak ketemu, bukan dianggap error.
     */
    private function tambahStokKembali($items): void
    {
        foreach ($items as $item) {
            Peralatan::whereKey($item->id_peralatan)->increment('stok', $item->jumlah);
        }
    }

    private function simpanItem(Peminjaman $peminjaman, array $peralatanIds, array $jumlahArr): void
    {
        foreach ($peralatanIds as $i => $id) {
            if (empty($id) || empty($jumlahArr[$i])) continue;
            PeminjamanItem::create([
                'id_peminjaman' => $peminjaman->id_peminjaman,
                'id_peralatan' => $id,
                'jumlah' => $jumlahArr[$i],
            ]);
        }
    }

    private function kirimNotifKeInventaris(Peminjaman $peminjaman): void
    {
        $peminjaman->load(['items.peralatan', 'user', 'penjadwalan']);

        $daftarInventaris = User::where('role', 'inventaris')->where('status', 'active')->get();
        [$daftarPeralatan, $gedungLabel] = $this->kelompokkanPerGedung($peminjaman->items);

        $terkaitJadwal = $peminjaman->penjadwalan
            ? "{$peminjaman->penjadwalan->judul_kegiatan} ({$peminjaman->penjadwalan->tanggal->format('d/m/Y')})"
            : null;

        foreach ($daftarInventaris as $inventaris) {
            if (!$inventaris->nohp) continue;

            $pesan = $this->wa->templatePeminjamanBaru(
                namaInventaris: $inventaris->nama_user,
                namaOperator: $peminjaman->user->nama_user,
                gedung: $gedungLabel,
                tanggalPinjam: $peminjaman->tanggal_pinjam->format('d/m/Y'),
                tanggalKembali: $peminjaman->tanggal_kembali_rencana->format('d/m/Y'),
                keperluan: $peminjaman->keperluan,
                daftarPeralatan: $daftarPeralatan,
                terkaitJadwal: $terkaitJadwal,
            );
            $this->wa->kirim($inventaris->nomor_wa, $pesan);
        }
    }

    private function kirimNotifPerubahanKeInventaris(Peminjaman $peminjaman): void
    {
        $peminjaman->load(['items.peralatan', 'user', 'penjadwalan']);

        $daftarInventaris = User::where('role', 'inventaris')->where('status', 'active')->get();
        [$daftarPeralatan, $gedungLabel] = $this->kelompokkanPerGedung($peminjaman->items);

        $terkaitJadwal = $peminjaman->penjadwalan
            ? "{$peminjaman->penjadwalan->judul_kegiatan} ({$peminjaman->penjadwalan->tanggal->format('d/m/Y')})"
            : null;

        foreach ($daftarInventaris as $inventaris) {
            if (!$inventaris->nohp) continue;

            $pesan = $this->wa->templatePeminjamanDiubah(
                namaInventaris: $inventaris->nama_user,
                namaOperator: $peminjaman->user->nama_user,
                gedung: $gedungLabel,
                tanggalPinjam: $peminjaman->tanggal_pinjam->format('d/m/Y'),
                tanggalKembali: $peminjaman->tanggal_kembali_rencana->format('d/m/Y'),
                keperluan: $peminjaman->keperluan,
                daftarPeralatan: $daftarPeralatan,
                terkaitJadwal: $terkaitJadwal,
            );
            $this->wa->kirim($inventaris->nomor_wa, $pesan);
        }
    }

    /**
     * Kelompokkan item peminjaman berdasarkan gedung asal peralatan jadi SATU daftar
     * alat (diberi sub-judul per gedung kalau lebih dari satu gedung) beserta satu
     * label gedung untuk header pesan. Dulu tiap kelompok gedung dikirim sebagai pesan
     * WA terpisah padahal semua inventaris tetap menerima semuanya (belum ada
     * pemisahan penerima per gedung) - sekarang digabung jadi SATU pesan per pengajuan.
     *
     * @param  \Illuminate\Support\Collection  $items  Koleksi PeminjamanItem (relasi peralatan sudah di-load)
     * @return array{0: string, 1: string}  [$daftarPeralatan, $gedungLabel]
     */
    private function kelompokkanPerGedung($items): array
    {
        $itemPerGedung = $items->groupBy(fn($item) => $item->peralatan->gedung);
        $satuGedungSaja = $itemPerGedung->count() === 1;

        $daftarPeralatan = $itemPerGedung->map(function ($itemsGedung, $gedung) use ($satuGedungSaja) {
            $baris = $itemsGedung->map(fn($item) => "  - {$item->peralatan->nama_peralatan} (x{$item->jumlah})")->join("\n");
            return $satuGedungSaja ? $baris : "*{$gedung}:*\n{$baris}";
        })->join("\n\n");

        $gedungLabel = $satuGedungSaja
            ? $itemPerGedung->keys()->first()
            : $itemPerGedung->keys()->join(' & ');

        return [$daftarPeralatan, $gedungLabel];
    }
}