<?php

namespace Database\Factories;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Penyewaan>
 */
class PenyewaanFactory extends Factory
{
    protected $model = Penyewaan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = fake()->dateTimeBetween('-1 month', '+1 month');
        $selesai = (clone $mulai)->modify('+'.fake()->numberBetween(1, 5).' days');

        return [
            'penyewa_id' => User::factory()->penyewa(),
            'motor_id' => Motor::factory(),
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'tipe_durasi' => Penyewaan::TIPE_HARIAN,
            'harga' => 0,
            'status' => Penyewaan::STATUS_PENDING,
        ];
    }
}
