<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Penyewaan\StorePenyewaanRequest;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Services\PenyewaanService;
use App\Services\PengembalianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Modul penyewaan sisi penyewa.
 *
 * Penyewa hanya dapat melihat & membatalkan penyewaannya sendiri
 * (ditegakkan PenyewaanPolicy + query where('penyewa_id', ...)).
 */
class PenyewaanController extends Controller
{
    public function __construct(private readonly PenyewaanService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Penyewaan::class);

        $status = $request->query('status');

        $penyewaan = $request->user()->penyewaans()
            ->with(['motor.pemilik', 'motor.tarifRental', 'transaksis'])
            ->when(
                in_array($status, Penyewaan::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->latest('tanggal_mulai')
            ->paginate(10)
            ->withQueryString();

        return view('penyewa.penyewaan.index', [
            'penyewaan' => $penyewaan,
            'status' => $status,
        ]);
    }

    /**
     * Form pemesanan untuk satu motor.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Penyewaan::class);

        $motor = Motor::with(['tarifRental', 'pemilik'])
            ->where('status', Motor::STATUS_TERSEDIA)
            ->find($request->query('motor_id'));

        if (! $motor || ! $motor->tarifRental) {
            return redirect()
                ->route('penyewa.motor.index')
                ->with('error', 'Pilih motor yang tersedia dan sudah memiliki tarif untuk disewa.');
        }

        return view('penyewa.penyewaan.create', [
            'motor' => $motor,
            'tipeDurasi' => Penyewaan::TIPE_DURASI,
            'hariIni' => now()->toDateString(),
        ]);
    }

    public function store(StorePenyewaanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $motor = Motor::with('tarifRental')->findOrFail($data['motor_id']);

        try {
            $penyewaan = $this->service->buat(
                $request->user(),
                $motor,
                $data['tanggal_mulai'],
                $data['tanggal_selesai'],
                $data['tipe_durasi'],
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('penyewa.penyewaan.show', $penyewaan)
            ->with('success', 'Penyewaan berhasil dibuat. Silakan lanjutkan pembayaran sesuai instruksi admin.');
    }

    public function show(Penyewaan $penyewaan): View
    {
        $this->authorize('view', $penyewaan);

        $penyewaan->load(['motor.pemilik', 'motor.tarifRental', 'transaksis', 'bagiHasil']);

        return view('penyewa.penyewaan.show', ['penyewaan' => $penyewaan]);
    }

    /**
     * Batalkan penyewaan milik sendiri.
     */
    public function batalkan(Penyewaan $penyewaan): RedirectResponse
    {
        $this->authorize('batalkan', $penyewaan);

        try {
            $this->service->ubahStatus($penyewaan, Penyewaan::STATUS_DIBATALKAN);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('penyewa.penyewaan.show', $penyewaan)
            ->with('success', 'Penyewaan dibatalkan.');
    }

    /**
     * Penyewa mengajukan pengembalian motor (spesifikasi §14).
     * Admin yang mengonfirmasi setelah pemeriksaan.
     */
    public function ajukanPengembalian(Penyewaan $penyewaan, PengembalianService $pengembalian): RedirectResponse
    {
        $this->authorize('ajukanPengembalian', $penyewaan);

        try {
            $pengembalian->ajukan($penyewaan);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('penyewa.penyewaan.show', $penyewaan)
            ->with('success', 'Pengembalian diajukan. Menunggu pemeriksaan dan konfirmasi admin.');
    }
}
