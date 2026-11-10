<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Transaksi\StoreTransaksiRequest;
use App\Models\Penyewaan;
use App\Services\TransaksiService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

/**
 * Pembayaran sisi penyewa.
 *
 * Nominal tetap diambil dari penyewaan (bukan input), dan status
 * penyewaan wajib `dikonfirmasi` — aturan yang sama dengan website
 * ditegakkan oleh StoreTransaksiRequest + TransaksiService.
 */
class PaymentController extends ApiController
{
    public function store(StoreTransaksiRequest $request, TransaksiService $service): JsonResponse
    {
        $data = $request->validated();
        $penyewaan = Penyewaan::findOrFail($data['penyewaan_id']);

        try {
            $transaksi = $service->buat(
                $request->user(),
                $penyewaan,
                $data['metode_pembayaran'],
            );
        } catch (InvalidArgumentException $e) {
            return $this->gagal($e->getMessage(), ['payment' => [$e->getMessage()]]);
        }

        return $this->sukses(
            $this->dataTransaksi($transaksi),
            'Pembayaran berhasil dicatat dan menunggu verifikasi admin.',
            201
        );
    }
}
