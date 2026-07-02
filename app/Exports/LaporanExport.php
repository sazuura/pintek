<?php
namespace App\Exports;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class LaporanExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Absensi::with(['user','penjadwalan']);
        if ($this->request->start) $query->whereDate('tanggal','>=',$this->request->start);
        if ($this->request->end) $query->whereDate('tanggal','<=',$this->request->end);
        if ($this->request->operator) $query->where('id_user',$this->request->operator);
        $absensi = $query->orderBy('tanggal','desc')->get();
        return $absensi->map(function($a){
            switch($a->status) {
                case 'pending': $status = 'Pending'; break;
                case 'hadir': $status = 'Hadir'; break;
                case 'izin': $status = 'Izin'; break;
                case 'sakit': $status = 'Sakit'; break;
                case 'sakit_disetujui': $status = 'Sakit'; break;
                case 'izin_disetujui': $status = 'Izin'; break;
                case 'alpha': $status = 'Alpha'; break;
                case 'ditolak': $status = 'Ditolak'; break;
                default: $status = 'Tidak Diketahui'; break;
            }
            $waktuMulai = $a->penjadwalan->waktu_mulai ? Carbon::parse($a->penjadwalan->waktu_mulai)->format('H:i') : '-';
            $waktuSelesai = $a->penjadwalan->waktu_selesai ? Carbon::parse($a->penjadwalan->waktu_selesai)->format('H:i') : '-';
            return [
                'Tanggal' => $a->tanggal?->format('d/m/Y') ?? '-',
                'Judul Kegiatan' => $a->penjadwalan->judul_kegiatan ?? '-',
                'Operator' => $a->user->nama_user ?? '-',
                'Waktu' => $waktuMulai . ' - ' . $waktuSelesai,
                'Status Presensi' => $status,
                'Keterangan' => $a->keterangan ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Judul Kegiatan',
            'Operator',
            'Waktu',
            'Status Presensi',
            'Keterangan'
        ];
    }
}