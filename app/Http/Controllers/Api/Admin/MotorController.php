<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Motor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Verifikasi motor oleh admin.
 *
 * Mengikuti alur status website: hanya motor `pending` yang dapat
 * diverifikasi menjadi `tersedia` (Motor::bolehBerubahKe + MotorPolicy).
 */
class MotorController extends ApiController
{
    public function verify(Motor $motor): JsonResponse
    {
        $this->authorize('verifikasi', $motor);

        if ($motor->status !== Motor::STATUS_PENDING || ! $motor->bolehBerubahKe(Motor::STATUS_TERSEDIA)) {
            return $this->gagal(
                'Motor ini tidak dapat diverifikasi pada status saat ini.',
                ['status' => ['Hanya motor berstatus pending yang dapat diverifikasi.']]
            );
        }

        DB::transaction(fn () => $motor->update(['status' => Motor::STATUS_TERSEDIA]));

        $pesan = 'Motor berhasil diverifikasi dan berstatus tersedia.';
        if (! $motor->tarifRental()->exists()) {
            $pesan .= ' Tentukan tarif rental agar dapat disewa.';
        }

        return $this->sukses(
            $this->dataMotor($motor->load('tarifRental')),
            $pesan
        );
    }

    /**
     * Tolak motor: hanya motor pending yang dapat ditolak.
     */
    public function tolak(Request $request, Motor $motor): JsonResponse
    {
        $this->authorize('verifikasi', $motor);

        $validated = $request->validate([
            'alasan' => ['nullable', 'string', 'max:500'],
        ]);

        if ($motor->status !== Motor::STATUS_PENDING || ! $motor->bolehBerubahKe(Motor::STATUS_DITOLAK)) {
            return $this->gagal(
                'Motor ini tidak dapat ditolak pada status saat ini.',
                ['status' => ['Hanya motor berstatus pending yang dapat ditolak.']]
            );
        }

        DB::transaction(fn () => $motor->update(['status' => Motor::STATUS_DITOLAK]));

        $pesan = 'Motor berhasil ditolak.';
        if (! empty($validated['alasan'])) {
            $pesan .= ' Alasan: '.$validated['alasan'];
        }

        return $this->sukses(
            $this->dataMotor($motor->load('tarifRental')),
            $pesan
        );
    }
}
