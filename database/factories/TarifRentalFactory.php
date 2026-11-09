<?php

namespace Database\Factories;

use App\Models\Motor;
use App\Models\TarifRental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TarifRental>
 */
class TarifRentalFactory extends Factory
{
    protected $model = TarifRental::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $harian = fake()->randomElement([40000, 50000, 60000, 75000, 100000]);

        return [
            'motor_id' => Motor::factory(),
            'tarif_harian' => $harian,
            // Diskon wajar: mingguan ~6x harian, bulanan ~20x harian.
            'tarif_mingguan' => $harian * 6,
            'tarif_bulanan' => $harian * 20,
        ];
    }
}
