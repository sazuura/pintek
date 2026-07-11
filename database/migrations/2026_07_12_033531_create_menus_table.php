<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry menu/halaman aplikasi - dipakai bareng oleh sidebar dinamis dan modal
 * "Hak Akses Halaman". Mendukung menu bertingkat lewat id_parent meskipun struktur
 * menu Pintek saat ini masih 1 tingkat (lihat docs/plans/planning-role-akses-dinamis.md §3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_menu', 100);
            $table->string('slug', 50)->unique();
            $table->string('route_name', 100)->nullable();
            $table->string('icon', 50)->nullable();
            $table->foreignId('id_parent')->nullable()->constrained('menus')->nullOnDelete();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
