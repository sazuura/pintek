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
        // Pembatalan dicatat di pengajuan induk saja - status item sengaja dibiarkan
        // 'diajukan' oleh PeminjamanService::batalkan() karena inventaris tidak pernah
        // memutuskan apa-apa. Tanpa cek ini, item pengajuan yang sudah dibatalkan
        // tampil "Menunggu" seolah masih menunggu keputusan.
        if ($this->peminjaman?->isDibatalkan()) {
            return ['class' => 'badge-danger', 'label' => 'Dibatalkan'];
        }
        // Sama halnya pengembalian - konfirmasiKembali() cuma menandai pengajuan induk
        // (status + tanggal_kembali_aktual), status tiap item TIDAK ikut diubah jadi
        // 'dikembalikan'. Tanpa cek ini, item yang sudah kembali tetap tampil "Disetujui"
        // seolah masih dipinjam.
        if ($this->peminjaman?->isDikembalikan()) {
            return ['class' => 'badge-info', 'label' => 'Dikembalikan'];
        }
        return match ($this->status) {
            'diajukan'  => ['class' => 'badge-warning', 'label' => 'Menunggu'],
            'disetujui' => ['class' => 'badge-active',  'label' => 'Disetujui'],
            'ditolak'   => ['class' => 'badge-danger',  'label' => 'Ditolak'],
            default     => ['class' => '',              'label' => $this->status],
        };
    }
}