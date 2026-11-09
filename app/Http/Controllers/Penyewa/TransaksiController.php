<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaksi\StoreTransaksiRequest;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Pembayaran sisi penyewa: membuat dan melihat transaksi miliknya.
 */
class TransaksiController extends Controller
{
    public function __construct(private readonly TransaksiService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Transaksi::class);

        $status = $request->query('status');

        $transaksi = Transaksi::query()
            ->whereHas('penyewaan', fn ($q) => $q->where('penyewa_id', $request->user()->id))
            ->with(['penyewaan.motor'])
            ->when(
                in_array($status, Transaksi::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->latest('tanggal')
            ->paginate(10)
            ->withQueryString();

        return view('penyewa.transaksi.index', [
            'transaksi' => $transaksi,
            'status' => $status,
        ]);
    }

    /**
     * Form pembayaran untuk satu penyewaan.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Transaksi::class);

        $penyewaan = Penyewaan::with('motor')
            ->where('penyewa_id', $request->user()->id)
            ->find($request->query('penyewaan_id'));

        if (! $penyewaan) {
            return redirect()
                ->route('penyewa.penyewaan.index')
                ->with('error', 'Penyewaan tidak ditemukan.');
        }

        if ($penyewaan->transaksis()->exists()) {
            return redirect()
                ->route('penyewa.penyewaan.show', $penyewaan)
                ->with('error', 'Penyewaan ini sudah memiliki transaksi pembayaran.');
        }

        return view('penyewa.transaksi.create', [
            'penyewaan' => $penyewaan,
            'metode' => Transaksi::METODE,
        ]);
    }

    public function store(StoreTransaksiRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $penyewaan = Penyewaan::findOrFail($data['penyewaan_id']);

        try {
            $transaksi = $this->service->buat($request->user(), $penyewaan, $data['metode_pembayaran']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('penyewa.transaksi.show', $transaksi)
            ->with('success', 'Pembayaran berhasil dicatat dan menunggu verifikasi admin.');
    }

    public function show(Transaksi $transaksi): View
    {
        $this->authorize('view', $transaksi);

        $transaksi->load(['penyewaan.motor.pemilik']);

        return view('penyewa.transaksi.show', ['transaksi' => $transaksi]);
    }
}
