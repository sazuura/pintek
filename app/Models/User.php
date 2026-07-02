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
        'nohp',
        'email',
        'password',
        'role',
        'status',
        'gedung', 
    ];
    protected $hidden = ['password', 'remember_token'];

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'id_user', 'id_user');
    }

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class, 'id_user', 'id_user');
    }

    public function isAdmin(): bool      { return $this->role === 'admin'; }
    public function isOperator(): bool   { return $this->role === 'operator'; }
    public function isInventaris(): bool { return $this->role === 'inventaris'; }
    public function isActive(): bool     { return $this->status === 'active'; }
}