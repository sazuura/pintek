<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status penempatan fisik alat di luar konteks rapat/peminjaman - apakah alatnya
 * sedang terpasang/digunakan di suatu tempat (mis. proyektor yang memang dipasang
 * permanen di ruangan) atau sedang tersimpan di lemari/gudang. Beda dari modul
 * Alat Terpasang (yang mencatat instalasi permanen tersendiri) - ini murni
 * penanda kondisi penyimpanan untuk peralatan yang memang dipinjam-pakaikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peralatan', function (Blueprint $table) {
            $table->enum('status_penempatan', ['terpasang', 'tidak_terpasang'])
                ->default('tidak_terpasang')
                ->after('lokasi_detail');
        });
    }

    public function down(): void
    {
        Schema::table('peralatan', function (Blueprint $table) {
            $table->dropColumn('status_penempatan');
        });
    }
};
