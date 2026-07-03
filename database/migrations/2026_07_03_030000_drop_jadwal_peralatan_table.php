<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('jadwal_peralatan');
    }

    public function down(): void
    {
        // Proyek masih development, tidak wajib diimplementasikan penuh
    }
};
