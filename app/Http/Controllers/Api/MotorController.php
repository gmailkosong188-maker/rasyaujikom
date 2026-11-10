<?php

namespace App\Http\Controllers\Api;

use App\Models\Motor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Daftar motor yang dapat disewa.
 *
 * Sama dengan halaman penyewa: hanya motor berstatus `tersedia` dan
 * sudah memiliki tarif rental.
 */
class MotorController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Motor::class);

        $cari = trim((string) $request->query('cari'));
        $cc = $request->query('tipe_cc');

        $motor = Motor::query()
            ->tersedia()
            ->whereHas('tarifRental')
            ->with(['pemilik', 'tarifRental'])
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('merk', 'like', "%{$cari}%")
                        ->orWhere('no_plat', 'like', "%{$cari}%");
                });
            })
            ->when(
                in_array((string) $cc, Motor::TIPE_CC, true),
                fn ($q) => $q->where('tipe_cc', (string) $cc)
            )
            ->latest()
            ->get();

        return $this->sukses(
            $motor->map(fn (Motor $m) => $this->dataMotor($m))->values(),
            'Daftar motor tersedia berhasil diambil.'
        );
    }
}
