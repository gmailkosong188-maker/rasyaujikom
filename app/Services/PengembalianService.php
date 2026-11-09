<?php

namespace App\Services;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Alur pengembalian motor (spesifikasi §14).
 *
 *   berjalan --(penyewa ajukan)--> ditandai --> (admin konfirmasi) --> selesai
 *                                                                     motor tersedia
 *                                                                     bagi hasil tercatat
 *
 * Penanda pengajuan memakai `transaksis.status = dikembalikan` — nilai enum
 * yang memang tersedia di spesifikasi dan belum dipakai alur lain. Dengan
 * cara ini tidak ada kolom/tabel baru dan data nominal pembayaran tetap utuh.
 */
class PengembalianService
{
    public function __construct(private readonly BagiHasilService $bagiHasil) {}
    /**
     * Penyewa menandai bahwa motor sudah dikembalikan (pengajuan).
     *
     * Hanya boleh untuk penyewaan berstatus `berjalan`, sudah lunas,
     * dan belum pernah diajukan sebelumnya.
     */
    public function ajukan(Penyewaan $penyewaan): Transaksi
    {
        if ($penyewaan->status !== Penyewaan::STATUS_BERJALAN) {
            throw new InvalidArgumentException(
                'Hanya penyewaan berstatus "berjalan" yang dapat diajukan pengembalian.'
            );
        }

        $lunas = $penyewaan->transaksis()->where('status', Transaksi::STATUS_DIBAYAR)->first();

        if (! $lunas) {
            throw new InvalidArgumentException(
                'Pengembalian hanya dapat diajukan setelah pembayaran diverifikasi lunas.'
            );
        }

        if ($this->sudahDiajukan($penyewaan)) {
            throw new InvalidArgumentException('Pengembalian untuk penyewaan ini sudah diajukan.');
        }

        return DB::transaction(function () use ($lunas) {
            $lunas->update(['status' => Transaksi::STATUS_DIKEMBALIKAN]);

            return $lunas;
        });
    }

    /**
     * Admin mengonfirmasi pengembalian setelah memeriksa motor.
     *
     * Penyewaan -> selesai, motor -> tersedia.
     */
    public function konfirmasi(Penyewaan $penyewaan): Penyewaan
    {
        if ($penyewaan->status !== Penyewaan::STATUS_BERJALAN) {
            throw new InvalidArgumentException(
                'Hanya penyewaan berstatus "berjalan" yang dapat dikonfirmasi pengembaliannya.'
            );
        }

        if (! $this->sudahDiajukan($penyewaan)) {
            throw new InvalidArgumentException(
                'Penyewa belum mengajukan pengembalian untuk penyewaan ini.'
            );
        }

        return DB::transaction(function () use ($penyewaan) {
            $penyewaan->update(['status' => Penyewaan::STATUS_SELESAI]);

            if ($penyewaan->motor && $penyewaan->motor->status === Motor::STATUS_DISEWA) {
                $penyewaan->motor->update(['status' => Motor::STATUS_TERSEDIA]);
            }

            // Spesifikasi §14: setelah penyewaan selesai -> sistem mencatat
            // pendapatan & mencatat bagi hasil. Settlement ganda dicegah oleh
            // unique index + guard di BagiHasilService::catat().
            $this->catatBagiHasil($penyewaan);

            return $penyewaan;
        });
    }

    /**
     * Catat bagi hasil bila belum ada. Kegagalan settlement tidak boleh
     * membatalkan pengembalian yang sudah sah — dicatat sebagai peringatan
     * dan admin tetap dapat mencatatnya manual dari menu Bagi Hasil.
     */
    private function catatBagiHasil(Penyewaan $penyewaan): void
    {
        if ($penyewaan->bagiHasil()->exists()) {
            return;
        }

        try {
            $this->bagiHasil->catat($penyewaan);
        } catch (InvalidArgumentException $e) {
            // Settlement gagal tidak membatalkan pengembalian yang sudah sah.
            // Admin masih bisa mencatat manual dari menu Bagi Hasil.
            report($e);
        }
    }

    /**
     * Penyewa sudah mengajukan pengembalian?
     */
    public function sudahDiajukan(Penyewaan $penyewaan): bool
    {
        return $penyewaan->transaksis()
            ->where('status', Transaksi::STATUS_DIKEMBALIKAN)
            ->exists();
    }
}
