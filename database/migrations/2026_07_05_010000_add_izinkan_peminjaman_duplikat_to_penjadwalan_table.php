<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Toggle per-jadwal: kalau true, operator tetap boleh mengajukan peminjaman untuk
     * peralatan yang sudah diajukan operator lain di jadwal yang sama (lewat konfirmasi
     * di form), bukan diblokir keras. Default false (strict - cegah duplikasi).
     */
    public function up(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->boolean('izinkan_peminjaman_duplikat')->default(false)->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->dropColumn('izinkan_peminjaman_duplikat');
        });
    }
};
