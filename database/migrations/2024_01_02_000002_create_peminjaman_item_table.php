<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_item', function (Blueprint $table) {
            $table->id('id_item');
            $table->unsignedBigInteger('id_peminjaman');
            $table->string('id_peralatan', 10);
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
        Schema::dropIfExists('peminjaman_item');
    }
};
