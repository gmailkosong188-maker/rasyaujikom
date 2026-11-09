<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TarifRental\StoreTarifRentalRequest;
use App\Http\Requests\TarifRental\UpdateTarifRentalRequest;
use App\Models\Motor;
use App\Models\TarifRental;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Modul tarif rental sisi admin.
 *
 * Ini satu-satunya tempat penentuan tarif. Modul Penyewaan nantinya
 * hanya MEMBACA TarifRental melalui Penyewaan::hitungHarga() sehingga
 * tidak ada harga yang di-hardcode.
 */
class TarifRentalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TarifRental::class);

        $cari = trim((string) $request->query('cari'));
        $belumAda = $request->boolean('belum_ada');

        $tarif = TarifRental::query()
            ->with('motor.pemilik')
            ->when($cari !== '', function ($q) use ($cari) {
                $q->whereHas('motor', function ($sub) use ($cari) {
                    $sub->where('merk', 'like', "%{$cari}%")
                        ->orWhere('no_plat', 'like', "%{$cari}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Motor yang sudah diverifikasi tapi belum punya tarif.
        $motorTanpaTarif = Motor::query()
            ->whereIn('status', [Motor::STATUS_TERSEDIA, Motor::STATUS_DISEWA, Motor::STATUS_PERAWATAN])
            ->whereDoesntHave('tarifRental')
            ->with('pemilik')
            ->orderBy('no_plat')
            ->get();

        return view('admin.tarif.index', [
            'tarif' => $tarif,
            'cari' => $cari,
            'belumAda' => $belumAda,
            'motorTanpaTarif' => $motorTanpaTarif,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', TarifRental::class);

        // Motor yang sudah diverifikasi dan belum punya tarif.
        $motor = Motor::query()
            ->whereIn('status', [Motor::STATUS_TERSEDIA, Motor::STATUS_DISEWA, Motor::STATUS_PERAWATAN])
            ->whereDoesntHave('tarifRental')
            ->with('pemilik')
            ->orderBy('no_plat')
            ->get();

        $motorTerpilih = $request->query('motor_id');

        return view('admin.tarif.create', [
            'motor' => $motor,
            'motorTerpilih' => $motorTerpilih,
        ]);
    }

    public function store(StoreTarifRentalRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $tarif = DB::transaction(function () use ($data) {
            return TarifRental::create([
                'motor_id' => $data['motor_id'],
                'tarif_harian' => $data['tarif_harian'],
                'tarif_mingguan' => $data['tarif_mingguan'],
                'tarif_bulanan' => $data['tarif_bulanan'],
            ]);
        });

        return redirect()
            ->route('admin.tarif.show', $tarif)
            ->with('success', 'Tarif rental berhasil ditambahkan.');
    }

    public function show(TarifRental $tarif): View
    {
        $this->authorize('view', $tarif);

        $tarif->load(['motor.pemilik']);

        return view('admin.tarif.show', ['tarif' => $tarif]);
    }

    public function edit(TarifRental $tarif): View
    {
        $this->authorize('update', $tarif);

        $tarif->load('motor.pemilik');

        return view('admin.tarif.edit', ['tarif' => $tarif]);
    }

    public function update(UpdateTarifRentalRequest $request, TarifRental $tarif): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $tarif) {
            $tarif->update([
                'tarif_harian' => $data['tarif_harian'],
                'tarif_mingguan' => $data['tarif_mingguan'],
                'tarif_bulanan' => $data['tarif_bulanan'],
            ]);
        });

        return redirect()
            ->route('admin.tarif.show', $tarif)
            ->with('success', 'Tarif rental berhasil diperbarui.');
    }

    public function destroy(TarifRental $tarif): RedirectResponse
    {
        $this->authorize('delete', $tarif);

        $noPlat = $tarif->motor?->no_plat ?? '-';

        DB::transaction(function () use ($tarif) {
            $tarif->delete();
        });

        return redirect()
            ->route('admin.tarif.index')
            ->with('success', "Tarif untuk motor {$noPlat} berhasil dihapus.");
    }
}
