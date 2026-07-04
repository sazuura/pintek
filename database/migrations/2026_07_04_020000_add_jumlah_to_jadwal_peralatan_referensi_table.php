<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_peralatan_referensi', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah')->default(1)->after('id_peralatan');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_peralatan_referensi', function (Blueprint $table) {
            $table->dropColumn('jumlah');
        });
    }
};
