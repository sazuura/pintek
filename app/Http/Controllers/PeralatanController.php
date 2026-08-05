<?php
namespace App\Http\Controllers;
use App\Helpers\IdGenerator;
use App\Models\Peralatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PeralatanController extends Controller
{
    public function index(Request $request)
    {
        $userRole = auth()->user()->role;

        $stokTersediaRaw = '(stok - COALESCE(rusak,0))';

        $peralatan = Peralatan::query()
            ->when($request->search, function ($q, $s) use ($userRole) {
                return $q->where(function ($subQuery) use ($s, $userRole) {
                    $subQuery->where('nama_peralatan', 'like', "%{$s}%")
                            ->orWhere('kode_barang', 'like', "%{$s}%");
                    if ($userRole === 'admin') {
                        $subQuery->orWhere('gedung', 'like', "%{$s}%");
                    }
                });
            })
            ->when($request->gedung, fn($q, $v) => $q->where('gedung', $v))
            ->when($request->status, fn($q, $v) => match ($v) {
                'terpasang'       => $q->where('status_terpasang', 'terpasang'),
                'tidak_terpasang' => $q->where(function ($query) {
                    $query->where('status_terpasang', 'tidak terpasang')
                          ->orWhereNull('status_terpasang');
                }),
                default            => $q,
            })
            ->when($request->kondisi, fn($q, $v) => match ($v) {
                'baik'  => $q->whereRaw('COALESCE(rusak,0) <= 0'),
                'rusak' => $q->whereRaw('COALESCE(rusak,0) > 0'),
                default => $q,
            })
            ->when($request->urutkan, fn($q, $v) => match ($v) {
                'nama_asc'  => $q->orderBy('nama_peralatan'),
                'nama_desc' => $q->orderByDesc('nama_peralatan'),
                'stok_asc'  => $q->orderByRaw("{$stokTersediaRaw} asc"),
                'stok_desc' => $q->orderByRaw("{$stokTersediaRaw} desc"),
                default     => $q->orderBy('gedung')->orderBy('nama_peralatan'),
            }, fn($q) => $q->orderBy('gedung')->orderBy('nama_peralatan'))
            ->paginate(10)
            ->withQueryString();
        $gedungList = Peralatan::distinct()->orderBy('gedung')->pluck('gedung');
        return view('dashboard.peralatan.index', compact('peralatan', 'gedungList'));
    }
    public function create()
    {
        abort_if(!auth()->user()->punyaAkses('peralatan', 'tambah'), 403, 'Anda tidak memiliki akses untuk menambah peralatan.');
        $gedungList = Peralatan::distinct()->orderBy('gedung')->pluck('gedung');
        $lokasiPerGedung = $this->lokasiPerGedung();
        return view('dashboard.peralatan.create', compact('gedungList', 'lokasiPerGedung'));
    }
    public function store(Request $request)
    {
        abort_if(!auth()->user()->punyaAkses('peralatan', 'tambah'), 403, 'Anda tidak memiliki akses untuk menambah peralatan.');
        $data = $request->validate([
            'kode_barang'       => 'nullable|string|max:50|unique:peralatan,kode_barang',
            'nama_peralatan'    => 'required|string|max:100',
            'gedung'            => 'required|string|max:100',
            'lokasi_detail'     => 'nullable|string|max:255',
            'stok'              => 'required|integer|min:0',
            'status_terpasang'  => 'nullable|in:terpasang,tidak terpasang',
            'keterangan'        => 'nullable|string|max:255',
            'foto'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $data['id_peralatan'] = IdGenerator::next(Peralatan::class, 'id_peralatan', 'PR-');
        $data['foto']         = $request->hasFile('foto')
            ? $request->file('foto')->store('peralatan', 'public')
            : null;
        // Validasi bisnis: jika ingin menandai sebagai 'terpasang', pastikan minimal 1 unit baik tersedia
        $stok = (int) ($data['stok'] ?? 0);
        $rusak = (int) ($data['rusak'] ?? 0);
        $tersedia = max(0, $stok - $rusak);
        if (($data['status_terpasang'] ?? '') === 'terpasang' && $tersedia < 1) {
            return back()->withInput()->withErrors(['status_terpasang' => 'Tidak dapat menandai sebagai terpasang: minimal 1 unit dalam kondisi baik diperlukan.']);
        }

        Peralatan::create($data);
        return redirect()->route(auth()->user()->role . '.peralatan.index')
            ->with('success', 'Peralatan berhasil ditambahkan.');
    }
    public function edit(string $id)
    {
        abort_if(!auth()->user()->punyaAkses('peralatan', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah peralatan.');
        $gedungList = Peralatan::distinct()->orderBy('gedung')->pluck('gedung');
        return view('dashboard.peralatan.edit', [
            'peralatan' => Peralatan::findOrFail($id),
            'gedungList' => $gedungList,
            'lokasiPerGedung' => $this->lokasiPerGedung(),
        ]);
    }

    private function lokasiPerGedung(): array
    {
        return Peralatan::whereNotNull('lokasi_detail')->where('lokasi_detail', '!=', '')
            ->select('gedung', 'lokasi_detail')
            ->distinct()
            ->get()
            ->groupBy('gedung')
            ->map(fn($rows) => $rows->pluck('lokasi_detail')->unique()->values())
            ->toArray();
    }
    public function update(Request $request, string $id)
    {
        abort_if(!auth()->user()->punyaAkses('peralatan', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah peralatan.');
        $peralatan = Peralatan::findOrFail($id);
        $data = $request->validate([
            'kode_barang'       => 'nullable|string|max:50|unique:peralatan,kode_barang,' . $id . ',id_peralatan',
            'nama_peralatan'    => 'required|string|max:100',
            'gedung'            => 'required|string|max:100',
            'lokasi_detail'     => 'nullable|string|max:255',
            'stok'              => 'required|integer|min:0',
            'rusak'             => 'nullable|integer|min:0',
            'status_terpasang'  => 'nullable|in:terpasang,tidak terpasang',
            'keterangan'        => 'nullable|string|max:255',
            'foto'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $rusak = (int) ($data['rusak'] ?? 0);
        if ($rusak > (int) $data['stok']) {
            return back()->withInput()
                ->withErrors(['rusak' => 'Jumlah rusak tidak boleh melebihi stok total.']);
        }
        // Validasi bisnis: jika ingin menandai sebagai 'terpasang', pastikan minimal 1 unit baik tersedia
        $stok = (int) ($data['stok'] ?? 0);
        $tersedia = max(0, $stok - $rusak);
        if (($data['status_terpasang'] ?? '') === 'terpasang' && $tersedia < 1) {
            return back()->withInput()->withErrors(['status_terpasang' => 'Tidak dapat menandai sebagai terpasang: minimal 1 unit dalam kondisi baik diperlukan.']);
        }
        $data['foto'] = $this->prosesUploadFoto($request, $peralatan);
        $peralatan->update($data);
        return redirect()->route(auth()->user()->role . '.peralatan.index')
            ->with('success', 'Peralatan berhasil diperbarui.');
    }
    public function destroy(string $id)
    {
        abort_if(!auth()->user()->punyaAkses('peralatan', 'hapus'), 403, 'Anda tidak memiliki akses untuk menghapus peralatan.');
        $peralatan = Peralatan::findOrFail($id);
        try {
            if ($peralatan->foto) {
                Storage::disk('public')->delete($peralatan->foto);
            }
            $peralatan->delete();
        } catch (\Exception $e) {
            return back()->with('error', 'Peralatan tidak dapat dihapus karena masih tercatat di jadwal atau peminjaman aktif.');
        }
        return back()->with('success', 'Peralatan berhasil dihapus.');
    }

    public function updateStatus(Request $request, string $id)
    {
        abort_if(!auth()->user()->punyaAkses('peralatan', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah peralatan.');
        $peralatan = Peralatan::findOrFail($id);
        $data = $request->validate([
            'status_terpasang' => 'required|in:terpasang,tidak terpasang',
        ]);

        $stok = (int) $peralatan->stok;
        $rusak = (int) ($peralatan->rusak ?? 0);
        $tersedia = max(0, $stok - $rusak);
        if ($data['status_terpasang'] === 'terpasang' && $tersedia < 1) {
            return back()->with('error', 'Tidak dapat menandai sebagai terpasang: minimal 1 unit dalam kondisi baik diperlukan.');
        }

        $peralatan->update(['status_terpasang' => $data['status_terpasang']]);

        return back()->with('success', 'Status peralatan berhasil diperbarui.');
    }

    private function prosesUploadFoto(Request $request, Peralatan $peralatan): ?string
    {
        if ($request->boolean('hapus_foto') && $peralatan->foto) {
            Storage::disk('public')->delete($peralatan->foto);
            return null;
        }
        if ($request->hasFile('foto')) {
            if ($peralatan->foto) {
                Storage::disk('public')->delete($peralatan->foto);
            }
            return $request->file('foto')->store('peralatan', 'public');
        }
        return $peralatan->foto;
    }
}
