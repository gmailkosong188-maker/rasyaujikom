<?php

namespace App\Services;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Logika bisnis penyewaan.
 *
 * Satu-satunya tempat yang:
 * - menghitung harga (memakai Penyewaan::hitungHarga() dari Tahap 7)
 * - membuat penyewaan + menyimpan snapshot harga
 * - mengubah status penyewaan sekaligus menjaga status motor tetap konsisten
 *
 * Semua operasi multi-tabel dibungkus DB::transaction (spesifikasi §15).
 */
class PenyewaanService
{
    /**
     * Buat penyewaan baru.
     *
     * Harga dihitung SERVER-SIDE dari tarif motor, bukan dari input user.
     */
    public function buat(User $penyewa, Motor $motor, string $tanggalMulai, string $tanggalSelesai, string $tipeDurasi): Penyewaan
    {
        $tarif = $motor->tarifRental;

        if (! $tarif instanceof TarifRental) {
            throw new InvalidArgumentException('Motor belum memiliki tarif rental.');
        }

        if (! $motor->isTersedia()) {
            throw new InvalidArgumentException('Motor tidak tersedia untuk disewa.');
        }

        $mulai = Carbon::parse($tanggalMulai)->startOfDay();
        $selesai = Carbon::parse($tanggalSelesai)->startOfDay();

        if ($selesai->lt($mulai)) {
            throw new InvalidArgumentException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        $harga = Penyewaan::hitungHarga($tarif, $tipeDurasi, $mulai, $selesai);

        return DB::transaction(function () use ($penyewa, $motor, $mulai, $selesai, $tipeDurasi, $harga) {
            // Cek ulang bentrok di dalam transaksi untuk menghindari race condition.
            $bentrok = Penyewaan::query()
                ->where('motor_id', $motor->id)
                ->bentrok($mulai->toDateString(), $selesai->toDateString())
                ->lockForUpdate()
                ->exists();

            if ($bentrok) {
                throw new InvalidArgumentException('Motor sudah disewa pada periode tersebut.');
            }

            return Penyewaan::create([
                'penyewa_id' => $penyewa->id,
                'motor_id' => $motor->id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'tipe_durasi' => $tipeDurasi,
                // Snapshot harga saat transaksi dibuat (spesifikasi §4.4).
                'harga' => $harga,
                'status' => Penyewaan::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Ubah status penyewaan dan sinkronkan status motor.
     */
    public function ubahStatus(Penyewaan $penyewaan, string $tujuan): Penyewaan
    {
        $penyewaan = Penyewaan::with('motor')->findOrFail($penyewaan->id);

        if (! $penyewaan->bolehBerubahKe($tujuan)) {
            throw new InvalidArgumentException(
                "Status tidak dapat diubah dari \"{$penyewaan->status}\" ke \"{$tujuan}\"."
            );
        }

        return DB::transaction(function () use ($penyewaan, $tujuan) {
            $motor = $penyewaan->motor;

            $penyewaan->update(['status' => $tujuan]);

            // Motor ikut disesuaikan agar konsisten dengan status penyewaan.
            if ($motor) {
                $this->sinkronkanMotor($motor, $tujuan);
            }

            return $penyewaan;
        });
    }

    /**
     * Sinkronisasi status motor berdasarkan status penyewaan.
     */
    protected function sinkronkanMotor(Motor $motor, string $statusPenyewaan): void
    {
        match ($statusPenyewaan) {
            // Sewa dikonfirmasi / sedang berjalan -> motor dipakai.
            Penyewaan::STATUS_DIKONFIRMASI,
            Penyewaan::STATUS_BERJALAN => $motor->status !== Motor::STATUS_DISEWA
                && $motor->bolehBerubahKe(Motor::STATUS_DISEWA)
                    ? $motor->update(['status' => Motor::STATUS_DISEWA])
                    : null,

            // Sewa selesai atau dibatalkan -> motor kembali tersedia.
            Penyewaan::STATUS_SELESAI,
            Penyewaan::STATUS_DIBATALKAN => in_array($motor->status, [Motor::STATUS_DISEWA, Motor::STATUS_TERSEDIA], true)
                ? $motor->update(['status' => Motor::STATUS_TERSEDIA])
                : null,

            default => null,
        };
    }
}
