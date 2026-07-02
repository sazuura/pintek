<?php
namespace App\Services;
use App\Helpers\IdGenerator;
use App\Models\Absensi;
use App\Models\JadwalPeralatan;
use App\Models\Peralatan;
use App\Models\Penjadwalan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * PenjadwalanService
 * Menangani semua business logic terkait jadwal rapat:
 *   - Validasi bentrok waktu per operator
 *   - Simpan jadwal + absensi + peralatan dalam satu transaksi
 *   - Kirim notif WA ke operator yang ditugaskan
 * 
 */
class PenjadwalanService
{
    public function __construct(private WhatsAppService $wa) {}

    public function buat(array $data, array $operatorIds, array $peralatanIds, array $jumlahArr): Penjadwalan
    {
        $this->validasiBentrokOperator($operatorIds, $data['tanggal'], $data['waktu_mulai'], $data['waktu_selesai']);
        
        return DB::transaction(function () use ($data, $operatorIds, $peralatanIds, $jumlahArr) {
            $jadwal = $this->simpanJadwal($data);
            $this->simpanAbsensi($jadwal, $operatorIds, $data['tanggal']);
            $this->simpanPeralatanDanPotongStok($jadwal, $peralatanIds, $jumlahArr);
            $this->kirimNotifKeOperator($jadwal, $operatorIds);
            return $jadwal;
        });
    }

    public function ubah(Penjadwalan $jadwal, array $data, array $operatorIds, array $peralatanIds, array $jumlahArr): Penjadwalan
    {
        return DB::transaction(function () use ($jadwal, $data, $operatorIds, $peralatanIds, $jumlahArr) {
            $jadwal->loadMissing('jadwalPeralatan.peralatan');
            foreach ($jadwal->jadwalPeralatan as $jp) {
                $jp->peralatan->increment('stok', $jp->jumlah);
            }
            $jadwal->update($data);
            Absensi::where('id_penjadwalan', $jadwal->id_penjadwalan)->delete();
            $this->simpanAbsensi($jadwal, $operatorIds, $data['tanggal']);
            JadwalPeralatan::where('id_penjadwalan', $jadwal->id_penjadwalan)->delete();
            $this->simpanPeralatanDanPotongStok($jadwal, $peralatanIds, $jumlahArr);
            $this->kirimNotifKeOperator($jadwal, $operatorIds);
            return $jadwal->fresh();
        });
    }

    public function hapus(Penjadwalan $jadwal): void
    {
        DB::transaction(function () use ($jadwal) {
            $jadwal->loadMissing('jadwalPeralatan.peralatan');
            foreach ($jadwal->jadwalPeralatan as $jp) {
                $jp->peralatan->increment('stok', $jp->jumlah);
            }
            $jadwal->delete();
        });
    }

    public function batalkan(Penjadwalan $jadwal, string $alasan): void
    {
        if ($jadwal->isDibatalkan()) {
            throw new \RuntimeException('Jadwal ini sudah dibatalkan sebelumnya.');
        }

        DB::transaction(function () use ($jadwal, $alasan) {
            $jadwal->loadMissing('jadwalPeralatan.peralatan');
            foreach ($jadwal->jadwalPeralatan as $jp) {
                $jp->peralatan->increment('stok', $jp->jumlah);
            }
            $jadwal->update([
                'status'        => 'dibatalkan',
                'alasan_batal'  => $alasan,
                'dibatalkan_at' => now(),
            ]);
        });

        foreach ($jadwal->absensi as $a) {
            $operator = $a->user;
            if (!$operator?->nohp) continue;
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

    private function simpanPeralatanDanPotongStok(Penjadwalan $jadwal, array $peralatanIds, array $jumlahArr): void
    {
        foreach ($peralatanIds as $i => $idPeralatan) {
            if (empty($idPeralatan) || empty($jumlahArr[$i])) continue;
            
            $alat = Peralatan::findOrFail($idPeralatan);
            $qtyRequested = (int) $jumlahArr[$i];

            if ($qtyRequested > $alat->stok) {
                throw new \RuntimeException(
                    "Stok {$alat->nama_peralatan} tidak mencukupi. Tersedia: {$alat->stok}, diminta: {$qtyRequested}."
                );
            }

            // Ubah Angka fisik di DB
            $alat->decrement('stok', $qtyRequested);

            JadwalPeralatan::create([
                'id_penjadwalan'    => $jadwal->id_penjadwalan,
                'id_peralatan'      => $idPeralatan,
                'jumlah'            => $qtyRequested,
                'status_pemasangan' => 'belum_dipasang',
            ]);
        }
    }

    private function validasiBentrokOperator(array $operatorIds, string $tanggal, string $mulai, string $selesai): void
    {
        foreach ($operatorIds as $id) {
            $bentrok = Penjadwalan::bentrok($tanggal, $mulai, $selesai)
                ->whereHas('absensi', fn($q) => $q->where('id_user', $id))
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

    private function simpanAbsensi(Penjadwalan $jadwal, array $operatorIds, string $tanggal): void
    {
        foreach ($operatorIds as $id) {
            if (empty($id)) continue;
            Absensi::create([
                'id_penjadwalan' => $jadwal->id_penjadwalan,
                'id_user'        => $id,
                'tanggal'        => $tanggal,
                'status'         => Absensi::STATUS_PENDING,
                'validated'      => false,
            ]);
        }
    }

    private function kirimNotifKeOperator(Penjadwalan $jadwal, array $operatorIds): void
    {
        foreach ($operatorIds as $id) {
            $operator = User::find($id);
            if (!$operator?->nohp) continue;
            $pesan = $this->wa->templateJadwalBaru(
                $operator->nama_user,
                $jadwal->tanggal->format('d/m/Y'),
                $jadwal->waktu_mulai,
                $jadwal->waktu_selesai,
                $jadwal->judul_kegiatan,
                $jadwal->platform,
                $jadwal->keterangan
            );
            $this->wa->kirim($operator->nohp, $pesan);
        }
    }
}
