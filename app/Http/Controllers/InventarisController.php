<?php
namespace App\Http\Controllers;

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
        $totalTersedia  = Peralatan::whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) > 0')->count();
        $totalRusak     = Peralatan::sum('rusak');

        $peralatanKritis = Peralatan::whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) BETWEEN 1 AND 2')
            ->orderBy('gedung') // Tetap diurutkan berdasarkan gedung asal peralatan
            ->orderByRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) ASC')
            ->take(5)
            ->get();

        $peminjamanMenunggu = Peminjaman::with(['user', 'items.peralatan'])
            ->where('status', 'diajukan')
            ->whereDate('tanggal_pinjam', '>=', $today)
            ->orderBy('created_at')
            ->take(5)
            ->get();

        $totalMenunggu = Peminjaman::where('status', 'diajukan')->count();

        return view('inventaris.dashboard', compact(
            'totalPeralatan',
            'totalTersedia',
            'totalRusak',
            'peralatanKritis',
            'peminjamanMenunggu',
            'totalMenunggu',
        ));
    }

    public function peralatanIndex(Request $request)
    {
        $peralatan = Peralatan::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('nama_peralatan', 'like', "%{$s}%")
                  ->orWhere('kode_barang',  'like', "%{$s}%")
            )
            ->when($request->status, fn($q, $v) => match ($v) {
                'tersedia'       => $q->whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) > 0'),
                'tidak_tersedia' => $q->whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) <= 0'),
                'kritis'         => $q->whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) BETWEEN 1 AND 2'),
                default          => $q,
            })
            ->orderBy('gedung') 
            ->orderBy('nama_peralatan')
            ->paginate(10)
            ->withQueryString();

        return view('inventaris.peralatan.index', compact('peralatan'));
    }
}