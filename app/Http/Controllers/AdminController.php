<?php
namespace App\Http\Controllers;
use App\Models\Absensi;
use App\Models\JadwalPeralatan;
use App\Models\Peralatan;
use App\Models\Penjadwalan;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $absensiStats = Absensi::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $stats = [
            'jumlahOperator'    => User::where('role', 'operator')->where('status', 'active')->count(),
            'jumlahJadwal'      => Penjadwalan::count(),
            'jumlahPeralatan'   => Peralatan::count(),
            'totalHadir'        => $absensiStats[Absensi::STATUS_HADIR]           ?? 0,
            'totalIzin'         => $absensiStats[Absensi::STATUS_IZIN_DISETUJUI]  ?? 0,
            'totalSakit'        => $absensiStats[Absensi::STATUS_SAKIT_DISETUJUI] ?? 0,
            'totalAlpha'        => $absensiStats[Absensi::STATUS_ALPHA]            ?? 0,
        ];
        $tren = Absensi::selectRaw('tanggal, status, COUNT(*) as total')
            ->whereIn('status', [
                Absensi::STATUS_HADIR,
                Absensi::STATUS_IZIN_DISETUJUI,
                Absensi::STATUS_SAKIT_DISETUJUI,
                Absensi::STATUS_ALPHA,
            ])
            ->groupBy('tanggal', 'status')
            ->orderBy('tanggal')
            ->get()
            ->groupBy('tanggal');
        $trenLabels = [];
        $trenHadir  = $trenIzin = $trenSakit = $trenAlpha = [];
        foreach ($tren as $tanggal => $rows) {
            $trenLabels[] = \Carbon\Carbon::parse($tanggal)->format('d/m');
            $map          = $rows->pluck('total', 'status');
            $trenHadir[]  = $map[Absensi::STATUS_HADIR]           ?? 0;
            $trenIzin[]   = $map[Absensi::STATUS_IZIN_DISETUJUI]  ?? 0;
            $trenSakit[]  = $map[Absensi::STATUS_SAKIT_DISETUJUI] ?? 0;
            $trenAlpha[]  = $map[Absensi::STATUS_ALPHA]            ?? 0;
        }
        $operatorChart = User::where('role', 'operator')
            ->withCount('absensi')
            ->orderByDesc('absensi_count')
            ->get();
        return view('admin.dashboard', array_merge($stats, compact(
            'trenLabels', 'trenHadir', 'trenIzin', 'trenSakit', 'trenAlpha',
            'operatorChart',
        )));
    }

    public function absensiIndex(Request $request)
    {
        $absensi = Absensi::with(['user', 'penjadwalan'])
            ->when($request->search, fn($q, $s) =>
                $q->where(function ($q2) use ($s) {
                    $q2->whereHas('user',        fn($u) => $u->where('nama_user',      'like', "%{$s}%"))
                       ->orWhereHas('penjadwalan', fn($p) => $p->where('judul_kegiatan', 'like', "%{$s}%"));
                })
            )
            ->when($request->status, fn($q, $status) => match ($status) {
                'hadir'           => $q->where('status', Absensi::STATUS_HADIR),
                'tidak_hadir'     => $q->whereIn('status', [Absensi::STATUS_IZIN_DISETUJUI, Absensi::STATUS_SAKIT_DISETUJUI, Absensi::STATUS_ALPHA]),
                'perlu_disetujui' => $q->whereIn('status', [Absensi::STATUS_IZIN, Absensi::STATUS_SAKIT]),
                'pending'         => $q->where('status', Absensi::STATUS_PENDING),
                default           => $q,
            })
            ->orderByDesc('tanggal')
            ->paginate(10)
            ->withQueryString();
        return view('admin.absensi.index', compact('absensi'));
    }

    public function absensiShow(string $id)
    {
        $absensi = Absensi::with(['user', 'penjadwalan', 'dokumentasi'])->findOrFail($id);
        return view('admin.absensi.show', compact('absensi'));
    }

    public function absensiUpdateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:hadir,izin_disetujui,sakit_disetujui,alpha,ditolak',
        ]);
        Absensi::findOrFail($id)->update([
            'status'    => $request->status,
            'validated' => true,
        ]);
        return back()->with('success', 'Status presensi berhasil diperbarui.');
    }

    public function laporanIndex(Request $request)
    {
        $operators = User::where('role', 'operator')->orderBy('nama_user')->get();
        
        $absensi = Absensi::with(['user', 'penjadwalan'])
            ->when($request->start,    fn($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($request->end,      fn($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->when($request->operator, fn($q, $v) => $q->where('id_user', $v))
            ->orderByDesc('tanggal')
            ->get();

        // Menggunakan method baru agar logic filter seragam
        $jadwalPeralatan = $this->queryJadwalPeralatan($request)->get();

        return view('admin.laporan.index', compact('absensi', 'operators', 'jadwalPeralatan'));
    }

    public function laporanExportPdf(Request $request)
    {
        // Tab peralatan → export data peralatan
        if ($request->tab === 'panel-peralatan') {
            $jadwalPeralatan = $this->queryJadwalPeralatan($request)->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'admin.laporan.pdf_peralatan',
                compact('jadwalPeralatan')
            );
            return $pdf->download('laporan-peralatan.pdf');
        }

        // Default → export data presensi
        $absensi = Absensi::with(['user', 'penjadwalan'])
            ->when($request->start,    fn($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($request->end,      fn($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->when($request->operator, fn($q, $v) => $q->where('id_user', $v))
            ->orderByDesc('tanggal')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.laporan.pdf', compact('absensi'));
        return $pdf->download('laporan-presensi.pdf');
    }

    public function laporanExportExcel(Request $request)
    {
        // Tab peralatan → export data peralatan
        if ($request->tab === 'panel-peralatan') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PeralatanExport($request),
                'laporan-peralatan.xlsx'
            );
        }

        // Default → export data presensi
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LaporanExport($request),
            'laporan-presensi.xlsx'
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
     * Helper method untuk query Jadwal Peralatan agar filter PDF dan Index selalu sinkron.
     */
    private function queryJadwalPeralatan(Request $request)
    {
        return JadwalPeralatan::with(['penjadwalan', 'peralatan'])
            ->when($request->start, fn($q, $v) =>
                $q->whereHas('penjadwalan', fn($p) => $p->whereDate('tanggal', '>=', $v))
            )
            ->when($request->end, fn($q, $v) =>
                $q->whereHas('penjadwalan', fn($p) => $p->whereDate('tanggal', '<=', $v))
            );
    }
}
