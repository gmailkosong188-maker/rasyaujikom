<?php

namespace App\Services;

use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Logika bisnis pembayaran.
 *
 * Nominal pembayaran selalu diambil dari Penyewaan::harga — tidak pernah
 * dari input user (spesifikasi §13 & §22).
 */
class TransaksiService
{
    /**
     * Buat transaksi pembayaran untuk sebuah penyewaan.
     */
    public function buat(User $penyewa, Penyewaan $penyewaan, string $metode): Transaksi
    {
        if ($penyewaan->penyewa_id !== $penyewa->id) {
            throw new InvalidArgumentException('Penyewaan ini bukan milik Anda.');
        }

        if (in_array($penyewaan->status, [Penyewaan::STATUS_DIBATALKAN, Penyewaan::STATUS_SELESAI], true)) {
            throw new InvalidArgumentException("Penyewaan berstatus \"{$penyewaan->status}\" tidak dapat dibayar.");
        }

        $jumlah = (float) $penyewaan->harga;

        if ($jumlah <= 0) {
            throw new InvalidArgumentException('Penyewaan tidak memiliki harga yang valid.');
        }

        return DB::transaction(function () use ($penyewaan, $metode, $jumlah) {
            $sudahAda = $penyewaan->transaksis()
                ->whereIn('status', [Transaksi::STATUS_PENDING, Transaksi::STATUS_DIBAYAR])
                ->lockForUpdate()
                ->exists();

            if ($sudahAda) {
                throw new InvalidArgumentException('Penyewaan ini sudah memiliki pembayaran.');
            }

            return Transaksi::create([
                'penyewaan_id' => $penyewaan->id,
                // Nominal dari database, bukan dari request.
                'jumlah' => $jumlah,
                'metode_pembayaran' => $metode,
                'status' => Transaksi::STATUS_PENDING,
                'tanggal' => now()->toDateString(),
            ]);
        });
    }

    /**
     * Admin memverifikasi/mengubah status pembayaran.
     *
     * Saat ditandai `dibayar`, penyewaan ikut dikonfirmasi agar
     * status penyewaan dan motor tetap konsisten dengan alur §13.
     */
    public function ubahStatus(Transaksi $transaksi, string $tujuan): Transaksi
    {
        if (! in_array($tujuan, Transaksi::STATUSES, true)) {
            throw new InvalidArgumentException('Status pembayaran tidak valid.');
        }

        if ($tujuan === $transaksi->status) {
            throw new InvalidArgumentException('Status pembayaran sudah bernilai sama.');
        }

        return DB::transaction(function () use ($transaksi, $tujuan) {
            $transaksi->update(['status' => $tujuan]);

            $penyewaan = $transaksi->penyewaan;

            if ($tujuan === Transaksi::STATUS_DIBAYAR && $penyewaan) {
                // Pembayaran diterima -> penyewaan dikonfirmasi (bila masih pending).
                if ($penyewaan->status === Penyewaan::STATUS_PENDING) {
                    $penyewaan->update(['status' => Penyewaan::STATUS_DIKONFIRMASI]);

                    if ($penyewaan->motor && $penyewaan->motor->status === \App\Models\Motor::STATUS_TERSEDIA) {
                        $penyewaan->motor->update(['status' => \App\Models\Motor::STATUS_DISEWA]);
                    }
                }
            }

            if ($tujuan === Transaksi::STATUS_DITOLAK && $penyewaan) {
                // Pembayaran ditolak -> penyewaan kembali pending agar bisa dibayar ulang.
                if ($penyewaan->status === Penyewaan::STATUS_DIKONFIRMASI) {
                    $penyewaan->update(['status' => Penyewaan::STATUS_PENDING]);

                    if ($penyewaan->motor && $penyewaan->motor->status === \App\Models\Motor::STATUS_DISEWA) {
                        $penyewaan->motor->update(['status' => \App\Models\Motor::STATUS_TERSEDIA]);
                    }
                }
            }

            return $transaksi;
        });
    }
}
