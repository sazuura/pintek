<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_peralatan_referensi', function (Blueprint $table) {
            $table->id();
            $table->string('id_penjadwalan');
            $table->string('id_peralatan');
            $table->timestamps();

            $table->foreign('id_penjadwalan')->references('id_penjadwalan')->on('penjadwalan')->cascadeOnDelete();
            $table->foreign('id_peralatan')->references('id_peralatan')->on('peralatan')->cascadeOnDelete();
            $table->unique(['id_penjadwalan', 'id_peralatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_peralatan_referensi');
    }
};
