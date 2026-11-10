<?php

namespace Tests\Feature\Api;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;

class AdminApiTest extends ApiTestCase
{
    public function test_admin_dapat_memverifikasi_motor(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $motor = $this->motor($pemilik, 'B 1111 AA', Motor::STATUS_PENDING);

        $this->sebagai($this->pengguna(User::ROLE_ADMIN, 'a@t.test'))
            ->patchJson("/api/admin/motors/{$motor->id}/verify")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Motor::STATUS_TERSEDIA);

        $this->assertSame(Motor::STATUS_TERSEDIA, $motor->fresh()->status);
    }

    public function test_verifikasi_motor_yang_sudah_tersedia_ditolak(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $motor = $this->motor($pemilik, 'B 1111 AA', Motor::STATUS_TERSEDIA);

        $this->sebagai($this->pengguna(User::ROLE_ADMIN, 'a@t.test'))
            ->patchJson("/api/admin/motors/{$motor->id}/verify")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_dapat_mengonfirmasi_booking(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik);
        $penyewaan = $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_PENDING);

        $this->sebagai($this->pengguna(User::ROLE_ADMIN, 'a@t.test'))
            ->patchJson("/api/admin/bookings/{$penyewaan->id}/confirm")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Penyewaan::STATUS_DIKONFIRMASI);

        $this->assertSame(Penyewaan::STATUS_DIKONFIRMASI, $penyewaan->fresh()->status);
        // Status motor ikut disinkronkan oleh PenyewaanService.
        $this->assertSame(Motor::STATUS_DISEWA, $motor->fresh()->status);
    }

    public function test_konfirmasi_booking_dengan_status_tidak_sah_ditolak(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik);
        $penyewaan = $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_SELESAI);

        $this->sebagai($this->pengguna(User::ROLE_ADMIN, 'a@t.test'))
            ->patchJson("/api/admin/bookings/{$penyewaan->id}/confirm")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_dapat_melihat_laporan_pendapatan(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik);
        $penyewaan = $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_SELESAI);

        Transaksi::create([
            'penyewaan_id' => $penyewaan->id,
            'jumlah' => 250000,
            'metode_pembayaran' => Transaksi::METODE_TRANSFER,
            'status' => Transaksi::STATUS_DIBAYAR,
            'tanggal' => now()->toDateString(),
        ]);

        $res = $this->sebagai($this->pengguna(User::ROLE_ADMIN, 'a@t.test'))
            ->getJson('/api/admin/reports/revenue'.$this->rentang());

        $res->assertOk()->assertJsonPath('success', true);
        $this->assertSame(250000.0, (float) $res->json('data.total_pendapatan'));
        $this->assertSame(1.0, (float) $res->json('data.jumlah_motor'));
        $this->assertArrayHasKey('periode', $res->json('data'));
    }

    public function test_pemilik_dan_penyewa_tidak_bisa_akses_endpoint_admin(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik, 'B 1111 AA', Motor::STATUS_PENDING);

        $this->sebagai($pemilik)
            ->patchJson("/api/admin/motors/{$motor->id}/verify")
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->sebagai($penyewa)
            ->getJson('/api/admin/reports/revenue')
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertSame(Motor::STATUS_PENDING, $motor->fresh()->status);
    }

    public function test_data_tidak_ditemukan_mengembalikan_404(): void
    {
        $this->sebagai($this->pengguna(User::ROLE_ADMIN, 'a@t.test'))
            ->patchJson('/api/admin/motors/99999/verify')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data tidak ditemukan.');
    }
}
