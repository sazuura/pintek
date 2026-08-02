<?php
namespace App\Http\Controllers;

use App\Exports\PeralatanExport;
use App\Models\AlatTerpasang;
use App\Models\Peralatan;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InventarisController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today('Asia/Jakarta');
        $totalPeralatan = Peralatan::count();
        $totalTersedia  = Peralatan::whereRaw('(stok - COALESCE(rusak,0)) > 0')->count();
        $totalRusak     = Peralatan::sum('rusak');

        $peralatanKritis = Peralatan::whereRaw('(stok - COALESCE(rusak,0)) BETWEEN 1 AND 2')
            ->orderBy('gedung')
            ->orderByRaw('(stok - COALESCE(rusak,0)) ASC')
            ->take(4)
            ->get();

        $peminjamanMenunggu = Peminjaman::with(['user', 'items.peralatan'])
            ->where('status', 'diajukan')
            ->whereDate('tanggal_pinjam', '>=', $today)
            ->orderBy('tanggal_pinjam')
            ->take(4)
            ->get();

        $totalMenunggu = Peminjaman::where('status', 'diajukan')->count();

        return view('dashboard.beranda.inventaris', compact(
            'totalPeralatan',
            'totalTersedia',
            'totalRusak',
            'peralatanKritis',
            'peminjamanMenunggu',
            'totalMenunggu',
        ));
    }

    public function laporanIndex(Request $request)
    {
        $gedungList = Peralatan::distinct()->pluck('gedung')
            ->merge(AlatTerpasang::distinct()->pluck('gedung'))
            ->unique()
            ->sort()
            ->values();

        $stok       = $this->queryStokLaporan($request)->paginate(10, ['*'], 'stok_page')->withQueryString();
        $terpasang  = $this->queryTerpasangLaporan($request)->paginate(10, ['*'], 'terpasang_page')->withQueryString();
        $peminjaman = $this->queryPeminjamanLaporan($request)->paginate(10, ['*'], 'peminjaman_page')->withQueryString();

        return view('dashboard.laporan.inventaris-index', compact('stok', 'terpasang', 'peminjaman', 'gedungList'));
    }

    public function laporanExportPdf(Request $request)
    {
        $namaFile = $this->buatNamaLaporanPeralatan($request);

        if ($request->tab === 'panel-terpasang') {
            $terpasang = $this->queryTerpasangLaporan($request)->get();
            return view('dashboard.laporan.print_terpasang', [
                'terpasang'      => $terpasang,
                'namaFile'       => $namaFile,
                'judul'          => 'LAPORAN ALAT TERPASANG',
                'pdfHeaders'     => ['#', 'Nama Alat', 'Gedung', 'Lokasi Detail', 'Tanggal Pasang', 'Kondisi'],
                'pdfRows'        => $this->barisPdfTerpasang($terpasang),
                'pdfStatusIndex' => 5,
            ]);
        }

        if ($request->tab === 'panel-peminjaman') {
            $peminjaman = $this->queryPeminjamanLaporan($request)->get();
            return view('dashboard.laporan.print_peralatan', [
                'peralatan'      => $peminjaman,
                'namaFile'       => $namaFile,
                'judul'          => 'LAPORAN RIWAYAT PEMINJAMAN',
                'pdfHeaders'     => ['#', 'Judul Rapat', 'Peralatan', 'Kode', 'Lokasi', 'Jumlah', 'Peminjam', 'Tgl Pinjam', 'Status'],
                'pdfRows'        => $this->barisPdfPeminjaman($peminjaman),
                'pdfStatusIndex' => 8,
            ]);
        }

        $stok = $this->queryStokLaporan($request)->get();
        return view('dashboard.laporan.print_stok', [
            'stok'           => $stok,
            'namaFile'       => $namaFile,
            'judul'          => 'LAPORAN STOK PERALATAN',
            'pdfHeaders'     => ['#', 'Nama Alat', 'Kode Barang', 'Gedung', 'Stok Total', 'Rusak', 'Tersedia', 'Status'],
            'pdfRows'        => $this->barisPdfStok($stok),
            'pdfStatusIndex' => 7,
        ]);
    }

    private function barisPdfStok($stok): array
    {
        return $stok->values()->map(function ($p, $index) {
            return [
                $index + 1,
                $p->nama_peralatan,
                $p->kode_barang ?? '-',
                $p->gedung,
                $p->stok,
                $p->rusak ?? 0,
                $p->stok_tersedia,
                $p->statusLabel,
            ];
        })->toArray();
    }

    private function barisPdfTerpasang($terpasang): array
    {
        return $terpasang->values()->map(function ($a, $index) {
            return [
                $index + 1,
                $a->nama_alat,
                $a->gedung,
                $a->lokasi_detail ?? '-',
                $a->tanggal_pasang->format('d/m/Y'),
                $a->kondisiLabel,
            ];
        })->toArray();
    }

    private function barisPdfPeminjaman($peminjaman): array
    {
        $baris = [];
        $no = 1;
        foreach ($peminjaman->values() as $p) {
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

    public function laporanExportExcel(Request $request)
    {
        $namaFile = $this->buatNamaLaporanPeralatan($request);

        if ($request->tab === 'panel-terpasang') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\AlatTerpasangExport($request),
                $namaFile . '.xlsx'
            );
        }

        if ($request->tab === 'panel-peminjaman') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new PeralatanExport($request),
                $namaFile . '.xlsx'
            );
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StokPeralatanExport($request),
            $namaFile . '.xlsx'
        );
    }

    private function buatNamaLaporanPeralatan(Request $request): string
    {
        $jenis = match ($request->tab) {
            'panel-terpasang'  => 'alat-terpasang',
            'panel-peminjaman' => 'riwayat-peminjaman',
            default            => 'stok-peralatan',
        };
        return "laporan-{$jenis}_" . now()->format('d-m-Y');
    }

    private function queryStokLaporan(Request $request)
    {

        $stokTersediaRaw = '(stok - COALESCE(rusak,0))';

        return Peralatan::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('nama_peralatan', 'like', "%{$s}%")
                  ->orWhere('kode_barang', 'like', "%{$s}%")
            )
            ->when($request->gedung, fn($q, $v) => $q->where('gedung', $v))
            ->when($request->kondisi, fn($q, $v) => match ($v) {
                'baik'  => $q->whereRaw('COALESCE(rusak,0) <= 0'),
                'rusak' => $q->whereRaw('COALESCE(rusak,0) > 0'),
                default => $q,
            })
            ->when($request->status, fn($q, $v) => match ($v) {
                'tersedia'       => $q->whereRaw("{$stokTersediaRaw} > 2"),
                'kritis'         => $q->whereRaw("{$stokTersediaRaw} between 1 and 2"),
                'tidak_tersedia' => $q->whereRaw("{$stokTersediaRaw} <= 0"),
                default          => $q,
            })
            ->orderBy('gedung')
            ->orderBy('nama_peralatan');
    }

    private function queryTerpasangLaporan(Request $request)
    {
        return AlatTerpasang::query()
            ->when($request->search, fn($q, $s) => $q->where('nama_alat', 'like', "%{$s}%"))
            ->when($request->gedung, fn($q, $v) => $q->where('gedung', $v))
            ->when($request->kondisi, fn($q, $v) => $q->where('kondisi', $v))
            ->when($request->start, fn($q, $v) => $q->whereDate('tanggal_pasang', '>=', $v))
            ->when($request->end, fn($q, $v) => $q->whereDate('tanggal_pasang', '<=', $v))
            ->orderBy('gedung')
            ->orderBy('nama_alat');
    }

    private function queryPeminjamanLaporan(Request $request)
    {
        return Peminjaman::with(['user', 'penjadwalan', 'items.peralatan'])
            ->whereHas('items')
            ->when($request->search, fn($q, $s) =>
                $q->where('keperluan', 'like', "%{$s}%")
                  ->orWhereHas('user', fn($qq) => $qq->where('nama_user', 'like', "%{$s}%"))
                  ->orWhereHas('items.peralatan', fn($qq) => $qq->where('nama_peralatan', 'like', "%{$s}%"))
            )
            ->when($request->gedung, fn($q, $v) =>
                $q->whereHas('items.peralatan', fn($qq) => $qq->where('gedung', $v))
            )
            ->when($request->start, fn($q, $v) => $q->whereDate('tanggal_pinjam', '>=', $v))
            ->when($request->end, fn($q, $v) => $q->whereDate('tanggal_pinjam', '<=', $v))
            ->orderByDesc('tanggal_pinjam');
    }
}