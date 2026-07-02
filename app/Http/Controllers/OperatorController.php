<?php
namespace App\Http\Controllers;
use App\Models\Absensi;
use App\Models\JadwalPeralatan;
use App\Models\Peralatan;
use App\Models\Penjadwalan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today('Asia/Jakarta');
        $userId = auth()->user()->id_user;
        $absensiStats = Absensi::where('id_user', $userId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $jadwalTerakhir = Absensi::with('penjadwalan')
            ->where('id_user', $userId)
            ->whereDate('tanggal', '>=', $today)
            ->orderBy('tanggal')
            ->limit(5)
            ->get();
        return view('operator.dashboard', [
            'jumlahJadwal'    => Absensi::where('id_user', $userId)->count(),
            'jumlahPeralatan' => JadwalPeralatan::whereHas('penjadwalan.absensi',
                                    fn($q) => $q->where('id_user', $userId))->count(),
            'totalHadir'      => $absensiStats[Absensi::STATUS_HADIR]           ?? 0,
            'totalIzin'       => ($absensiStats[Absensi::STATUS_IZIN]            ?? 0)
                               + ($absensiStats[Absensi::STATUS_IZIN_DISETUJUI]  ?? 0),
            'totalSakit'      => ($absensiStats[Absensi::STATUS_SAKIT]           ?? 0)
                               + ($absensiStats[Absensi::STATUS_SAKIT_DISETUJUI] ?? 0),
            'totalAlpha'      => $absensiStats[Absensi::STATUS_ALPHA]            ?? 0,
            'jadwalTerakhir'  => $jadwalTerakhir,
        ]);
    }
    public function jadwalIndex()
    {
        $jadwal = Penjadwalan::with(['absensi.user', 'jadwalPeralatan.peralatan'])
            ->whereHas('absensi', fn($q) => $q->where('id_user', auth()->user()->id_user))
            ->orderByDesc('tanggal')
            ->paginate(10);
        return view('operator.jadwal.index', compact('jadwal'));
    }
    public function absensiIndex()
    {
        $userId = auth()->user()->id_user;
        $this->autoAlpha($userId);
        $jadwalAktif = Absensi::with(['penjadwalan.jadwalPeralatan.peralatan', 'dokumentasi'])
            ->where('id_user', $userId)
            ->whereHas('penjadwalan', fn($q) =>
                $q->whereDate('tanggal', '>=', now('Asia/Jakarta')->toDateString())
            )
            ->orderBy('tanggal')
            ->get();
        $riwayat = Absensi::with('penjadwalan')
            ->where('id_user', $userId)
            ->orderByDesc('tanggal')
            ->paginate(10);
        return view('operator.absensi.index', compact('jadwalAktif', 'riwayat'));
    }
    public function absensiStore(Request $request)
    {
        $request->validate([
            'id_absensi' => 'required|exists:absensi,id_absensi',
            'status'     => 'required|in:hadir,izin,sakit',
            'keterangan' => 'nullable|string|max:255',
        ]);
        $absensi = Absensi::with('penjadwalan')->findOrFail($request->id_absensi);
        abort_if($absensi->id_user !== auth()->user()->id_user, 403);
        if ($absensi->isFinal()) {
            return back()->with('error', 'Presensi ini sudah divalidasi dan tidak dapat diubah.');
        }
        $now    = Carbon::now('Asia/Jakarta');
        $jadwal = $absensi->penjadwalan;
        $start  = $jadwal->startDateTime;
        $end    = $jadwal->endDateTime;
        if ($request->status === 'hadir') {
            if ($now->toDateString() !== $start->toDateString()) {
                return back()->with('error', 'Hadir hanya bisa diisi pada hari jadwal berlangsung.');
            }
            if (!$now->between($start, $end)) {
                return back()->with('error', "Hadir hanya bisa diisi saat rapat berlangsung ({$start->format('H:i')} - {$end->format('H:i')} WIB).");
            }
        }
        if (in_array($request->status, ['izin', 'sakit'])) {
            if ($now->toDateString() >= $start->toDateString()) {
                return back()->with('error', 'Izin/Sakit hanya bisa diajukan paling lambat H-1 sebelum jadwal.');
            }
        }
        $absensi->update([
            'status'     => $request->status,
            'keterangan' => $request->keterangan,
        ]);
        return back()->with('success', 'Presensi berhasil diperbarui.');
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
    public function peralatanKonfirmasi(string $id)
    {
        $item = JadwalPeralatan::with('penjadwalan.absensi')->findOrFail($id);
        $ditugaskan = $item->penjadwalan->absensi
            ->pluck('id_user')
            ->contains(auth()->user()->id_user);
        abort_if(!$ditugaskan, 403, 'Anda tidak ditugaskan di jadwal ini.');
        $item->update(['status_pemasangan' => 'sudah_dipasang']);
        return back()->with('success', 'Peralatan berhasil dikonfirmasi terpasang.');
    }
    private function autoAlpha(string $userId): void
    {
        $now = Carbon::now('Asia/Jakarta');
        Absensi::with('penjadwalan')
            ->where('id_user', $userId)
            ->where('status', Absensi::STATUS_PENDING)
            ->get()
            ->each(function ($absensi) use ($now) {
                $end = $absensi->penjadwalan?->endDateTime;
                if ($end && $now->gt($end)) {
                    $absensi->update([
                        'status'     => Absensi::STATUS_ALPHA,
                        'keterangan' => 'Tidak melakukan presensi.',
                    ]);
                }
            });
    }
}
