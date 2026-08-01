<?php
namespace App\Http\Controllers;
use App\Models\Penjadwalan;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $bulanAktif = $this->bulanAktifDari($request->query('bulan'));
        $awalBulan  = $bulanAktif->copy()->startOfMonth();
        $akhirBulan = $bulanAktif->copy()->endOfMonth();

        $stats = [
            'jumlahRapatMendatang' => Penjadwalan::where('status', '!=', 'dibatalkan')
                ->whereRaw("TIMESTAMP(tanggal, waktu_selesai) >= NOW()")
                ->whereBetween('tanggal', [$awalBulan->format('Y-m-d'), $akhirBulan->format('Y-m-d')])
                ->count(),
            // Snapshot akun (bukan data per-periode) - sengaja TIDAK ikut berubah saat pindah bulan.
            'jumlahOperator' => User::where('role', 'operator')->where('status', 'active')->count(),
            'jumlahPeralatanDipinjam' => PeminjamanItem::whereHas('peminjaman', fn($q) =>
                $q->where('status', 'disetujui')->whereBetween('tanggal_pinjam', [$awalBulan->format('Y-m-d'), $akhirBulan->format('Y-m-d')])
            )->sum('jumlah'),
            'jumlahJadwal' => Penjadwalan::whereBetween('tanggal', [$awalBulan->format('Y-m-d'), $akhirBulan->format('Y-m-d')])->count(),
        ];

        $operatorChart = User::where('role', 'operator')
            ->withCount(['jadwalDitugaskan' => fn($q) =>
                $q->whereBetween('tanggal', [$awalBulan->format('Y-m-d'), $akhirBulan->format('Y-m-d')])
            ])
            ->orderByDesc('jadwal_ditugaskan_count')
            ->get();

        // Peralatan paling sering dipinjam di bulan yang lagi dilihat (hanya hitung peminjaman
        $topPeralatan = PeminjamanItem::query()
            ->join('peralatan', 'peminjaman_item.id_peralatan', '=', 'peralatan.id_peralatan')
            ->join('peminjaman', 'peminjaman_item.id_peminjaman', '=', 'peminjaman.id_peminjaman')
            ->whereIn('peminjaman.status', ['disetujui', 'dikembalikan'])
            ->whereBetween('peminjaman.tanggal_pinjam', [$awalBulan->format('Y-m-d'), $akhirBulan->format('Y-m-d')])
            ->selectRaw('peralatan.nama_peralatan, SUM(peminjaman_item.jumlah) as total_dipinjam')
            ->groupBy('peralatan.nama_peralatan')
            ->orderByDesc('total_dipinjam')
            ->limit(6)
            ->get();

        // Aktivitas Terbaru sengaja TETAP selalu "14 hari terakhir dari hari ini" 
        $activities = $this->recentActivities();
        $kalender   = $this->jadwalKalender($bulanAktif);

        // Detail tambahan tiap stat card: tren dihitung dari data asli
        $bulanSebelumnyaAwal  = $awalBulan->copy()->subMonth();
        $bulanSebelumnyaAkhir = $bulanSebelumnyaAwal->copy()->endOfMonth();

        $rapatBulanLalu = Penjadwalan::where('status', '!=', 'dibatalkan')
            ->whereBetween('tanggal', [$bulanSebelumnyaAwal->format('Y-m-d'), $bulanSebelumnyaAkhir->format('Y-m-d')])
            ->count();
        $trenRapat = $this->hitungTrenSelisih($stats['jumlahRapatMendatang'], $rapatBulanLalu);

        $jadwalBulanLalu = Penjadwalan::whereBetween('tanggal', [$bulanSebelumnyaAwal->format('Y-m-d'), $bulanSebelumnyaAkhir->format('Y-m-d')])->count();
        $trenJadwal = $this->hitungTrenSelisih($stats['jumlahJadwal'], $jadwalBulanLalu);

        $dipinjamBulanLalu = PeminjamanItem::whereHas('peminjaman', fn($q) =>
            $q->where('status', 'disetujui')->whereBetween('tanggal_pinjam', [$bulanSebelumnyaAwal->format('Y-m-d'), $bulanSebelumnyaAkhir->format('Y-m-d')])
        )->sum('jumlah');
        $trenPeralatan = $this->hitungTrenSelisih($stats['jumlahPeralatanDipinjam'], $dipinjamBulanLalu);

        $totalOperatorAkun = User::where('role', 'operator')->count();

        return view('dashboard.beranda.admin', array_merge($stats, compact(
            'operatorChart', 'topPeralatan', 'activities', 'kalender',
            'trenRapat', 'trenJadwal', 'trenPeralatan', 'totalOperatorAkun'
        )));
    }

    private function bulanAktifDari(?string $bulan): Carbon
    {
        return $bulan
            ? Carbon::createFromFormat('Y-m', $bulan)->startOfMonth()
            : now()->startOfMonth();
    }

    /**
     * Hitung selisih angka mentah (bukan persentase) antara dua periode.
     */
    private function hitungTrenSelisih(int $sekarang, int $sebelumnya): array
    {
        $selisih = $sekarang - $sebelumnya;

        return [
            'arah'  => $selisih > 0 ? 'up' : ($selisih < 0 ? 'down' : 'flat'),
            'label' => (string) abs($selisih),
        ];
    }

    /**
     * 6 aktivitas terbaru (14 hari terakhir): jadwal baru & peminjaman baru diajukan.
     */
    private function recentActivities()
    {
        $sejak = now()->subDays(14);

        $jadwal = Penjadwalan::whereBetween('created_at', [$sejak, now()])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn($j) => [
                'icon' => 'bx-calendar-plus',
                'text' => "Jadwal \"{$j->judul_kegiatan}\" ditambahkan",
                'time' => $j->created_at,
            ]);

        $peminjaman = Peminjaman::whereBetween('created_at', [$sejak, now()])
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn($p) => [
                'icon' => 'bx-package',
                'text' => ($p->user->nama_user ?? 'Operator') . ' mengajukan peminjaman peralatan',
                'time' => $p->created_at,
            ]);

        return $jadwal->concat($peminjaman)->sortByDesc('time')->take(6)->values();
    }

    /**
     * Kalender bulanan (Min–Sab) berisi jadwal per tanggal, dengan navigasi bulan
     */
    private function jadwalKalender(Carbon $bulanAktif): array
    {
        $awalGrid  = $bulanAktif->copy()->startOfWeek(Carbon::SUNDAY);
        $akhirGrid = $bulanAktif->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $jadwalRange = Penjadwalan::with('operators')
            ->where('status', '!=', 'dibatalkan')
            ->whereBetween('tanggal', [$awalGrid->format('Y-m-d'), $akhirGrid->format('Y-m-d')])
            ->orderBy('waktu_mulai')
            ->get();

        $jadwalPerTanggal = $jadwalRange->groupBy(fn($j) => $j->tanggal->format('Y-m-d'));

        $detailPerTanggal = $jadwalPerTanggal->map(fn($list) => $list->map(fn($j) => [
            'judul'     => $j->judul_kegiatan,
            'waktu'     => Carbon::parse($j->waktu_mulai)->format('H:i') . '–' . Carbon::parse($j->waktu_selesai)->format('H:i'),
            'platform'  => $j->platform,
            'operators' => $j->operators->pluck('nama_user')->map(fn($n) => str_replace(' (Operator)', '', $n))->join(', ') ?: '-',
        ])->values())->all();

        $hariIni = today();
        $minggu  = [];
        $cursor  = $awalGrid->copy();
        while ($cursor->lte($akhirGrid)) {
            $pekan = [];
            for ($i = 0; $i < 7; $i++) {
                $tglKey  = $cursor->format('Y-m-d');
                $pekan[] = [
                    'tanggal'    => $cursor->day,
                    'tanggalKey' => $tglKey,
                    'inBulan'    => $cursor->month === $bulanAktif->month,
                    'isHariIni'  => $cursor->isToday(),
                    'sudahLewat' => $cursor->lt($hariIni),
                    'jumlah'     => $jadwalPerTanggal->get($tglKey)?->count() ?? 0,
                ];
                $cursor->addDay();
            }
            $minggu[] = $pekan;
        }

        return [
            'labelBulan'       => $bulanAktif->translatedFormat('F'),
            'tahun'            => $bulanAktif->format('Y'),
            'bulanAngka'       => $bulanAktif->month,
            'tahunAngka'       => (int) $bulanAktif->format('Y'),
            'bulanSebelumnya'  => $bulanAktif->copy()->subMonth()->format('Y-m'),
            'bulanBerikutnya'  => $bulanAktif->copy()->addMonth()->format('Y-m'),
            'hariHeader'       => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            'minggu'           => $minggu,
            'detailPerTanggal' => $detailPerTanggal,
        ];
    }

    public function laporanIndex(Request $request)
    {
        $operators = User::where('role', 'operator')->orderBy('nama_user')->get();
        $jadwal    = $this->queryJadwalLaporan($request)->paginate(10, ['*'], 'jadwal_page')->withQueryString();
        $peralatan = $this->queryPeralatanLaporan($request)->paginate(10, ['*'], 'peralatan_page')->withQueryString();

        return view('dashboard.laporan.admin-index', compact('jadwal', 'peralatan', 'operators'));
    }

    /**
     * PDF laporan dirender sebagai halaman HTML biasa (Tailwind, sama seperti tampilan
     * live).
     */
    public function laporanExportPdf(Request $request)
    {
        $namaFile = $this->buatNamaLaporan($request);

        if ($request->tab === 'panel-peralatan') {
            $peralatan = $this->queryPeralatanLaporan($request)->get();
            return view('dashboard.laporan.print_peralatan', [
                'peralatan'      => $peralatan,
                'namaFile'       => $namaFile,
                'judul'          => 'LAPORAN PERALATAN DIGUNAKAN',
                'pdfHeaders'     => ['#', 'Judul Rapat', 'Peralatan', 'Kode', 'Gedung', 'Jumlah', 'Peminjam', 'Tgl Pinjam', 'Status'],
                'pdfRows'        => $this->barisPdfPeralatan($peralatan),
                'pdfStatusIndex' => 8,
            ]);
        }

        $jadwal = $this->queryJadwalLaporan($request)->get();
        return view('dashboard.laporan.print_jadwal', [
            'jadwal'         => $jadwal,
            'namaFile'       => $namaFile,
            'judul'          => 'LAPORAN JADWAL & OPERATOR',
            'pdfHeaders'     => ['#', 'Operator', 'Judul Rapat', 'Tanggal', 'Platform', 'Status'],
            'pdfRows'        => $this->barisPdfJadwal($jadwal),
            'pdfStatusIndex' => 5,
        ]);
    }

    private function barisPdfJadwal($jadwal): array
    {
        return $jadwal->values()->map(function ($j, $index) {
            $sudahLewat = Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
            $dibatalkan = $j->isDibatalkan();
            $status     = $dibatalkan ? 'Dibatalkan' : ($sudahLewat ? 'Selesai' : 'Aktif');

            return [
                $index + 1,
                $j->operators->pluck('nama_user')->join(', ') ?: '-',
                $j->judul_kegiatan,
                $j->tanggal->translatedFormat('l, d F Y'),
                str_contains($j->platform, 'Online') ? 'Online' : 'Offline',
                $status,
            ];
        })->toArray();
    }

    // Satu baris per alat (bukan digabung satu sel per pengajuan) supaya kode, gedung,
    // dan jumlah masing-masing punya kolom sendiri - sama seperti struktur di Excel.
    private function barisPdfPeralatan($peralatan): array
    {
        $baris = [];
        $no = 1;
        foreach ($peralatan->values() as $p) {
            foreach ($p->items as $item) {
                $baris[] = [
                    $no++,
                    $p->penjadwalan->judul_kegiatan ?? $p->keperluan,
                    $item->peralatan->nama_peralatan ?? '-',
                    $item->peralatan->kode_barang ?? '-',
                    $item->peralatan->gedung ?? '-',
                    $item->jumlah,
                    $p->user->nama_user ?? '-',
                    $p->tanggal_pinjam->translatedFormat('l, d F Y'),
                    $p->badge['label'],
                ];
            }
        }
        return $baris;
    }

    /**
     * Excel tetap pakai PhpSpreadsheet (lewat maatwebsite/excel) 
     */
    public function laporanExportExcel(Request $request)
    {
        $namaFile = $this->buatNamaLaporan($request);

        if ($request->tab === 'panel-peralatan') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PeralatanExport($request),
                $namaFile . '.xlsx'
            );
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LaporanExport($request),
            $namaFile . '.xlsx'
        );
    }

    /**
     * Bikin nama file laporan dinamis dari jenis tab + rentang tanggal filter yang
     * sedang dipakai, supaya tidak selalu "laporan-jadwal.pdf" yang generik.
     */
    private function buatNamaLaporan(Request $request): string
    {
        $jenis = $request->tab === 'panel-peralatan' ? 'peralatan-digunakan' : 'jadwal-operator';

        if ($request->start && $request->end) {
            $periode = Carbon::parse($request->start)->format('d-m-Y') . '_sd_' . Carbon::parse($request->end)->format('d-m-Y');
        } elseif ($request->start) {
            $periode = 'sejak_' . Carbon::parse($request->start)->format('d-m-Y');
        } elseif ($request->end) {
            $periode = 'sampai_' . Carbon::parse($request->end)->format('d-m-Y');
        } else {
            $periode = now()->format('d-m-Y');
        }

        return "laporan-{$jenis}_{$periode}";
    }

    /**
     * Helper method untuk query Jadwal + Operator agar filter PDF, Excel, dan Index selalu sinkron.
     */
    private function queryJadwalLaporan(Request $request)
    {
        return Penjadwalan::with('operators')
            ->when($request->start, fn($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($request->end,   fn($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->when($request->operator, fn($q, $v) =>
                $q->whereHas('operators', fn($qq) => $qq->where('users.id_user', $v))
            )
            // Aktif tampil dulu (tanggal terdekat); Selesai+Dibatalkan digabung satu riwayat di bawah (paling baru dulu).
            ->orderByRaw("(status = 'dibatalkan' OR TIMESTAMP(tanggal, waktu_selesai) < NOW()) ASC")
            ->orderByRaw("CASE WHEN status = 'dibatalkan' OR TIMESTAMP(tanggal, waktu_selesai) < NOW()
                          THEN -DATEDIFF(tanggal, CURDATE()) ELSE DATEDIFF(tanggal, CURDATE()) END ASC")
            ->orderBy('waktu_mulai');
    }

    /**
     * Helper method untuk query pemakaian peralatan (monitoring) via Peminjaman
     */
    private function queryPeralatanLaporan(Request $request)
    {
        return Peminjaman::with(['user', 'penjadwalan', 'items.peralatan'])
            ->whereHas('items')
            ->when($request->start, fn($q, $v) => $q->whereDate('tanggal_pinjam', '>=', $v))
            ->when($request->end, fn($q, $v) => $q->whereDate('tanggal_pinjam', '<=', $v))
            ->when($request->operator, fn($q, $v) => $q->where('id_user', $v))
            ->orderByDesc('tanggal_pinjam');
    }
}
