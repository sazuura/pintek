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
        'status',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function peralatan()
    {
        return $this->belongsTo(Peralatan::class, 'id_peralatan', 'id_peralatan');
    }

    public function isMenunggu(): bool { return $this->status === 'diajukan'; }

    public function getBadgeAttribute(): array
    {
        return match ($this->status) {
            'diajukan'  => ['class' => 'badge-warning', 'label' => 'Menunggu'],
            'disetujui' => ['class' => 'badge-active',  'label' => 'Disetujui'],
            'ditolak'   => ['class' => 'badge-danger',  'label' => 'Ditolak'],
            default     => ['class' => '',              'label' => $this->status],
        };
    }
}