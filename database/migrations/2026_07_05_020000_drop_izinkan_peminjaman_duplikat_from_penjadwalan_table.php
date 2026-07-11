<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rollback fitur toggle per-jadwal - diputuskan duplikasi peminjaman SELALU
     * diizinkan (bukan strict-by-default), cukup dikonfirmasi lewat modal di form,
     * tidak perlu diatur per-jadwal oleh admin.
     */
    public function up(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->dropColumn('izinkan_peminjaman_duplikat');
        });
    }

    public function down(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->boolean('izinkan_peminjaman_duplikat')->default(false)->after('keterangan');
        });
    }
};
