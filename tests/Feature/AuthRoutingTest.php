<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test autentikasi dan routing post-login per role.
 */
class AuthRoutingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_diarahkan_ke_login_jika_akses_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    /** @test */
    public function admin_diarahkan_ke_dashboard_admin_setelah_login(): void
    {
        $admin = $this->buatUser('US001', 'admin');

        $this->actingAs($admin)
             ->get('/dashboard')
             ->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function operator_diarahkan_ke_dashboard_operator_setelah_login(): void
    {
        $operator = $this->buatUser('US002', 'operator');

        $this->actingAs($operator)
             ->get('/dashboard')
             ->assertRedirect(route('operator.dashboard'));
    }

    /** @test */
    public function inventaris_diarahkan_ke_dashboard_inventaris_setelah_login(): void
    {
        $inventaris = $this->buatUser('US003', 'inventaris', 'Gedung A');

        $this->actingAs($inventaris)
             ->get('/dashboard')
             ->assertRedirect(route('inventaris.dashboard'));
    }

    /** @test */
    public function operator_tidak_bisa_akses_halaman_admin(): void
    {
        $operator = $this->buatUser('US002', 'operator');

        $this->actingAs($operator)
             ->get('/admin/dashboard')
             ->assertForbidden();
    }

    /** @test */
    public function inventaris_tidak_bisa_akses_halaman_admin(): void
    {
        $inventaris = $this->buatUser('US003', 'inventaris', 'Gedung A');

        $this->actingAs($inventaris)
             ->get('/admin/dashboard')
             ->assertForbidden();
    }

    /** @test */
    public function admin_tidak_bisa_akses_halaman_operator(): void
    {
        $admin = $this->buatUser('US001', 'admin');

        $this->actingAs($admin)
             ->get('/operator/dashboard')
             ->assertForbidden();
    }

    /** @test */
    public function inventaris_tidak_bisa_akses_halaman_operator(): void
    {
        $inventaris = $this->buatUser('US003', 'inventaris', 'Gedung A');

        $this->actingAs($inventaris)
             ->get('/operator/dashboard')
             ->assertForbidden();
    }

    /** @test */
    public function admin_tidak_bisa_akses_halaman_inventaris(): void
    {
        $admin = $this->buatUser('US001', 'admin');

        $this->actingAs($admin)
             ->get('/inventaris/dashboard')
             ->assertForbidden();
    }

    /** @test */
    public function operator_tidak_bisa_akses_halaman_inventaris(): void
    {
        $operator = $this->buatUser('US002', 'operator');

        $this->actingAs($operator)
             ->get('/inventaris/dashboard')
             ->assertForbidden();
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function buatUser(string $id, string $role, ?string $gedung = null): User
    {
        return User::create([
            'id_user'   => $id,
            'nama_user' => "User $id",
            'nohp'      => '08000000000' . substr($id, -1),
            'email'     => "$id@test.com",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'gedung'    => $gedung,
            'status'    => 'active',
        ]);
    }
}
