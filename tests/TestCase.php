<?php

namespace Tests;

use Database\Seeders\RoleAksesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Semua route sekarang digerbangi middleware 'menu-akses' yang baca tabel
     * role_menu_akses (lihat app/Http/Middleware/MenuAkses.php) - tanpa baris
     * seed ini, SETIAP test yang memakai RefreshDatabase akan selalu kena 403
     * karena tabel itu kosong di database testing. Dijalankan di sini (bukan per
     * file test) supaya semua test yang sudah ada otomatis tetap jalan tanpa
     * masing-masing perlu ditambahi $this->seed(...) manual.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class))) {
            $this->seed(RoleAksesSeeder::class);
        }
    }
}
