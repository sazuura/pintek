<?php
namespace App\Http\Controllers;
use App\Models\Peminjaman;
use App\Models\Penjadwalan;
use App\Models\Peralatan;
use App\Services\PeminjamanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PeminjamanController extends Controller
{
    public function __construct(private PeminjamanService $service) {}
    public function operatorIndex(Request $request)
    {
        $peminjaman = Peminjaman::with(['items.peralatan', 'penjadwalan'])
            ->where('id_user', auth()->user()->id_user)
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();
        return view('dashboard.peminjaman.operator-index', compact('peminjaman'));
    }
    public function operatorCreate(Request $request)
    {
        $peralatan = Peralatan::whereRaw('(stok - COALESCE(rusak,0)) > 0')
            ->orderBy('gedung')
            ->orderBy('nama_peralatan')
            ->get()
            ->groupBy('gedung');

        $selectedPeralatanId = $request->query('id_peralatan');
        $jadwalAktif          = $this->jadwalAktifOperator();
        return view('dashboard.peminjaman.create', compact('peralatan', 'selectedPeralatanId', 'jadwalAktif'));
    }
    public function operatorStore(Request $request)
    {
        abort_if(!auth()->user()->punyaAkses('peminjaman', 'tambah'), 403, 'Anda tidak memiliki akses untuk mengajukan peminjaman.');
        $idJadwalAktif = $this->jadwalAktifOperator()->pluck('id_penjadwalan');
        $request->validate($this->aturanValidasiPengajuan($idJadwalAktif), $this->pesanValidasiPengajuan());

        try {
            $this->service->ajukan(
                header: [
                    'id_user'                 => auth()->user()->id_user,
                    'id_penjadwalan'          => $request->id_penjadwalan ?: null,
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

    /**
     * Dipanggil lewat AJAX sesaat sebelum form submit (create & edit) - mengecek apakah
     * operator yang login sudah berkali-kali mengajukan alat yang sama untuk tanggal
     * pinjam yang sama (indikasi tidak sengaja submit berulang/spam), supaya bisa
     * ditampilkan sebagai peringatan (bukan blokir) lewat modal sebelum benar-benar
     * disimpan.
     */
    public function operatorCekSpam(Request $request)
    {
        $request->validate([
            'tanggal_pinjam'  => 'required|date',
            'peralatan_ids'   => 'nullable|array',
        ]);

        return response()->json([
            'peringatan' => $this->peringatanSpam(
                $request->tanggal_pinjam,
                $request->input('peralatan_ids', []),
                $request->input('kecuali_id_peminjaman'),
            ),
        ]);
    }

    /**
     * Alat yang sudah diajukan operator ybs (status diajukan/disetujui/dikembalikan)
     * sebanyak >= 2 kali untuk tanggal pinjam yang sama - dicocokkan lewat NAMA alat
     * (bukan id_peralatan) supaya alat yang sama tapi baris stoknya beda gedung tetap
     * terhitung. Dikecualikan pengajuan yang sedang diedit sendiri (tidak menghitung
     * dirinya sendiri sebagai "pengajuan sebelumnya").
     */
    private function peringatanSpam(string $tanggalPinjam, array $peralatanIds, ?string $kecualiIdPeminjaman = null): array
    {
        $peralatanIds = array_values(array_unique(array_filter($peralatanIds)));
        if (empty($peralatanIds)) {
            return [];
        }

        $namaPerId = Peralatan::whereIn('id_peralatan', $peralatanIds)->pluck('nama_peralatan', 'id_peralatan');

        $jumlahPerNama = Peminjaman::where('id_user', auth()->id())
            ->where('tanggal_pinjam', $tanggalPinjam)
            ->whereIn('status', ['diajukan', 'disetujui', 'dikembalikan'])
            ->when($kecualiIdPeminjaman, fn($q, $id) => $q->where('id_peminjaman', '!=', $id))
            ->with('items.peralatan')
            ->get()
            ->flatMap(fn($p) => $p->items)
            ->groupBy(fn($item) => $item->peralatan->nama_peralatan ?? '')
            ->map->count();

        $peringatan = [];
        foreach ($peralatanIds as $id) {
            $nama = $namaPerId[$id] ?? null;
            if (!$nama) continue;
            $sebelumnya = $jumlahPerNama[$nama] ?? 0;
            if ($sebelumnya >= 2) {
                $peringatan[] = ['nama' => $nama, 'jumlah_sebelumnya' => $sebelumnya];
            }
        }
        return $peringatan;
    }

    public function operatorEdit(string $id)
    {
        $peminjaman = Peminjaman::with('items.peralatan')->findOrFail($id);
        abort_if($peminjaman->id_user !== auth()->user()->id_user, 403);
        if (!$peminjaman->isMenunggu()) {
            return redirect()->route('operator.peminjaman.index')
                ->with('error', 'Pengajuan yang sudah diproses tidak dapat diubah.');
        }

        $peralatan = Peralatan::whereRaw('(stok - COALESCE(rusak,0)) > 0')
            ->orderBy('gedung')
            ->orderBy('nama_peralatan')
            ->get()
            ->groupBy('gedung');

        $jadwalAktif = $this->jadwalAktifOperator();
        return view('dashboard.peminjaman.edit', compact('peminjaman', 'peralatan', 'jadwalAktif'));
    }

    public function operatorUpdate(Request $request, string $id)
    {
        abort_if(!auth()->user()->punyaAkses('peminjaman', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah pengajuan peminjaman.');
        $peminjaman = Peminjaman::findOrFail($id);
        abort_if($peminjaman->id_user !== auth()->user()->id_user, 403);

        $idJadwalAktif = $this->jadwalAktifOperator()->pluck('id_penjadwalan');
        $request->validate($this->aturanValidasiPengajuan($idJadwalAktif), $this->pesanValidasiPengajuan());

        try {
            $this->service->ubah(
                peminjaman: $peminjaman,
                header: [
                    'id_penjadwalan'          => $request->id_penjadwalan ?: null,
                    'tanggal_pinjam'          => $request->tanggal_pinjam,
                    'tanggal_kembali_rencana' => $request->tanggal_kembali_rencana,
                    'keperluan'               => $request->keperluan,
                ],
                peralatanIds: $request->peralatan_ids,
                jumlahArr:    $request->peralatan_jumlah,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('operator.peminjaman.index')
            ->with('success', 'Pengajuan berhasil diperbarui. Notifikasi telah dikirim ke petugas inventaris.');
    }

    private function aturanValidasiPengajuan($idJadwalAktif): array
    {
        return [
            'id_penjadwalan'          => ['nullable', Rule::in($idJadwalAktif)],
            'tanggal_pinjam'          => 'required|date|after_or_equal:today',
            'tanggal_kembali_rencana' => 'required|date|after_or_equal:tanggal_pinjam',
            'keperluan'               => 'required|string|max:255',
            'peralatan_ids'           => 'required|array|min:1',
            'peralatan_ids.*'         => 'required|exists:peralatan,id_peralatan',
            'peralatan_jumlah'        => 'required|array',
            'peralatan_jumlah.*'      => 'required|integer|min:1',
        ];
    }

    private function pesanValidasiPengajuan(): array
    {
        return [
            'id_penjadwalan.in'       => 'Jadwal yang dipilih tidak valid.',
            'peralatan_ids.required'  => 'Pilih minimal 1 peralatan.',
            'tanggal_kembali_rencana.after_or_equal' => 'Tanggal kembali harus sama dengan atau setelah tanggal pinjam.',
        ];
    }

    /**
     * Jadwal milik operator yang login, berstatus aktif (belum lewat & belum dibatalkan) -
     * dipakai sebagai pilihan "kaitkan ke jadwal" saat mengajukan peminjaman.
     */
    private function jadwalAktifOperator()
    {
        return Penjadwalan::whereHas('operators', fn($q) => $q->where('users.id_user', auth()->user()->id_user))
            ->where('status', '!=', 'dibatalkan')
            ->whereRaw("TIMESTAMP(tanggal, waktu_selesai) >= NOW()")
            ->with('peralatanReferensi')
            ->orderBy('tanggal')
            ->get();
    }
    public function inventarisIndex(Request $request){
        $peminjaman = Peminjaman::with(['user', 'items.peralatan', 'penjadwalan'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->id_user, fn($q, $v) => $q->where('id_user', $v))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $operatorList = \App\Models\User::where('role', 'operator')
            ->where('status', 'active')
            ->orderBy('nama_user')
            ->get();
        return view('dashboard.peminjaman.inventaris-index', compact('peminjaman', 'operatorList'));
    }
    public function inventarisApprove(Request $request, string $id)
    {
        abort_if(!auth()->user()->punyaAkses('peminjaman', 'ubah'), 403, 'Anda tidak memiliki akses untuk menyetujui pengajuan.');
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
        abort_if(!auth()->user()->punyaAkses('peminjaman', 'ubah'), 403, 'Anda tidak memiliki akses untuk menolak pengajuan.');
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
        abort_if(!auth()->user()->punyaAkses('peminjaman', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengonfirmasi pengembalian.');
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
        abort_if(!auth()->user()->punyaAkses('peminjaman', 'ubah'), 403, 'Anda tidak memiliki akses untuk membatalkan pengajuan.');
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
