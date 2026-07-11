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

    public function setujui(Peminjaman $peminjaman, User $inventaris, ?string $catatan = null): void
    {
        // Stok sudah direservasi sejak status "diajukan" (lihat ajukan()), jadi menyetujui
        // di sini cukup ubah status - tidak ada lagi pengurangan stok kedua kalinya.
        $peminjaman->update([
            'status' => 'disetujui',
            'catatan_inventaris' => $catatan,
        ]);
    }

    public function tolak(Peminjaman $peminjaman, User $inventaris, string $alasan): void
    {
        DB::transaction(function () use ($peminjaman, $alasan) {
            $peminjaman->loadMissing('items');
            $this->tambahStokKembali($peminjaman->items);
            $peminjaman->update([
                'status' => 'ditolak',
                'catatan_inventaris' => $alasan,
            ]);
        });
    }

    public function konfirmasiKembali(Peminjaman $peminjaman, User $inventaris): void
    {
        // PERBAIKAN: Validasi pembatasan gedung user dihapus.
        // Stok dikembalikan di sini karena alatnya baru sungguhan bebas dipakai lagi
        // setelah fisiknya dikembalikan (beda dari tolak/batalkan yang mengembalikan stok
        // begitu reservasinya dilepas, karena alatnya memang tidak jadi dipakai sama sekali).
        DB::transaction(function () use ($peminjaman) {
            $peminjaman->loadMissing('items');
            $this->tambahStokKembali($peminjaman->items);
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