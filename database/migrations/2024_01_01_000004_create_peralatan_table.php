<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peralatan', function (Blueprint $table) {
            $table->string('id_peralatan', 20)->primary();       
            $table->string('kode_barang', 50)->nullable()->unique(); 
            $table->string('nama_peralatan', 100);
            $table->string('gedung', 100);                      
            $table->string('lokasi_detail', 255)->nullable();    
            $table->unsignedSmallInteger('stok')->default(0);
            $table->unsignedSmallInteger('rusak')->default(0);
            $table->unsignedSmallInteger('perbaikan')->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->string('foto', 255)->nullable();           
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peralatan');
    }
};
