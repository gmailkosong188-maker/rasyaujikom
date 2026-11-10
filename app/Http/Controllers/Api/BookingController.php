<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Penyewaan\StorePenyewaanRequest;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Services\PenyewaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Booking sisi penyewa.
 *
 * - Pembuatan booking memakai StorePenyewaanRequest + PenyewaanService
 *   yang sama dengan website: validasi tanggal/durasi, cek bentrok, dan
 *   perhitungan harga tetap server-side.
 * - Riwayat selalu dibatasi ke penyewaan milik user yang login.
 */
class BookingController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Penyewaan::class);

        $status = $request->query('status');

        $penyewaan = $request->user()->penyewaans()
            ->with(['motor.tarifRental', 'transaksis'])
            ->when(
                in_array($status, Penyewaan::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->latest('tanggal_mulai')
            ->get();

        return $this->sukses(
            $penyewaan->map(fn (Penyewaan $p) => $this->dataPenyewaan($p))->values(),
            'Riwayat booking berhasil diambil.'
        );
    }

    public function store(StorePenyewaanRequest $request, PenyewaanService $service): JsonResponse
    {
        $data = $request->validated();
        $motor = Motor::with('tarifRental')->findOrFail($data['motor_id']);

        try {
            $penyewaan = $service->buat(
                $request->user(),
                $motor,
                $data['tanggal_mulai'],
                $data['tanggal_selesai'],
                $data['tipe_durasi'],
            );
        } catch (InvalidArgumentException $e) {
            return $this->gagal($e->getMessage(), ['booking' => [$e->getMessage()]]);
        }

        return $this->sukses(
            $this->dataPenyewaan($penyewaan->load(['motor.tarifRental', 'transaksis'])),
            'Booking berhasil dibuat dan menunggu konfirmasi admin.',
            201
        );
    }
}
