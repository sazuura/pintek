<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peralatan', function (Blueprint $table) {
            $table->dropColumn('status_penempatan');
        });
    }

    public function down(): void
    {
        Schema::table('peralatan', function (Blueprint $table) {
            $table->enum('status_penempatan', ['terpasang', 'tidak_terpasang'])
                ->default('tidak_terpasang')
                ->after('lokasi_detail');
        });
    }
};
