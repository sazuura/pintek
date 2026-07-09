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

        $operatorLamaIds = $jadwal->operators()->pluck('users.id_user')->all();

        // Dibandingkan manual (bukan $jadwal->wasChanged()) karena waktu_mulai/waktu_selesai
        // kolom TIME - MySQL menyimpan "09:00:00" tapi form submit "09:00", jadi
        // wasChanged() akan SELALU melihat itu sebagai "berubah" walau user tidak
        // mengubah apa-apa. Fingerprint di sini menormalisasi format dulu sebelum
        // dibandingkan, supaya notifikasi "jadwal diubah" tidak salah terkirim.
        $fingerprintLama = $this->fingerprintDetail(
            $jadwal->judul_kegiatan, $jadwal->tanggal->format('Y-m-d'), $jadwal->waktu_mulai,
            $jadwal->waktu_selesai, $jadwal->platform, $jadwal->keterangan
        );

        return DB::transaction(function () use ($jadwal, $data, $operatorIds, $peralatanSync, $operatorLamaIds, $fingerprintLama) {
            $jadwal->update($data);
            $fingerprintBaru = $this->fingerprintDetail(
                $data['judul_kegiatan'], $data['tanggal'], $data['waktu_mulai'],
                $data['waktu_selesai'], $data['platform'], $data['keterangan'] ?? null
            );
            $adaPerubahanDetail = $fingerprintLama !== $fingerprintBaru;

            $jadwal->operators()->sync($operatorIds);
            $jadwal->peralatanReferensi()->sync($peralatanSync);
            $this->kirimNotifPerubahan($jadwal, $operatorLamaIds, $operatorIds, $adaPerubahanDetail);
            return $jadwal->fresh();
        });
    }

    private function fingerprintDetail(string $judul, string $tanggal, string $waktuMulai, string $waktuSelesai, string $platform, ?string $keterangan): array
    {
        return [
            'judul_kegiatan' => $judul,
            'tanggal'        => \Carbon\Carbon::parse($tanggal)->format('Y-m-d'),
            'waktu_mulai'    => substr($waktuMulai, 0, 5),
            'waktu_selesai'  => substr($waktuSelesai, 0, 5),
            'platform'       => $platform,
            'keterangan'     => $keterangan ?? '',
        ];
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
            $this->wa->kirim($operator->nomor_wa, $pesan);
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
        $daftarPeralatan = $this->formatPeralatanReferensi($jadwal);

        foreach (User::whereIn('id_user', $operatorIds)->get() as $operator) {
            if (!$operator->nohp) continue;
            $pesan = $this->wa->templateJadwalBaru(
                $operator->nama_user,
                $jadwal->tanggal->format('d/m/Y'),
                $jadwal->waktu_mulai,
                $jadwal->waktu_selesai,
                $jadwal->judul_kegiatan,
                $jadwal->platform,
                $jadwal->keterangan ?? '-',
                $daftarPeralatan
            );
            $this->wa->kirim($operator->nomor_wa, $pesan);
        }
    }

    /**
     * Format daftar peralatan referensi (catatan acuan admin, bukan pengajuan
     * peminjaman - lihat form Tambah/Edit Jadwal) untuk ditampilkan di notif WA,
     * diposisikan di bawah baris Keterangan. Null kalau jadwal tidak punya
     * rekomendasi peralatan sama sekali (baris ini tidak ditampilkan).
     */
    private function formatPeralatanReferensi(Penjadwalan $jadwal): ?string
    {
        $items = $jadwal->load('peralatanReferensi')->peralatanReferensi;
        if ($items->isEmpty()) {
            return null;
        }

        return $items->map(fn($p) => "   - {$p->nama_peralatan} (x{$p->pivot->jumlah})")->implode("\n");
    }

    /**
     * Operator yang BARU ditambahkan di edit ini dapat notif "jadwal baru" (baru
     * pertama kali ditugaskan), sementara operator yang SUDAH ada sebelumnya dan
     * tetap ditugaskan cuma dinotif "jadwal diubah" - dan hanya kalau memang ada
     * detail yang berubah (bukan cuma re-sync operator/peralatan tanpa perubahan).
     * Ini mencegah operator lama dapat notif "jadwal baru" yang salah/membingungkan
     * tiap kali admin edit jadwal.
     */
    private function kirimNotifPerubahan(Penjadwalan $jadwal, array $operatorLamaIds, array $operatorBaruIds, bool $adaPerubahanDetail): void
    {
        $operatorBaruSaja = array_diff($operatorBaruIds, $operatorLamaIds);
        $operatorTetap    = array_intersect($operatorBaruIds, $operatorLamaIds);

        $this->kirimNotifKeOperator($jadwal, $operatorBaruSaja);

        if (!$adaPerubahanDetail) {
            return;
        }

        $daftarPeralatan = $this->formatPeralatanReferensi($jadwal);

        foreach (User::whereIn('id_user', $operatorTetap)->get() as $operator) {
            if (!$operator->nohp) continue;
            $pesan = $this->wa->templateJadwalDiubah(
                $operator->nama_user,
                $jadwal->tanggal->format('d/m/Y'),
                $jadwal->waktu_mulai,
                $jadwal->waktu_selesai,
                $jadwal->judul_kegiatan,
                $jadwal->platform,
                $jadwal->keterangan ?? '-',
                $daftarPeralatan
            );
            $this->wa->kirim($operator->nomor_wa, $pesan);
        }
    }
}
