<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->string('zoom_meeting_id', 50)->nullable()->after('keterangan');
            $table->string('zoom_password', 20)->nullable()->after('zoom_meeting_id');
            $table->enum('zoom_account', ['akun_1', 'akun_2'])->nullable()->after('zoom_password');
            $table->boolean('link_otomatis')->default(false)->after('zoom_account');
        });
    }

    public function down(): void
    {
        Schema::table('penjadwalan', function (Blueprint $table) {
            $table->dropColumn(['zoom_meeting_id', 'zoom_password', 'zoom_account', 'link_otomatis']);
        });
    }
};
