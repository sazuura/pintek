<?php
namespace App\Models;
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
        'lokasi_fisik',
        'status',
        'alasan_batal',
        'zoom_meeting_id',
        'zoom_password',
        'zoom_account',
        'link_otomatis',
    ];

    public function operators()
    {
        return $this->belongsToMany(User::class, 'jadwal_operator', 'id_penjadwalan', 'id_user');
    }
    protected $casts = [
        'tanggal'       => 'date:Y-m-d',
        'link_otomatis' => 'boolean',
    ];

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'id_penjadwalan', 'id_penjadwalan');
    }

    public function peralatanSudahDiajukan(?string $kecualiIdPeminjaman = null): array
    {
        return $this->peminjaman()
            ->whereIn('status', ['diajukan', 'disetujui', 'dikembalikan'])
            ->when($kecualiIdPeminjaman, fn ($q, $id) => $q->where('id_peminjaman', '!=', $id))
            ->with('items.peralatan')
            ->get()
            ->flatMap(fn ($p) => $p->items)
            ->filter(fn ($item) => $item->peralatan)
            ->groupBy('peralatan.nama_peralatan')
            ->map(fn ($items) => $items->sum('jumlah'))
            ->all();
    }

    public function peralatanReferensi()
    {
        return $this->belongsToMany(Peralatan::class, 'jadwal_peralatan', 'id_penjadwalan', 'id_peralatan')
            ->withPivot('jumlah');
    }

    public function isDibatalkan(): bool
    {
        return $this->status === 'dibatalkan' || !empty($this->alasan_batal);
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
