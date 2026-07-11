<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel konfigurasi role (bagian dari sistem "Sistem Settings" / role akses dinamis).
 * Kolom `slug` HARUS selalu sinkron dengan nilai enum di users.role - kolom itu
 * sengaja TIDAK diubah jadi foreign key ke sini, supaya semua pengecekan role
 * berbasis string yang sudah ada di kode (mis. User::where('role','inventaris'))
 * tetap jalan tanpa perubahan. Lihat docs/plans/planning-role-akses-dinamis.md §2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nama_role', 100);
            $table->string('slug', 50)->unique();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->boolean('is_terkunci')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
