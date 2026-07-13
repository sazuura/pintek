<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            // Terpisah dari "keterangan" karena keterangan dipakai untuk link meeting
            // (ditimpa otomatis saat link_otomatis aktif) - rapat Hybrid butuh tempat
            // menyimpan lokasi fisik yang tidak ikut ketimpa saat link Zoom dibuat/diubah.
            $table->string('lokasi_fisik', 255)->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->dropColumn('lokasi_fisik');
        });
    }
};
