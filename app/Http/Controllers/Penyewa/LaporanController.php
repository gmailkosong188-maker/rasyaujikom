<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Laporan sisi penyewa: HANYA riwayat penyewaan & pembayaran miliknya
 * (spesifikasi §24).
 */
class LaporanController extends Controller
{
    public function __construct(private readonly LaporanService $service) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->isPenyewa(), 403);

        [$dari, $sampai] = $this->service->rentang(
            $request->query('dari'),
            $request->query('sampai')
        );

        return view('penyewa.laporan.index', [
            'dari' => $dari,
            'sampai' => $sampai,
            ...$this->service->penyewa($request->user(), $dari, $sampai),
        ]);
    }
}
