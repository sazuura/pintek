<?php
namespace App\Http\Controllers;
use App\Helpers\IdGenerator;
use App\Models\AlatTerpasang;
use App\Models\Peralatan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AlatTerpasangController extends Controller
{
    public function index(Request $request)
    {
        // Alat yang identik (peralatan, gedung, lokasi, tanggal pasang, kondisi, keterangan,
        // foto sama persis - artinya ditambahkan dalam satu batch yang sama lewat store())
        // ditampilkan sebagai SATU kartu dengan badge jumlah, bukan kartu terpisah per unit.
        $alat = AlatTerpasang::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('nama_alat', 'like', "%{$s}%")
                  ->orWhere('gedung', 'like', "%{$s}%")
            )
            ->when($request->tanggal, fn($q, $v) => $q->whereDate('tanggal_pasang', $v))
            ->when($request->kondisi, fn($q, $v) => $q->where('kondisi', $v))
            ->selectRaw('MIN(id_alat_terpasang) as id_alat_terpasang, id_peralatan, nama_alat, gedung, lokasi_detail, tanggal_pasang, kondisi, keterangan, foto, COUNT(*) as jumlah')
            ->groupBy('id_peralatan', 'nama_alat', 'gedung', 'lokasi_detail', 'tanggal_pasang', 'kondisi', 'keterangan', 'foto')
            ->when($request->urutkan, fn($q, $v) => match ($v) {
                'nama_asc'    => $q->orderBy('nama_alat'),
                'jumlah_desc' => $q->orderByDesc('jumlah'),
                'jumlah_asc'  => $q->orderBy('jumlah'),
                default       => $q->orderBy('gedung')->orderBy('nama_alat'),
            }, fn($q) => $q->orderBy('gedung')->orderBy('nama_alat'))
            ->paginate(10)
            ->withQueryString();

        // Ringkasan berapa unit dari tiap peralatan yang sedang terpasang permanen
        // vs. yang masih tersimpan di gudang (stok tersedia - yang sudah terpasang),
        // dipaginasi terpisah (page param sendiri) supaya tidak bentrok dengan pagination kartu.
        $ringkasanSemua = AlatTerpasang::selectRaw('id_peralatan, COUNT(*) as jumlah_terpasang')
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

        $ringkasanPerPage = 5;
        $ringkasanPage    = max(1, (int) $request->input('ringkasan_page', 1));
        $ringkasan = new LengthAwarePaginator(
            $ringkasanSemua->forPage($ringkasanPage, $ringkasanPerPage)->values(),
            $ringkasanSemua->count(),
            $ringkasanPerPage,
            $ringkasanPage,
            ['path' => $request->url(), 'pageName' => 'ringkasan_page', 'query' => $request->query()]
        );

        return view('dashboard.alat-terpasang.index', compact('alat', 'ringkasan'));
    }

    public function create()
    {
        $daftarPeralatan = Peralatan::orderBy('gedung')->orderBy('nama_peralatan')->get()
            ->map(function ($p) {
                $sudahTerpasang = AlatTerpasang::where('id_peralatan', $p->id_peralatan)->count();
                $p->sisa_bisa_dipasang = max(0, $p->stok_tersedia - $sudahTerpasang);
                return $p;
            });
        return view('dashboard.alat-terpasang.create', compact('daftarPeralatan'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->punyaAkses('alat-terpasang', 'tambah'), 403, 'Anda tidak memiliki akses untuk menambah alat terpasang.');
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
        $alat = AlatTerpasang::findOrFail($id);
        return view('dashboard.alat-terpasang.edit', [
            'alat' => $alat,
            'daftarPeralatan' => $daftarPeralatan,
            'jumlahDalamKelompok' => $this->kelompokQuery($alat)->count(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        abort_if(!auth()->user()->punyaAkses('alat-terpasang', 'ubah'), 403, 'Anda tidak memiliki akses untuk mengubah alat terpasang.');
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

        // Perubahan berlaku untuk SELURUH unit identik dalam kelompok yang sama (bukan
        // cuma baris representatif $alat), supaya konsisten dengan tampilan kartu di index()
        // yang menggabungkan unit identik jadi satu kartu.
        $kelompok = $this->kelompokQuery($alat)->pluck('id_alat_terpasang');
        AlatTerpasang::whereIn('id_alat_terpasang', $kelompok)->update($data);

        return redirect()->route('inventaris.alat-terpasang.index')
            ->with('success', $kelompok->count() > 1
                ? "{$kelompok->count()} alat berhasil diperbarui."
                : 'Alat terpasang berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        abort_if(!auth()->user()->punyaAkses('alat-terpasang', 'hapus'), 403, 'Anda tidak memiliki akses untuk menghapus alat terpasang.');
        $alat = AlatTerpasang::findOrFail($id);
        $kelompok = $this->kelompokQuery($alat)->pluck('id_alat_terpasang');

        if ($alat->foto) {
            Storage::disk('public')->delete($alat->foto);
        }
        AlatTerpasang::whereIn('id_alat_terpasang', $kelompok)->delete();

        return back()->with('success', $kelompok->count() > 1
            ? "{$kelompok->count()} alat berhasil dihapus."
            : 'Alat terpasang berhasil dihapus.');
    }

    /**
     * Baris-baris lain yang identik dengan $alat (peralatan, gedung, lokasi, tanggal pasang,
     * kondisi, keterangan, foto sama persis) - representasi "kelompok" yang digabung jadi
     * satu kartu di index(). Dipakai supaya edit/hapus di satu kartu berlaku ke semua unit
     * identik di kelompok itu, bukan cuma satu baris representatif.
     */
    private function kelompokQuery(AlatTerpasang $alat)
    {
        return AlatTerpasang::where('id_peralatan', $alat->id_peralatan)
            ->where('gedung', $alat->gedung)
            ->where('kondisi', $alat->kondisi)
            ->where(fn($q) => is_null($alat->lokasi_detail)
                ? $q->whereNull('lokasi_detail')
                : $q->where('lokasi_detail', $alat->lokasi_detail))
            ->where(fn($q) => is_null($alat->keterangan)
                ? $q->whereNull('keterangan')
                : $q->where('keterangan', $alat->keterangan))
            ->where(fn($q) => is_null($alat->foto)
                ? $q->whereNull('foto')
                : $q->where('foto', $alat->foto))
            ->whereDate('tanggal_pasang', $alat->tanggal_pasang);
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
