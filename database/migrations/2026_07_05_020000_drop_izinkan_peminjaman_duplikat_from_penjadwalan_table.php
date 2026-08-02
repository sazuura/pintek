<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

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
