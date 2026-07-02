<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PeminjamanItem extends Model
{
    protected $table      = 'peminjaman_item';
    protected $primaryKey = 'id_item';
    public    $timestamps = false;
    protected $fillable = [
        'id_peminjaman',
        'id_peralatan',
        'jumlah',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function peralatan()
    {
        return $this->belongsTo(Peralatan::class, 'id_peralatan', 'id_peralatan');
    }
}