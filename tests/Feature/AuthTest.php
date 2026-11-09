<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function seedUsers(): void
    {
        User::create(['nama'=>'Admin','email'=>'admin@t.test','no_tlpn'=>'0811','password'=>'password','role'=>'admin']);
        User::create(['nama'=>'Pemilik','email'=>'pemilik@t.test','no_tlpn'=>'0812','password'=>'password','role'=>'pemilik']);
        User::create(['nama'=>'Penyewa','email'=>'penyewa@t.test','no_tlpn'=>'0813','password'=>'password','role'=>'penyewa']);
    }

    public function test_login_sukses_redirect_ke_dashboard_admin(): void
    {
        $this->seedUsers();
        $res = $this->post('/login', ['email'=>'admin@t.test','password'=>'password']);
        $res->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_redirect_sesuai_role(): void
    {
        $this->seedUsers();
        $this->post('/login', ['email'=>'pemilik@t.test','password'=>'password'])->assertRedirect(route('pemilik.dashboard'));
        auth()->logout();
        $this->post('/login', ['email'=>'penyewa@t.test','password'=>'password'])->assertRedirect(route('penyewa.dashboard'));
    }

    public function test_password_salah_ditolak(): void
    {
        $this->seedUsers();
        $this->post('/login', ['email'=>'admin@t.test','password'=>'salah'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_email_salah_ditolak(): void
    {
        $this->seedUsers();
        $this->post('/login', ['email'=>'tidakada@t.test','password'=>'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_register_sukses_dan_password_terhash(): void
    {
        $res = $this->post('/register', [
            'nama'=>'Baru','email'=>'baru@t.test','no_tlpn'=>'081999999999',
            'role'=>'penyewa','password'=>'rahasia123','password_confirmation'=>'rahasia123',
        ]);
        $res->assertRedirect(route('penyewa.dashboard'));
        $u = User::where('email','baru@t.test')->firstOrFail();
        $this->assertSame('penyewa', $u->role);
        $this->assertNotSame('rahasia123', $u->password);
        $this->assertTrue(Hash::check('rahasia123', $u->password));
    }

    public function test_tidak_bisa_register_sebagai_admin(): void
    {
        $this->post('/register', [
            'nama'=>'Palsu','email'=>'palsu@t.test','no_tlpn'=>'081999999999',
            'role'=>'admin','password'=>'rahasia123','password_confirmation'=>'rahasia123',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email'=>'palsu@t.test']);
        $this->assertGuest();
    }

    public function test_penyewa_tidak_bisa_akses_admin_dashboard(): void
    {
        $this->seedUsers();
        $penyewa = User::where('role','penyewa')->first();
        $this->actingAs($penyewa)->get('/admin/dashboard')
            ->assertRedirect(route('penyewa.dashboard'));
    }

    public function test_pemilik_tidak_bisa_akses_admin_dashboard(): void
    {
        $this->seedUsers();
        $pemilik = User::where('role','pemilik')->first();
        $this->actingAs($pemilik)->get('/admin/dashboard')->assertRedirect(route('pemilik.dashboard'));
    }

    public function test_admin_tidak_bisa_akses_penyewa_dashboard(): void
    {
        $this->seedUsers();
        $admin = User::where('role','admin')->first();
        $this->actingAs($admin)->get('/penyewa/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/pemilik/dashboard')->assertRedirect(route('login'));
        $this->get('/penyewa/dashboard')->assertRedirect(route('login'));
    }

    public function test_dashboard_route_mengarahkan_sesuai_role(): void
    {
        $this->seedUsers();
        $this->actingAs(User::where('role','pemilik')->first())
            ->get('/dashboard')->assertRedirect(route('pemilik.dashboard'));
    }

    public function test_logout(): void
    {
        $this->seedUsers();
        $user = User::where('role','penyewa')->first();
        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_json_request_tanpa_role_returns_403(): void
    {
        $this->seedUsers();
        $penyewa = User::where('role','penyewa')->first();
        $this->actingAs($penyewa)->getJson('/admin/dashboard')->assertForbidden();
    }
}
