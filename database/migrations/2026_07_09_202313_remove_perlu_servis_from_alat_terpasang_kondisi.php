<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remap dulu data lama yang masih 'perlu_servis' ke 'rusak' supaya tidak ada
        // baris yang nilainya jadi tidak valid setelah enum diperkecil.
        DB::table('alat_terpasang')->where('kondisi', 'perlu_servis')->update(['kondisi' => 'rusak']);

        DB::statement("ALTER TABLE alat_terpasang MODIFY kondisi ENUM('baik', 'rusak') NOT NULL DEFAULT 'baik'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE alat_terpasang MODIFY kondisi ENUM('baik', 'rusak', 'perlu_servis') NOT NULL DEFAULT 'baik'");
    }
};
