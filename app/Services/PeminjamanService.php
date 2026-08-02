<?php
namespace App\Services;

use App\Mail\PeminjamanBaruMail;
use App\Mail\PeminjamanDiubahMail;
use App\Mail\PeminjamanDibatalkanMail;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\IdGenerator;

class PeminjamanService
{

    public function ajukan(array $header, array $peralatanIds, array $jumlahArr): Peminjaman
    {
        $peminjaman = DB::transaction(function () use ($header, $peralatanIds, $jumlahArr) {

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

    public function setujuiItem(PeminjamanItem $item): void
    {
        $item->loadMissing('peminjaman');
        if (!$item->peminjaman->isMenunggu()) {
            throw new \RuntimeException('Pengajuan ini sudah dibatalkan, tidak bisa diproses.');
        }
        if ($item->status !== 'diajukan') {
            throw new \RuntimeException('Alat ini sudah diputuskan sebelumnya.');
        }
        DB::transaction(function () use ($item) {
            $item->update(['status' => 'disetujui']);
            $this->rekomputeStatusPeminjaman($item->peminjaman);
        });
    }

    public function tolakItem(PeminjamanItem $item, string $alasan): void
    {
        $item->loadMissing('peminjaman');
        if (!$item->peminjaman->isMenunggu()) {
            throw new \RuntimeException('Pengajuan ini sudah dibatalkan, tidak bisa diproses.');
        }
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

    public function setujui(Peminjaman $peminjaman, User $inventaris, ?string $catatan = null): void
    {
        if (!$peminjaman->isMenunggu()) {
            throw new \RuntimeException('Pengajuan ini sudah dibatalkan, tidak bisa diproses.');
        }
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

    public function tolak(Peminjaman $peminjaman, User $inventaris, string $alasan): void
    {
        if (!$peminjaman->isMenunggu()) {
            throw new \RuntimeException('Pengajuan ini sudah dibatalkan, tidak bisa diproses.');
        }
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

    private function rekomputeStatusPeminjaman(Peminjaman $peminjaman): void
    {

        $peminjaman->load('items');
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

    private function tambahStokKembaliUntukItemBelumDikembalikan($items): void
    {
        foreach ($items as $item) {
            if ($item->status === 'ditolak') continue;
            Peralatan::whereKey($item->id_peralatan)->increment('stok', $item->jumlah);
        }
    }

    private function pastikanBelumAdaItemDiputuskan(Peminjaman $peminjaman): void
    {
        $peminjaman->loadMissing('items');
        if ($peminjaman->items->contains(fn($i) => $i->status !== 'diajukan')) {
            throw new \RuntimeException('Sudah ada alat yang diproses inventaris, pengajuan ini tidak bisa diubah/dibatalkan lagi.');
        }
    }

    public function konfirmasiKembali(Peminjaman $peminjaman, User $inventaris): void
    {

        if ($peminjaman->tanggal_pinjam->isFuture()) {
            throw new \RuntimeException(
                'Peminjaman ini baru dimulai ' . $peminjaman->tanggal_pinjam->translatedFormat('d F Y') . ' - belum bisa dikonfirmasi kembali.'
            );
        }

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

        $peminjaman->load(['items.peralatan', 'user', 'penjadwalan']);

        $daftarInventaris = User::where('role', 'inventaris')->where('status', 'active')->get();
        [$peralatanPerGedung, $gedungLabel] = $this->kelompokkanPerGedung($peminjaman->items);
        $terkaitJadwal = $peminjaman->penjadwalan
            ? "{$peminjaman->penjadwalan->judul_kegiatan} ({$peminjaman->penjadwalan->tanggal->translatedFormat('l, d F Y')})"
            : null;

        foreach ($daftarInventaris as $inventaris) {
            Mail::to($inventaris->email)->send(new PeminjamanDibatalkanMail(
                namaInventaris: $inventaris->nama_user,
                namaOperator: $peminjaman->user->nama_user,
                gedung: $gedungLabel,
                tanggalPinjam: $peminjaman->tanggal_pinjam->translatedFormat('l, d F Y'),
                tanggalKembali: $peminjaman->tanggal_kembali_rencana->translatedFormat('l, d F Y'),
                keperluan: $peminjaman->keperluan,
                peralatanPerGedung: $peralatanPerGedung,
                alasan: $alasan,
                terkaitJadwal: $terkaitJadwal,
            ));
        }
    }

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
        [$peralatanPerGedung, $gedungLabel] = $this->kelompokkanPerGedung($peminjaman->items);

        $terkaitJadwal = $peminjaman->penjadwalan
            ? "{$peminjaman->penjadwalan->judul_kegiatan} ({$peminjaman->penjadwalan->tanggal->translatedFormat('l, d F Y')})"
            : null;

        foreach ($daftarInventaris as $inventaris) {
            Mail::to($inventaris->email)->send(new PeminjamanBaruMail(
                namaInventaris: $inventaris->nama_user,
                namaOperator: $peminjaman->user->nama_user,
                gedung: $gedungLabel,
                tanggalPinjam: $peminjaman->tanggal_pinjam->translatedFormat('l, d F Y'),
                tanggalKembali: $peminjaman->tanggal_kembali_rencana->translatedFormat('l, d F Y'),
                keperluan: $peminjaman->keperluan,
                peralatanPerGedung: $peralatanPerGedung,
                terkaitJadwal: $terkaitJadwal,
            ));
        }
    }

    private function kirimNotifPerubahanKeInventaris(Peminjaman $peminjaman): void
    {
        $peminjaman->load(['items.peralatan', 'user', 'penjadwalan']);

        $daftarInventaris = User::where('role', 'inventaris')->where('status', 'active')->get();
        [$peralatanPerGedung, $gedungLabel] = $this->kelompokkanPerGedung($peminjaman->items);

        $terkaitJadwal = $peminjaman->penjadwalan
            ? "{$peminjaman->penjadwalan->judul_kegiatan} ({$peminjaman->penjadwalan->tanggal->translatedFormat('l, d F Y')})"
            : null;

        foreach ($daftarInventaris as $inventaris) {
            Mail::to($inventaris->email)->send(new PeminjamanDiubahMail(
                namaInventaris: $inventaris->nama_user,
                namaOperator: $peminjaman->user->nama_user,
                gedung: $gedungLabel,
                tanggalPinjam: $peminjaman->tanggal_pinjam->translatedFormat('l, d F Y'),
                tanggalKembali: $peminjaman->tanggal_kembali_rencana->translatedFormat('l, d F Y'),
                keperluan: $peminjaman->keperluan,
                peralatanPerGedung: $peralatanPerGedung,
                terkaitJadwal: $terkaitJadwal,
            ));
        }
    }

    private function kelompokkanPerGedung($items): array
    {
        $itemPerGedung = $items->groupBy(fn($item) => $item->peralatan->gedung);

        $peralatanPerGedung = $itemPerGedung->map(
            fn($itemsGedung) => $itemsGedung->map(fn($item) => [
                'nama'   => $item->peralatan->nama_peralatan,
                'jumlah' => $item->jumlah,
            ])->all()
        )->all();

        $gedungLabel = $itemPerGedung->count() === 1
            ? $itemPerGedung->keys()->first()
            : $itemPerGedung->keys()->join(' & ');

        return [$peralatanPerGedung, $gedungLabel];
    }
}