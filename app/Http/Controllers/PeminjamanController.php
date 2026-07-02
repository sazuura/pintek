<?php
namespace App\Http\Controllers;
use App\Models\Peminjaman;
use App\Models\Peralatan;
use App\Services\PeminjamanService;
use Illuminate\Http\Request;

class PeminjamanController extends Controller
{
    public function __construct(private PeminjamanService $service) {}
    public function operatorIndex(Request $request)
    {
        $peminjaman = Peminjaman::with('items.peralatan')
            ->where('id_user', auth()->user()->id_user)
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();
        return view('operator.peminjaman.index', compact('peminjaman'));
    }
    public function operatorCreate(Request $request)
    {
        $peralatan = Peralatan::whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) > 0')
            ->orderBy('gedung')
            ->orderBy('nama_peralatan')
            ->get()
            ->groupBy('gedung');

        $selectedPeralatanId = $request->query('id_peralatan');
        return view('operator.peminjaman.create', compact('peralatan', 'selectedPeralatanId'));
    }
    public function operatorStore(Request $request)
    {
        $request->validate([
            'tanggal_pinjam'          => 'required|date|after_or_equal:today',
            'tanggal_kembali_rencana' => 'required|date|after:tanggal_pinjam',
            'keperluan'               => 'required|string|max:255',
            'peralatan_ids'           => 'required|array|min:1',
            'peralatan_ids.*'         => 'required|exists:peralatan,id_peralatan',
            'peralatan_jumlah'        => 'required|array',
            'peralatan_jumlah.*'      => 'required|integer|min:1',
        ], [
            'peralatan_ids.required' => 'Pilih minimal 1 peralatan.',
            'tanggal_kembali_rencana.after' => 'Tanggal kembali harus setelah tanggal pinjam.',
        ]);
        try {
            $this->service->ajukan(
                header: [
                    'id_user'                 => auth()->user()->id_user,
                    'tanggal_pinjam'          => $request->tanggal_pinjam,
                    'tanggal_kembali_rencana' => $request->tanggal_kembali_rencana,
                    'keperluan'               => $request->keperluan,
                    'status'                  => 'diajukan',
                ],
                peralatanIds: $request->peralatan_ids,
                jumlahArr:    $request->peralatan_jumlah,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('operator.peminjaman.index')
            ->with('success', 'Pengajuan berhasil dikirim. Notifikasi telah dikirim ke petugas inventaris.');
    }
    public function inventarisIndex(Request $request){
        $peminjaman = Peminjaman::with(['user', 'items.peralatan'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->id_user, fn($q, $v) => $q->where('id_user', $v))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $operatorList = \App\Models\User::where('role', 'operator')
            ->where('status', 'active')
            ->orderBy('nama_user')
            ->get();
        return view('inventaris.peminjaman.index', compact('peminjaman', 'operatorList'));
    }
    public function inventarisApprove(Request $request, string $id)
    {
        $request->validate([
            'catatan_inventaris' => 'nullable|string|max:255',
        ]);
        $peminjaman = Peminjaman::findOrFail($id);
        if (!$peminjaman->isMenunggu()) {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }
        try {
            $this->service->setujui($peminjaman, auth()->user(), $request->catatan_inventaris);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Pengajuan berhasil disetujui.');
    }
    public function inventarisReject(Request $request, string $id)
    {
        $request->validate([
            'catatan_inventaris' => 'required|string|max:255',
        ], [
            'catatan_inventaris.required' => 'Alasan penolakan wajib diisi.',
        ]);
        $peminjaman = Peminjaman::findOrFail($id);
        if (!$peminjaman->isMenunggu()) {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }
        try {
            $this->service->tolak($peminjaman, auth()->user(), $request->catatan_inventaris);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Pengajuan berhasil ditolak.');
    }
    public function inventarisKembali(Request $request, string $id)
    {
        $peminjaman = Peminjaman::findOrFail($id);
        if (!$peminjaman->isDisetujui()) {
            return back()->with('error', 'Hanya peminjaman berstatus "Disetujui" yang bisa dikonfirmasi kembali.');
        }
        try {
            $this->service->konfirmasiKembali($peminjaman, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Pengembalian berhasil dikonfirmasi.');
    }
    public function operatorBatalkan(Request $request, string $id)
    {
        $request->validate([
            'alasan_batal' => 'required|string|max:255',
        ], [
            'alasan_batal.required' => 'Alasan pembatalan wajib diisi.',
        ]);
        $peminjaman = Peminjaman::findOrFail($id);
        abort_if($peminjaman->id_user !== auth()->user()->id_user, 403);
        try {
            $this->service->batalkan($peminjaman, $request->alasan_batal);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('operator.peminjaman.index')
            ->with('success', 'Pengajuan berhasil dibatalkan dan notifikasi WA telah dikirim ke inventaris.');
    }
}
