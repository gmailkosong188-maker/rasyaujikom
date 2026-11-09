<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifRental extends Model
{
    use HasFactory;

    protected $fillable = [
        'motor_id',
        'tarif_harian',
        'tarif_mingguan',
        'tarif_bulanan',
    ];

    protected function casts(): array
    {
        return [
            'tarif_harian' => 'decimal:2',
            'tarif_mingguan' => 'decimal:2',
            'tarif_bulanan' => 'decimal:2',
        ];
    }

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motor::class);
    }

    /**
     * Ambil tarif sesuai tipe durasi penyewaan.
     */
    public function tarifUntuk(string $tipeDurasi): float
    {
        return (float) match ($tipeDurasi) {
            Penyewaan::TIPE_HARIAN => $this->tarif_harian,
            Penyewaan::TIPE_MINGGUAN => $this->tarif_mingguan,
            Penyewaan::TIPE_BULANAN => $this->tarif_bulanan,
            default => 0,
        };
    }
}
