<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->dropForeign(['id_user']);
            $table->dropColumn('id_user');
        });

        Schema::create('jadwal_operator', function (Blueprint $table) {
            $table->id();
            $table->string('id_penjadwalan', 10);
            $table->string('id_user', 10);

            $table->foreign('id_penjadwalan')
                  ->references('id_penjadwalan')
                  ->on('penjadwalan')
                  ->cascadeOnDelete();

            $table->foreign('id_user')
                  ->references('id_user')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->unique(['id_penjadwalan', 'id_user']);
        });
    }

    public function down(): void
    {
        // Proyek masih development, tidak wajib diimplementasikan penuh
    }
};
