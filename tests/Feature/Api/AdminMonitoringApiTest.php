<?php

namespace Tests\Feature\Api;

use App\Models\BagiHasil;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;

class AdminMonitoringApiTest extends ApiTestCase
{
    /**
     * @return array{0: User, 1: User}
     */
    private function dataPendapatan(): array
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $motor = $this->motor($pemilik, 'B 1111 AA');
        $penyewaan = $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_SELESAI);

        Transaksi::create([
            'penyewaan_id' => $penyewaan->id,
            'jumlah' => 250000,
            'metode_pembayaran' => Transaksi::METODE_TRANSFER,
            'status' => Transaksi::STATUS_DIBAYAR,
            'tanggal' => now()->toDateString(),
        ]);

        // Transaksi pending tidak boleh dihitung sebagai pendapatan.
        Transaksi::create([
            'penyewaan_id' => $penyewaan->id,
            'jumlah' => 100000,
            'metode_pembayaran' => Transaksi::METODE_CASH,
            'status' => Transaksi::STATUS_PENDING,
            'tanggal' => now()->toDateString(),
        ]);

        BagiHasil::create([
            'penyewaan_id' => $penyewaan->id,
            'persentase_hasil_pemilik' => 80,
            'persentase_hasil_admin' => 20,
            'nominal_pemilik' => 200000,
            'nominal_admin' => 50000,
            'settled_at' => now(),
            'tanggal' => now()->toDateString(),
        ]);

        return [$pemilik, $penyewa];
    }

    public function test_admin_dapat_melihat_daftar_pemilik(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        [$pemilik] = $this->dataPendapatan();

        $res = $this->sebagai($admin)->getJson('/api/admin/owners');

        $res->assertOk()->assertJsonPath('success', true)->assertJsonCount(1, 'data');

        $data = collect($res->json('data'))->firstWhere('id', $pemilik->id);
        $this->assertNotNull($data);
        $this->assertSame(1, $data['jumlah_motor']);
        $this->assertSame(1, $data['jumlah_penyewaan']);
        $this->assertSame(200000.0, (float) $data['total_pendapatan']);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function test_daftar_pemilik_tidak_memuat_penyewa(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $this->sebagai($admin)
            ->getJson('/api/admin/owners')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_dapat_melihat_daftar_penyewa(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        [, $penyewa] = $this->dataPendapatan();

        $res = $this->sebagai($admin)->getJson('/api/admin/renters');

        $res->assertOk()->assertJsonPath('success', true)->assertJsonCount(1, 'data');

        $data = collect($res->json('data'))->firstWhere('id', $penyewa->id);
        $this->assertNotNull($data);
        $this->assertSame(1, $data['jumlah_penyewaan']);
        // Hanya transaksi dibayar (250.000); transaksi pending diabaikan.
        $this->assertSame(250000.0, (float) $data['total_pembayaran']);
    }

    public function test_daftar_penyewa_tidak_memuat_pemilik(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');

        $this->sebagai($admin)
            ->getJson('/api/admin/renters')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_dapat_melihat_detail_pemilik(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        [$pemilik, $penyewa] = $this->dataPendapatan();

        $res = $this->sebagai($admin)->getJson("/api/admin/owners/{$pemilik->id}");

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $pemilik->id)
            ->assertJsonPath('data.ringkasan.jumlah_motor', 1)
            ->assertJsonPath('data.ringkasan.jumlah_penyewaan', 1)
            ->assertJsonCount(1, 'data.motors')
            ->assertJsonCount(1, 'data.penyewaan');

        $this->assertSame(200000.0, (float) $res->json('data.ringkasan.pendapatan'));
        $this->assertSame($penyewa->id, $res->json('data.penyewaan.0.penyewa.id'));
        $this->assertSame('B 1111 AA', $res->json('data.motors.0.no_plat'));
    }

    public function test_detail_pemilik_menolak_user_dengan_role_lain(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $this->sebagai($admin)
            ->getJson("/api/admin/owners/{$penyewa->id}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data pemilik tidak ditemukan.');
    }

    public function test_admin_dapat_melihat_detail_penyewa(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        [, $penyewa] = $this->dataPendapatan();

        $res = $this->sebagai($admin)->getJson("/api/admin/renters/{$penyewa->id}");

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $penyewa->id)
            ->assertJsonPath('data.ringkasan.jumlah_penyewaan', 1)
            ->assertJsonCount(1, 'data.penyewaan');

        $this->assertSame(250000.0, (float) $res->json('data.ringkasan.total_pembayaran'));
        $this->assertSame('B 1111 AA', $res->json('data.penyewaan.0.motor.no_plat'));
    }

    public function test_detail_penyewa_menolak_user_dengan_role_lain(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');

        $this->sebagai($admin)
            ->getJson("/api/admin/renters/{$pemilik->id}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data penyewa tidak ditemukan.');
    }

    public function test_monitoring_tidak_ditemukan_mengembalikan_404(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'admin@t.test');

        $this->sebagai($admin)
            ->getJson('/api/admin/owners/99999')
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->sebagai($admin)
            ->getJson('/api/admin/renters/99999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_role_non_admin_tidak_bisa_akses_monitoring(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $this->sebagai($pemilik)->getJson('/api/admin/owners')->assertForbidden();
        $this->sebagai($penyewa)->getJson('/api/admin/owners')->assertForbidden();
        $this->sebagai($pemilik)->getJson('/api/admin/renters')->assertForbidden();
        $this->sebagai($penyewa)->getJson('/api/admin/renters')->assertForbidden();
        $this->sebagai($pemilik)->getJson("/api/admin/owners/{$pemilik->id}")->assertForbidden();
        $this->sebagai($penyewa)->getJson("/api/admin/renters/{$penyewa->id}")->assertForbidden();
    }

    public function test_monitoring_menolak_tanpa_token(): void
    {
        $this->getJson('/api/admin/owners')->assertStatus(401);
        $this->getJson('/api/admin/renters')->assertStatus(401);
        $this->getJson('/api/admin/owners/1')->assertStatus(401);
        $this->getJson('/api/admin/renters/1')->assertStatus(401);
    }
}
