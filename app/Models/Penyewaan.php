<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Penyewaan extends Model
{
    use HasFactory;

    public const TIPE_HARIAN = 'harian';

    public const TIPE_MINGGUAN = 'mingguan';

    public const TIPE_BULANAN = 'bulanan';

    public const TIPE_DURASI = [
        self::TIPE_HARIAN,
        self::TIPE_MINGGUAN,
        self::TIPE_BULANAN,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_DIKONFIRMASI = 'dikonfirmasi';

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_DIKONFIRMASI,
        self::STATUS_BERJALAN,
        self::STATUS_SELESAI,
        self::STATUS_DIBATALKAN,
    ];

    /**
     * Status yang masih memakai/memblokir motor.
     */
    public const STATUS_AKTIF = [
        self::STATUS_PENDING,
        self::STATUS_DIKONFIRMASI,
        self::STATUS_BERJALAN,
    ];

    protected $fillable = [
        'penyewa_id',
        'motor_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'tipe_durasi',
        'harga',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'harga' => 'decimal:2',
        ];
    }

    // -----------------------------------------------------------------
    // Relasi
    // -----------------------------------------------------------------

    public function penyewa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penyewa_id');
    }

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motor::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function bagiHasil(): HasOne
    {
        return $this->hasOne(BagiHasil::class);
    }

    // -----------------------------------------------------------------
    // Scope
    // -----------------------------------------------------------------

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', self::STATUS_AKTIF);
    }

    /**
     * Penyewaan yang periodenya beririsan dengan rentang tanggal tertentu.
     *
     * Dua periode beririsan bila: mulai_A <= selesai_B DAN selesai_A >= mulai_B.
     * Hanya penyewaan aktif yang dianggap memblokir (selesai/dibatalkan bebas).
     */
    public function scopeBentrok(Builder $query, $mulai, $selesai): Builder
    {
        return $query->aktif()
            ->where('tanggal_mulai', '<=', $selesai)
            ->where('tanggal_selesai', '>=', $mulai);
    }

    /**
     * Transisi status penyewaan yang diizinkan.
     *
     * pending      -> dikonfirmasi / dibatalkan
     * dikonfirmasi -> berjalan / dibatalkan
     * berjalan     -> selesai
     * selesai      -> (final)
     * dibatalkan   -> (final)
     */
    public const TRANSISI = [
        self::STATUS_PENDING => [self::STATUS_DIKONFIRMASI, self::STATUS_DIBATALKAN],
        self::STATUS_DIKONFIRMASI => [self::STATUS_BERJALAN, self::STATUS_DIBATALKAN],
        self::STATUS_BERJALAN => [self::STATUS_SELESAI],
        self::STATUS_SELESAI => [],
        self::STATUS_DIBATALKAN => [],
    ];

    public function bolehBerubahKe(string $tujuan): bool
    {
        if ($tujuan === $this->status) {
            return false;
        }

        return in_array($tujuan, self::TRANSISI[$this->status] ?? [], true);
    }

    /**
     * Penyewaan masih boleh dibatalkan oleh penyewa.
     */
    public function bolehDibatalkan(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_DIKONFIRMASI], true);
    }

    // -----------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------

    /**
     * Pemilik motor, diturunkan dari motor_id (tanpa kolom tambahan).
     */
    public function pemilik(): ?User
    {
        return $this->motor?->pemilik;
    }

    /**
     * Alias agar tetap bisa dipakai sebagai atribut: $penyewaan->pemilik
     * tanpa menimpa relasi/atribut dasar Eloquent.
     */
    public function getPemilikMotorAttribute(): ?User
    {
        return $this->pemilik();
    }

    public function pemilikId(): ?int
    {
        return $this->motor?->pemilik_id;
    }

    /**
     * Jumlah periode sewa sesuai tipe durasi.
     *
     * Tanggal sewa dihitung inklusif: 2025-03-01 s/d 2025-03-01 = 1 hari.
     */
    public function jumlahPeriode(): int
    {
        return self::hitungPeriode($this->tipe_durasi, $this->tanggal_mulai, $this->tanggal_selesai);
    }

    /**
     * Hitung jumlah periode dari tipe durasi dan rentang tanggal.
     */
    public static function hitungPeriode(string $tipeDurasi, CarbonInterface $mulai, CarbonInterface $selesai): int
    {
        $hari = max(1, (int) $mulai->copy()->startOfDay()->diffInDays($selesai->copy()->startOfDay()) + 1);

        return match ($tipeDurasi) {
            self::TIPE_HARIAN => $hari,
            self::TIPE_MINGGUAN => max(1, (int) ceil($hari / 7)),
            self::TIPE_BULANAN => max(1, (int) ceil($hari / 30)),
            default => $hari,
        };
    }

    /**
     * Hitung harga penyewaan berdasarkan tarif motor yang berlaku saat ini.
     * Hasilnya harus disimpan ke kolom `harga` saat transaksi dibuat.
     */
    public static function hitungHarga(TarifRental $tarif, string $tipeDurasi, CarbonInterface $mulai, CarbonInterface $selesai): float
    {
        $periode = self::hitungPeriode($tipeDurasi, $mulai, $selesai);

        return round($periode * $tarif->tarifUntuk($tipeDurasi), 2);
    }

    public function isAktif(): bool
    {
        return in_array($this->status, self::STATUS_AKTIF, true);
    }

    public function isSelesai(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    /**
     * Total pembayaran yang sudah lunas (status dibayar).
     */
    public function totalDibayar(): float
    {
        return (float) $this->transaksis
            ->where('status', Transaksi::STATUS_DIBAYAR)
            ->sum('jumlah');
    }
}
