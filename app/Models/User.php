<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    protected $table        = 'users';
    protected $primaryKey   = 'id_user';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;
    protected $fillable = [
        'id_user',
        'nama_user',
        'jenis_kelamin',
        'alamat',
        'nohp',
        'email',
        'password',
        'role',
        'status',
    ];
    protected $hidden = ['password', 'remember_token'];

    public function jadwalDitugaskan()
    {
        return $this->belongsToMany(Penjadwalan::class, 'jadwal_operator', 'id_user', 'id_penjadwalan');
    }

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'id_user', 'id_user');
    }

    public function isActive(): bool { return $this->status === 'active'; }

    public function roleAkses()
    {
        return $this->belongsTo(Role::class, 'role', 'slug');
    }

    public function punyaAkses(string $menuSlug, string $aksi = 'lihat'): bool
    {
        return $this->roleAkses?->punyaAkses($menuSlug, $aksi) ?? false;
    }

    public function getNomorWaAttribute(): ?string
    {
        return $this->nohp ? '62' . ltrim(preg_replace('/\D/', '', $this->nohp), '0') : null;
    }
}