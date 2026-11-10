<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\BagiHasil;
use App\Services\BagiHasilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Pencairan dana bagi hasil (API admin).
 *
 * Aturan identik dengan website: memakai BagiHasilService::cairkan()
 * sehingga tidak ada business logic yang diduplikasi.
 */
class PencairanController extends ApiController
{
    /**
     * Daftar bagi hasil yang menunggu pencairan.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $menunggu = BagiHasil::query()
            ->menungguPencairan()
            ->with(['penyewaan.motor.pemilik', 'penyewaan.penyewa'])
            ->latest('tanggal')
            ->get();

        return $this->sukses([
            'total_dana_menunggu_pencairan' => (float) $menunggu->sum('nominal_pemilik'),
            'jumlah_menunggu_pencairan' => $menunggu->count(),
            'daftar' => $menunggu->map(fn (BagiHasil $b) => $this->dataBagiHasil($b))->values(),
        ], 'Daftar dana menunggu pencairan berhasil diambil.');
    }

    /**
     * Admin mengonfirmasi pencairan (uang sudah diserahkan ke pemilik).
     */
    public function cairkan(Request $request, BagiHasil $bagiHasil, BagiHasilService $service): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return $this->gagal('Hanya admin yang dapat mengonfirmasi pencairan dana.', [], 403);
        }

        try {
            $service->cairkan($bagiHasil, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->gagal($e->getMessage(), [], 422);
        }

        $bagiHasil->refresh()->load(['penyewaan.motor.pemilik', 'pencairan']);

        return $this->sukses($this->dataBagiHasil($bagiHasil), 'Pencairan dana berhasil dikonfirmasi.');
    }

    /**
     * Riwayat pencairan yang sudah dilakukan.
     */
    public function riwayat(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $sudah = BagiHasil::query()
            ->sudahDicairkan()
            ->with(['penyewaan.motor.pemilik', 'pencairan'])
            ->latest('settled_at')
            ->get();

        return $this->sukses([
            'total_sudah_dicairkan' => (float) $sudah->sum('nominal_pemilik'),
            'jumlah_sudah_dicairkan' => $sudah->count(),
            'daftar' => $sudah->map(fn (BagiHasil $b) => $this->dataBagiHasil($b))->values(),
        ], 'Riwayat pencairan berhasil diambil.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataBagiHasil(BagiHasil $b): array
    {
        return [
            'id' => $b->id,
            'penyewaan_id' => $b->penyewaan_id,
            'tanggal' => $b->tanggal?->toDateString(),
            'persentase_hasil_pemilik' => $b->persentase_hasil_pemilik,
            'persentase_hasil_admin' => $b->persentase_hasil_admin,
            'nominal_pemilik' => (float) $b->nominal_pemilik,
            'nominal_admin' => (float) $b->nominal_admin,
            'status_pencairan' => $b->isSettled() ? 'sudah_dicairkan' : 'menunggu_pencairan',
            'settled_at' => $b->settled_at?->toIso8601String(),
            'dicairkan_oleh' => $b->dicairkan_oleh,
            'nama_admin_pencairan' => $b->pencairan?->nama,
            'pemilik' => [
                'id' => $b->penyewaan?->motor?->pemilik?->id,
                'nama' => $b->penyewaan?->motor?->pemilik?->nama,
            ],
            'motor' => [
                'id' => $b->penyewaan?->motor?->id,
                'no_plat' => $b->penyewaan?->motor?->no_plat,
                'merk' => $b->penyewaan?->motor?->merk,
            ],
            'penyewa' => $b->penyewaan?->penyewa?->nama,
        ];
    }
}
