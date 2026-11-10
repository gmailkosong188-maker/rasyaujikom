<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'dicairkan_oleh',
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

    /**
     * Admin yang menyerahkan uang kepada pemilik.
     */
    public function pencairan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicairkan_oleh');
    }

    /**
     * Dana sudah diserahkan ke pemilik.
     */
    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    /**
     * Dana belum diserahkan -> menunggu pencairan.
     */
    public function isMenungguPencairan(): bool
    {
        return $this->settled_at === null;
    }

    /**
     * Hanya catatan yang dananya belum diserahkan (hanya untuk admin).
     */
    public function scopeMenungguPencairan(Builder $query): Builder
    {
        return $query->whereNull('settled_at');
    }

    /**
     * Catatan yang dananya sudah diserahkan admin.
     */
    public function scopeSudahDicairkan(Builder $query): Builder
    {
        return $query->whereNotNull('settled_at');
    }
}
