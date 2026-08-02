<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_item', function (Blueprint $table) {
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak'])->default('diajukan')->after('jumlah');
        });

        DB::table('peminjaman_item')
            ->join('peminjaman', 'peminjaman_item.id_peminjaman', '=', 'peminjaman.id_peminjaman')
            ->whereIn('peminjaman.status', ['disetujui', 'dikembalikan'])
            ->update(['peminjaman_item.status' => 'disetujui']);

        DB::table('peminjaman_item')
            ->join('peminjaman', 'peminjaman_item.id_peminjaman', '=', 'peminjaman.id_peminjaman')
            ->whereIn('peminjaman.status', ['ditolak', 'dibatalkan'])
            ->update(['peminjaman_item.status' => 'ditolak']);
    }

    public function down(): void
    {
        Schema::table('peminjaman_item', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
