<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthRoutingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_diarahkan_ke_login_jika_akses_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    #[Test]
    public function admin_diarahkan_ke_dashboard_admin_setelah_login(): void
    {
        $admin = $this->buatUser('US001', 'admin');

        $this->actingAs($admin)
             ->get('/dashboard')
             ->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function operator_diarahkan_ke_dashboard_operator_setelah_login(): void
    {
        $operator = $this->buatUser('US002', 'operator');

        $this->actingAs($operator)
             ->get('/dashboard')
             ->assertRedirect(route('operator.dashboard'));
    }

    #[Test]
    public function inventaris_diarahkan_ke_dashboard_inventaris_setelah_login(): void
    {
        $inventaris = $this->buatUser('US003', 'inventaris');

        $this->actingAs($inventaris)
             ->get('/dashboard')
             ->assertRedirect(route('inventaris.dashboard'));
    }

    #[Test]
    public function operator_tidak_bisa_akses_halaman_admin(): void
    {
        $operator = $this->buatUser('US002', 'operator');

        $this->actingAs($operator)
             ->get('/admin/dashboard')
             ->assertForbidden();
    }

    #[Test]
    public function inventaris_tidak_bisa_akses_halaman_admin(): void
    {
        $inventaris = $this->buatUser('US003', 'inventaris');

        $this->actingAs($inventaris)
             ->get('/admin/dashboard')
             ->assertForbidden();
    }

    #[Test]
    public function admin_tidak_bisa_akses_halaman_operator(): void
    {
        $admin = $this->buatUser('US001', 'admin');

        $this->actingAs($admin)
             ->get('/operator/dashboard')
             ->assertForbidden();
    }

    #[Test]
    public function inventaris_tidak_bisa_akses_halaman_operator(): void
    {
        $inventaris = $this->buatUser('US003', 'inventaris');

        $this->actingAs($inventaris)
             ->get('/operator/dashboard')
             ->assertForbidden();
    }

    #[Test]
    public function admin_tidak_bisa_akses_halaman_inventaris(): void
    {
        $admin = $this->buatUser('US001', 'admin');

        $this->actingAs($admin)
             ->get('/inventaris/dashboard')
             ->assertForbidden();
    }

    #[Test]
    public function operator_tidak_bisa_akses_halaman_inventaris(): void
    {
        $operator = $this->buatUser('US002', 'operator');

        $this->actingAs($operator)
             ->get('/inventaris/dashboard')
             ->assertForbidden();
    }

    #[Test]
    public function user_yang_dinonaktifkan_di_tengah_sesi_langsung_dipaksa_logout(): void
    {
        $operator = $this->buatUser('US002', 'operator');

        $this->actingAs($operator)
             ->get(route('operator.dashboard'))
             ->assertOk();

        $operator->update(['status' => 'inactive']);

        $this->get(route('operator.dashboard'))
             ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[Test]
    public function user_nonaktif_yang_dipaksa_logout_mendapat_pesan_akun_dinonaktifkan(): void
    {
        $operator = $this->buatUser('US002', 'operator');
        $operator->update(['status' => 'inactive']);

        $this->actingAs($operator)
             ->get(route('operator.dashboard'))
             ->assertRedirect(route('login'))
             ->assertSessionHasErrors('email');
    }

    private function buatUser(string $id, string $role): User
    {
        return User::create([
            'id_user'   => $id,
            'nama_user' => "User $id",
            'nohp'      => '08000000000' . substr($id, -1),
            'email'     => "$id@test.com",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'status'    => 'active',
        ]);
    }
}
