<?php
namespace App\Services;
use App\Helpers\IdGenerator;
use App\Mail\JadwalBaruMail;
use App\Mail\JadwalDiubahMail;
use App\Mail\JadwalDibatalkanMail;
use App\Models\Penjadwalan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PenjadwalanService
{

    private ?string $peringatanZoom = null;

    public function __construct(private ZoomService $zoom) {}

    public function peringatanZoom(): ?string
    {
        return $this->peringatanZoom;
    }

    public function buat(array $data, array $operatorIds, array $peralatanSync = [], ?string $akunPilihan = null): Penjadwalan
    {
        $this->validasiBentrokOperator($operatorIds, $data['tanggal'], $data['waktu_mulai'], $data['waktu_selesai']);
        $this->peringatanZoom = null;

        if (!empty($data['link_otomatis']) && $this->platformPakaiZoom($data['platform'])) {
            $data = $this->prosesLinkOtomatis($data, null, $akunPilihan);
        } else {
            $data['link_otomatis'] = false;
        }

        return DB::transaction(function () use ($data, $operatorIds, $peralatanSync) {
            $jadwal = $this->simpanJadwal($data);
            $jadwal->operators()->sync($operatorIds);
            $jadwal->peralatanReferensi()->sync($peralatanSync);
            $this->kirimNotifKeOperator($jadwal, $operatorIds);
            return $jadwal;
        });
    }

    public function ubah(Penjadwalan $jadwal, array $data, array $operatorIds, array $peralatanSync = [], ?string $akunPilihan = null): Penjadwalan
    {
        $this->validasiBentrokOperator($operatorIds, $data['tanggal'], $data['waktu_mulai'], $data['waktu_selesai'], $jadwal->id_penjadwalan);
        $this->peringatanZoom = null;
        $data = $this->sinkronkanLinkZoom($jadwal, $data, $akunPilihan);

        $operatorLamaIds = $jadwal->operators()->pluck('users.id_user')->all();

        $fingerprintLama = $this->fingerprintDetail(
            $jadwal->judul_kegiatan, $jadwal->tanggal->format('Y-m-d'), $jadwal->waktu_mulai,
            $jadwal->waktu_selesai, $jadwal->platform, $jadwal->keterangan, $jadwal->lokasi_fisik
        );

        return DB::transaction(function () use ($jadwal, $data, $operatorIds, $peralatanSync, $operatorLamaIds, $fingerprintLama) {
            $jadwal->update($data);
            $fingerprintBaru = $this->fingerprintDetail(
                $data['judul_kegiatan'], $data['tanggal'], $data['waktu_mulai'],
                $data['waktu_selesai'], $data['platform'], $data['keterangan'] ?? null, $data['lokasi_fisik'] ?? null
            );
            $adaPerubahanDetail = $fingerprintLama !== $fingerprintBaru;

            $jadwal->operators()->sync($operatorIds);
            $jadwal->peralatanReferensi()->sync($peralatanSync);
            $this->kirimNotifPerubahan($jadwal, $operatorLamaIds, $operatorIds, $adaPerubahanDetail);
            return $jadwal->fresh();
        });
    }

    private function fingerprintDetail(string $judul, string $tanggal, string $waktuMulai, string $waktuSelesai, string $platform, ?string $keterangan, ?string $lokasiFisik = null): array
    {
        return [
            'judul_kegiatan' => $judul,
            'tanggal'        => \Carbon\Carbon::parse($tanggal)->format('Y-m-d'),
            'waktu_mulai'    => substr($waktuMulai, 0, 5),
            'waktu_selesai'  => substr($waktuSelesai, 0, 5),
            'platform'       => $platform,
            'keterangan'     => $keterangan ?? '',
            'lokasi_fisik'   => $lokasiFisik ?? '',
        ];
    }

    public function batalkan(Penjadwalan $jadwal, string $alasan): void
    {
        if ($jadwal->isDibatalkan()) {
            throw new \RuntimeException('Jadwal ini sudah dibatalkan sebelumnya.');
        }

        $this->hapusMeetingZoomJikaAda($jadwal);
        $jadwal->loadMissing('operators');

        $jadwal->update([
            'status'        => 'dibatalkan',
            'alasan_batal'  => $alasan,
            'dibatalkan_at' => now(),
        ]);

        foreach ($jadwal->operators as $operator) {
            Mail::to($operator->email)->send(new JadwalDibatalkanMail(
                $operator->nama_user,
                $jadwal->tanggal->translatedFormat('l, d F Y'),
                $jadwal->waktu_mulai,
                $jadwal->waktu_selesai,
                $jadwal->judul_kegiatan,
                $jadwal->platform,
                $alasan,
                $jadwal->keterangan ?? '-'
            ));
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

    private function platformPakaiZoom(string $platform): bool
    {
        return str_contains($platform, 'Zoom') || $platform === 'Hybrid';
    }

    private function hitungDurasiMenit(string $mulai, string $selesai): int
    {
        $awal  = Carbon::parse($mulai);
        $akhir = Carbon::parse($selesai);
        return max(1, $akhir->diffInMinutes($awal));
    }

    private function akunBentrok(string $akun, string $tanggal, string $mulai, string $selesai, ?string $excludeId = null): bool
    {
        return Penjadwalan::bentrok($tanggal, $mulai, $selesai, $excludeId)
            ->where('link_otomatis', true)
            ->where('zoom_account', $akun)
            ->exists();
    }

    private function pilihAkunZoomBebas(string $tanggal, string $mulai, string $selesai, ?string $excludeId = null): ?string
    {
        foreach (['akun_1', 'akun_2'] as $akun) {
            if (!$this->akunBentrok($akun, $tanggal, $mulai, $selesai, $excludeId)) {
                return $akun;
            }
        }
        return null;
    }

    private function namaAkun(string $akun): string
    {
        return $akun === 'akun_1' ? 'Akun 1' : 'Akun 2';
    }

    private function prosesLinkOtomatis(array $data, ?string $excludeId = null, ?string $akunPilihan = null): array
    {
        if ($akunPilihan) {
            $bentrok = $this->akunBentrok($akunPilihan, $data['tanggal'], $data['waktu_mulai'], $data['waktu_selesai'], $excludeId);
            $akun = $bentrok ? null : $akunPilihan;
            if (!$akun) {
                $this->peringatanZoom = $this->namaAkun($akunPilihan) . ' sudah terpakai di jam ini. Link meeting tidak dibuat otomatis - silakan isi secara manual atau pilih akun lain.';
                $data['link_otomatis'] = false;
                return $data;
            }
        } else {
            $akun = $this->pilihAkunZoomBebas($data['tanggal'], $data['waktu_mulai'], $data['waktu_selesai'], $excludeId);
        }

        if (!$akun) {
            $this->peringatanZoom = 'Kedua akun Zoom sudah terpakai di jam ini. Link meeting tidak dibuat otomatis - silakan isi secara manual.';
            $data['link_otomatis'] = false;
            return $data;
        }

        $hasil = $this->zoom->buatMeeting($akun, [
            'topic'      => $data['judul_kegiatan'],
            'start_time' => Carbon::parse($data['tanggal'] . ' ' . $data['waktu_mulai'])->format('Y-m-d\TH:i:s'),
            'duration'   => $this->hitungDurasiMenit($data['waktu_mulai'], $data['waktu_selesai']),
        ]);

        if (!$hasil) {
            $this->peringatanZoom = 'Link Zoom otomatis gagal dibuat. Silakan isi link meeting secara manual.';
            $data['link_otomatis'] = false;
            return $data;
        }

        $data['keterangan']      = $hasil['join_url'];
        $data['zoom_meeting_id'] = $hasil['meeting_id'];
        $data['zoom_password']   = $hasil['password'];
        $data['zoom_account']    = $akun;
        $data['link_otomatis']   = true;
        return $data;
    }

    private function sinkronkanLinkZoom(Penjadwalan $jadwal, array $data, ?string $akunPilihan = null): array
    {
        $mauOtomatis   = !empty($data['link_otomatis']) && $this->platformPakaiZoom($data['platform']);
        $sudahOtomatis = $jadwal->link_otomatis && $jadwal->zoom_meeting_id;

        if (!$mauOtomatis) {
            if ($sudahOtomatis) {
                $this->zoom->hapusMeeting($jadwal->zoom_account, $jadwal->zoom_meeting_id);
            }
            $data['zoom_meeting_id'] = null;
            $data['zoom_password']   = null;
            $data['zoom_account']    = null;
            $data['link_otomatis']   = false;
            return $data;
        }

        $akunBerubah = $sudahOtomatis && $akunPilihan && $jadwal->zoom_account !== $akunPilihan;

        if ($sudahOtomatis && !$akunBerubah) {
            $waktuBerubah = $jadwal->tanggal->format('Y-m-d') !== $data['tanggal']
                || substr($jadwal->waktu_mulai, 0, 5)   !== substr($data['waktu_mulai'], 0, 5)
                || substr($jadwal->waktu_selesai, 0, 5) !== substr($data['waktu_selesai'], 0, 5);
            $judulBerubah = $jadwal->judul_kegiatan !== $data['judul_kegiatan'];

            if ($waktuBerubah || $judulBerubah) {
                $ok = $this->zoom->updateMeeting($jadwal->zoom_account, $jadwal->zoom_meeting_id, [
                    'topic'      => $data['judul_kegiatan'],
                    'start_time' => Carbon::parse($data['tanggal'] . ' ' . $data['waktu_mulai'])->format('Y-m-d\TH:i:s'),
                    'duration'   => $this->hitungDurasiMenit($data['waktu_mulai'], $data['waktu_selesai']),
                ]);
                if (!$ok) {
                    $this->peringatanZoom = 'Jadwal tersimpan, tapi update waktu meeting Zoom gagal. Silakan cek link secara manual.';
                }
            }

            $data['keterangan']      = $jadwal->keterangan;
            $data['zoom_meeting_id'] = $jadwal->zoom_meeting_id;
            $data['zoom_password']   = $jadwal->zoom_password;
            $data['zoom_account']    = $jadwal->zoom_account;
            $data['link_otomatis']   = true;
            return $data;
        }

        if ($sudahOtomatis) {
            $this->zoom->hapusMeeting($jadwal->zoom_account, $jadwal->zoom_meeting_id);
        }
        return $this->prosesLinkOtomatis($data, $jadwal->id_penjadwalan, $akunPilihan);
    }

    private function hapusMeetingZoomJikaAda(Penjadwalan $jadwal): void
    {
        if ($jadwal->link_otomatis && $jadwal->zoom_meeting_id) {
            $this->zoom->hapusMeeting($jadwal->zoom_account, $jadwal->zoom_meeting_id);
        }
    }

    private function kirimNotifKeOperator(Penjadwalan $jadwal, array $operatorIds): void
    {
        $daftarPeralatan = $this->formatPeralatanReferensi($jadwal);

        foreach (User::whereIn('id_user', $operatorIds)->get() as $operator) {
            Mail::to($operator->email)->send(new JadwalBaruMail(
                $operator->nama_user,
                $jadwal->tanggal->translatedFormat('l, d F Y'),
                $jadwal->waktu_mulai,
                $jadwal->waktu_selesai,
                $jadwal->judul_kegiatan,
                $jadwal->platform,
                $jadwal->keterangan ?? '-',
                $daftarPeralatan,
                $jadwal->link_otomatis ? $jadwal->zoom_password : null,
                $jadwal->lokasi_fisik
            ));
        }
    }

    private function formatPeralatanReferensi(Penjadwalan $jadwal): array
    {
        return $jadwal->load('peralatanReferensi')->peralatanReferensi
            ->map(fn($p) => ['nama' => $p->nama_peralatan, 'jumlah' => $p->pivot->jumlah])
            ->all();
    }

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
            Mail::to($operator->email)->send(new JadwalDiubahMail(
                $operator->nama_user,
                $jadwal->tanggal->translatedFormat('l, d F Y'),
                $jadwal->waktu_mulai,
                $jadwal->waktu_selesai,
                $jadwal->judul_kegiatan,
                $jadwal->platform,
                $jadwal->keterangan ?? '-',
                $daftarPeralatan,
                $jadwal->link_otomatis ? $jadwal->zoom_password : null,
                $jadwal->lokasi_fisik
            ));
        }
    }
}
