<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';
    protected $fillable = [
        'nama_menu',
        'slug',
        'route_name',
        'icon',
        'id_parent',
        'urutan',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'id_parent');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'id_parent')->orderBy('urutan');
    }

    public function aksesRole()
    {
        return $this->hasMany(RoleMenuAkses::class, 'id_menu');
    }
}
