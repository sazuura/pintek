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
        $this->validasiStok($peralatanIds, $jumlahArr);
        $peminjaman = DB::transaction(function () use ($header, $peralatanIds, $jumlahArr) {
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

    public function setujui(Peminjaman $peminjaman, User $inventaris, ?string $catatan = null): void
    {
        DB::transaction(function () use ($peminjaman, $catatan) {
            $peminjaman->loadMissing('items.peralatan');
            foreach ($peminjaman->items as $item) {
                $alat = $item->peralatan;
                if ($item->jumlah > $alat->stok) { 
                    throw new \RuntimeException("Gagal menyetujui. Stok {$alat->nama_peralatan} mendadak tidak mencukupi.");
                }
                $alat->decrement('stok', $item->jumlah);
            }
            $peminjaman->update([
                'status' => 'disetujui',
                'catatan_inventaris' => $catatan,
            ]);
        });
    }

    public function tolak(Peminjaman $peminjaman, User $inventaris, string $alasan): void
    {
        $peminjaman->update([
            'status' => 'ditolak',
            'catatan_inventaris' => $alasan,
        ]);
    }

    public function konfirmasiKembali(Peminjaman $peminjaman, User $inventaris): void
    {
        // PERBAIKAN: Validasi pembatasan gedung user dihapus.
        DB::transaction(function () use ($peminjaman) {
            $peminjaman->loadMissing('items.peralatan');
            foreach ($peminjaman->items as $item) {
                $item->peralatan->increment('stok', $item->jumlah);
            }
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
            $peminjaman->update([
                'status' => 'dibatalkan',
                'alasan_batal' => $alasan,
                'dibatalkan_at' => now(),
            ]);
        });

        $peminjaman->load(['items.peralatan', 'user']);
        
        // Tetap kelompokkan pesan berdasarkan gedung asal PERALATAN untuk teks notifikasi
        $itemPerGedung = $peminjaman->items->groupBy(fn($item) => $item->peralatan->gedung);
        
        // Kirim ke semua user ber-role inventaris yang aktif
        $daftarInventaris = User::where('role', 'inventaris')->where('status', 'active')->get();

        foreach ($itemPerGedung as $gedungPeralatan => $items) {
            $daftarPeralatan = $items->map(function ($item) {
                return "  - {$item->peralatan->nama_peralatan} (x{$item->jumlah})";
            })->join("\n");

            foreach ($daftarInventaris as $inventaris) {
                if (!$inventaris->nohp) continue;

                $pesan = $this->wa->templatePeminjamanDibatalkan(
                    namaInventaris: $inventaris->nama_user,
                    namaOperator: $peminjaman->user->nama_user,
                    gedung: $gedungPeralatan, // Menggunakan nama gedung dari peralatan
                    tanggalPinjam: $peminjaman->tanggal_pinjam->format('d/m/Y'),
                    tanggalKembali: $peminjaman->tanggal_kembali_rencana->format('d/m/Y'),
                    keperluan: $peminjaman->keperluan,
                    daftarPeralatan: $daftarPeralatan,
                    alasan: $alasan
                );
                $this->wa->kirim($inventaris->nomor_wa, $pesan);
            }
        }
    }

    private function validasiStok(array $peralatanIds, array $jumlahArr): void
    {
        foreach ($peralatanIds as $i => $id) {
            if (empty($id) || empty($jumlahArr[$i])) continue;
            $alat = Peralatan::findOrFail($id);
            if ((int) $jumlahArr[$i] > $alat->stok) {
                throw new \RuntimeException(
                    "Stok {$alat->nama_peralatan} tidak mencukupi. Tersedia: {$alat->stok}, diminta: {$jumlahArr[$i]}."
                );
            }
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

        // Kelompokkan daftar berdasarkan gedung asal PERALATAN
        $itemPerGedung = $peminjaman->items->groupBy(fn($item) => $item->peralatan->gedung);
        $daftarInventaris = User::where('role', 'inventaris')->where('status', 'active')->get();

        $terkaitJadwal = $peminjaman->penjadwalan
            ? "{$peminjaman->penjadwalan->judul_kegiatan} ({$peminjaman->penjadwalan->tanggal->format('d/m/Y')})"
            : null;

        foreach ($itemPerGedung as $gedungPeralatan => $items) {
            $daftarPeralatan = $items->map(function ($item) {
                return "  - {$item->peralatan->nama_peralatan} (x{$item->jumlah})";
            })->join("\n");

            foreach ($daftarInventaris as $inventaris) {
                if (!$inventaris->nohp) continue;

                $pesan = $this->wa->templatePeminjamanBaru(
                    namaInventaris: $inventaris->nama_user,
                    namaOperator: $peminjaman->user->nama_user,
                    gedung: $gedungPeralatan, // Menggunakan nama gedung dari peralatan
                    tanggalPinjam: $peminjaman->tanggal_pinjam->format('d/m/Y'),
                    tanggalKembali: $peminjaman->tanggal_kembali_rencana->format('d/m/Y'),
                    keperluan: $peminjaman->keperluan,
                    daftarPeralatan: $daftarPeralatan,
                    terkaitJadwal: $terkaitJadwal,
                );
                $this->wa->kirim($inventaris->nomor_wa, $pesan);
            }
        }
    }
}