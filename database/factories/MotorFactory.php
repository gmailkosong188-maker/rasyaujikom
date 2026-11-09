<?php

namespace Database\Factories;

use App\Models\Motor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Motor>
 */
class MotorFactory extends Factory
{
    protected $model = Motor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pemilik_id' => User::factory()->pemilik(),
            'merk' => fake()->randomElement(['Honda Beat', 'Yamaha Mio', 'Honda Vario', 'Yamaha NMAX', 'Suzuki Nex']),
            'tipe_cc' => fake()->randomElement(Motor::TIPE_CC),
            'no_plat' => strtoupper(fake()->bothify('B #### ???')),
            'status' => Motor::STATUS_PENDING,
            'photo' => null,
            'dokumen_kepemilikan' => null,
        ];
    }

    public function tersedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Motor::STATUS_TERSEDIA,
        ]);
    }

    public function disewa(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Motor::STATUS_DISEWA,
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Motor::STATUS_DITOLAK,
        ]);
    }

    public function perawatan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Motor::STATUS_PERAWATAN,
        ]);
    }
}
