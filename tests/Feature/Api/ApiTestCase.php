<?php

namespace Tests\Feature\Api;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dasar pengujian REST API.
 *
 * Token dibuat sungguhan lewat Sanctum (bukan actingAs) agar jalur
 * autentikasi Bearer token benar-benar diuji.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function pengguna(string $role, ?string $email = null): User
    {
        return User::create([
            'nama' => ucfirst($role),
            'email' => $email ?? $role.'-'.uniqid().'@t.test',
            'no_tlpn' => '08123456789',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    /**
     * Rentang tanggal eksplisit untuk laporan, agar pengujian tidak
     * bergantung pada tanggal hari pertama bulan berjalan.
     */
    protected function rentang(): string
    {
        return '?dari='.now()->subDays(7)->toDateString().'&sampai='.now()->addDay()->toDateString();
    }

    /**
     * Pakai token Bearer milik user untuk request berikutnya.
     */
    protected function sebagai(User $user): static
    {
        // Guard Sanctum menyimpan user per instance; lupakan guard agar token
        // milik user berikutnya benar-benar dipakai pada request selanjutnya.
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    protected function motor(
        User $pemilik,
        string $plat = 'B 1234 XY',
        string $status = Motor::STATUS_TERSEDIA,
        bool $denganTarif = true
    ): Motor {
        $motor = Motor::create([
            'pemilik_id' => $pemilik->id,
            'merk' => 'Honda Beat',
            'tipe_cc' => '125',
            'no_plat' => $plat,
            'status' => $status,
            'photo' => 'foto.jpg',
            'dokumen_kepemilikan' => 'dokumen.pdf',
        ]);

        if ($denganTarif) {
            TarifRental::create([
                'motor_id' => $motor->id,
                'tarif_harian' => 50000,
                'tarif_mingguan' => 300000,
                'tarif_bulanan' => 1000000,
            ]);
        }

        return $motor->load('tarifRental');
    }

    protected function penyewaan(
        User $penyewa,
        Motor $motor,
        string $status = Penyewaan::STATUS_PENDING,
        int $hari = 5,
        float $harga = 250000
    ): Penyewaan {
        return Penyewaan::create([
            'penyewa_id' => $penyewa->id,
            'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(1 + $hari)->toDateString(),
            'tipe_durasi' => Penyewaan::TIPE_HARIAN,
            'harga' => $harga,
            'status' => $status,
        ]);
    }
}
