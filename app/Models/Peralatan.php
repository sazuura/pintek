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
        'perbaikan',
        'keterangan',
        'foto',
    ];

    public function jadwalPeralatan()
    {
        return $this->hasMany(JadwalPeralatan::class, 'id_peralatan', 'id_peralatan');
    }

    public function peminjamanItems()
    {
        return $this->hasMany(PeminjamanItem::class, 'id_peralatan', 'id_peralatan');
    }

    public function getStokTersediaAttribute(): int
    {
        return max(0, $this->stok - ($this->rusak ?? 0) - ($this->perbaikan ?? 0));
    }
    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            $this->stok_tersedia <= 0 => 'Tidak Tersedia',
            $this->stok_tersedia <= 2 => 'Hampir Habis',
            default                   => 'Tersedia',
        };
    }
    public function getStatusBadgeClassAttribute(): string
    {
        return match (true) {
            $this->stok_tersedia <= 0 => 'badge-danger',
            $this->stok_tersedia <= 2 => 'badge-warning',
            default                   => 'badge-active',
        };
    }
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::url($this->foto) : null;
    }
}
