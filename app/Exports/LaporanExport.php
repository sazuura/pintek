<?php
namespace App\Exports;
use App\Exports\Concerns\StyledExport;
use App\Models\Penjadwalan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Carbon\Carbon;

class LaporanExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    use StyledExport;

    protected $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Penjadwalan::with('operators');
        if ($this->request->start) $query->whereDate('tanggal', '>=', $this->request->start);
        if ($this->request->end) $query->whereDate('tanggal', '<=', $this->request->end);
        if ($this->request->operator) {
            $query->whereHas('operators', fn($q) => $q->where('users.id_user', $this->request->operator));
        }
        $jadwal = $query->orderBy('tanggal', 'desc')->get();

        return $jadwal->map(function ($j) {
            $waktuMulai   = $j->waktu_mulai   ? Carbon::parse($j->waktu_mulai)->format('H:i')   : '-';
            $waktuSelesai = $j->waktu_selesai ? Carbon::parse($j->waktu_selesai)->format('H:i') : '-';
            $sudahLewat   = Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
            $status       = $j->isDibatalkan() ? 'Dibatalkan' : ($sudahLewat ? 'Selesai' : 'Aktif');
            return [
                'Tanggal'     => $j->tanggal?->translatedFormat('D, d/m/Y') ?? '-',
                'Judul Rapat' => $j->judul_kegiatan ?? '-',
                'Operator'       => $j->operators->pluck('nama_user')->join(', ') ?: '-',
                'Waktu'          => $waktuMulai . ' - ' . $waktuSelesai,
                'Platform'       => $j->platform ?? '-',
                'Status'         => $status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Judul Rapat',
            'Operator',
            'Waktu',
            'Platform',
            'Status',
        ];
    }
}
