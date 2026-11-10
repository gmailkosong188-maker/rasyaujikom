<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Monitoring penyewa oleh admin.
 *
 * Total pembayaran memakai definisi pendapatan sah yang sudah berlaku
 * (transaksi `dibayar` + `dikembalikan`), dijumlahkan dari kolom yang
 * tersimpan — tidak ada perhitungan baru.
 */
class RenterController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $cari = trim((string) $request->query('cari'));

        $renters = User::query()
            ->where('role', User::ROLE_PENYEWA)
            ->withCount('penyewaans')
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('nama', 'like', "%{$cari}%")
                        ->orWhere('email', 'like', "%{$cari}%");
                });
            })
            ->orderBy('nama')
            ->get();

        $pembayaran = DB::table('transaksis')
            ->join('penyewaans', 'penyewaans.id', '=', 'transaksis.penyewaan_id')
            ->whereIn('penyewaans.penyewa_id', $renters->pluck('id'))
            ->whereIn('transaksis.status', [Transaksi::STATUS_DIBAYAR, Transaksi::STATUS_DIKEMBALIKAN])
            ->groupBy('penyewaans.penyewa_id')
            ->select('penyewaans.penyewa_id', DB::raw('SUM(transaksis.jumlah) as total'))
            ->pluck('total', 'penyewa_id');

        $data = $renters->map(fn (User $renter) => array_merge($this->dataUser($renter), [
            'jumlah_penyewaan' => (int) $renter->penyewaans_count,
            'total_pembayaran' => (float) ($pembayaran[$renter->id] ?? 0),
        ]))->values();

        return $this->sukses($data, 'Daftar penyewa berhasil diambil.');
    }

    public function show(User $user): JsonResponse
    {
        if (! $user->isPenyewa()) {
            return $this->gagal('Data penyewa tidak ditemukan.', [], 404);
        }

        $penyewaan = $user->penyewaans()
            ->with(['motor.pemilik', 'motor.tarifRental', 'transaksis'])
            ->latest('tanggal_mulai')
            ->get();

        $totalPembayaran = (float) Transaksi::query()
            ->whereHas('penyewaan', fn ($q) => $q->where('penyewa_id', $user->id))
            ->whereIn('status', [Transaksi::STATUS_DIBAYAR, Transaksi::STATUS_DIKEMBALIKAN])
            ->sum('jumlah');

        $pembayaranPending = (float) Transaksi::query()
            ->whereHas('penyewaan', fn ($q) => $q->where('penyewa_id', $user->id))
            ->where('status', Transaksi::STATUS_PENDING)
            ->sum('jumlah');

        return $this->sukses(array_merge($this->dataUser($user), [
            'ringkasan' => [
                'jumlah_penyewaan' => $penyewaan->count(),
                'penyewaan_aktif' => $penyewaan->whereIn('status', Penyewaan::STATUS_AKTIF)->count(),
                'total_pembayaran' => $totalPembayaran,
                'pembayaran_pending' => $pembayaranPending,
            ],
            'penyewaan' => $penyewaan
                ->map(fn (Penyewaan $item) => $this->dataPenyewaan($item))
                ->values(),
        ]), 'Detail penyewa berhasil diambil.');
    }
}
