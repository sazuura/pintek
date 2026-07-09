<?php
namespace App\Http\Controllers;
use App\Helpers\IdGenerator;
use App\Models\AlatTerpasang;
use App\Models\Peralatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('gedung')
            ->orderBy('nama_alat')
            ->paginate(10)
            ->withQueryString();

        // Ringkasan berapa unit dari tiap peralatan yang sedang terpasang permanen
        // vs. yang masih tersimpan di gudang (stok tersedia - yang sudah terpasang).
        $ringkasan = AlatTerpasang::selectRaw('id_peralatan, COUNT(*) as jumlah_terpasang')
            ->groupBy('id_peralatan')
            ->with('peralatan')
            ->get()
            ->map(function ($row) {
                $stokTersedia = $row->peralatan->stok_tersedia ?? 0;
                return [
                    'nama_peralatan'   => $row->peralatan->nama_peralatan ?? '-',
                    'jumlah_terpasang' => $row->jumlah_terpasang,
                    'jumlah_tersimpan' => max(0, $stokTersedia - $row->jumlah_terpasang),
                    'stok_tersedia'    => $stokTersedia,
                ];
            })
            ->sortBy('nama_peralatan')
            ->values();

        return view('inventaris.alat-terpasang.index', compact('alat', 'ringkasan'));
    }

    public function create()
    {
        $daftarPeralatan = Peralatan::orderBy('gedung')->orderBy('nama_peralatan')->get()
            ->map(function ($p) {
                $sudahTerpasang = AlatTerpasang::where('id_peralatan', $p->id_peralatan)->count();
                $p->sisa_bisa_dipasang = max(0, $p->stok_tersedia - $sudahTerpasang);
                return $p;
            });
        return view('inventaris.alat-terpasang.create', compact('daftarPeralatan'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_peralatan'   => 'required|array|min:1',
            'id_peralatan.*' => 'required|string|exists:peralatan,id_peralatan|distinct',
            'jumlah'         => 'required|array',
            'jumlah.*'       => 'required|integer|min:1',
            'gedung'         => 'required|string|max:100',
            'lokasi_detail'  => 'nullable|string|max:255',
            'tanggal_pasang' => 'required|date',
            'kondisi'        => 'required|in:baik,rusak',
            'keterangan'     => 'nullable|string|max:255',
            'foto'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Validasi tiap alat: jumlah yang diminta tidak boleh melebihi stok yang masih
        // bisa dipasang (stok tersedia dikurangi yang sudah terpasang sebelumnya).
        foreach ($data['id_peralatan'] as $i => $idPeralatan) {
            $peralatan = Peralatan::findOrFail($idPeralatan);
            $sudahTerpasang = AlatTerpasang::where('id_peralatan', $idPeralatan)->count();
            $sisaBisaDipasang = max(0, $peralatan->stok_tersedia - $sudahTerpasang);

            if ((int) $data['jumlah'][$i] > $sisaBisaDipasang) {
                return back()->withInput()->withErrors([
                    "jumlah.{$i}" => "{$peralatan->nama_peralatan}: jumlah melebihi stok yang bisa dipasang (tersisa {$sisaBisaDipasang}).",
                ]);
            }
        }

        $foto = $request->hasFile('foto')
            ? $request->file('foto')->store('alat-terpasang', 'public')
            : null;

        $totalDitambahkan = 0;

        DB::transaction(function () use ($data, $foto, &$totalDitambahkan) {
            foreach ($data['id_peralatan'] as $i => $idPeralatan) {
                $peralatan = Peralatan::findOrFail($idPeralatan);
                $jumlah = (int) $data['jumlah'][$i];

                for ($u = 0; $u < $jumlah; $u++) {
                    AlatTerpasang::create([
                        'id_alat_terpasang' => IdGenerator::next(AlatTerpasang::class, 'id_alat_terpasang', 'AT-'),
                        'id_peralatan'      => $peralatan->id_peralatan,
                        'nama_alat'         => $peralatan->nama_peralatan,
                        'gedung'            => $data['gedung'],
                        'lokasi_detail'     => $data['lokasi_detail'] ?? null,
                        'tanggal_pasang'    => $data['tanggal_pasang'],
                        'kondisi'           => $data['kondisi'],
                        'keterangan'        => $data['keterangan'] ?? null,
                        'foto'              => $foto,
                    ]);

                    $totalDitambahkan++;
                }
            }
        });

        return redirect()->route('inventaris.alat-terpasang.index')
            ->with('success', $totalDitambahkan > 1
                ? "{$totalDitambahkan} alat berhasil ditambahkan."
                : 'Alat terpasang berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $daftarPeralatan = Peralatan::orderBy('gedung')->orderBy('nama_peralatan')->get();
        return view('inventaris.alat-terpasang.edit', [
            'alat' => AlatTerpasang::findOrFail($id),
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
            'kondisi'        => 'required|in:baik,rusak',
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
