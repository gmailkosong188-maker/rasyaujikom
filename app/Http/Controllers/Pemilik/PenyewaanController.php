<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\Penyewaan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Modul penyewaan sisi pemilik: READ-ONLY.
 *
 * Pemilik hanya dapat melihat penyewaan atas motor miliknya
 * (diturunkan lewat penyewaans.motor_id -> motors.pemilik_id,
 * tanpa kolom pemilik_id di tabel penyewaans).
 */
class PenyewaanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Penyewaan::class);

        $user = $request->user();
        $status = $request->query('status');

        $penyewaan = Penyewaan::query()
            ->whereHas('motor', fn ($q) => $q->where('pemilik_id', $user->id))
            ->with(['penyewa', 'motor', 'transaksis'])
            ->when(
                in_array($status, Penyewaan::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->latest('tanggal_mulai')
            ->paginate(10)
            ->withQueryString();

        return view('pemilik.penyewaan.index', [
            'penyewaan' => $penyewaan,
            'status' => $status,
        ]);
    }

    public function show(Penyewaan $penyewaan): View
    {
        $this->authorize('view', $penyewaan);

        $penyewaan->load(['penyewa', 'motor.tarifRental', 'transaksis', 'bagiHasil']);

        return view('pemilik.penyewaan.show', ['penyewaan' => $penyewaan]);
    }
}
