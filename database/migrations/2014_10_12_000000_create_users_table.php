<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id_user', 10)->primary();  
            $table->string('nama_user', 100);
            $table->string('nohp', 20)->nullable();    
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'operator', 'inventaris']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('gedung', 100)->nullable(); 
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
