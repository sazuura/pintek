<?php
namespace App\Services;
use App\Helpers\IdGenerator;
use App\Models\Penjadwalan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * PenjadwalanService
 * Menangani semua business logic terkait jadwal rapat:
 *   - Validasi bentrok waktu per operator
 *   - Simpan jadwal dalam satu transaksi
 *   - Kirim notif WA ke operator yang ditugaskan
 *
 */
class PenjadwalanService
{
    public function __construct(private WhatsAppService $wa) {}

    public function buat(array $data, array $operatorIds, array $peralatanSync = []): Penjadwalan
    {
        $this->validasiBentrokOperator($operatorIds, $data['tanggal'], $data['waktu_mulai'], $data['waktu_selesai']);

        return DB::transaction(function () use ($data, $operatorIds, $peralatanSync) {
            $jadwal = $this->simpanJadwal($data);
            $jadwal->operators()->sync($operatorIds);
            $jadwal->peralatanReferensi()->sync($peralatanSync);
            $this->kirimNotifKeOperator($jadwal, $operatorIds);
            return $jadwal;
        });
    }

    public function ubah(Penjadwalan $jadwal, array $data, array $operatorIds, array $peralatanSync = []): Penjadwalan
    {
        $this->validasiBentrokOperator($operatorIds, $data['tanggal'], $data['waktu_mulai'], $data['waktu_selesai'], $jadwal->id_penjadwalan);

        return DB::transaction(function () use ($jadwal, $data, $operatorIds, $peralatanSync) {
            $jadwal->update($data);
            $jadwal->operators()->sync($operatorIds);
            $jadwal->peralatanReferensi()->sync($peralatanSync);
            $this->kirimNotifKeOperator($jadwal, $operatorIds);
            return $jadwal->fresh();
        });
    }

    public function hapus(Penjadwalan $jadwal): void
    {
        $jadwal->delete();
    }

    public function batalkan(Penjadwalan $jadwal, string $alasan): void
    {
        if ($jadwal->isDibatalkan()) {
            throw new \RuntimeException('Jadwal ini sudah dibatalkan sebelumnya.');
        }

        $jadwal->loadMissing('operators');

        $jadwal->update([
            'status'        => 'dibatalkan',
            'alasan_batal'  => $alasan,
            'dibatalkan_at' => now(),
        ]);

        // Kirim notifikasi pembatalan ke semua operator yang ditugaskan
        foreach ($jadwal->operators as $operator) {
            if (!$operator->nohp) continue;
            $pesan = $this->wa->templateJadwalDibatalkan(
                $operator->nama_user,
                $jadwal->tanggal->format('d/m/Y'),
                $jadwal->waktu_mulai,
                $jadwal->waktu_selesai,
                $jadwal->judul_kegiatan,
                $jadwal->platform,
                $alasan,
                $jadwal->keterangan ?? '-'
            );
            $this->wa->kirim($operator->nohp, $pesan);
        }
    }

    private function validasiBentrokOperator(array $operatorIds, string $tanggal, string $mulai, string $selesai, ?string $excludeId = null): void
    {
        foreach ($operatorIds as $id) {
            $bentrok = Penjadwalan::bentrok($tanggal, $mulai, $selesai, $excludeId)
                ->whereHas('operators', fn($q) => $q->where('users.id_user', $id))
                ->exists();
            if ($bentrok) {
                $nama = User::find($id)?->nama_user ?? $id;
                throw new \RuntimeException("Operator {$nama} sudah memiliki jadwal di waktu yang sama.");
            }
        }
    }

    private function simpanJadwal(array $data): Penjadwalan
    {
        return Penjadwalan::create(array_merge($data, [
            'id_penjadwalan' => IdGenerator::next(Penjadwalan::class, 'id_penjadwalan', 'JDW-'),
        ]));
    }

    private function kirimNotifKeOperator(Penjadwalan $jadwal, array $operatorIds): void
    {
        foreach (User::whereIn('id_user', $operatorIds)->get() as $operator) {
            if (!$operator->nohp) continue;
            $pesan = $this->wa->templateJadwalBaru(
                $operator->nama_user,
                $jadwal->tanggal->format('d/m/Y'),
                $jadwal->waktu_mulai,
                $jadwal->waktu_selesai,
                $jadwal->judul_kegiatan,
                $jadwal->platform,
                $jadwal->keterangan ?? '-'
            );
            $this->wa->kirim($operator->nohp, $pesan);
        }
    }
}
