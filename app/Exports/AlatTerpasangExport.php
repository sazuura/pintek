<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExport;
use App\Models\AlatTerpasang;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;

class AlatTerpasangExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    use StyledExport;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = AlatTerpasang::query();

        if ($this->request->search) {
            $query->where('nama_alat', 'like', "%{$this->request->search}%");
        }
        if ($this->request->gedung) {
            $query->where('gedung', $this->request->gedung);
        }
        if ($this->request->kondisi) {
            $query->where('kondisi', $this->request->kondisi);
        }
        if ($this->request->start) {
            $query->whereDate('tanggal_pasang', '>=', $this->request->start);
        }
        if ($this->request->end) {
            $query->whereDate('tanggal_pasang', '<=', $this->request->end);
        }

        return $query->orderBy('gedung')->orderBy('nama_alat')->get()->map(fn($a) => [
            'Nama Alat'      => $a->nama_alat,
            'Gedung'         => $a->gedung,
            'Lokasi Detail'  => $a->lokasi_detail ?? '-',
            'Tanggal Pasang' => $a->tanggal_pasang->format('d/m/Y'),
            'Kondisi'        => $a->kondisiLabel,
        ]);
    }

    public function headings(): array
    {
        return ['Nama Alat', 'Gedung', 'Lokasi Detail', 'Tanggal Pasang', 'Kondisi'];
    }
}
