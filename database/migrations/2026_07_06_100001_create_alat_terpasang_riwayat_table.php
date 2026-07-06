<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alat_terpasang_riwayat', function (Blueprint $table) {
            $table->id();
            $table->string('id_alat_terpasang', 20);
            $table->foreign('id_alat_terpasang')->references('id_alat_terpasang')->on('alat_terpasang')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['pemasangan', 'pemeriksaan', 'servis', 'perbaikan']);
            $table->string('keterangan', 255);
            $table->string('id_user', 20)->nullable();
            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alat_terpasang_riwayat');
    }
};
