<?php

namespace App\Exports;

use App\Models\PeminjamanItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PeralatanExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = PeminjamanItem::with(['peralatan', 'peminjaman.user']);

        if ($this->request->start) {
            $query->whereHas('peminjaman', fn($q) => $q->whereDate('tanggal_pinjam', '>=', $this->request->start));
        }
        if ($this->request->end) {
            $query->whereHas('peminjaman', fn($q) => $q->whereDate('tanggal_pinjam', '<=', $this->request->end));
        }
        if ($this->request->operator) {
            $query->whereHas('peminjaman', fn($q) => $q->where('id_user', $this->request->operator));
        }

        return $query->orderByDesc('id_item')->get()->map(function ($item) {
            return [
                'Peralatan'      => $item->peralatan->nama_peralatan,
                'Nomor Seri'     => $item->peralatan->kode_barang ?? '-',
                'Gedung'         => $item->peralatan->gedung,
                'Peminjam'       => $item->peminjaman->user->nama_user ?? '-',
                'Tanggal Pinjam' => $item->peminjaman->tanggal_pinjam->format('d/m/Y'),
                'Jumlah'         => $item->jumlah,
                'Status'         => $item->peminjaman->badge['label'],
            ];
        });
    }

    public function headings(): array
    {
        return ['Peralatan', 'Nomor Seri', 'Gedung', 'Peminjam', 'Tanggal Pinjam', 'Jumlah', 'Status'];
    }
}
