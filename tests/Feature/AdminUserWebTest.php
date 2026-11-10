<?php

namespace Tests\Feature;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserWebTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'admin@t.test'): User
    {
        return User::create([
            'nama' => 'Admin',
            'email' => $email,
            'no_tlpn' => '081234567890',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function pemilik(string $email = 'pemilik@t.test'): User
    {
        return User::create([
            'nama' => 'Pemilik',
            'email' => $email,
            'no_tlpn' => '081234567891',
            'password' => 'password',
            'role' => User::ROLE_PEMILIK,
        ]);
    }

    private function penyewa(string $email = 'penyewa@t.test'): User
    {
        return User::create([
            'nama' => 'Penyewa',
            'email' => $email,
            'no_tlpn' => '081234567892',
            'password' => 'password',
            'role' => User::ROLE_PENYEWA,
        ]);
    }

    private function motor(User $pemilik, string $plat = 'B 1111 AA', string $status = Motor::STATUS_TERSEDIA): Motor
    {
        $motor = Motor::create([
            'pemilik_id' => $pemilik->id,
            'merk' => 'Beat',
            'tipe_cc' => '125',
            'no_plat' => $plat,
            'status' => $status,
        ]);

        \App\Models\TarifRental::create([
            'motor_id' => $motor->id,
            'tarif_harian' => 50000,
            'tarif_mingguan' => 300000,
            'tarif_bulanan' => 1000000,
        ]);

        return $motor;
    }

    private function penyewaan(User $penyewa, Motor $motor): Penyewaan
    {
        return Penyewaan::create([
            'penyewa_id' => $penyewa->id,
            'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDay(),
            'tanggal_selesai' => now()->addDays(5),
            'tipe_durasi' => Penyewaan::TIPE_HARIAN,
            'harga' => 250000,
            'status' => Penyewaan::STATUS_PENDING,
        ]);
    }

    // ---------- INDEX ----------

    public function test_admin_dapat_melihat_halaman_daftar_user(): void
    {
        $admin = $this->admin();
        $this->pemilik('p@t.test');
        $this->penyewa('s@t.test');

        $this->actingAs($admin)->get(route('admin.user.index'))
            ->assertOk()
            ->assertSee('Data User')
            ->assertSee('Admin')
            ->assertSee('Pemilik')
            ->assertSee('Penyewa');
    }

    public function test_daftar_user_menampilkan_jumlah_motor_dan_penyewaan_benar(): void
    {
        $admin = $this->admin();

        // Pemilik dengan 3 motor (status berbeda)
        $pemilik = $this->pemilik('p@t.test');
        $this->motor($pemilik, 'B 1111 AA', Motor::STATUS_TERSEDIA);
        $this->motor($pemilik, 'B 2222 BB', Motor::STATUS_PENDING);
        $this->motor($pemilik, 'B 3333 CC', Motor::STATUS_DISEWA);

        // Penyewa dengan 2 penyewaan
        $penyewa = $this->penyewa('s@t.test');
        $motor = $this->motor($pemilik, 'B 4444 DD', Motor::STATUS_TERSEDIA);
        $this->penyewaan($penyewa, $motor);
        $this->penyewaan($penyewa, $this->motor($pemilik, 'B 5555 EE', Motor::STATUS_TERSEDIA));

        // Admin tanpa data
        $this->actingAs($admin)->get(route('admin.user.index'))
            ->assertOk()
            ->assertSee('3') // Jumlah motor pemilik
            ->assertSee('2'); // Jumlah penyewaan penyewa
    }

    public function test_daftar_user_bisa_difilter_role(): void
    {
        $admin = $this->admin();
        $this->pemilik('p@t.test');
        $this->penyewa('s@t.test');

        $this->actingAs($admin)->get(route('admin.user.index', ['role' => 'penyewa']))
            ->assertOk()
            ->assertSee('Penyewa')
            ->assertDontSee('p@t.test');
    }

    public function test_daftar_user_bisa_dicari(): void
    {
        $admin = $this->admin();
        $this->pemilik('pemilik1@t.test');
        $this->penyewa('penyewa1@t.test');

        $this->actingAs($admin)->get(route('admin.user.index', ['cari' => 'pemilik1']))
            ->assertOk()
            ->assertSee('pemilik1@t.test')
            ->assertDontSee('penyewa1@t.test');
    }

    public function test_role_non_admin_tidak_bisa_akses_halaman_user(): void
    {
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();

        $this->actingAs($pemilik)->get(route('admin.user.index'))->assertRedirect(route('pemilik.dashboard'))->assertSessionHas('error');
        $this->actingAs($penyewa)->get(route('admin.user.index'))->assertRedirect(route('penyewa.dashboard'))->assertSessionHas('error');
    }

    // ---------- CREATE ----------

    public function test_admin_dapat_melihat_halaman_tambah_user(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.user.create'))
            ->assertOk()
            ->assertSee('Tambah User')
            ->assertSee('admin')
            ->assertSee('pemilik')
            ->assertSee('penyewa');
    }

    public function test_admin_dapat_membuat_user_dan_password_terhash(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.user.store'), [
            'nama' => 'User Baru',
            'email' => 'baru@t.test',
            'no_tlpn' => '081234567890',
            'role' => User::ROLE_PENYEWA,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('admin.user.index'));

        $user = User::where('email', 'baru@t.test')->firstOrFail();
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertSame(User::ROLE_PENYEWA, $user->role);
    }

    public function test_admin_boleh_membuat_akun_admin_lewat_halaman_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.user.store'), [
            'nama' => 'Admin Baru',
            'email' => 'adminbaru@t.test',
            'no_tlpn' => '081234567890',
            'role' => User::ROLE_ADMIN,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'adminbaru@t.test',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_pembuatan_user_dengan_data_tidak_valid_ditolak(): void
    {
        $admin = $this->admin();
        $this->penyewa('sudah@t.test');

        $this->actingAs($admin)->post(route('admin.user.store'), [
            'nama' => '',
            'email' => 'sudah@t.test',
            'no_tlpn' => '123',
            'role' => 'kepala',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['nama', 'email', 'no_tlpn', 'role', 'password']);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_role_non_admin_tidak_bisa_akses_tambah_user(): void
    {
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();

        $this->actingAs($pemilik)->get(route('admin.user.create'))->assertRedirect(route('pemilik.dashboard'))->assertSessionHas('error');
        $this->actingAs($pemilik)->post(route('admin.user.store'), [])->assertRedirect(route('pemilik.dashboard'))->assertSessionHas('error');
        $this->actingAs($penyewa)->get(route('admin.user.create'))->assertRedirect(route('penyewa.dashboard'))->assertSessionHas('error');
        $this->actingAs($penyewa)->post(route('admin.user.store'), [])->assertRedirect(route('penyewa.dashboard'))->assertSessionHas('error');
    }

    // ---------- EDIT ----------

    public function test_admin_dapat_melihat_halaman_edit_user(): void
    {
        $admin = $this->admin();
        $user = $this->penyewa('s@t.test');

        $this->actingAs($admin)->get(route('admin.user.edit', $user))
            ->assertOk()
            ->assertSee('Ubah User')
            ->assertSee('s@t.test')
            ->assertSee('penyewa');
    }

    public function test_admin_dapat_memperbarui_user_tanpa_mengubah_password(): void
    {
        $admin = $this->admin();
        $user = $this->penyewa('lama@t.test');
        $hashLama = $user->password;

        $this->actingAs($admin)->put(route('admin.user.update', $user), [
            'nama' => 'Nama Baru',
            'email' => 'baru@t.test',
            'no_tlpn' => '081298765432',
            'role' => User::ROLE_PEMILIK,
        ])->assertRedirect(route('admin.user.index'));

        $user->refresh();
        $this->assertSame('Nama Baru', $user->nama);
        $this->assertSame('baru@t.test', $user->email);
        $this->assertSame(User::ROLE_PEMILIK, $user->role);
        // Password tidak dikirim -> password lama dipertahankan
        $this->assertSame($hashLama, $user->password);
    }

    public function test_admin_dapat_mengganti_password_user(): void
    {
        $admin = $this->admin();
        $user = $this->penyewa('s@t.test');

        $this->actingAs($admin)->put(route('admin.user.update', $user), [
            'nama' => $user->nama,
            'email' => $user->email,
            'no_tlpn' => $user->no_tlpn,
            'role' => $user->role,
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->password));
    }

    public function test_email_sendiri_tidak_dianggap_duplikat(): void
    {
        $admin = $this->admin();
        $user = $this->penyewa('s@t.test');

        $this->actingAs($admin)->put(route('admin.user.update', $user), [
            'nama' => 'Tetap',
            'email' => 's@t.test',
            'no_tlpn' => '081234567890',
            'role' => User::ROLE_PENYEWA,
        ])->assertRedirect();
    }

    public function test_email_milik_user_lain_ditolak_saat_update(): void
    {
        $admin = $this->admin();
        $a = $this->penyewa('a@t.test');
        $this->penyewa('b@t.test');

        $this->actingAs($admin)->put(route('admin.user.update', $a), [
            'nama' => 'A',
            'email' => 'b@t.test',
            'no_tlpn' => '081234567890',
            'role' => User::ROLE_PENYEWA,
        ])->assertSessionHasErrors('email');
    }

    public function test_role_admin_utama_tidak_dapat_diturunkan(): void
    {
        $utama = $this->admin('utama@t.test'); // id=1 (pertama)
        $lain = $this->admin('lain@t.test');

        $this->actingAs($lain)->put(route('admin.user.update', $utama), [
            'nama' => $utama->nama,
            'email' => $utama->email,
            'no_tlpn' => $utama->no_tlpn,
            'role' => User::ROLE_PENYEWA,
        ])->assertSessionHas('error');

        $this->assertSame(User::ROLE_ADMIN, $utama->fresh()->role);
    }

    public function test_role_non_admin_tidak_bisa_akses_edit_user(): void
    {
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $target = $this->penyewa('target@t.test');

        $this->actingAs($pemilik)->get(route('admin.user.edit', $target))->assertRedirect(route('pemilik.dashboard'))->assertSessionHas('error');
        $this->actingAs($pemilik)->put(route('admin.user.update', $target), [])->assertRedirect(route('pemilik.dashboard'))->assertSessionHas('error');
        $this->actingAs($penyewa)->get(route('admin.user.edit', $target))->assertRedirect(route('penyewa.dashboard'))->assertSessionHas('error');
        $this->actingAs($penyewa)->put(route('admin.user.update', $target), [])->assertRedirect(route('penyewa.dashboard'))->assertSessionHas('error');
    }

    // ---------- DELETE ----------

    public function test_admin_dapat_menghapus_user_tanpa_data_historis(): void
    {
        $admin = $this->admin();
        $user = $this->penyewa('s@t.test');
        $user->createToken('test');

        $this->actingAs($admin)->delete(route('admin.user.destroy', $user))
            ->assertRedirect(route('admin.user.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_user_dengan_riwayat_penyewaan_tidak_dapat_dihapus(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik('p@t.test');
        $penyewa = $this->penyewa('s@t.test');
        $this->penyewaan($penyewa, $this->motor($pemilik));

        $this->actingAs($admin)->delete(route('admin.user.destroy', $penyewa))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $penyewa->id]);
    }

    public function test_pemilik_dengan_motor_terdaftar_tidak_dapat_dihapus(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik('p@t.test');
        $this->motor($pemilik, 'B 1111 AA', Motor::STATUS_PENDING);

        $this->actingAs($admin)->delete(route('admin.user.destroy', $pemilik))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $pemilik->id]);
        $this->assertDatabaseHas('motors', ['pemilik_id' => $pemilik->id]);
    }

    public function test_akun_admin_utama_tidak_dapat_dihapus(): void
    {
        $utama = $this->admin('utama@t.test');
        $lain = $this->admin('lain@t.test');

        $this->actingAs($lain)->delete(route('admin.user.destroy', $utama))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $utama->id]);
    }

    public function test_admin_tidak_dapat_menghapus_akun_sendiri(): void
    {
        $this->admin('utama@t.test');
        $admin = $this->admin('lain@t.test');

        $this->actingAs($admin)->delete(route('admin.user.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_role_non_admin_tidak_bisa_hapus_user(): void
    {
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $target = $this->penyewa('target@t.test');

        $this->actingAs($pemilik)->delete(route('admin.user.destroy', $target))->assertRedirect(route('pemilik.dashboard'))->assertSessionHas('error');
        $this->actingAs($penyewa)->delete(route('admin.user.destroy', $target))->assertRedirect(route('penyewa.dashboard'))->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}