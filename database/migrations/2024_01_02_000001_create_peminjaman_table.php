<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id('id_peminjaman');
            $table->string('id_user', 10);                    
            $table->date('tanggal_pinjam');
            $table->date('tanggal_kembali_rencana');
            $table->date('tanggal_kembali_aktual')->nullable(); 
            $table->string('keperluan', 255);
            $table->enum('status', [
                'diajukan',
                'disetujui',
                'ditolak',
                'dikembalikan',
                'dibatalkan',
            ])->default('diajukan');
            $table->string('catatan_inventaris', 255)->nullable();
            $table->timestamps();
            $table->foreign('id_user')
                  ->references('id_user')
                  ->on('users')
                  ->cascadeOnDelete();
            $table->string('alasan_batal', 255)->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
