<?php

namespace App\Exports;

use App\Models\JadwalPeralatan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class PeralatanExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = JadwalPeralatan::with(['penjadwalan', 'peralatan']);

        if ($this->request->start) {
            $query->whereHas('penjadwalan', fn($q) =>
                $q->whereDate('tanggal', '>=', $this->request->start)
            );
        }
        if ($this->request->end) {
            $query->whereHas('penjadwalan', fn($q) =>
                $q->whereDate('tanggal', '<=', $this->request->end)
            );
        }

        return $query->get()->map(function ($item) {
            return [
                'Tanggal'   => Carbon::parse($item->penjadwalan->tanggal)->format('d/m/Y'),
                'Kegiatan'  => $item->penjadwalan->judul_kegiatan,
                'Peralatan' => $item->peralatan->nama_peralatan,
                'Jumlah'    => $item->jumlah,
                'Lokasi'    => $item->peralatan->lokasi_penyimpanan ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return ['Tanggal', 'Kegiatan', 'Peralatan', 'Jumlah', 'Lokasi'];
    }
}
