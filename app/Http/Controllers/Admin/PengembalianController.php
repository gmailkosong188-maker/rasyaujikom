<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penyewaan;
use App\Services\PengembalianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Pengembalian motor sisi admin: pemeriksaan & konfirmasi (spesifikasi §14).
 */
class PengembalianController extends Controller
{
    public function __construct(private readonly PengembalianService $service) {}

    /**
     * Daftar pengembalian yang menunggu konfirmasi + riwayat selesai.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Penyewaan::class);

        $menunggu = Penyewaan::query()
            ->where('status', Penyewaan::STATUS_BERJALAN)
            ->whereHas('transaksis', fn ($q) => $q->where('status', \App\Models\Transaksi::STATUS_DIKEMBALIKAN))
            ->with(['penyewa', 'motor.pemilik'])
            ->oldest('tanggal_selesai')
            ->get();

        $selesai = Penyewaan::query()
            ->where('status', Penyewaan::STATUS_SELESAI)
            ->with(['penyewa', 'motor'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('admin.pengembalian.index', [
            'menunggu' => $menunggu,
            'selesai' => $selesai,
        ]);
    }

    /**
     * Konfirmasi pengembalian: penyewaan -> selesai, motor -> tersedia.
     */
    public function konfirmasi(Penyewaan $penyewaan): RedirectResponse
    {
        $this->authorize('proses', $penyewaan);

        try {
            $this->service->konfirmasi($penyewaan);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "Pengembalian penyewaan #{$penyewaan->id} dikonfirmasi. Penyewaan selesai dan motor kembali tersedia."
        );
    }
}
