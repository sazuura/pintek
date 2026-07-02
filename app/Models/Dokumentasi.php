<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Dokumentasi extends Model
{
    protected $table      = 'dokumentasi';
    protected $primaryKey = 'id_dokumentasi';
    protected $fillable = [
        'id_absensi',
        'file_path',
        'keterangan',
    ];

    public function absensi()
    {
        return $this->belongsTo(Absensi::class, 'id_absensi');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}