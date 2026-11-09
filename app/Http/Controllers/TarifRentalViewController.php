<?php

namespace App\Http\Controllers;

use App\Models\TarifRental;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tampilan tarif rental READ-ONLY untuk pemilik & penyewa.
 *
 * Sengaja dipisah dari Admin\TarifRentalController agar tidak ada
 * kemungkinan aksi tulis di sisi non-admin. Otorisasi tetap diperiksa
 * oleh TarifRentalPolicy.
 */
class TarifRentalViewController extends Controller
{
    /**
     * Daftar tarif untuk pemilik: hanya motor miliknya.
     */
    public function pemilik(Request $request): View
    {
        $this->authorize('viewAny', TarifRental::class);

        $user = $request->user();

        $tarif = TarifRental::query()
            ->whereHas('motor', fn ($q) => $q->where('pemilik_id', $user->id))
            ->with('motor')
            ->latest()
            ->paginate(10);

        // Motor miliknya yang belum bertarif, agar pemilik tahu.
        $motorTanpaTarif = $user->motors()
            ->whereDoesntHave('tarifRental')
            ->orderBy('no_plat')
            ->get();

        return view('pemilik.tarif.index', [
            'tarif' => $tarif,
            'motorTanpaTarif' => $motorTanpaTarif,
        ]);
    }

    /**
     * Daftar tarif untuk penyewa: semua motor yang sudah bertarif.
     */
    public function penyewa(): View
    {
        $this->authorize('viewAny', TarifRental::class);

        $tarif = TarifRental::query()
            ->whereHas('motor', fn ($q) => $q->where('status', \App\Models\Motor::STATUS_TERSEDIA))
            ->with('motor.pemilik')
            ->orderBy(
                \App\Models\Motor::query()->select('merk')->whereColumn('motors.id', 'tarif_rentals.motor_id')
            )
            ->paginate(10);

        return view('penyewa.tarif.index', ['tarif' => $tarif]);
    }

    /**
     * Detail tarif (read-only) untuk pemilik/penyewa.
     */
    public function show(TarifRental $tarif): View
    {
        $this->authorize('view', $tarif);

        $tarif->load(['motor.pemilik']);

        return view('tarif.show', ['tarif' => $tarif]);
    }
}
