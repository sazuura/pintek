<?php
namespace App\Http\Controllers;
use App\Helpers\IdGenerator;
use App\Models\AlatTerpasang;
use App\Models\Peralatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlatTerpasangController extends Controller
{
    public function index(Request $request)
    {
        $alat = AlatTerpasang::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('nama_alat', 'like', "%{$s}%")
                  ->orWhere('gedung', 'like', "%{$s}%")
            )
            ->when($request->kondisi, fn($q, $v) => $q->where('kondisi', $v))
            ->orderBy('gedung')
            ->orderBy('nama_alat')
            ->paginate(12)
            ->withQueryString();
        return view('inventaris.alat-terpasang.index', compact('alat'));
    }

    public function create()
    {
        $daftarPeralatan = Peralatan::orderBy('gedung')->orderBy('nama_peralatan')->get();
        return view('inventaris.alat-terpasang.create', compact('daftarPeralatan'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_peralatan'   => 'required|string|exists:peralatan,id_peralatan',
            'gedung'         => 'required|string|max:100',
            'lokasi_detail'  => 'nullable|string|max:255',
            'tanggal_pasang' => 'required|date',
            'kondisi'        => 'required|in:baik,rusak,perlu_servis',
            'keterangan'     => 'nullable|string|max:255',
            'foto'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $data['nama_alat'] = Peralatan::findOrFail($data['id_peralatan'])->nama_peralatan;
        $data['id_alat_terpasang'] = IdGenerator::next(AlatTerpasang::class, 'id_alat_terpasang', 'AT-');
        $data['foto'] = $request->hasFile('foto')
            ? $request->file('foto')->store('alat-terpasang', 'public')
            : null;
        $alat = AlatTerpasang::create($data);

        $alat->riwayat()->create([
            'tanggal'    => $data['tanggal_pasang'],
            'jenis'      => 'pemasangan',
            'keterangan' => 'Alat dipasang.',
            'id_user'    => auth()->id(),
        ]);

        return redirect()->route('inventaris.alat-terpasang.index')
            ->with('success', 'Alat terpasang berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $daftarPeralatan = Peralatan::orderBy('gedung')->orderBy('nama_peralatan')->get();
        return view('inventaris.alat-terpasang.edit', [
            'alat' => AlatTerpasang::with('riwayat.user')->findOrFail($id),
            'daftarPeralatan' => $daftarPeralatan,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $alat = AlatTerpasang::findOrFail($id);
        $data = $request->validate([
            'id_peralatan'   => 'required|string|exists:peralatan,id_peralatan',
            'gedung'         => 'required|string|max:100',
            'lokasi_detail'  => 'nullable|string|max:255',
            'tanggal_pasang' => 'required|date',
            'kondisi'        => 'required|in:baik,rusak,perlu_servis',
            'keterangan'     => 'nullable|string|max:255',
            'foto'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $data['nama_alat'] = Peralatan::findOrFail($data['id_peralatan'])->nama_peralatan;
        $data['foto'] = $this->prosesUploadFoto($request, $alat);
        $alat->update($data);
        return redirect()->route('inventaris.alat-terpasang.index')
            ->with('success', 'Alat terpasang berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $alat = AlatTerpasang::findOrFail($id);
        if ($alat->foto) {
            Storage::disk('public')->delete($alat->foto);
        }
        $alat->delete();
        return back()->with('success', 'Alat terpasang berhasil dihapus.');
    }

    public function storeRiwayat(Request $request, string $id)
    {
        $alat = AlatTerpasang::findOrFail($id);
        $data = $request->validate([
            'tanggal'    => 'required|date',
            'jenis'      => 'required|in:pemasangan,pemeriksaan,servis,perbaikan',
            'keterangan' => 'required|string|max:255',
        ]);
        $data['id_user'] = auth()->id();
        $alat->riwayat()->create($data);

        return back()->with('success', 'Riwayat berhasil ditambahkan.');
    }

    public function destroyRiwayat(string $id, int $riwayatId)
    {
        $riwayat = \App\Models\AlatTerpasangRiwayat::where('id_alat_terpasang', $id)->findOrFail($riwayatId);
        $riwayat->delete();
        return back()->with('success', 'Riwayat berhasil dihapus.');
    }

    private function prosesUploadFoto(Request $request, AlatTerpasang $alat): ?string
    {
        if ($request->boolean('hapus_foto') && $alat->foto) {
            Storage::disk('public')->delete($alat->foto);
            return null;
        }
        if ($request->hasFile('foto')) {
            if ($alat->foto) {
                Storage::disk('public')->delete($alat->foto);
            }
            return $request->file('foto')->store('alat-terpasang', 'public');
        }
        return $alat->foto;
    }
}
