<?php
namespace App\Http\Controllers;
use App\Models\Peralatan;
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
        $stats = [
            'jumlahRapatMendatang' => Penjadwalan::where('status', '!=', 'dibatalkan')
                ->whereRaw("TIMESTAMP(tanggal, waktu_selesai) >= NOW()")
                ->whereDate('tanggal', '<=', now()->addDays(7))
                ->count(),
            'jumlahOperator' => User::where('role', 'operator')->where('status', 'active')->count(),
            'jumlahPeralatanDipinjam' => PeminjamanItem::whereHas('peminjaman', fn($q) => $q->where('status', 'disetujui'))->sum('jumlah'),
            'jumlahJadwal'   => Penjadwalan::count(),
        ];

        $operatorChart = User::where('role', 'operator')
            ->withCount('jadwalDitugaskan')
            ->orderByDesc('jadwal_ditugaskan_count')
            ->get();

        // Peralatan paling sering dipinjam (hanya hitung peminjaman yang benar-benar terjadi: disetujui/dikembalikan)
        $topPeralatan = PeminjamanItem::query()
            ->join('peralatan', 'peminjaman_item.id_peralatan', '=', 'peralatan.id_peralatan')
            ->join('peminjaman', 'peminjaman_item.id_peminjaman', '=', 'peminjaman.id_peminjaman')
            ->whereIn('peminjaman.status', ['disetujui', 'dikembalikan'])
            ->selectRaw('peralatan.nama_peralatan, SUM(peminjaman_item.jumlah) as total_dipinjam')
            ->groupBy('peralatan.nama_peralatan')
            ->orderByDesc('total_dipinjam')
            ->limit(6)
            ->get();

        $activities = $this->recentActivities();
        $kalender   = $this->jadwalKalender($request->query('bulan'));

        // Detail tambahan tiap stat card: tren dihitung dari data asli (bukan angka karangan).
        $rapatMingguLalu = Penjadwalan::where('status', '!=', 'dibatalkan')
            ->whereBetween('tanggal', [now()->subDays(7)->format('Y-m-d'), now()->subDay()->format('Y-m-d')])
            ->count();
        $trenRapat = $this->hitungTren($stats['jumlahRapatMendatang'], $rapatMingguLalu);

        $jadwalMingguIni  = Penjadwalan::whereBetween('tanggal', [now()->subDays(7)->format('Y-m-d'), now()->format('Y-m-d')])->count();
        $jadwalMingguLalu = Penjadwalan::whereBetween('tanggal', [now()->subDays(14)->format('Y-m-d'), now()->subDays(7)->format('Y-m-d')])->count();
        $trenJadwal = $this->hitungTren($jadwalMingguIni, $jadwalMingguLalu);

        $dipinjamMingguIni  = PeminjamanItem::whereHas('peminjaman', fn($q) => $q->where('status', 'disetujui')->where('created_at', '>=', now()->subDays(7)))->sum('jumlah');
        $dipinjamMingguLalu = PeminjamanItem::whereHas('peminjaman', fn($q) => $q->where('status', 'disetujui')->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)]))->sum('jumlah');
        $trenPeralatan = $this->hitungTren($dipinjamMingguIni, $dipinjamMingguLalu);

        $totalOperatorAkun = User::where('role', 'operator')->count();

        return view('admin.dashboard', array_merge($stats, compact(
            'operatorChart', 'topPeralatan', 'activities', 'kalender',
            'trenRapat', 'trenJadwal', 'trenPeralatan', 'totalOperatorAkun'
        )));
    }

    /**
     * Hitung persentase perubahan dari dua angka nyata (minggu ini vs minggu lalu).
     * Tidak pernah mengarang angka: kalau tidak ada data pembanding, badge tidak ditampilkan.
     */
    private function hitungTren(int $sekarang, int $sebelumnya): array
    {
        if ($sebelumnya === 0) {
            return $sekarang > 0
                ? ['arah' => 'up', 'label' => 'Baru']
                : ['arah' => 'flat', 'label' => null];
        }

        $pct = round((($sekarang - $sebelumnya) / $sebelumnya) * 100, 1);

        if ($pct == 0) {
            return ['arah' => 'flat', 'label' => '0%'];
        }

        return [
            'arah'  => $pct > 0 ? 'up' : 'down',
            'label' => abs($pct) . '%',
        ];
    }

    /**
     * 6 aktivitas terbaru (14 hari terakhir): jadwal baru & peminjaman baru diajukan.
     * Query ini mengambil created_at asli dari database (bukan hardcode), jadi begitu ada
     * jadwal/peminjaman sungguhan dibuat lewat aplikasi, otomatis ikut tampil di sini.
     * Untuk sementara data seeder juga ikut ditampilkan (belum ada data produksi asli).
     */
    private function recentActivities()
    {
        $sejak = now()->subDays(14);

        $jadwal = Penjadwalan::where('created_at', '>=', $sejak)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn($j) => [
                'icon' => 'bx-calendar-plus',
                'text' => "Jadwal \"{$j->judul_kegiatan}\" ditambahkan",
                'time' => $j->created_at,
            ]);

        $peminjaman = Peminjaman::where('created_at', '>=', $sejak)
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
     * serta daftar jadwal per tanggal (dipakai saat tanggal diklik di frontend).
     * Rapat yang sudah dibatalkan sengaja tidak diikutkan sama sekali di kalender ini.
     */
    private function jadwalKalender(?string $bulan): array
    {
        $bulanAktif = $bulan
            ? Carbon::createFromFormat('Y-m', $bulan)->startOfMonth()
            : now()->startOfMonth();

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

        return view('admin.laporan.index', compact('jadwal', 'peralatan', 'operators'));
    }

    public function laporanExportPdf(Request $request)
    {
        if ($request->tab === 'panel-peralatan') {
            $peralatan = $this->queryPeralatanLaporan($request)->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.laporan.pdf_peralatan', compact('peralatan'));
            return $pdf->download('laporan-peralatan.pdf');
        }

        $jadwal = $this->queryJadwalLaporan($request)->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.laporan.pdf', compact('jadwal'));
        return $pdf->download('laporan-jadwal.pdf');
    }

    public function laporanExportExcel(Request $request)
    {
        if ($request->tab === 'panel-peralatan') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PeralatanExport($request),
                'laporan-peralatan.xlsx'
            );
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LaporanExport($request),
            'laporan-jadwal.xlsx'
        );
    }

    public function peralatanIndex(Request $request)
    {
        $gedung = $request->gedung;
        $peralatan = Peralatan::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('nama_peralatan', 'like', "%{$s}%")
                  ->orWhere('kode_barang',  'like', "%{$s}%")
                  ->orWhere('gedung',       'like', "%{$s}%")
            )
            ->when($request->gedung, fn($q, $v) => $q->where('gedung', $v))
            ->when($request->status, fn($q, $v) => match ($v) {
                'tersedia'       => $q->whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) > 0'),
                'tidak_tersedia' => $q->whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) <= 0'),
                default          => $q,
            })
            ->orderBy('gedung')
            ->orderBy('nama_peralatan')
            ->paginate(10)
            ->withQueryString();
        $gedungList = Peralatan::distinct()->orderBy('gedung')->pluck('gedung');
        return view('admin.peralatan.index', compact('peralatan', 'gedungList','gedung'));
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
            ->orderByDesc('tanggal');
    }

    /**
     * Helper method untuk query pemakaian peralatan (monitoring) via Peminjaman -
     * sumber kebenaran alat dipakai sekarang, bukan alokasi manual admin ke jadwal.
     */
    private function queryPeralatanLaporan(Request $request)
    {
        return PeminjamanItem::with(['peralatan', 'peminjaman.user', 'peminjaman.penjadwalan'])
            ->when($request->start, fn($q, $v) =>
                $q->whereHas('peminjaman', fn($p) => $p->whereDate('tanggal_pinjam', '>=', $v))
            )
            ->when($request->end, fn($q, $v) =>
                $q->whereHas('peminjaman', fn($p) => $p->whereDate('tanggal_pinjam', '<=', $v))
            )
            ->when($request->operator, fn($q, $v) =>
                $q->whereHas('peminjaman', fn($p) => $p->where('id_user', $v))
            )
            ->orderByDesc('id_item');
    }
}
