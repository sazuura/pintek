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
        $userRole   = auth()->user()->role; 
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
            // ->when($request->gedung, fn($q, $v) => $q->where('gedung', $v))
            ->when($request->status, fn($q, $v) => match ($v) {
                'tersedia'       => $q->whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) > 0'),
                'tidak_tersedia' => $q->whereRaw('(stok - COALESCE(rusak,0) - COALESCE(perbaikan,0)) <= 0'),
                default          => $q,
            })
            ->orderBy('gedung')
            ->orderBy('nama_peralatan')
            ->paginate(12)
            ->withQueryString();
        $gedungList = Peralatan::distinct()->orderBy('gedung')->pluck('gedung');
        return view('inventaris.peralatan.index', compact('peralatan', 'gedungList'));
    }
    public function create()
    {
        $gedungList = Peralatan::distinct()->orderBy('gedung')->pluck('gedung');
        return view('inventaris.peralatan.create', compact('gedungList'));
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_barang'    => 'nullable|string|max:50|unique:peralatan,kode_barang',
            'nama_peralatan' => 'required|string|max:100',
            'gedung'         => 'required|string|max:100',
            'lokasi_detail'  => 'nullable|string|max:255',
            'stok'           => 'required|integer|min:0',
            'keterangan'     => 'nullable|string|max:255',
            'foto'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $data['id_peralatan'] = IdGenerator::next(Peralatan::class, 'id_peralatan', 'PR-');
        $data['foto']         = $request->hasFile('foto')
            ? $request->file('foto')->store('peralatan', 'public')
            : null;
        Peralatan::create($data);
        return redirect()->route('inventaris.peralatan.index')
            ->with('success', 'Peralatan berhasil ditambahkan.');
    }
    public function edit(string $id)
    {
        $gedungList = Peralatan::distinct()->orderBy('gedung')->pluck('gedung');
        return view('inventaris.peralatan.edit', [
            'peralatan' => Peralatan::findOrFail($id),
            'gedungList' => $gedungList,
        ]);
    }
    public function update(Request $request, string $id)
    {
        $peralatan = Peralatan::findOrFail($id);
        $data = $request->validate([
            'kode_barang'    => 'nullable|string|max:50|unique:peralatan,kode_barang,' . $id . ',id_peralatan',
            'nama_peralatan' => 'required|string|max:100',
            'gedung'         => 'required|string|max:100',
            'lokasi_detail'  => 'nullable|string|max:255',
            'stok'           => 'required|integer|min:0',
            'rusak'          => 'nullable|integer|min:0',
            'perbaikan'      => 'nullable|integer|min:0',
            'keterangan'     => 'nullable|string|max:255',
            'foto'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $rusak     = (int) ($data['rusak']     ?? 0);
        $perbaikan = (int) ($data['perbaikan'] ?? 0);
        if (($rusak + $perbaikan) > (int) $data['stok']) {
            return back()->withInput()
                ->withErrors(['rusak' => 'Jumlah rusak + perbaikan tidak boleh melebihi stok total.']);
        }
        $data['foto'] = $this->prosesUploadFoto($request, $peralatan);
        $peralatan->update($data);
        return redirect()->route('inventaris.peralatan.index')
            ->with('success', 'Peralatan berhasil diperbarui.');
    }
    public function destroy(string $id)
    {
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
