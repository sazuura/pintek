<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel referensi "alat yang dibutuhkan" per jadwal - murni catatan/acuan untuk operator,
 * TIDAK memotong stok dan TIDAK sama dengan peminjaman sungguhan (yang tetap harus
 * diajukan operator lewat modul Peminjaman). Beda dari tabel jadwal_peralatan lama
 * yang sudah dihapus (itu dulu ikut mengunci stok saat admin alokasikan alat).
 */
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
