<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\ApiController;
use App\Services\LaporanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pendapatan pemilik.
 *
 * Memakai LaporanService::pemilik() yang sudah dipakai halaman laporan,
 * sehingga rumus bagi hasil tidak dihitung ulang di API dan data selalu
 * dibatasi pada motor milik user yang login.
 */
class RevenueController extends ApiController
{
    public function index(Request $request, LaporanService $laporan): JsonResponse
    {
        abort_unless($request->user()->isPemilik(), 403);

        [$dari, $sampai] = $laporan->rentang(
            $request->query('dari'),
            $request->query('sampai')
        );

        $data = $laporan->pemilik($request->user(), $dari, $sampai);

        return $this->sukses([
            'periode' => [
                'dari' => $dari->toDateString(),
                'sampai' => $sampai->toDateString(),
            ],
            'jumlah_motor' => $data['jumlahMotor'],
            'jumlah_penyewaan' => $data['jumlahPenyewaan'],
            'total_pendapatan' => $data['totalPendapatan'],
            'total_bagi_hasil_pemilik' => $data['totalBagiHasil'],
            'pendapatan_per_motor' => $data['pendapatanPerMotor'],
            'bagi_hasil' => $data['bagiHasil'],
        ], 'Laporan pendapatan berhasil diambil.');
    }
}
