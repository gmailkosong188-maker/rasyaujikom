<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagiHasil extends Model
{
    use HasFactory;

    protected $fillable = [
        'penyewaan_id',
        'persentase_hasil_pemilik',
        'persentase_hasil_admin',
        'nominal_pemilik',
        'nominal_admin',
        'settled_at',
        'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'persentase_hasil_pemilik' => 'integer',
            'persentase_hasil_admin' => 'integer',
            'nominal_pemilik' => 'decimal:2',
            'nominal_admin' => 'decimal:2',
            'settled_at' => 'datetime',
            'tanggal' => 'date',
        ];
    }

    public function penyewaan(): BelongsTo
    {
        return $this->belongsTo(Penyewaan::class);
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }
}
