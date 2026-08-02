<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('id_penjadwalan', 10)->nullable()->after('id_user');
            $table->foreign('id_penjadwalan')
                  ->references('id_penjadwalan')
                  ->on('penjadwalan')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {

    }
};
