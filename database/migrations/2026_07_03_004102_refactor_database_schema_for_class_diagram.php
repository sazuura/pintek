<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->dropForeign(['id_pemateri']);
            $table->dropColumn('id_pemateri');

            $table->string('id_user', 10)->nullable();
            $table->foreign('id_user')
                  ->references('id_user')
                  ->on('users')
                  ->cascadeOnDelete();
        });

        Schema::dropIfExists('peminjaman_item');
        Schema::dropIfExists('peminjaman');

        Schema::create('peminjaman', function (Blueprint $table) {
            $table->string('id_peminjaman', 20)->primary();
            $table->string('id_user', 10);
            $table->date('tanggal_pinjam');
            $table->date('tanggal_kembali_rencana');
            $table->date('tanggal_kembali_aktual')->nullable();
            $table->string('keperluan', 255);
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'dikembalikan', 'dibatalkan'])->default('diajukan');
            $table->string('catatan_inventaris', 255)->nullable();
            $table->string('alasan_batal', 255)->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->timestamps();

            $table->foreign('id_user')
                  ->references('id_user')
                  ->on('users')
                  ->cascadeOnDelete();
        });

        Schema::create('peminjaman_item', function (Blueprint $table) {
            $table->id('id_item');
            $table->string('id_peminjaman', 20);
            $table->string('id_peralatan', 20);
            $table->unsignedSmallInteger('jumlah')->default(1);

            $table->foreign('id_peminjaman')
                  ->references('id_peminjaman')
                  ->on('peminjaman')
                  ->cascadeOnDelete();

            $table->foreign('id_peralatan')
                  ->references('id_peralatan')
                  ->on('peralatan')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {

    }
};