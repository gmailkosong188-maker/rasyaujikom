<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Penyewaan;
use App\Services\PenyewaanService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

/**
 * Konfirmasi booking oleh admin.
 *
 * Memakai PenyewaanService::ubahStatus() yang sama dengan website,
 * sehingga transisi status penyewaan & status motor tetap konsisten.
 */
class BookingController extends ApiController
{
    public function confirm(Penyewaan $penyewaan, PenyewaanService $service): JsonResponse
    {
        $this->authorize('proses', $penyewaan);

        try {
            $service->ubahStatus($penyewaan, Penyewaan::STATUS_DIKONFIRMASI);
        } catch (InvalidArgumentException $e) {
            return $this->gagal($e->getMessage(), ['status' => [$e->getMessage()]]);
        }

        return $this->sukses(
            $this->dataPenyewaan($penyewaan->fresh(['motor.tarifRental', 'transaksis'])),
            'Booking berhasil dikonfirmasi.'
        );
    }
}
