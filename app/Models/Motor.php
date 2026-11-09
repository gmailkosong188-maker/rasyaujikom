<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Motor extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_TERSEDIA = 'tersedia';

    public const STATUS_DISEWA = 'disewa';

    public const STATUS_PERAWATAN = 'perawatan';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_TERSEDIA,
        self::STATUS_DISEWA,
        self::STATUS_PERAWATAN,
        self::STATUS_DITOLAK,
    ];

    public const TIPE_CC = ['100', '125', '150'];

    /**
     * Transisi status yang diizinkan.
     *
     * - pending   -> tersedia (diverifikasi) / ditolak
     * - tersedia  -> disewa / perawatan / ditolak
     * - disewa    -> tersedia (setelah pengembalian dikonfirmasi) / perawatan
     * - perawatan -> tersedia / ditolak
     * - ditolak   -> pending (pemilik memperbaiki data lalu ajukan ulang)
     */
    public const TRANSISI = [
        self::STATUS_PENDING => [self::STATUS_TERSEDIA, self::STATUS_DITOLAK],
        self::STATUS_TERSEDIA => [self::STATUS_DISEWA, self::STATUS_PERAWATAN, self::STATUS_DITOLAK],
        self::STATUS_DISEWA => [self::STATUS_TERSEDIA, self::STATUS_PERAWATAN],
        self::STATUS_PERAWATAN => [self::STATUS_TERSEDIA, self::STATUS_DITOLAK],
        self::STATUS_DITOLAK => [self::STATUS_PENDING],
    ];

    /**
     * Status yang boleh dipilih pemilik saat mengajukan motor.
     * Pemilik tidak boleh langsung menetapkan `tersedia`/`disewa`.
     */
    public const STATUS_PILIHAN_PEMILIK = [self::STATUS_PENDING, self::STATUS_PERAWATAN];

    /**
     * Status yang terlihat oleh penyewa.
     */
    public const STATUS_PUBLIK = [self::STATUS_TERSEDIA];

    protected $fillable = [
        'pemilik_id',
        'merk',
        'tipe_cc',
        'no_plat',
        'status',
        'photo',
        'dokumen_kepemilikan',
    ];

    protected function casts(): array
    {
        return [
            'tipe_cc' => 'string',
        ];
    }

    // -----------------------------------------------------------------
    // Relasi
    // -----------------------------------------------------------------

    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }

    public function tarifRental(): HasOne
    {
        return $this->hasOne(TarifRental::class);
    }

    public function penyewaans(): HasMany
    {
        return $this->hasMany(Penyewaan::class);
    }

    // -----------------------------------------------------------------
    // Scope
    // -----------------------------------------------------------------

    /**
     * Hanya motor yang boleh disewa (sudah diverifikasi & tidak dipakai).
     */
    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_TERSEDIA);
    }

    // -----------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------

    public function isTersedia(): bool
    {
        return $this->status === self::STATUS_TERSEDIA;
    }

    public function isDisewa(): bool
    {
        return $this->status === self::STATUS_DISEWA;
    }

    /**
     * Sudah diverifikasi admin dan tarifnya sudah ditentukan.
     * Memakai relationLoaded() agar tidak memicu query tambahan
     * pada listing motor (hindari N+1).
     */
    public function siapDisewa(): bool
    {
        return $this->isTersedia()
            && $this->relationLoaded('tarifRental')
            && $this->tarifRental !== null;
    }

    /**
     * Apakah status boleh diubah ke $tujuan menurut aturan transisi.
     */
    public function bolehBerubahKe(string $tujuan): bool
    {
        if ($tujuan === $this->status) {
            return false;
        }

        return in_array($tujuan, self::TRANSISI[$this->status] ?? [], true);
    }

    /**
     * Pemilik masih boleh mengubah data motor.
     * Motor yang sedang disewa tidak boleh diedit agar tidak bentrok
     * dengan transaksi yang berjalan.
     */
    public function bolehDieditPemilik(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_TERSEDIA,
            self::STATUS_PERAWATAN,
            self::STATUS_DITOLAK,
        ], true);
    }

    /**
     * Pemilik boleh menghapus motor bila belum punya riwayat penyewaan.
     */
    public function bolehDihapusPemilik(): bool
    {
        return ! $this->penyewaans()->exists();
    }

    public function urlPhoto(): ?string
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    public function urlDokumen(): ?string
    {
        return $this->dokumen_kepemilikan ? asset('storage/'.$this->dokumen_kepemilikan) : null;
    }
}
