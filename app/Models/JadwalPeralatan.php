<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class JadwalPeralatan extends Model
{
    protected $table      = 'jadwal_peralatan';
    protected $primaryKey = 'id_jadwal_alat';
    public    $timestamps = false;
    protected $fillable = [
        'id_penjadwalan',
        'id_peralatan',
        'jumlah',
        'status_pemasangan',
    ];

    public function penjadwalan()
    {
        return $this->belongsTo(Penjadwalan::class, 'id_penjadwalan', 'id_penjadwalan');
    }

    public function peralatan()
    {
        return $this->belongsTo(Peralatan::class, 'id_peralatan', 'id_peralatan');
    }

    public function sudahDipasang(): bool
    {
        return $this->status_pemasangan === 'sudah_dipasang';
    }
}