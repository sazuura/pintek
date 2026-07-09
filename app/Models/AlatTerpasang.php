<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AlatTerpasang extends Model
{
    protected $table      = 'alat_terpasang';
    protected $primaryKey = 'id_alat_terpasang';
    public    $incrementing = false;
    protected $keyType    = 'string';
    protected $fillable = [
        'id_alat_terpasang',
        'id_peralatan',
        'nama_alat',
        'gedung',
        'lokasi_detail',
        'tanggal_pasang',
        'kondisi',
        'keterangan',
        'foto',
    ];
    protected $casts = [
        'tanggal_pasang' => 'date',
    ];

    public function peralatan()
    {
        return $this->belongsTo(Peralatan::class, 'id_peralatan', 'id_peralatan');
    }

    public function getKondisiLabelAttribute(): string
    {
        return match ($this->kondisi) {
            'rusak'  => 'Rusak',
            default  => 'Baik',
        };
    }

    public function getKondisiBadgeClassAttribute(): string
    {
        return match ($this->kondisi) {
            'rusak'  => 'badge-danger',
            default  => 'badge-active',
        };
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::url($this->foto) : null;
    }
}
