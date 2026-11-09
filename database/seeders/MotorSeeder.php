<?php

namespace Database\Seeders;

use App\Models\Motor;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Motor contoh milik pemilik yang dibuat UserSeeder.
 *
 * Sengaja mencakup berbagai status agar alur verifikasi admin
 * dan daftar motor tersedia bisa langsung diuji.
 */
class MotorSeeder extends Seeder
{
    /**
     * [email pemilik, merk, tipe_cc, no_plat, status]
     */
    public const MOTOR = [
        ['pemilik@rentalmotor.test', 'Honda Beat', '125', 'B 1234 XY', Motor::STATUS_TERSEDIA],
        ['pemilik@rentalmotor.test', 'Yamaha Mio', '125', 'B 2345 YZ', Motor::STATUS_TERSEDIA],
        ['pemilik@rentalmotor.test', 'Honda Vario', '150', 'B 3456 ZA', Motor::STATUS_PENDING],
        ['pemilik@rentalmotor.test', 'Honda Supra', '100', 'B 4567 AB', Motor::STATUS_PERAWATAN],
        ['pemilik2@rentalmotor.test', 'Yamaha NMAX', '150', 'B 5678 BC', Motor::STATUS_TERSEDIA],
        ['pemilik2@rentalmotor.test', 'Suzuki Nex', '125', 'B 6789 CD', Motor::STATUS_DISEWA],
    ];

    public function run(): void
    {
        foreach (self::MOTOR as [$email, $merk, $cc, $plat, $status]) {
            $pemilik = User::where('email', $email)->first();

            if (! $pemilik) {
                $this->command?->warn("MotorSeeder: pemilik {$email} tidak ditemukan, dilewati.");

                continue;
            }

            Motor::updateOrCreate(
                ['no_plat' => $plat],
                [
                    'pemilik_id' => $pemilik->id,
                    'merk' => $merk,
                    'tipe_cc' => $cc,
                    'status' => $status,
                ]
            );
        }

        $this->command?->info('MotorSeeder: '.count(self::MOTOR).' motor dibuat.');
    }
}
