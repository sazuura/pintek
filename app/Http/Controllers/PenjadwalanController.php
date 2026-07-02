<?php
namespace App\Http\Controllers;
use App\Models\Peralatan;
use App\Models\Penjadwalan;
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
        $jadwal = Penjadwalan::with(['absensi.user', 'jadwalPeralatan.peralatan'])
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
            'operators'  => User::where('role', 'operator')->where('status', 'active')->orderBy('nama_user')->get(),
            'peralatans' => Peralatan::orderBy('gedung')->orderBy('nama_peralatan')->get(),
        ]);
    }
    public function store(Request $request)
    {
        $data = $this->validasiForm($request);
        try {
            $this->service->buat(
                data:         $data['jadwal'],
                operatorIds:  $data['operator_ids'],
                peralatanIds: $request->input('peralatan_ids', []),
                jumlahArr:    $request->input('peralatan_jumlah', []),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil ditambahkan dan notifikasi WA telah dikirim.');
    }
    public function show(string $id)
    {
        $jadwal = Penjadwalan::with([
            'absensi.user',
            'jadwalPeralatan.peralatan',
            'pemateri',
        ])->findOrFail($id);
        return view('admin.jadwal.show', compact('jadwal'));
    }
    public function edit(string $id)
    {
        $jadwal = Penjadwalan::with(['absensi.user', 'jadwalPeralatan.peralatan'])->findOrFail($id);
        return view('admin.jadwal.edit', [
            'jadwal'            => $jadwal,
            'operators'         => User::where('role', 'operator')->where('status', 'active')->orderBy('nama_user')->get(),
            'peralatans'        => Peralatan::orderBy('gedung')->orderBy('nama_peralatan')->get(),
            'selectedOperators' => $jadwal->absensi->pluck('id_user')->toArray(),
            'selectedPeralatan' => $jadwal->jadwalPeralatan->keyBy('id_peralatan'),
        ]);
    }
    public function update(Request $request, string $id)
    {
        $jadwal = Penjadwalan::findOrFail($id);
        $data   = $this->validasiForm($request, isUpdate: true);
        try {
            $this->service->ubah(
                jadwal:       $jadwal,
                data:         $data['jadwal'],
                operatorIds:  $data['operator_ids'],
                peralatanIds: $request->input('peralatan_ids', []),
                jumlahArr:    $request->input('peralatan_jumlah', []),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
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
            'judul_kegiatan' => 'required|string|max:150',
            'tanggal'        => 'required|date' . ($isUpdate ? '' : '|after_or_equal:today'),
            'waktu_mulai'    => 'required|date_format:H:i',
            'waktu_selesai'  => 'required|date_format:H:i|after:waktu_mulai',
            'platform'       => 'required|string|max:100',
            'keterangan'     => 'nullable|string|max:255',
            'id_pemateri'    => 'nullable|exists:users,id_user',
            'operator_ids'   => 'required|array|min:1',
            'operator_ids.*' => 'required|exists:users,id_user',
        ], [
            'operator_ids.required' => 'Pilih minimal 1 operator.',
            'waktu_selesai.after'   => 'Waktu selesai harus setelah waktu mulai.',
        ]);
        return [
            'jadwal' => [
                'judul_kegiatan' => $validated['judul_kegiatan'],
                'tanggal'        => $validated['tanggal'],
                'waktu_mulai'    => $validated['waktu_mulai'],
                'waktu_selesai'  => $validated['waktu_selesai'],
                'platform'       => $validated['platform'],
                'keterangan'     => $validated['keterangan'] ?? null,
                'id_pemateri'    => $validated['id_pemateri'] ?? null,
            ],
            'operator_ids' => $validated['operator_ids'],
        ];
    }
    public function batalkan(Request $request, string $id)
    {
        $request->validate([
            'alasan_batal' => 'required|string|max:255',
        ], [
            'alasan_batal.required' => 'Alasan pembatalan wajib diisi.',
        ]);
        $jadwal = Penjadwalan::with('absensi.user')->findOrFail($id);
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
