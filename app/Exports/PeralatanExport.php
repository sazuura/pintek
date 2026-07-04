<?php

namespace App\Exports;

use App\Models\Peminjaman;
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
        $query = Peminjaman::with(['user', 'penjadwalan', 'items.peralatan'])->whereHas('items');

        if ($this->request->start) {
            $query->whereDate('tanggal_pinjam', '>=', $this->request->start);
        }
        if ($this->request->end) {
            $query->whereDate('tanggal_pinjam', '<=', $this->request->end);
        }
        if ($this->request->operator) {
            $query->where('id_user', $this->request->operator);
        }

        $baris = collect();
        foreach ($query->orderByDesc('tanggal_pinjam')->get() as $p) {
            foreach ($p->items as $item) {
                $baris->push([
                    'Judul Rapat'    => $p->penjadwalan->judul_kegiatan ?? $p->keperluan,
                    'Peralatan'      => $item->peralatan->nama_peralatan ?? '-',
                    'Nomor Seri'     => $item->peralatan->kode_barang ?? '-',
                    'Gedung'         => $item->peralatan->gedung ?? '-',
                    'Peminjam'       => $p->user->nama_user ?? '-',
                    'Tanggal Pinjam' => $p->tanggal_pinjam->format('d/m/Y'),
                    'Jumlah'         => $item->jumlah,
                    'Status'         => $p->badge['label'],
                ]);
            }
        }

        return $baris;
    }

    public function headings(): array
    {
        return ['Judul Rapat', 'Peralatan', 'Nomor Seri', 'Gedung', 'Peminjam', 'Tanggal Pinjam', 'Jumlah', 'Status'];
    }
}
