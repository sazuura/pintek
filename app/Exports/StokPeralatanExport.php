<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExport;
use App\Models\Peralatan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;

class StokPeralatanExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    use StyledExport;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Peralatan::query();

        if ($this->request->search) {
            $s = $this->request->search;
            $query->where(fn($q) => $q->where('nama_peralatan', 'like', "%{$s}%")
                ->orWhere('kode_barang', 'like', "%{$s}%"));
        }
        if ($this->request->gedung) {
            $query->where('gedung', $this->request->gedung);
        }
        if ($this->request->kondisi === 'baik') {
            $query->whereRaw('COALESCE(rusak,0) <= 0');
        } elseif ($this->request->kondisi === 'rusak') {
            $query->whereRaw('COALESCE(rusak,0) > 0');
        }
        if ($this->request->status === 'terpasang') {
            $query->where('status_terpasang', 'terpasang');
        } elseif ($this->request->status === 'tidak_terpasang') {
            $query->where(function ($q) {
                $q->where('status_terpasang', 'tidak terpasang')
                  ->orWhereNull('status_terpasang');
            });
        }

        return $query->orderBy('gedung')->orderBy('nama_peralatan')->get()->map(fn($p) => [
            'Nama Alat'   => $p->nama_peralatan,
            'Kode Barang' => $p->kode_barang ?? '-',
            'Gedung'      => $p->gedung,
            'Stok Total'  => $p->stok,
            'Rusak'       => $p->rusak ?? 0,
            'Status'      => $p->statusLabel,
        ]);
    }

    public function headings(): array
    {
        return ['Nama Alat', 'Kode Barang', 'Gedung', 'Stok Total', 'Rusak', 'Status'];
    }
}
