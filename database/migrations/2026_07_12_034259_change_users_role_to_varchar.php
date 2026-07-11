<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * users.role tadinya ENUM('admin','operator','inventaris') - dilebarkan jadi VARCHAR
 * supaya role baru yang dibuat lewat "Sistem Settings" (tabel roles.slug) bisa
 * di-assign ke user tanpa MySQL menolak (data truncated) karena nilainya di luar
 * daftar enum lama. Ini TIDAK mengubah perilaku 3 role yang sudah ada - semua
 * perbandingan string (role === 'admin' dst) tetap identik, cuma kolomnya sekarang
 * menerima nilai apa pun, bukan cuma 3 pilihan tetap. Lihat
 * docs/plans/planning-role-akses-dinamis.md §2.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        // Rollback cuma aman kalau belum ada user dengan role di luar 3 nilai lama.
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','operator','inventaris') NOT NULL");
    }
};
