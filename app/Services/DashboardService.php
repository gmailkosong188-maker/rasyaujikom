<?php

namespace App\Services;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Query statistik dashboard.
 *
 * Semua angka berasal dari database (tidak ada data hardcoded).
 * Setiap method menerima batas akses secara eksplisit sehingga data
 * pemilik/penyewa tidak pernah tercampur dengan milik user lain.
 */
class DashboardService
{
    /**
     * Ringkasan untuk admin: data seluruh sistem.
     *
     * @return array<string, mixed>
     */
    public function admin(): array
    {
        // Satu query agregat untuk menghitung motor per status (hindari 5 query).
        $motorPerStatus = Motor::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalDibayar = (float) Transaksi::query()
            ->where('status', Transaksi::STATUS_DIBAYAR)
            ->sum('jumlah');

        return [
            'jumlahUser' => User::count(),
            'jumlahMotor' => (int) $motorPerStatus->sum(),
            'motorTersedia' => (int) ($motorPerStatus[Motor::STATUS_TERSEDIA] ?? 0),
            'motorDisewa' => (int) ($motorPerStatus[Motor::STATUS_DISEWA] ?? 0),
            'motorPending' => (int) ($motorPerStatus[Motor::STATUS_PENDING] ?? 0),
            'motorPerawatan' => (int) ($motorPerStatus[Motor::STATUS_PERAWATAN] ?? 0),
            'motorDitolak' => (int) ($motorPerStatus[Motor::STATUS_DITOLAK] ?? 0),
            'jumlahPenyewaan' => Penyewaan::count(),
            'penyewaanAktif' => Penyewaan::aktif()->count(),
            'totalPembayaran' => $totalDibayar,
            'motorTerbaru' => Motor::with(['pemilik', 'tarifRental'])
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Ringkasan untuk pemilik: HANYA motor & penyewaan miliknya.
     *
     * @return array<string, mixed>
     */
    public function pemilik(User $pemilik): array
    {
        $motorPerStatus = $pemilik->motors()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Pendapatan = nominal_pemilik dari bagi hasil yang sudah settled,
        // untuk penyewaan yang motornya milik user ini.
        $pendapatan = (float) DB::table('bagi_hasils')
            ->join('penyewaans', 'penyewaans.id', '=', 'bagi_hasils.penyewaan_id')
            ->join('motors', 'motors.id', '=', 'penyewaans.motor_id')
            ->where('motors.pemilik_id', $pemilik->id)
            ->whereNotNull('bagi_hasils.settled_at')
            ->sum('bagi_hasils.nominal_pemilik');

        $pendapatanTertunda = (float) DB::table('bagi_hasils')
            ->join('penyewaans', 'penyewaans.id', '=', 'bagi_hasils.penyewaan_id')
            ->join('motors', 'motors.id', '=', 'penyewaans.motor_id')
            ->where('motors.pemilik_id', $pemilik->id)
            ->whereNull('bagi_hasils.settled_at')
            ->sum('bagi_hasils.nominal_pemilik');

        $motorIds = $pemilik->motors()->pluck('id');

        return [
            'jumlahMotor' => (int) $motorPerStatus->sum(),
            'motorTersedia' => (int) ($motorPerStatus[Motor::STATUS_TERSEDIA] ?? 0),
            'motorDisewa' => (int) ($motorPerStatus[Motor::STATUS_DISEWA] ?? 0),
            'motorPending' => (int) ($motorPerStatus[Motor::STATUS_PENDING] ?? 0),
            'pendapatan' => $pendapatan,
            'pendapatanTertunda' => $pendapatanTertunda,
            'jumlahPenyewaan' => Penyewaan::whereIn('motor_id', $motorIds)->count(),
            // Motor miliknya saja, tidak pernah motor pemilik lain.
            'motorSaya' => $pemilik->motors()
                ->with('tarifRental')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Ringkasan untuk penyewa: HANYA penyewaan & pembayaran miliknya.
     *
     * @return array<string, mixed>
     */
    public function penyewa(User $penyewa): array
    {
        $penyewaanIds = $penyewa->penyewaans()->pluck('id');

        $totalPembayaran = (float) Transaksi::whereIn('penyewaan_id', $penyewaanIds)
            ->where('status', Transaksi::STATUS_DIBAYAR)
            ->sum('jumlah');

        $pembayaranPending = (float) Transaksi::whereIn('penyewaan_id', $penyewaanIds)
            ->where('status', Transaksi::STATUS_PENDING)
            ->sum('jumlah');

        return [
            // Motor tersedia = data publik (boleh dilihat siapa pun yang login).
            'motorTersedia' => Motor::tersedia()->whereHas('tarifRental')->count(),
            'penyewaanAktif' => $penyewa->penyewaans()->aktif()->count(),
            'totalPenyewaan' => $penyewa->penyewaans()->count(),
            'totalPembayaran' => $totalPembayaran,
            'pembayaranPending' => $pembayaranPending,
            'riwayat' => $penyewa->penyewaans()
                ->with(['motor.pemilik', 'transaksis'])
                ->latest('tanggal_mulai')
                ->limit(5)
                ->get(),
        ];
    }
}
