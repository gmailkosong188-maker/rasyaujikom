<?php

namespace Tests\Feature\Api;

use App\Models\Motor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserApiTest extends ApiTestCase
{
    private function payloadUser(array $over = []): array
    {
        return array_merge([
            'nama' => 'User Baru',
            'email' => 'baru@t.test',
            'no_tlpn' => '081234567890',
            'role' => User::ROLE_PENYEWA,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $over);
    }

    public function test_admin_dapat_melihat_daftar_user(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');
        $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $res = $this->sebagai($admin)->getJson('/api/admin/users');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');

        $this->assertArrayNotHasKey('password', $res->json('data.0'));
    }

    public function test_daftar_user_bisa_difilter_role(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');
        $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $res = $this->sebagai($admin)->getJson('/api/admin/users?role=penyewa');

        $res->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame(User::ROLE_PENYEWA, $res->json('data.0.role'));
    }

    public function test_admin_dapat_membuat_user_dan_password_di_hash(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');

        $res = $this->sebagai($admin)->postJson('/api/admin/users', $this->payloadUser());

        $res->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'baru@t.test')
            ->assertJsonPath('data.role', User::ROLE_PENYEWA);

        $this->assertArrayNotHasKey('password', $res->json('data'));

        $user = User::where('email', 'baru@t.test')->firstOrFail();
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_admin_boleh_membuat_akun_admin_lewat_endpoint_admin(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');

        $this->sebagai($admin)
            ->postJson('/api/admin/users', $this->payloadUser([
                'email' => 'adminbaru@t.test',
                'role' => User::ROLE_ADMIN,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.role', User::ROLE_ADMIN);
    }

    public function test_registrasi_publik_tetap_tidak_boleh_membuat_admin(): void
    {
        $this->postJson('/api/auth/register', [
            'nama' => 'Nakal',
            'email' => 'nakal@t.test',
            'no_tlpn' => '081234567890',
            'role' => User::ROLE_ADMIN,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'nakal@t.test']);
    }

    public function test_pembuatan_user_dengan_data_tidak_valid_ditolak(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $this->pengguna(User::ROLE_PENYEWA, 'sudah@t.test');

        $this->sebagai($admin)
            ->postJson('/api/admin/users', $this->payloadUser([
                'nama' => '',
                'email' => 'sudah@t.test',
                'no_tlpn' => '123',
                'role' => 'kepala',
                'password' => 'pendek',
                'password_confirmation' => 'beda',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['nama', 'email', 'no_tlpn', 'role', 'password']);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_dapat_melihat_detail_user(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $this->motor($pemilik, 'B 1111 AA');
        $this->penyewaan($penyewa, $this->motor($pemilik, 'B 2222 BB'));

        $this->sebagai($admin)
            ->getJson("/api/admin/users/{$penyewa->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $penyewa->id)
            ->assertJsonPath('data.jumlah_penyewaan', 1)
            ->assertJsonPath('data.admin_utama', false);
    }

    public function test_detail_user_tidak_ditemukan_mengembalikan_404(): void
    {
        $this->sebagai($this->pengguna(User::ROLE_ADMIN, 'admin@t.test'))
            ->getJson('/api/admin/users/99999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_admin_dapat_memperbarui_user(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $user = $this->pengguna(User::ROLE_PENYEWA, 'lama@t.test');
        $hashLama = $user->password;

        $this->sebagai($admin)
            ->putJson("/api/admin/users/{$user->id}", [
                'nama' => 'Nama Baru',
                'email' => 'baru@t.test',
                'no_tlpn' => '081298765432',
                'role' => User::ROLE_PEMILIK,
            ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Nama Baru')
            ->assertJsonPath('data.email', 'baru@t.test')
            ->assertJsonPath('data.role', User::ROLE_PEMILIK);

        $user->refresh();
        $this->assertSame('Nama Baru', $user->nama);
        // Password tidak dikirim -> password lama dipertahankan.
        $this->assertSame($hashLama, $user->password);
    }

    public function test_admin_dapat_mengganti_password_user(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $user = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $this->sebagai($admin)
            ->putJson("/api/admin/users/{$user->id}", [
                'nama' => $user->nama,
                'email' => $user->email,
                'no_tlpn' => $user->no_tlpn,
                'role' => $user->role,
                'password' => 'passwordbaru',
                'password_confirmation' => 'passwordbaru',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->password));
    }

    public function test_email_sendiri_tidak_dianggap_duplikat(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $user = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $this->sebagai($admin)
            ->putJson("/api/admin/users/{$user->id}", [
                'nama' => 'Tetap',
                'email' => 's@t.test',
                'no_tlpn' => '081234567890',
                'role' => User::ROLE_PENYEWA,
            ])
            ->assertOk();
    }

    public function test_email_milik_user_lain_ditolak_saat_update(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $a = $this->pengguna(User::ROLE_PENYEWA, 'a@t.test');
        $this->pengguna(User::ROLE_PENYEWA, 'b@t.test');

        $this->sebagai($admin)
            ->putJson("/api/admin/users/{$a->id}", [
                'nama' => 'A',
                'email' => 'b@t.test',
                'no_tlpn' => '081234567890',
                'role' => User::ROLE_PENYEWA,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_dapat_menghapus_user_tanpa_data_historis(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $user = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $user->createToken('test');

        $this->sebagai($admin)
            ->deleteJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_user_dengan_riwayat_penyewaan_tidak_dapat_dihapus(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $this->penyewaan($penyewa, $this->motor($pemilik));

        $this->sebagai($admin)
            ->deleteJson("/api/admin/users/{$penyewa->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', ['id' => $penyewa->id]);
    }

    public function test_pemilik_dengan_motor_terdaftar_tidak_dapat_dihapus(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');
        $this->motor($pemilik, 'B 1111 AA', Motor::STATUS_PENDING);

        $this->sebagai($admin)
            ->deleteJson("/api/admin/users/{$pemilik->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', ['id' => $pemilik->id]);
        $this->assertDatabaseHas('motors', ['pemilik_id' => $pemilik->id]);
    }

    public function test_akun_admin_utama_tidak_dapat_dihapus(): void
    {
        $utama = $this->pengguna(User::ROLE_ADMIN, 'utama@t.test');
        $lain = $this->pengguna(User::ROLE_ADMIN, 'lain@t.test');

        $this->sebagai($lain)
            ->deleteJson("/api/admin/users/{$utama->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', ['id' => $utama->id]);
    }

    public function test_role_admin_utama_tidak_dapat_diturunkan(): void
    {
        $utama = $this->pengguna(User::ROLE_ADMIN, 'utama@t.test');
        $lain = $this->pengguna(User::ROLE_ADMIN, 'lain@t.test');

        $this->sebagai($lain)
            ->putJson("/api/admin/users/{$utama->id}", [
                'nama' => $utama->nama,
                'email' => $utama->email,
                'no_tlpn' => $utama->no_tlpn,
                'role' => User::ROLE_PENYEWA,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(User::ROLE_ADMIN, $utama->fresh()->role);
    }

    public function test_admin_tidak_dapat_menghapus_akun_sendiri(): void
    {
        $this->pengguna(User::ROLE_ADMIN, 'utama@t.test');
        $admin = $this->pengguna(User::ROLE_ADMIN, 'lain@t.test');

        $this->sebagai($admin)
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_role_non_admin_tidak_bisa_akses_crud_user(): void
    {
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $target = $this->pengguna(User::ROLE_PENYEWA, 'target@t.test');

        $this->sebagai($penyewa)->getJson('/api/admin/users')->assertForbidden();
        $this->sebagai($penyewa)->postJson('/api/admin/users', $this->payloadUser())->assertForbidden();
        $this->sebagai($penyewa)->getJson("/api/admin/users/{$target->id}")->assertForbidden();
        $this->sebagai($penyewa)->putJson("/api/admin/users/{$target->id}", [])->assertForbidden();
        $this->sebagai($penyewa)->deleteJson("/api/admin/users/{$target->id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_crud_user_menolak_tanpa_token(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
        $this->postJson('/api/admin/users', [])->assertStatus(401);
        $this->deleteJson('/api/admin/users/1')->assertStatus(401);
    }

    public function test_delete_user_yang_tidak_ada_mengembalikan_404(): void
    {
        $this->sebagai($this->pengguna(User::ROLE_ADMIN, 'admin@t.test'))
            ->deleteJson('/api/admin/users/99999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
