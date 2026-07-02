<?php
namespace App\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * IdGenerator
 * Generate ID sequential dengan prefix untuk semua tabel yang butuh ID kustom.
 * Dibuat sebagai helper statis agar mudah dipanggil dari mana saja.
 *
 */
class IdGenerator
{
    /**
     * @param  string  $modelClass  Nama class model, cth: User::class
     * @param  string  $column      Nama kolom PK, cth: 'id_user'
     * @param  string  $prefix      Prefix ID, cth: 'US', 'PJ', 'PR-'
     * @param  int     $pad         Panjang angka dengan zero-padding, default 3
     */
    public static function next(string $modelClass, string $column, string $prefix, int $pad = 3): string
    {
        $tabel = (new $modelClass)->getTable();
        $lastId = DB::table($tabel)
            ->orderByDesc($column)
            ->lockForUpdate()
            ->value($column);
        $lastNumber = $lastId ? (int) substr($lastId, strlen($prefix)) : 0;
        return $prefix . str_pad($lastNumber + 1, $pad, '0', STR_PAD_LEFT);
    }
}
