<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Models\Motor;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Modul motor sisi penyewa: hanya motor berstatus `tersedia`.
 *
 * Dokumen kepemilikan tidak pernah ditampilkan di sini (lihat
 * MotorPolicy::lihatDokumen).
 */
class MotorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Motor::class);

        $cari = trim((string) $request->query('cari'));
        $cc = $request->query('tipe_cc');

        $motor = Motor::query()
            ->tersedia()
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
            ->paginate(9)
            ->withQueryString();

        return view('penyewa.motor.index', [
            'motor' => $motor,
            'cari' => $cari,
            'cc' => (string) $cc,
            'tipeCc' => Motor::TIPE_CC,
        ]);
    }

    public function show(Motor $motor): View
    {
        $this->authorize('view', $motor);

        $motor->load(['pemilik', 'tarifRental']);

        return view('penyewa.motor.show', ['motor' => $motor]);
    }
}
