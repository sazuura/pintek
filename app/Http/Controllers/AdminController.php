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

        // Rapat "mendatang" dibatasi ke bulan yang lagi dilihat di kalender (bukan lagi
        // fixed 30 hari dari hari ini) - jadi kalau kalender dipindah ke bulan lain, kartu
        // statistik & chart ikut menyesuaikan. Untuk bulan yang sudah lewat total, hasilnya
        // otomatis 0 (memang tidak ada lagi yang "mendatang" di bulan yang sudah lewat).
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
        // yang benar-benar terjadi: disetujui/dikembalikan).
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

        // Aktivitas Terbaru sengaja TETAP selalu "14 hari terakhir dari hari ini" terlepas
        // dari bulan yang dipilih di kalender - karena "terbaru" secara alami berarti dekat
        // dengan sekarang, bukan bulan yang sedang di-browse.
        $activities = $this->recentActivities();
        $kalender   = $this->jadwalKalender($bulanAktif);

        // Detail tambahan tiap stat card: tren dihitung dari data asli, dibandingkan
        // terhadap bulan SEBELUM bulan yang sedang dilihat (bukan lagi N hari dari hari ini).
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
     * Dipakai untuk kartu yang angkanya kecil, di mana persentase jadi terkesan
     * berlebihan/menyesatkan (mis. dari 1 ke 2 rapat itu "100%" tapi tidak berarti apa-apa).
     * Ditampilkan cukup lewat ikon panah + angka, tanpa teks penjelas.
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
     * Query ini mengambil created_at asli dari database (bukan hardcode), jadi begitu ada
     * jadwal/peminjaman sungguhan dibuat lewat aplikasi, otomatis ikut tampil di sini.
     * Untuk sementara data seeder juga ikut ditampilkan (belum ada data produksi asli).
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
     * serta daftar jadwal per tanggal (dipakai saat tanggal diklik di frontend).
     * Rapat yang sudah dibatalkan sengaja tidak diikutkan sama sekali di kalender ini.
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
     * live). Selain ditampilkan sebagai tabel, data yang sama juga disertakan sebagai
     * headers+rows supaya jsPDF+AutoTable di sisi browser bisa langsung membuat PDF
     * asli (teks vektor, bisa di-select/search) dan otomatis diunduh - window.print()
     * tetap disediakan sebagai tombol fallback manual di halamannya.
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
                'pdfHeaders'     => ['#', 'Judul Rapat', 'Peralatan', 'Peminjam', 'Tgl Pinjam', 'Status'],
                'pdfRows'        => $this->barisPdfPeralatan($peralatan),
                'pdfStatusIndex' => 5,
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
                $j->tanggal->translatedFormat('D, d/m/Y'),
                str_contains($j->platform, 'Online') ? 'Online' : 'Offline',
                $status,
            ];
        })->toArray();
    }

    private function barisPdfPeralatan($peralatan): array
    {
        return $peralatan->values()->map(function ($p, $index) {
            $daftarAlat = $p->items->map(function ($item) {
                $nama   = $item->peralatan->nama_peralatan ?? '-';
                $seri   = $item->peralatan->kode_barang ?? '-';
                $gedung = $item->peralatan->gedung ?? '-';
                return "{$nama} ({$seri}, {$gedung}) x{$item->jumlah}";
            })->join("\n");

            return [
                $index + 1,
                $p->penjadwalan->judul_kegiatan ?? $p->keperluan,
                $daftarAlat,
                $p->user->nama_user ?? '-',
                $p->tanggal_pinjam->format('d/m/Y'),
                $p->badge['label'],
            ];
        })->toArray();
    }

    /**
     * Excel tetap pakai PhpSpreadsheet (lewat maatwebsite/excel) - beda dengan PDF,
     * library ini tidak merender HTML/CSS jadi tidak kena masalah kompatibilitas
     * Tailwind seperti dompdf, dan hasilnya file .xlsx asli tanpa peringatan
     * "format tidak cocok" dari Excel (yang muncul kalau pakai trik HTML-sebagai-.xls).
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
            ->orderByDesc('tanggal');
    }

    /**
     * Helper method untuk query pemakaian peralatan (monitoring) via Peminjaman -
     * sumber kebenaran alat dipakai sekarang, bukan alokasi manual admin ke jadwal.
     */
    /**
     * Laporan peralatan dikelompokkan per peminjaman (bukan per item), supaya peminjam &
     * tanggal pinjam yang sama tidak duplikat jadi banyak baris - daftar alatnya ditampilkan
     * lewat dropdown per baris.
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
