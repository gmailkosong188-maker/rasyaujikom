<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BagiHasil;
use App\Models\Penyewaan;
use App\Services\BagiHasilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Bagi hasil sisi admin: melihat settlement & mencatat bagi hasil
 * untuk penyewaan yang sudah selesai (spesifikasi §15).
 *
 * Persentase TIDAK di-hardcode di sini — semuanya lewat BagiHasilService
 * yang membaca config/bagi_hasil.php.
 */
class BagiHasilController extends Controller
{
    public function __construct(private readonly BagiHasilService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BagiHasil::class);

        $cari = trim((string) $request->query('cari'));

        $bagiHasil = BagiHasil::query()
            ->with(['penyewaan.motor.pemilik', 'penyewaan.penyewa'])
            ->when($cari !== '', function ($q) use ($cari) {
                $q->whereHas('penyewaan.motor.pemilik', fn ($u) => $u->where('nama', 'like', "%{$cari}%"))
                    ->orWhereHas('penyewaan.motor', fn ($m) => $m->where('no_plat', 'like', "%{$cari}%"))
                    ->orWhereHas('penyewaan.penyewa', fn ($u) => $u->where('nama', 'like', "%{$cari}%"));
            })
            ->latest('tanggal')
            ->paginate(10)
            ->withQueryString();

        // Penyewaan selesai yang belum punya bagi hasil -> bisa dicatat manual.
        $belumSettled = Penyewaan::query()
            ->where('status', Penyewaan::STATUS_SELESAI)
            ->whereDoesntHave('bagiHasil')
            ->with(['motor.pemilik', 'penyewa'])
            ->latest('tanggal_selesai')
            ->get();

        return view('admin.bagi-hasil.index', [
            'bagiHasil' => $bagiHasil,
            'belumSettled' => $belumSettled,
            'cari' => $cari,
            'persentasePemilik' => $this->service->persentasePemilik(),
            'persentaseAdmin' => $this->service->persentaseAdmin(),
            'totalNominalPemilik' => (float) BagiHasil::sum('nominal_pemilik'),
            'totalNominalAdmin' => (float) BagiHasil::sum('nominal_admin'),
        ]);
    }

    public function show(BagiHasil $bagiHasil): View
    {
        $this->authorize('view', $bagiHasil);

        $bagiHasil->load(['penyewaan.motor.pemilik', 'penyewaan.penyewa', 'penyewaan.transaksis']);

        return view('admin.bagi-hasil.show', ['bagiHasil' => $bagiHasil]);
    }

    /**
     * Catat bagi hasil untuk penyewaan yang sudah selesai.
     * Aman dari settlement ganda (unique index + guard di service).
     */
    public function catat(Penyewaan $penyewaan): RedirectResponse
    {
        $this->authorize('proses', $penyewaan);

        try {
            $hasil = $this->service->catat($penyewaan);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.bagi-hasil.show', $hasil)
            ->with('success', 'Bagi hasil berhasil dicatat.');
    }
}
