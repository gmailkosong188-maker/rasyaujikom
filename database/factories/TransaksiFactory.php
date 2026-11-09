<?php

namespace Database\Factories;

use App\Models\Penyewaan;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaksi>
 */
class TransaksiFactory extends Factory
{
    protected $model = Transaksi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'penyewaan_id' => Penyewaan::factory(),
            'jumlah' => 0,
            'metode_pembayaran' => fake()->randomElement(Transaksi::METODE),
            'status' => Transaksi::STATUS_PENDING,
            'tanggal' => now()->toDateString(),
        ];
    }

    public function dibayar(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaksi::STATUS_DIBAYAR,
        ]);
    }
}
