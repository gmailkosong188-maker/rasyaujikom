<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthApiTest extends ApiTestCase
{
    private function payloadRegister(array $over = []): array
    {
        return array_merge([
            'nama' => 'Penyewa Baru',
            'email' => 'baru@t.test',
            'no_tlpn' => '081234567890',
            'role' => User::ROLE_PENYEWA,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $over);
    }

    public function test_register_berhasil_dan_mendapat_token(): void
    {
        $res = $this->postJson('/api/auth/register', $this->payloadRegister());

        $res->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', User::ROLE_PENYEWA)
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token', 'token_type']]);

        $token = $res->json('data.token');
        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $user = User::where('email', 'baru@t.test')->firstOrFail();
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertArrayNotHasKey('password', $res->json('data.user'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_register_sebagai_admin_ditolak(): void
    {
        $this->postJson('/api/auth/register', $this->payloadRegister(['role' => User::ROLE_ADMIN]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'baru@t.test']);
    }

    public function test_register_data_tidak_valid_ditolak(): void
    {
        $this->postJson('/api/auth/register', [
            'nama' => '',
            'email' => 'bukan-email',
            'no_tlpn' => '123',
            'role' => 'kepala',
            'password' => 'pendek',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['nama', 'email', 'no_tlpn', 'role', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_berhasil_dan_mendapat_token(): void
    {
        $this->pengguna(User::ROLE_PENYEWA, 'penyewa@t.test');

        $res = $this->postJson('/api/auth/login', [
            'email' => 'penyewa@t.test',
            'password' => 'password',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'penyewa@t.test')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertIsString($res->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_password_salah_ditolak(): void
    {
        $this->pengguna(User::ROLE_PENYEWA, 'penyewa@t.test');

        $this->postJson('/api/auth/login', [
            'email' => 'penyewa@t.test',
            'password' => 'salah-sekali',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_endpoint_terlindungi_menolak_tanpa_token(): void
    {
        $this->getJson('/api/owner/motors')
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        $this->getJson('/api/bookings/history')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_logout_menghapus_token(): void
    {
        $user = $this->pengguna(User::ROLE_PENYEWA, 'penyewa@t.test');

        $this->sebagai($user)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
