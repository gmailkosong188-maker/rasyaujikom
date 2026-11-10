<?php

namespace Tests\Feature\Api;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;

class PenyewaApiTest extends ApiTestCase
{
    private function payloadBooking(Motor $motor, array $over = []): array
    {
        return array_merge([
            'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(6)->toDateString(), // 5 hari
            'tipe_durasi' => Penyewaan::TIPE_HARIAN,
        ], $over);
    }

    public function test_penyewa_hanya_melihat_motor_tersedia(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $tersedia = $this->motor($pemilik, 'B 1111 AA', Motor::STATUS_TERSEDIA);
        $pending = $this->motor($pemilik, 'B 2222 BB', Motor::STATUS_PENDING);
        $disewa = $this->motor($pemilik, 'B 3333 CC', Motor::STATUS_DISEWA);

        $res = $this->sebagai($this->pengguna(User::ROLE_PENYEWA, 's@t.test'))
            ->getJson('/api/motors');

        $res->assertOk()->assertJsonPath('success', true)->assertJsonCount(1, 'data');
        $this->assertSame($tersedia->id, $res->json('data.0.id'));

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertNotContains($pending->id, $ids);
        $this->assertNotContains($disewa->id, $ids);
    }

    public function test_booking_mengikuti_perhitungan_harga_sistem(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik);

        $res = $this->sebagai($penyewa)
            ->postJson('/api/bookings', $this->payloadBooking($motor, ['harga' => 1]));

        $res->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Penyewaan::STATUS_PENDING)
            // Harga dari tarif (5 hari x 50.000), bukan dari input `harga`.
            ->assertJsonPath('data.harga', 250000);

        $this->assertDatabaseHas('penyewaans', [
            'penyewa_id' => $penyewa->id,
            'motor_id' => $motor->id,
            'harga' => 250000,
            'status' => Penyewaan::STATUS_PENDING,
        ]);
    }

    public function test_booking_tanggal_tidak_valid_ditolak(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $motor = $this->motor($pemilik);

        $this->sebagai($this->pengguna(User::ROLE_PENYEWA, 's@t.test'))
            ->postJson('/api/bookings', [
                'motor_id' => $motor->id,
                'tanggal_mulai' => now()->addDays(6)->toDateString(),
                'tanggal_selesai' => now()->addDays(2)->toDateString(),
                'tipe_durasi' => Penyewaan::TIPE_HARIAN,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tanggal_selesai');

        $this->assertDatabaseCount('penyewaans', 0);
    }

    public function test_booking_durasi_tidak_sesuai_diterima_dengan_harga_server_side(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik);

        // 5 hari dengan tipe mingguan: harga = 1 minggu x tarif_mingguan (300000)
        $res = $this->sebagai($penyewa)
            ->postJson('/api/bookings', $this->payloadBooking($motor, [
                'tipe_durasi' => Penyewaan::TIPE_MINGGUAN,
            ]));

        $res->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Penyewaan::STATUS_PENDING)
            ->assertJsonPath('data.harga', 300000)
            ->assertJsonPath('data.tipe_durasi', Penyewaan::TIPE_MINGGUAN);

        $this->assertDatabaseHas('penyewaans', [
            'penyewa_id' => $penyewa->id,
            'motor_id' => $motor->id,
            'harga' => 300000,
            'tipe_durasi' => Penyewaan::TIPE_MINGGUAN,
            'status' => Penyewaan::STATUS_PENDING,
        ]);
    }

    public function test_booking_bentrok_ditolak(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik);

        $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_PENDING);

        $this->sebagai($penyewa)
            ->postJson('/api/bookings', $this->payloadBooking($motor))
            ->assertStatus(422)
            ->assertJsonValidationErrors('motor_id');
    }

    public function test_pembayaran_sebelum_konfirmasi_admin_ditolak(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik);
        $penyewaan = $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_PENDING);

        $res = $this->sebagai($penyewa)
            ->postJson('/api/payments', [
                'penyewaan_id' => $penyewaan->id,
                'metode_pembayaran' => Transaksi::METODE_TRANSFER,
            ]);

        $res->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('belum dikonfirmasi', $res->json('message'));
        $this->assertDatabaseCount('transaksis', 0);
    }

    public function test_pembayaran_setelah_konfirmasi_admin_berhasil(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik);
        $penyewaan = $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_DIKONFIRMASI);

        $res = $this->sebagai($penyewa)
            ->postJson('/api/payments', [
                'penyewaan_id' => $penyewaan->id,
                'metode_pembayaran' => Transaksi::METODE_TRANSFER,
                'jumlah' => 1, // harus diabaikan
            ]);

        $res->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.jumlah', 250000)
            ->assertJsonPath('data.status', Transaksi::STATUS_PENDING);

        $this->assertDatabaseHas('transaksis', [
            'penyewaan_id' => $penyewaan->id,
            'jumlah' => 250000,
            'status' => Transaksi::STATUS_PENDING,
        ]);
    }

    public function test_riwayat_booking_hanya_milik_pengguna_login(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewaA = $this->pengguna(User::ROLE_PENYEWA, 'a@t.test');
        $penyewaB = $this->pengguna(User::ROLE_PENYEWA, 'b@t.test');

        $motorA = $this->motor($pemilik, 'B 1111 AA');
        $motorB = $this->motor($pemilik, 'B 2222 BB');

        $milikA = $this->penyewaan($penyewaA, $motorA);
        $milikB = $this->penyewaan($penyewaB, $motorB);

        $res = $this->sebagai($penyewaA)->getJson('/api/bookings/history');

        $res->assertOk()->assertJsonPath('success', true)->assertJsonCount(1, 'data');
        $this->assertSame($milikA->id, $res->json('data.0.id'));
        $this->assertNotContains($milikB->id, collect($res->json('data'))->pluck('id')->all());
    }

    public function test_role_admin_tidak_bisa_akses_endpoint_penyewa(): void
    {
        $admin = $this->pengguna(User::ROLE_ADMIN, 'a@t.test');

        $this->sebagai($admin)
            ->getJson('/api/bookings/history')
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->sebagai($admin)
            ->getJson('/api/motors')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_booking_motor_nonaktif_ditolak_lewat_api(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');
        $motor = $this->motor($pemilik, 'B 9401 AA', Motor::STATUS_TERSEDIA);
        $motor->update(['is_aktif' => false]);

        $res = $this->sebagai($penyewa)
            ->postJson('/api/bookings', $this->payloadBooking($motor));

        $res->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseCount('penyewaans', 0);
    }
}
