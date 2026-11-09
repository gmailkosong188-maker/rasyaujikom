<?php

namespace Database\Seeders;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\BagiHasilService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Satu skenario penyewaan lengkap (end-to-end) supaya dashboard,
 * laporan, pembayaran, dan bagi hasil langsung ada isinya.
 *
 * Alur: penyewaan selesai -> motor kembali tersedia -> transaksi
 * dibayar -> bagi hasil tercatat.
 */
class PenyewaanSeeder extends Seeder
{
    public function run(): void
    {
        $penyewa = User::where('email', 'penyewa@rentalmotor.test')->first();
        $motor = Motor::where('no_plat', 'B 2345 YZ')->with('tarifRental')->first();

        if (! $penyewa || ! $motor || ! $motor->tarifRental) {
            $this->command?->warn('PenyewaanSeeder: user/motor/tarif prasyarat tidak ditemukan, dilewati.');

            return;
        }

        // Idempotent: jangan buat ulang bila skenario contoh sudah ada.
        $sudahAda = Penyewaan::where('penyewa_id', $penyewa->id)
            ->where('motor_id', $motor->id)
            ->where('status', Penyewaan::STATUS_SELESAI)
            ->exists();

        if ($sudahAda) {
            $this->command?->line('PenyewaanSeeder: skenario contoh sudah ada, dilewati.');

            return;
        }

        DB::transaction(function () use ($penyewa, $motor) {
            $mulai = Carbon::today()->subDays(10);
            $selesai = Carbon::today()->subDays(6);

            $harga = Penyewaan::hitungHarga($motor->tarifRental, Penyewaan::TIPE_HARIAN, $mulai, $selesai);

            $penyewaan = Penyewaan::create([
                'penyewa_id' => $penyewa->id,
                'motor_id' => $motor->id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'tipe_durasi' => Penyewaan::TIPE_HARIAN,
                'harga' => $harga,
                'status' => Penyewaan::STATUS_SELESAI,
            ]);

            Transaksi::create([
                'penyewaan_id' => $penyewaan->id,
                'jumlah' => $harga,
                'metode_pembayaran' => Transaksi::METODE_TRANSFER,
                'status' => Transaksi::STATUS_DIBAYAR,
                'tanggal' => $mulai->toDateString(),
            ]);

            // Settlement memakai service terpusat (persentase dari config).
            app(BagiHasilService::class)->catat($penyewaan);

            // Motor sudah dikembalikan -> kembali tersedia.
            $motor->update(['status' => Motor::STATUS_TERSEDIA]);
        });

        $this->command?->info('PenyewaanSeeder: 1 penyewaan selesai + pembayaran + bagi hasil dibuat.');
    }
}
