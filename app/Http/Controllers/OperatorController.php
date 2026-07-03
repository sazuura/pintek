<?php
namespace App\Http\Controllers;
use App\Models\Peralatan;
use App\Models\Penjadwalan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function dashboard()
    {
        $today  = Carbon::today('Asia/Jakarta');
        $userId = auth()->user()->id_user;

        $jadwalQuery = Penjadwalan::whereHas('operators', fn($q) => $q->where('users.id_user', $userId));

        $jadwalTerakhir = (clone $jadwalQuery)
            ->whereDate('tanggal', '>=', $today)
            ->orderBy('tanggal')
            ->limit(5)
            ->get();

        return view('operator.dashboard', [
            'jumlahJadwal'   => (clone $jadwalQuery)->count(),
            'jadwalTerakhir' => $jadwalTerakhir,
        ]);
    }

    public function jadwalIndex()
    {
        $jadwal = Penjadwalan::with('operators')
            ->whereHas('operators', fn($q) => $q->where('users.id_user', auth()->user()->id_user))
            ->orderByDesc('tanggal')
            ->paginate(10);
        return view('operator.jadwal.index', compact('jadwal'));
    }

    public function peralatanIndex(Request $request)
    {
        $peralatan = Peralatan::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('nama_peralatan', 'like', "%{$s}%")
                  ->orWhere('gedung',       'like', "%{$s}%")
            )
            ->when($request->gedung, fn($q, $v) => $q->where('gedung', $v))
            ->orderBy('gedung')
            ->orderBy('nama_peralatan')
            ->paginate(12)
            ->withQueryString();
        $gedungList = Peralatan::distinct()->orderBy('gedung')->pluck('gedung');
        return view('operator.peralatan.index', compact('peralatan', 'gedungList'));
    }
}
