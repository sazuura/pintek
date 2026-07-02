<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table      = 'absensi';
    protected $primaryKey = 'id_absensi';
    public    $timestamps = false;
    protected $fillable = [
        'id_penjadwalan',
        'id_user',
        'tanggal',
        'status',
        'keterangan',
        'validated',
    ];
    protected $casts = [
        'tanggal'   => 'date',
        'validated' => 'boolean',
    ];
    const STATUS_PENDING         = 'pending';
    const STATUS_HADIR           = 'hadir';
    const STATUS_IZIN            = 'izin';
    const STATUS_IZIN_DISETUJUI  = 'izin_disetujui';
    const STATUS_SAKIT           = 'sakit';
    const STATUS_SAKIT_DISETUJUI = 'sakit_disetujui';
    const STATUS_ALPHA           = 'alpha';
    const STATUS_DITOLAK         = 'ditolak';

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
    public function penjadwalan()
    {
        return $this->belongsTo(Penjadwalan::class, 'id_penjadwalan', 'id_penjadwalan');
    }
    public function dokumentasi()
    {
        return $this->hasMany(Dokumentasi::class, 'id_absensi');
    }
    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
    public function isHadir(): bool   { return $this->status === self::STATUS_HADIR; }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_IZIN_DISETUJUI,
            self::STATUS_SAKIT_DISETUJUI,
            self::STATUS_ALPHA,
            self::STATUS_DITOLAK,
        ]);
    }

    public function getBadgeAttribute(): array
    {
        return match ($this->status) {
            self::STATUS_HADIR           => ['class' => 'badge-active',   'label' => 'Hadir'],
            self::STATUS_PENDING         => ['class' => 'badge-warning',  'label' => 'Pending'],
            self::STATUS_IZIN            => ['class' => 'badge-info',     'label' => 'Izin (Proses)'],
            self::STATUS_IZIN_DISETUJUI  => ['class' => 'badge-info',     'label' => 'Izin'],
            self::STATUS_SAKIT           => ['class' => 'badge-purple',   'label' => 'Sakit (Proses)'],
            self::STATUS_SAKIT_DISETUJUI => ['class' => 'badge-purple',   'label' => 'Sakit'],
            self::STATUS_ALPHA           => ['class' => 'badge-danger',   'label' => 'Alpha'],
            self::STATUS_DITOLAK         => ['class' => 'badge-inactive', 'label' => 'Ditolak'],
            default                      => ['class' => '',               'label' => $this->status],
        };
    }
}
