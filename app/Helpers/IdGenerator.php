<?php
namespace App\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IdGenerator
{

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
