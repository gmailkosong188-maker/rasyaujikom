<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Laporan sisi pemilik: HANYA motor miliknya (spesifikasi §24).
 */
class LaporanController extends Controller
{
    public function __construct(private readonly LaporanService $service) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->isPemilik(), 403);

        [$dari, $sampai] = $this->service->rentang(
            $request->query('dari'),
            $request->query('sampai')
        );

        return view('pemilik.laporan.index', [
            'dari' => $dari,
            'sampai' => $sampai,
            ...$this->service->pemilik($request->user(), $dari, $sampai),
        ]);
    }
}
