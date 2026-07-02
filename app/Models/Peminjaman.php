<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    protected $table      = 'peminjaman';
    protected $primaryKey = 'id_peminjaman';
    protected $fillable = [
        'id_user',
        'tanggal_pinjam',
        'tanggal_kembali_rencana',
        'tanggal_kembali_aktual',
        'keperluan',
        'status',
        'catatan_inventaris',
        'alasan_batal',
    ];
    protected $casts = [
        'tanggal_pinjam'          => 'date',
        'tanggal_kembali_rencana' => 'date',
        'tanggal_kembali_aktual'  => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function items()
    {
        return $this->hasMany(PeminjamanItem::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function isMenunggu(): bool     { return $this->status === 'diajukan'; }
    public function isDisetujui(): bool    { return $this->status === 'disetujui'; }
    public function isDitolak(): bool      { return $this->status === 'ditolak'; }
    public function isDikembalikan(): bool { return $this->status === 'dikembalikan'; }
    public function isDibatalkan(): bool   { return $this->status === 'dibatalkan'; }

    public function getBadgeAttribute(): array
    {
        return match ($this->status) {
            'diajukan','menunggu'     => ['class' => 'badge-warning',  'label' => 'Menunggu'],
            'disetujui'               => ['class' => 'badge-active',   'label' => 'Disetujui'],
            'ditolak'                 => ['class' => 'badge-danger',   'label' => 'Ditolak'],
            'dikembalikan'            => ['class' => 'badge-info',     'label' => 'Dikembalikan'],
            'dibatalkan'              => ['class' => 'badge-danger',   'label' => 'Dibatalkan'],
            default                   => ['class' => '',               'label' => $this->status],
        };
    }

    public function getGedungTerlibatAttribute(): array
    {
        return $this->items
            ->load('peralatan')
            ->pluck('peralatan.gedung')
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }
}