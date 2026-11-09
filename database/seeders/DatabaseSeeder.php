<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan penting: mengikuti dependensi foreign key.
     *
     * users -> motors -> tarif_rentals -> penyewaans -> transaksis/bagi_hasils
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MotorSeeder::class,
            TarifRentalSeeder::class,
            PenyewaanSeeder::class,
        ]);
    }
}
