<?php
namespace App\Models;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

    class Penjadwalan extends Model
    {
        protected $table        = 'penjadwalan';
        protected $primaryKey   = 'id_penjadwalan';
        public    $incrementing = false;
        protected $keyType      = 'string';
        protected $fillable = [
        'id_penjadwalan',
        'judul_kegiatan',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'platform',
        'keterangan',
        'status',
        'alasan_batal',
    ];

    public function operators()
    {
        return $this->belongsToMany(User::class, 'jadwal_operator', 'id_penjadwalan', 'id_user');
    }
    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'id_penjadwalan', 'id_penjadwalan');
    }

    public function isDibatalkan(): bool
    {
        return $this->status === 'dibatalkan' || !empty($this->alasan_batal);
    }

    public function getStartDateTimeAttribute(): ?Carbon
    {
        if (!$this->tanggal || !$this->waktu_mulai) return null;

        return Carbon::parse(
            $this->tanggal->format('Y-m-d') . ' ' . $this->waktu_mulai,
            'Asia/Jakarta'
        );
    }

    public function getEndDateTimeAttribute(): ?Carbon
    {
        if (!$this->tanggal || !$this->waktu_selesai) return null;

        return Carbon::parse(
            $this->tanggal->format('Y-m-d') . ' ' . $this->waktu_selesai,
            'Asia/Jakarta'
        );
    }

    public function scopeBentrok($query, string $tanggal, string $mulai, string $selesai, ?string $excludeId = null)
    {
        return $query
            ->whereDate('tanggal', $tanggal)
            ->when($excludeId, fn($q) => $q->where('id_penjadwalan', '!=', $excludeId))
            ->where(function ($q) use ($mulai, $selesai) {
                $q->whereBetween('waktu_mulai',    [$mulai, $selesai])
                  ->orWhereBetween('waktu_selesai', [$mulai, $selesai])
                  ->orWhere(function ($qq) use ($mulai, $selesai) {
                      $qq->where('waktu_mulai',   '<=', $mulai)
                         ->where('waktu_selesai', '>=', $selesai);
                  });
            });
    }
}
