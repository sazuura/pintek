<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AlatTerpasangRiwayat extends Model
{
    protected $table = 'alat_terpasang_riwayat';
    protected $fillable = [
        'id_alat_terpasang',
        'tanggal',
        'jenis',
        'keterangan',
        'id_user',
    ];
    protected $casts = [
        'tanggal' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            'servis'      => 'Servis',
            'perbaikan'   => 'Perbaikan',
            'pemeriksaan' => 'Pemeriksaan',
            default       => 'Pemasangan',
        };
    }
}
