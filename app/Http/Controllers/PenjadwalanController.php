<?php
namespace App\Http\Controllers;
use App\Models\Penjadwalan;
use App\Models\Peralatan;
use App\Models\User;
use App\Services\PenjadwalanService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PenjadwalanController extends Controller
{
    public function __construct(private PenjadwalanService $service) {}

    public function index(Request $request)
    {
        $this->updateJadwalSelesai();
        $jadwal = Penjadwalan::with('operators')
            ->when($request->search, fn($q, $s) =>
                $q->where('judul_kegiatan', 'like', "%{$s}%")
                  ->orWhere('platform', 'like', "%{$s}%")
            )
            ->when($request->platform, fn($q, $p) =>
                $q->where('platform', 'like', "%{$p}%")
            )
            ->when($request->status, fn($q, $o) =>
                $q->where('status', 'like', "%{$o}%")
            )
            ->orderByDesc('tanggal')
            ->paginate(10)
            ->withQueryString();
        return view('admin.jadwal.index', compact('jadwal'));
    }

    public function create()
    {
        return view('admin.jadwal.create', [
            'operators'      => $this->operatorsWithJadwalDates(),
            'daftarPeralatan' => $this->peralatanUntukReferensi(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasiForm($request);
        try {
            $this->service->buat(
                data:          $data['jadwal'],
                operatorIds:   $data['operator_ids'],
                peralatanSync: $data['peralatan_sync'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil ditambahkan dan notifikasi WA telah dikirim.');
    }

    public function show(string $id)
    {
        $jadwal = Penjadwalan::with(['operators', 'peminjaman.user', 'peralatanReferensi'])->findOrFail($id);
        return view('admin.jadwal.show', compact('jadwal'));
    }

    public function edit(string $id)
    {
        $jadwal = Penjadwalan::with(['operators', 'peralatanReferensi'])->findOrFail($id);
        return view('admin.jadwal.edit', [
            'jadwal'             => $jadwal,
            'operators'          => $this->operatorsWithJadwalDates(),
            'selectedOperators'  => $jadwal->operators->pluck('id_user')->toArray(),
            'daftarPeralatan'    => $this->peralatanUntukReferensi(),
            'selectedPeralatan'  => $jadwal->peralatanReferensi,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $jadwal = Penjadwalan::findOrFail($id);
        $data   = $this->validasiForm($request, isUpdate: true);
        try {
            $this->service->ubah(
                jadwal:        $jadwal,
                data:          $data['jadwal'],
                operatorIds:   $data['operator_ids'],
                peralatanSync: $data['peralatan_sync'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('admin.jadwal.index')
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

    public function destroy(string $id)
    {
        $this->service->hapus(Penjadwalan::findOrFail($id));
        return redirect()->route('admin.jadwal.index')
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
            'operator_ids'       => 'required|array|min:1',
            'operator_ids.*'     => 'exists:users,id_user',
            'peralatan_ids'      => 'nullable|array',
            'peralatan_ids.*'    => 'nullable|exists:peralatan,id_peralatan',
            'peralatan_jumlah'   => 'nullable|array',
            'peralatan_jumlah.*' => 'nullable|integer|min:1',
        ]);
        return [
            'jadwal' => [
                'judul_kegiatan' => $validated['judul_kegiatan'],
                'tanggal'        => $validated['tanggal'],
                'waktu_mulai'    => $validated['waktu_mulai'],
                'waktu_selesai'  => $validated['waktu_selesai'],
                'platform'       => $validated['platform'],
                'keterangan'     => $validated['keterangan'] ?? null,
            ],
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
        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil dibatalkan dan notifikasi WA telah dikirim ke operator.');
    }

    private function updateJadwalSelesai()
    {
        Penjadwalan::where('status', 'aktif')
            ->whereRaw(
                "TIMESTAMP(tanggal, waktu_selesai) <= NOW()"
            )
            ->update([
                'status' => 'selesai'
            ]);
    }
}
