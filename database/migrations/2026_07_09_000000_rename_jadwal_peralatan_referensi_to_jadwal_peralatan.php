<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('jadwal_peralatan_referensi', 'jadwal_peralatan');
    }

    public function down(): void
    {
        Schema::rename('jadwal_peralatan', 'jadwal_peralatan_referensi');
    }
};
