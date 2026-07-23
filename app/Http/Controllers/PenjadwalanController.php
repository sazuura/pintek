<?php
namespace App\Http\Controllers;
use App\Models\Penjadwalan;
use App\Models\Peralatan;
use App\Models\User;
use App\Services\PenjadwalanService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PenjadwalanController extends Controller
{
    public function __construct(private PenjadwalanService $service) {}

    public function index(Request $request)
    {
        // Kolom "status" di database cuma menyimpan selesai/dibatalkan - label
        // "Aktif" vs "Selesai" di tampilan sebenarnya diturunkan dari apakah waktu
        // rapat sudah lewat atau belum (lihat isDibatalkan()/$sudahLewat di view),
        // jadi filternya juga harus dihitung dari tanggal+waktu, bukan match string.
        $jadwal = Penjadwalan::with(['operators', 'peralatanReferensi'])
            ->when($request->search, fn($q, $s) =>
                $q->where('judul_kegiatan', 'like', "%{$s}%")
                  ->orWhere('platform', 'like', "%{$s}%")
            )
            ->when($request->platform, fn($q, $p) =>
                $q->where('platform', 'like', "%{$p}%")
            )
            ->when($request->status, fn($q, $s) => match ($s) {
                'aktif'      => $q->where('status', '!=', 'dibatalkan')->whereRaw('TIMESTAMP(tanggal, waktu_selesai) >= NOW()'),
                'selesai'    => $q->where('status', '!=', 'dibatalkan')->whereRaw('TIMESTAMP(tanggal, waktu_selesai) < NOW()'),
                'dibatalkan' => $q->where('status', 'dibatalkan'),
                default      => $q,
            })
            // Urutan default: rapat yang masih Aktif selalu tampil paling atas,
            // diurutkan dari tanggal yang paling dekat dengan hari ini (fokus utama
            // pengguna). Rapat yang sudah Selesai ATAU Dibatalkan digabung jadi satu
            // kelompok "riwayat" di bagian bawah dan diurutkan murni dari yang paling
            // BARU terjadi ke yang paling lama - bukan dipisah per status - supaya
            // tidak ada lompatan tanggal yang jauh (mis. rapat dibatalkan bulan
            // Februari nyempil duluan sebelum rapat selesai bulan Juli yang jauh
            // lebih baru). Definisi "sudah berakhir" sama persis dengan filter status
            // di atas supaya konsisten dengan badge yang ditampilkan.
            ->orderByRaw("(status = 'dibatalkan' OR TIMESTAMP(tanggal, waktu_selesai) < NOW()) ASC")
            ->orderByRaw("CASE WHEN status = 'dibatalkan' OR TIMESTAMP(tanggal, waktu_selesai) < NOW()
                          THEN -DATEDIFF(tanggal, CURDATE()) ELSE DATEDIFF(tanggal, CURDATE()) END ASC")
            ->orderBy('waktu_mulai')
            ->paginate(10)
            ->withQueryString();
        $bisaTambah = auth()->user()->punyaAkses('jadwal', 'tambah');
        $bisaUbah   = auth()->user()->punyaAkses('jadwal', 'ubah');
        return view('dashboard.jadwal.index', compact('jadwal', 'bisaTambah', 'bisaUbah'));
    }

    public function create()
    {
        abort_if(!auth()->user()->punyaAkses('jadwal', 'tambah'), 403, 'Anda tidak memiliki akses untuk menambah jadwal.');
        return view('dashboard.jadwal.create', [
            'operators'      => $this->operatorsWithJadwalDates(),
            'daftarPeralatan' => $this->peralatanUntukReferensi(),
            'zoomJadwal'      => $this->zoomJadwalPerAkun(),
        ]);
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->punyaAkses('jadwal', 'tambah'), 403, 'Anda tidak memiliki akses untuk menambah jadwal.');
        $data = $this->validasiForm($request);
        try {
            $this->service->buat(
                data:          $data['jadwal'],
                operatorIds:   $data['operator_ids'],
                peralatanSync: $data['peralatan_sync'],
                akunPilihan:   $data['zoom_akun_pilihan'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($peringatan = $this->service->peringatanZoom()) {
            return redirect()->route(auth()->user()->role . '.jadwal.index')
                ->with('warning', "Jadwal berhasil disimpan. {$peringatan}");
        }
        return redirect()->route(auth()->user()->role . '.jadwal.index')
            ->with('success', 'Jadwal berhasil ditambahkan dan notifikasi WA telah dikirim.');
    }

    public function show(string $id)
    {
        $jadwal = Penjadwalan::with(['operators', 'peminjaman.user', 'peralatanReferensi'])->findOrFail($id);
        return view('dashboard.jadwal.show', compact('jadwal'));
    }

    public function edit(string $id)
    {
        abort_if(!auth()->user()->punyaAkses('jadwal', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
        $jadwal = Penjadwalan::with(['operators', 'peralatanReferensi'])->findOrFail($id);
        return view('dashboard.jadwal.edit', [
            'jadwal'             => $jadwal,
            'operators'          => $this->operatorsWithJadwalDates(),
            'selectedOperators'  => $jadwal->operators->pluck('id_user')->toArray(),
            'daftarPeralatan'    => $this->peralatanUntukReferensi(),
            'selectedPeralatan'  => $jadwal->peralatanReferensi,
            'zoomJadwal'         => $this->zoomJadwalPerAkun($id),
        ]);
    }

    public function update(Request $request, string $id)
    {
        abort_if(!auth()->user()->punyaAkses('jadwal', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
        $jadwal = Penjadwalan::findOrFail($id);
        $data   = $this->validasiForm($request, isUpdate: true);
        try {
            $this->service->ubah(
                jadwal:        $jadwal,
                data:          $data['jadwal'],
                operatorIds:   $data['operator_ids'],
                peralatanSync: $data['peralatan_sync'],
                akunPilihan:   $data['zoom_akun_pilihan'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($peringatan = $this->service->peringatanZoom()) {
            return redirect()->route(auth()->user()->role . '.jadwal.index')
                ->with('warning', "Jadwal berhasil diperbarui. {$peringatan}");
        }
        return redirect()->route(auth()->user()->role . '.jadwal.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    private function operatorsWithJadwalDates()
    {
        return User::where('role', 'operator')
            ->where('status', 'active')
            ->orderBy('nama_user')
            ->with('jadwalDitugaskan:id_penjadwalan,tanggal')
            ->get();
    }

    private function peralatanUntukReferensi()
    {
        // stok/rusak ikut diambil supaya accessor stok_tersedia bisa dipakai
        // di view untuk menandai alat yang stoknya sedang habis (data-badge "Stok Habis").
        return Peralatan::orderBy('nama_peralatan')->get(['id_peralatan', 'nama_peralatan', 'gedung', 'stok', 'rusak']);
    }

    /**
     * Jadwal Zoom-otomatis existing, dikelompokkan per akun - dipakai JS di form
     * create/edit untuk disable opsi "Akun 1"/"Akun 2" di dropdown pilihan akun
     * kalau akun itu sudah bentrok jadwal di tanggal+jam yang sedang diisi.
     */
    private function zoomJadwalPerAkun(?string $excludeId = null)
    {
        return Penjadwalan::where('link_otomatis', true)
            ->when($excludeId, fn($q) => $q->where('id_penjadwalan', '!=', $excludeId))
            ->get(['zoom_account', 'tanggal', 'waktu_mulai', 'waktu_selesai'])
            ->groupBy('zoom_account')
            ->map(fn($grup) => $grup->map(fn($j) => [
                'tanggal' => $j->tanggal->format('Y-m-d'),
                'mulai'   => substr($j->waktu_mulai, 0, 5),
                'selesai' => substr($j->waktu_selesai, 0, 5),
            ])->values());
    }

    public function destroy(string $id)
    {
        abort_if(!auth()->user()->punyaAkses('jadwal', 'hapus'), 403, 'Anda tidak memiliki akses untuk menghapus jadwal.');
        $this->service->hapus(Penjadwalan::findOrFail($id));
        return redirect()->route(auth()->user()->role . '.jadwal.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    private function validasiForm(Request $request, bool $isUpdate = false): array
    {
        $request->merge([
            'waktu_mulai'   => substr($request->waktu_mulai   ?? '', 0, 5),
            'waktu_selesai' => substr($request->waktu_selesai ?? '', 0, 5),
        ]);
        $validated = $request->validate([
            'judul_kegiatan'   => 'required|string|max:150',
            'tanggal'          => 'required|date' . ($isUpdate ? '' : '|after_or_equal:today'),
            'waktu_mulai'      => 'required|date_format:H:i',
            'waktu_selesai'    => 'required|date_format:H:i|after:waktu_mulai',
            'platform'         => 'required|string|max:100',
            'keterangan'       => 'nullable|string|max:255',
            'lokasi_fisik'     => 'nullable|string|max:255',
            'link_otomatis'      => 'nullable|boolean',
            'zoom_akun_pilihan'  => 'nullable|in:akun_1,akun_2',
            'operator_ids'       => 'required|array|min:1',
            'operator_ids.*'     => 'exists:users,id_user',
            'peralatan_ids'      => 'nullable|array',
            'peralatan_ids.*'    => 'nullable|exists:peralatan,id_peralatan',
            'peralatan_jumlah'   => 'nullable|array',
            'peralatan_jumlah.*' => 'nullable|integer|min:1',
        ]);

        // Rule per-field di atas cuma menjamin tanggal >= hari ini dan selesai > mulai -
        // untuk rapat di HARI INI, jamnya masih bisa diisi jam yang sudah terlewati
        // sehingga jadwal baru langsung lahir berstatus "Selesai". Gabungan
        // tanggal+waktu_selesai harus masih di masa depan.
        $selesaiPada = Carbon::parse($validated['tanggal'] . ' ' . $validated['waktu_selesai']);
        if ($selesaiPada->isPast()) {
            throw ValidationException::withMessages([
                'waktu_selesai' => 'Waktu rapat sudah terlewati - jadwal tidak bisa dibuat/diubah ke jam yang sudah berlalu.',
            ]);
        }

        return [
            'jadwal' => [
                'judul_kegiatan' => $validated['judul_kegiatan'],
                'tanggal'        => $validated['tanggal'],
                'waktu_mulai'    => $validated['waktu_mulai'],
                'waktu_selesai'  => $validated['waktu_selesai'],
                'platform'       => $validated['platform'],
                'keterangan'     => $validated['keterangan'] ?? null,
                'lokasi_fisik'   => $validated['lokasi_fisik'] ?? null,
                'link_otomatis'  => $validated['link_otomatis'] ?? false,
            ],
            'zoom_akun_pilihan' => $validated['zoom_akun_pilihan'] ?? null,
            'peralatan_sync' => $this->buildPeralatanSync($validated['peralatan_ids'] ?? [], $validated['peralatan_jumlah'] ?? []),
            'operator_ids' => $validated['operator_ids'],
        ];
    }

    private function buildPeralatanSync(array $peralatanIds, array $peralatanJumlah): array
    {
        $sync = [];
        foreach ($peralatanIds as $i => $id) {
            if (!$id) continue;
            $jumlah = (int) ($peralatanJumlah[$i] ?? 1);
            $sync[$id] = ['jumlah' => $jumlah > 0 ? $jumlah : 1];
        }
        return $sync;
    }

    public function batalkan(Request $request, string $id)
    {
        abort_if(!auth()->user()->punyaAkses('jadwal', 'ubah'), 403, 'Anda tidak memiliki akses untuk membatalkan jadwal.');
        $request->validate([
            'alasan_batal' => 'required|string|max:255',
        ], [
            'alasan_batal.required' => 'Alasan pembatalan wajib diisi.',
        ]);
        $jadwal = Penjadwalan::with('operators')->findOrFail($id);
        try {
            $this->service->batalkan($jadwal, $request->alasan_batal);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route(auth()->user()->role . '.jadwal.index')
            ->with('success', 'Jadwal berhasil dibatalkan dan notifikasi WA telah dikirim ke operator.');
    }
}
