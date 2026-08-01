<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $fillable = [
        'nama_role',
        'slug',
        'status',
        'is_terkunci',
    ];
    protected $casts = [
        'is_terkunci' => 'boolean',
    ];

    public function aksesMenu()
    {
        return $this->hasMany(RoleMenuAkses::class, 'id_role');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'role_menu_akses', 'id_role', 'id_menu')
            ->withPivot('bisa_lihat', 'bisa_tambah', 'bisa_ubah', 'bisa_hapus');
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    /**
     * Cek apakah role ini punya izin aksi tertentu ($aksi: lihat/tambah/ubah/hapus)
     * pada menu dengan slug tertentu. Dipakai middleware & controller untuk
     * enforcement granular (lihat docs/plans/planning-role-akses-dinamis.md §4).
     *
     * Sengaja pakai $this->aksesMenu (property, di-cache di instance model setelah load
     * pertama) BUKAN $this->aksesMenu() (method, query SQL baru tiap dipanggil) - method ini
     * dipanggil berkali-kali per request (middleware menu-akses di hampir semua route, plus
     * puluhan pemanggilan langsung di controller/Blade untuk show/hide tombol), jadi versi
     * method-call menghasilkan query identik berulang-ulang padahal datanya tidak berubah
     * dalam satu request. Lihat docs/plan/performance_fix.md.
     */
    public function punyaAkses(string $menuSlug, string $aksi = 'lihat'): bool
    {
        $kolom = 'bisa_' . $aksi;
        return $this->aksesMenu
            ->loadMissing('menu')
            ->contains(fn ($akses) => $akses->menu?->slug === $menuSlug && $akses->$kolom);
    }
}
