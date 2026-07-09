<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peralatan', function (Blueprint $table) {
            $table->dropColumn('perbaikan');
        });
    }

    public function down(): void
    {
        Schema::table('peralatan', function (Blueprint $table) {
            $table->unsignedSmallInteger('perbaikan')->default(0)->after('rusak');
        });
    }
};
