<?php

namespace Tests\Unit;

use App\Helpers\IdGenerator;
use App\Models\Penjadwalan;
use App\Models\Peralatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function generate_id_pertama_jika_tabel_kosong(): void
    {
        $id = IdGenerator::next(User::class, 'id_user', 'US');

        $this->assertEquals('US001', $id);
    }

    /** @test */
    public function generate_id_berikutnya_berdasarkan_data_terakhir(): void
    {
        // Seed manual 2 user
        User::create([
            'id_user'   => 'US001',
            'nama_user' => 'User Satu',
            'email'     => 'satu@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'operator',
        ]);
        User::create([
            'id_user'   => 'US002',
            'nama_user' => 'User Dua',
            'email'     => 'dua@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'operator',
        ]);

        $id = IdGenerator::next(User::class, 'id_user', 'US');

        $this->assertEquals('US003', $id);
    }

    /** @test */
    public function generate_id_dengan_prefix_strip(): void
    {
        Peralatan::create([
            'id_peralatan'   => 'PR-001',
            'nama_peralatan' => 'Laptop',
            'gedung'         => 'Gedung A',
            'stok'           => 5,
        ]);

        $id = IdGenerator::next(Peralatan::class, 'id_peralatan', 'PR-');

        $this->assertEquals('PR-002', $id);
    }
}
