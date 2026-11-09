<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaksi extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DIBAYAR = 'dibayar';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DIKEMBALIKAN = 'dikembalikan';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_DIBAYAR,
        self::STATUS_DITOLAK,
        self::STATUS_DIKEMBALIKAN,
    ];

    public const METODE_CASH = 'cash';

    public const METODE_TRANSFER = 'transfer';

    public const METODE = [
        self::METODE_CASH,
        self::METODE_TRANSFER,
    ];

    protected $fillable = [
        'penyewaan_id',
        'jumlah',
        'metode_pembayaran',
        'status',
        'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function penyewaan(): BelongsTo
    {
        return $this->belongsTo(Penyewaan::class);
    }

    public function scopeLunas(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIBAYAR);
    }

    public function isDibayar(): bool
    {
        return $this->status === self::STATUS_DIBAYAR;
    }
}
