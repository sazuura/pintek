<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = hak akses satu role terhadap satu menu (gabungan konsep matrix C/R/U/D
 * di Gambar 1 dan checklist menu di Gambar 2 - satu sumber data untuk keduanya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_menu_akses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_role')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('id_menu')->constrained('menus')->cascadeOnDelete();
            $table->boolean('bisa_lihat')->default(false);
            $table->boolean('bisa_tambah')->default(false);
            $table->boolean('bisa_ubah')->default(false);
            $table->boolean('bisa_hapus')->default(false);
            $table->timestamps();
            $table->unique(['id_role', 'id_menu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_akses');
    }
};
