<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Pembayaran sisi admin: melihat semua transaksi & memverifikasi status.
 */
class TransaksiController extends Controller
{
    public function __construct(private readonly TransaksiService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Transaksi::class);

        $status = $request->query('status');
        $cari = trim((string) $request->query('cari'));

        $transaksi = Transaksi::query()
            ->with(['penyewaan.penyewa', 'penyewaan.motor'])
            ->when(
                in_array($status, Transaksi::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->when($cari !== '', function ($q) use ($cari) {
                $q->whereHas('penyewaan.penyewa', fn ($u) => $u->where('nama', 'like', "%{$cari}%"))
                    ->orWhereHas('penyewaan.motor', fn ($m) => $m->where('no_plat', 'like', "%{$cari}%"));
            })
            ->latest('tanggal')
            ->paginate(10)
            ->withQueryString();

        $totalDibayar = (float) Transaksi::where('status', Transaksi::STATUS_DIBAYAR)->sum('jumlah');

        return view('admin.transaksi.index', [
            'transaksi' => $transaksi,
            'status' => $status,
            'cari' => $cari,
            'jumlahPending' => Transaksi::where('status', Transaksi::STATUS_PENDING)->count(),
            'totalDibayar' => $totalDibayar,
        ]);
    }

    public function show(Transaksi $transaksi): View
    {
        $this->authorize('view', $transaksi);

        $transaksi->load(['penyewaan.penyewa', 'penyewaan.motor.pemilik']);

        return view('admin.transaksi.show', ['transaksi' => $transaksi]);
    }

    /**
     * Verifikasi / ubah status pembayaran.
     */
    public function proses(Request $request, Transaksi $transaksi): RedirectResponse
    {
        $this->authorize('proses', $transaksi);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Transaksi::STATUSES)],
        ], [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status pembayaran tidak valid.',
        ]);

        try {
            $this->service->ubahStatus($transaksi, $validated['status']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "Status pembayaran #{$transaksi->id} diubah menjadi \"{$validated['status']}\"."
        );
    }
}
