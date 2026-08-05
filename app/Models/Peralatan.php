<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Peralatan extends Model
{
    protected $table        = 'peralatan';
    protected $primaryKey   = 'id_peralatan';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;
    protected $fillable = [
        'id_peralatan',   
        'kode_barang',   
        'nama_peralatan',
        'gedung',
        'lokasi_detail',
        'stok',
        'rusak',
        'keterangan',
        'status_terpasang',
        'foto',
    ];

    public function getStokTersediaAttribute(): int
    {
        return max(0, $this->stok - ($this->rusak ?? 0));
    }
    public function getStatusLabelAttribute(): string
    {
        return $this->status_terpasang === 'terpasang'
            ? 'Terpasang'
            : 'Tidak Terpasang';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status_terpasang === 'terpasang'
            ? 'badge-info'
            : 'badge-inactive';
    }
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::url($this->foto) : null;
    }
}
