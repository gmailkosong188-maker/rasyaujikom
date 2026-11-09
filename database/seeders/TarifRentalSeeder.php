<?php

namespace Database\Seeders;

use App\Models\Motor;
use App\Models\TarifRental;
use Illuminate\Database\Seeder;

/**
 * Tarif rental untuk setiap motor.
 *
 * Hanya motor yang sudah diverifikasi (tersedia/disewa/perawatan)
 * yang diberi tarif; motor `pending`/`ditolak` sengaja belum punya
 * tarif karena belum boleh disewakan (spesifikasi §3 dan §23).
 */
class TarifRentalSeeder extends Seeder
{
    /**
     * no_plat => [harian, mingguan, bulanan]
     */
    public const TARIF = [
        'B 1234 XY' => [50000, 300000, 1000000],
        'B 2345 YZ' => [45000, 270000, 900000],
        'B 4567 AB' => [40000, 240000, 800000],
        'B 5678 BC' => [100000, 600000, 2000000],
        'B 6789 CD' => [55000, 330000, 1100000],
    ];

    public function run(): void
    {
        $jumlah = 0;

        foreach (self::TARIF as $plat => [$harian, $mingguan, $bulanan]) {
            $motor = Motor::where('no_plat', $plat)->first();

            if (! $motor) {
                $this->command?->warn("TarifRentalSeeder: motor {$plat} tidak ditemukan, dilewati.");

                continue;
            }

            // motor_id unique, jadi updateOrCreate aman dijalankan ulang.
            TarifRental::updateOrCreate(
                ['motor_id' => $motor->id],
                [
                    'tarif_harian' => $harian,
                    'tarif_mingguan' => $mingguan,
                    'tarif_bulanan' => $bulanan,
                ]
            );

            $jumlah++;
        }

        $this->command?->info("TarifRentalSeeder: {$jumlah} tarif dibuat.");
    }
}
