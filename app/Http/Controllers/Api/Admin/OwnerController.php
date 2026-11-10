<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Monitoring pemilik motor oleh admin.
 *
 * Angka pendapatan diambil dari `bagi_hasils.nominal_pemilik` yang sudah
 * tersimpan (definisi sama dengan DashboardService::pemilik) — tidak ada
 * rumus bagi hasil baru.
 */
class OwnerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $cari = trim((string) $request->query('cari'));

        $owners = User::query()
            ->where('role', User::ROLE_PEMILIK)
            ->withCount('motors')
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('nama', 'like', "%{$cari}%")
                        ->orWhere('email', 'like', "%{$cari}%");
                });
            })
            ->orderBy('nama')
            ->get();

        $ids = $owners->pluck('id');

        $jumlahPenyewaan = DB::table('penyewaans')
            ->join('motors', 'motors.id', '=', 'penyewaans.motor_id')
            ->whereIn('motors.pemilik_id', $ids)
            ->groupBy('motors.pemilik_id')
            ->select('motors.pemilik_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'pemilik_id');

        $pendapatan = DB::table('bagi_hasils')
            ->join('penyewaans', 'penyewaans.id', '=', 'bagi_hasils.penyewaan_id')
            ->join('motors', 'motors.id', '=', 'penyewaans.motor_id')
            ->whereIn('motors.pemilik_id', $ids)
            ->whereNotNull('bagi_hasils.settled_at')
            ->groupBy('motors.pemilik_id')
            ->select('motors.pemilik_id', DB::raw('SUM(bagi_hasils.nominal_pemilik) as total'))
            ->pluck('total', 'pemilik_id');

        $data = $owners->map(fn (User $owner) => array_merge($this->dataUser($owner), [
            'jumlah_motor' => (int) $owner->motors_count,
            'jumlah_penyewaan' => (int) ($jumlahPenyewaan[$owner->id] ?? 0),
            'total_pendapatan' => (float) ($pendapatan[$owner->id] ?? 0),
        ]))->values();

        return $this->sukses($data, 'Daftar pemilik berhasil diambil.');
    }

    public function show(User $user, DashboardService $dashboard): JsonResponse
    {
        if (! $user->isPemilik()) {
            return $this->gagal('Data pemilik tidak ditemukan.', [], 404);
        }

        $user->load(['motors.tarifRental']);
        $ringkasan = $dashboard->pemilik($user);

        $penyewaan = Penyewaan::query()
            ->whereIn('motor_id', $user->motors->pluck('id'))
            ->with(['penyewa', 'motor.tarifRental', 'transaksis'])
            ->latest('tanggal_mulai')
            ->get();

        return $this->sukses(array_merge($this->dataUser($user), [
            'ringkasan' => [
                'jumlah_motor' => $ringkasan['jumlahMotor'],
                'motor_tersedia' => $ringkasan['motorTersedia'],
                'motor_disewa' => $ringkasan['motorDisewa'],
                'motor_pending' => $ringkasan['motorPending'],
                'jumlah_penyewaan' => $ringkasan['jumlahPenyewaan'],
                'pendapatan' => $ringkasan['pendapatan'],
                'pendapatan_tertunda' => $ringkasan['pendapatanTertunda'],
            ],
            'motors' => $user->motors
                ->map(fn (Motor $motor) => $this->dataMotor($motor))
                ->values(),
            'penyewaan' => $penyewaan
                ->map(fn (Penyewaan $item) => $this->dataPenyewaan($item))
                ->values(),
        ]), 'Detail pemilik berhasil diambil.');
    }
}
