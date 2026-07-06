<?php
namespace App\Http\Controllers;
use App\Models\Peminjaman;
use App\Models\Peralatan;
use App\Models\Penjadwalan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function dashboard(Request $request)
    {
        $userId = auth()->user()->id_user;
        $today  = Carbon::today('Asia/Jakarta');

        $jadwalQuery     = Penjadwalan::whereHas('operators', fn($q) => $q->where('users.id_user', $userId));
        $peminjamanQuery = Peminjaman::where('id_user', $userId);

        $stats = [
            'jumlahJadwal'   => (clone $jadwalQuery)->where('status', '!=', 'dibatalkan')->whereDate('tanggal', '>=', $today)->count(),
            'menungguCount'  => (clone $peminjamanQuery)->where('status', 'diajukan')->count(),
            'disetujuiCount' => (clone $peminjamanQuery)->where('status', 'disetujui')->count(),
            'ditolakCount'   => (clone $peminjamanQuery)->where('status', 'ditolak')->count(),
        ];

        // Semua peminjaman yang sedang dipakai (sudah disetujui, belum ditandai dikembalikan
        // oleh inventaris) - bukan cuma yang sudah lewat rencana kembalinya, karena pengembalian
        // H+1/H+2 (apalagi kalau lewat akhir pekan) itu wajar dan bukan berarti "terlambat".
        $perluDikembalikan = (clone $peminjamanQuery)
            ->where('status', 'disetujui')
            ->with('items.peralatan')
            ->orderBy('tanggal_kembali_rencana')
            ->paginate(4, ['*'], 'kembali_page')
            ->withQueryString();

        // Alat yang paling sering dipinjam operator ini sendiri (hanya peminjaman yang benar-benar
        // terjadi: disetujui/dikembalikan), buat dilihat sebagai chart di atas daftar pengembalian.
        $topPeralatan = \App\Models\PeminjamanItem::query()
            ->join('peminjaman', 'peminjaman_item.id_peminjaman', '=', 'peminjaman.id_peminjaman')
            ->join('peralatan', 'peminjaman_item.id_peralatan', '=', 'peralatan.id_peralatan')
            ->where('peminjaman.id_user', $userId)
            ->whereIn('peminjaman.status', ['disetujui', 'dikembalikan'])
            ->selectRaw('peralatan.nama_peralatan, SUM(peminjaman_item.jumlah) as total_dipinjam')
            ->groupBy('peralatan.nama_peralatan')
            ->orderByDesc('total_dipinjam')
            ->limit(6)
            ->get();

        $activities = $this->recentActivities($userId);
        $kalender   = $this->jadwalKalender($request->query('bulan'), $userId);

        return view('operator.dashboard', array_merge($stats, compact('perluDikembalikan', 'topPeralatan', 'activities', 'kalender')));
    }

    /**
     * 6 aktivitas terbaru (14 hari terakhir) milik operator ini: jadwal baru yang ditugaskan
     * ke dia & pengajuan peminjamannya sendiri (baik yang baru diajukan maupun yang sudah diproses admin).
     */
    private function recentActivities(string $userId)
    {
        $sejak = now()->subDays(14);

        $jadwal = Penjadwalan::whereHas('operators', fn($q) => $q->where('users.id_user', $userId))
            ->whereBetween('created_at', [$sejak, now()])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn($j) => [
                'icon' => 'bx-calendar-plus',
                'text' => "Ditugaskan ke jadwal \"{$j->judul_kegiatan}\"",
                'time' => $j->created_at,
            ]);

        $peminjaman = Peminjaman::where('id_user', $userId)
            ->whereBetween('updated_at', [$sejak, now()])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->map(fn($p) => [
                'icon' => match ($p->status) {
                    'disetujui'    => 'bx-check-circle',
                    'ditolak'      => 'bx-x-circle',
                    'dikembalikan' => 'bx-undo',
                    default        => 'bx-package',
                },
                'text' => match ($p->status) {
                    'disetujui'    => 'Pengajuan peminjaman peralatan disetujui',
                    'ditolak'      => 'Pengajuan peminjaman peralatan ditolak',
                    'dikembalikan' => 'Peminjaman peralatan selesai dikembalikan',
                    default        => 'Mengajukan peminjaman peralatan',
                },
                'time' => $p->updated_at,
            ]);

        return $jadwal->concat($peminjaman)->sortByDesc('time')->take(6)->values();
    }

    /**
     * Kalender bulanan (Min–Sab) khusus jadwal yang ditugaskan ke operator ini, dengan navigasi
     * bulan serta daftar jadwal per tanggal (dipakai saat tanggal diklik di frontend).
     */
    private function jadwalKalender(?string $bulan, string $userId): array
    {
        $bulanAktif = $bulan
            ? Carbon::createFromFormat('Y-m', $bulan)->startOfMonth()
            : now()->startOfMonth();

        $awalGrid  = $bulanAktif->copy()->startOfWeek(Carbon::SUNDAY);
        $akhirGrid = $bulanAktif->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $jadwalRange = Penjadwalan::with('operators')
            ->whereHas('operators', fn($q) => $q->where('users.id_user', $userId))
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

    public function jadwalIndex()
    {
        $jadwal = Penjadwalan::with(['operators', 'peralatanReferensi'])
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
