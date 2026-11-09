<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penyewaan;
use App\Services\PenyewaanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Modul penyewaan sisi admin: melihat & memproses status.
 */
class PenyewaanController extends Controller
{
    public function __construct(private readonly PenyewaanService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Penyewaan::class);

        $status = $request->query('status');
        $cari = trim((string) $request->query('cari'));

        $penyewaan = Penyewaan::query()
            ->with(['penyewa', 'motor.pemilik', 'transaksis'])
            ->when(
                in_array($status, Penyewaan::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->whereHas('penyewa', fn ($u) => $u->where('nama', 'like', "%{$cari}%"))
                        ->orWhereHas('motor', fn ($m) => $m->where('no_plat', 'like', "%{$cari}%"));
                });
            })
            ->latest('tanggal_mulai')
            ->paginate(10)
            ->withQueryString();

        return view('admin.penyewaan.index', [
            'penyewaan' => $penyewaan,
            'status' => $status,
            'cari' => $cari,
            'jumlahPending' => Penyewaan::where('status', Penyewaan::STATUS_PENDING)->count(),
        ]);
    }

    public function show(Penyewaan $penyewaan): View
    {
        $this->authorize('view', $penyewaan);

        $penyewaan->load(['penyewa', 'motor.pemilik', 'motor.tarifRental', 'transaksis', 'bagiHasil']);

        return view('admin.penyewaan.show', [
            'penyewaan' => $penyewaan,
            'statusTersedia' => Penyewaan::TRANSISI[$penyewaan->status] ?? [],
        ]);
    }

    /**
     * Proses perubahan status oleh admin.
     */
    public function proses(Request $request, Penyewaan $penyewaan): RedirectResponse
    {
        $this->authorize('proses', $penyewaan);

        $validated = $request->validate([
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in(Penyewaan::STATUSES)],
        ], [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        if (! $penyewaan->bolehBerubahKe($validated['status'])) {
            return back()->with(
                'error',
                "Status tidak dapat diubah dari \"{$penyewaan->status}\" ke \"{$validated['status']}\"."
            );
        }

        try {
            $this->service->ubahStatus($penyewaan, $validated['status']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "Status penyewaan #{$penyewaan->id} diubah menjadi \"{$validated['status']}\"."
        );
    }
}
