<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function halaman_404_pakai_view_custom(): void
    {
        $this->get('/rute-tidak-ada-xyz')
            ->assertStatus(404)
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Kembali ke Halaman Sebelumnya');
    }

    #[Test]
    public function halaman_403_pakai_view_custom(): void
    {
        $operator = User::create([
            'id_user' => 'US001', 'nama_user' => 'Op', 'nohp' => '080000000001',
            'email' => 'op@test.com', 'password' => bcrypt('password'), 'role' => 'operator', 'status' => 'active',
        ]);

        $this->actingAs($operator)
            ->get('/admin/dashboard')
            ->assertStatus(403)
            ->assertSee('Akses Ditolak')
            ->assertSee('Kembali ke Halaman Sebelumnya');
    }
}
