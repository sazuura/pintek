<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alat_terpasang', function (Blueprint $table) {
            $table->string('id_alat_terpasang', 20)->primary();
            $table->string('id_peralatan', 20)->nullable();
            $table->foreign('id_peralatan')->references('id_peralatan')->on('peralatan')->nullOnDelete();
            $table->string('nama_alat', 100);
            $table->string('gedung', 100);
            $table->string('lokasi_detail', 255)->nullable();
            $table->date('tanggal_pasang');
            $table->enum('kondisi', ['baik', 'rusak', 'perlu_servis'])->default('baik');
            $table->string('keterangan', 255)->nullable();
            $table->string('foto', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alat_terpasang');
    }
};
