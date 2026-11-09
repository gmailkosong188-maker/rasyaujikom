<?php

namespace App\Services;

use App\Models\BagiHasil;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Satu-satunya tempat aturan pembagian pendapatan dihitung & disimpan.
 *
 * Persentase diambil dari config/bagi_hasil.php sehingga tidak
 * di-hardcode di banyak controller (lihat spesifikasi §4.6).
 */
class BagiHasilService
{
    public function persentasePemilik(): int
    {
        return (int) config('bagi_hasil.persentase_pemilik');
    }

    public function persentaseAdmin(): int
    {
        return (int) config('bagi_hasil.persentase_admin');
    }

    /**
     * Pastikan persentase pada konfigurasi valid (total 100, tidak negatif).
     */
    public function validasiPersentase(): void
    {
        $validator = Validator::make(
            [
                'persentase_hasil_pemilik' => $this->persentasePemilik(),
                'persentase_hasil_admin' => $this->persentaseAdmin(),
            ],
            [
                'persentase_hasil_pemilik' => ['required', 'integer', 'min:0', 'max:100'],
                'persentase_hasil_admin' => ['required', 'integer', 'min:0', 'max:100'],
            ]
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException('Konfigurasi bagi hasil tidak valid: '.$validator->errors()->first());
        }

        if ($this->persentasePemilik() + $this->persentaseAdmin() !== 100) {
            throw new InvalidArgumentException('Total persentase bagi hasil pemilik dan admin harus 100%.');
        }
    }

    /**
     * Hitung nominal pembagian dari total pendapatan.
     *
     * @return array{persentase_hasil_pemilik:int,persentase_hasil_admin:int,nominal_pemilik:float,nominal_admin:float}
     */
    public function hitung(float $totalPendapatan): array
    {
        $this->validasiPersentase();

        if ($totalPendapatan < 0) {
            throw new InvalidArgumentException('Total pendapatan tidak boleh negatif.');
        }

        $persentasePemilik = $this->persentasePemilik();
        $persentaseAdmin = $this->persentaseAdmin();

        $nominalPemilik = round($totalPendapatan * $persentasePemilik / 100, 2);
        // Sisa pembulatan dibebankan ke admin supaya total selalu pas.
        $nominalAdmin = round($totalPendapatan, 2) - $nominalPemilik;

        return [
            'persentase_hasil_pemilik' => $persentasePemilik,
            'persentase_hasil_admin' => $persentaseAdmin,
            'nominal_pemilik' => $nominalPemilik,
            'nominal_admin' => $nominalAdmin,
        ];
    }

    /**
     * Catat bagi hasil untuk satu penyewaan yang sudah selesai.
     * Idempotent: tidak akan membuat settlement ganda.
     */
    public function catat(Penyewaan $penyewaan, ?CarbonInterface $tanggal = null): BagiHasil
    {
        return DB::transaction(function () use ($penyewaan, $tanggal) {
            /** @var Penyewaan $penyewaan */
            $penyewaan = Penyewaan::query()->lockForUpdate()->findOrFail($penyewaan->id);

            if ($penyewaan->bagiHasil()->exists()) {
                throw new InvalidArgumentException('Bagi hasil untuk penyewaan ini sudah pernah dicatat.');
            }

            if (! $penyewaan->isSelesai()) {
                throw new InvalidArgumentException('Bagi hasil hanya dapat dicatat untuk penyewaan yang sudah selesai.');
            }

            // Pendapatan yang sah = transaksi lunas. `dikembalikan` ikut dihitung
            // karena dalam alur pengembalian (spesifikasi §14) transaksi yang
            // sudah dibayar ditandai `dikembalikan` ketika motor dipulangkan —
            // uangnya tetap diterima dan tetap harus dibagi hasilkan.
            $total = (float) $penyewaan->transaksis()
                ->whereIn('status', [Transaksi::STATUS_DIBAYAR, Transaksi::STATUS_DIKEMBALIKAN])
                ->sum('jumlah');

            if ($total <= 0) {
                throw new InvalidArgumentException('Belum ada pembayaran lunas untuk penyewaan ini.');
            }

            $hasil = $this->hitung($total);

            return BagiHasil::create([
                'penyewaan_id' => $penyewaan->id,
                'persentase_hasil_pemilik' => $hasil['persentase_hasil_pemilik'],
                'persentase_hasil_admin' => $hasil['persentase_hasil_admin'],
                'nominal_pemilik' => $hasil['nominal_pemilik'],
                'nominal_admin' => $hasil['nominal_admin'],
                'settled_at' => now(),
                'tanggal' => $tanggal ?? now()->toDateString(),
            ]);
        });
    }
}
