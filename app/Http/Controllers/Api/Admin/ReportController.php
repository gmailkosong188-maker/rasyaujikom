<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Services\LaporanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Laporan pendapatan admin.
 *
 * Sumber data & perhitungan sepenuhnya memakai LaporanService::admin()
 * yang juga dipakai halaman laporan admin.
 */
class ReportController extends ApiController
{
    public function revenue(Request $request, LaporanService $laporan): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        [$dari, $sampai] = $laporan->rentang(
            $request->query('dari'),
            $request->query('sampai')
        );

        $data = $laporan->admin($dari, $sampai);

        return $this->sukses([
            'periode' => [
                'dari' => $dari->toDateString(),
                'sampai' => $sampai->toDateString(),
            ],
            'jumlah_motor' => $data['jumlahMotor'],
            'motor_tersedia' => $data['motorTersedia'],
            'motor_disewa' => $data['motorDisewa'],
            'jumlah_penyewaan' => $data['jumlahPenyewaan'],
            'total_pendapatan' => $data['totalPendapatan'],
            'bagian_pemilik' => $data['bagianPemilik'],
            'bagian_admin' => $data['bagianAdmin'],
            'pembayaran' => $data['pembayaran'],
            'bagi_hasil' => $data['bagiHasil'],
        ], 'Laporan pendapatan berhasil diambil.');
    }
}
