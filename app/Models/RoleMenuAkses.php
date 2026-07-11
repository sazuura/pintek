<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RoleMenuAkses extends Model
{
    protected $table = 'role_menu_akses';
    protected $fillable = [
        'id_role',
        'id_menu',
        'bisa_lihat',
        'bisa_tambah',
        'bisa_ubah',
        'bisa_hapus',
    ];
    protected $casts = [
        'bisa_lihat'  => 'boolean',
        'bisa_tambah' => 'boolean',
        'bisa_ubah'   => 'boolean',
        'bisa_hapus'  => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'id_menu');
    }
}
