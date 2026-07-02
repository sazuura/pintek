<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id('id_absensi');
            $table->string('id_penjadwalan', 10);
            $table->string('id_user', 10);
            $table->date('tanggal');
            $table->enum('status', [
                'pending',
                'hadir',
                'izin',
                'izin_disetujui',
                'sakit',
                'sakit_disetujui',
                'alpha',
                'ditolak',
            ])->default('pending');
            $table->string('keterangan', 255)->nullable();
            $table->boolean('validated')->default(false);
            $table->foreign('id_penjadwalan')
                  ->references('id_penjadwalan')
                  ->on('penjadwalan')
                  ->cascadeOnDelete();
            $table->foreign('id_user')
                  ->references('id_user')
                  ->on('users')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
