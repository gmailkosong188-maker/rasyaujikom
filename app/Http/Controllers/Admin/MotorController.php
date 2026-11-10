<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Motor\UpdateStatusMotorRequest;
use App\Models\Motor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Modul motor sisi admin: melihat semua motor, verifikasi/terima/tolak,
 * serta mengubah status.
 *
 * Akses dijaga middleware `role:admin` pada route, lalu diperiksa lagi
 * oleh MotorPolicy di tiap aksi (pertahanan berlapis).
 */
class MotorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Motor::class);

        $status = $request->query('status');
        $cari = trim((string) $request->query('cari'));

        $motor = Motor::query()
            ->with(['pemilik', 'tarifRental'])
            ->when(
                in_array($status, Motor::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('merk', 'like', "%{$cari}%")
                        ->orWhere('no_plat', 'like', "%{$cari}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.motor.index', [
            'motor' => $motor,
            'status' => $status,
            'cari' => $cari,
            'jumlahPending' => Motor::where('status', Motor::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Daftar motor yang menunggu verifikasi.
     */
    public function verifikasi(Request $request): View
    {
        $this->authorize('viewAny', Motor::class);

        return view('admin.motor.verifikasi', [
            'motor' => Motor::with('pemilik')
                ->where('status', Motor::STATUS_PENDING)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Motor $motor): View
    {
        $this->authorize('view', $motor);

        $motor->load(['pemilik', 'tarifRental', 'penyewaans']);

        return view('admin.motor.show', [
            'motor' => $motor,
            'statusTersedia' => Motor::TRANSISI[$motor->status] ?? [],
        ]);
    }

    /**
     * Terima motor: pending -> tersedia.
     */
    public function terima(Motor $motor): RedirectResponse
    {
        $this->authorize('verifikasi', $motor);

        if ($motor->status !== Motor::STATUS_PENDING || ! $motor->bolehBerubahKe(Motor::STATUS_TERSEDIA)) {
            return back()->with('error', 'Motor ini tidak dapat diterima pada status saat ini.');
        }

        DB::transaction(function () use ($motor) {
            $motor->update(['status' => Motor::STATUS_TERSEDIA]);
        });

        $pesan = 'Motor '.$motor->no_plat.' diverifikasi dan berstatus tersedia.';

        if (! $motor->tarifRental()->exists()) {
            $pesan .= ' Tentukan tarif rental agar dapat disewa.';
        }

        return back()->with('success', $pesan);
    }

    /**
     * Tolak motor.
     */
    public function tolak(Request $request, Motor $motor): RedirectResponse
    {
        $this->authorize('verifikasi', $motor);

        $validated = $request->validate([
            'alasan' => ['nullable', 'string', 'max:500'],
        ]);

        // Penolakan verifikasi hanya untuk motor pending.
        if ($motor->status !== Motor::STATUS_PENDING || ! $motor->bolehBerubahKe(Motor::STATUS_DITOLAK)) {
            return back()->with('error', 'Motor ini tidak dapat ditolak pada status saat ini.');
        }

        DB::transaction(function () use ($motor) {
            $motor->update(['status' => Motor::STATUS_DITOLAK]);
        });

        $pesan = 'Motor '.$motor->no_plat.' ditolak.';
        if (! empty($validated['alasan'])) {
            $pesan .= ' Alasan: '.$validated['alasan'];
        }

        return back()->with('success', $pesan);
    }

    /**
     * Ubah status bebas sesuai aturan transisi.
     */
    public function updateStatus(UpdateStatusMotorRequest $request, Motor $motor): RedirectResponse
    {
        $this->authorize('ubahStatus', $motor);

        $validated = $request->validated();

        if (! $motor->bolehBerubahKe($validated['status'])) {
            return back()->with('error', 'Perubahan status tidak sah.');
        }

        $asal = $motor->status;

        DB::transaction(function () use ($motor, $validated) {
            $motor->update(['status' => $validated['status']]);
        });

        return back()->with(
            'success',
            "Status motor {$motor->no_plat} diubah dari \"{$asal}\" menjadi \"{$validated['status']}\"."
        );
    }

    /**
     * Halaman dokumen/foto untuk admin (dokumen hanya untuk admin & pemilik).
     */
    public function dokumen(Motor $motor): View
    {
        $this->authorize('lihatDokumen', $motor);

        return view('admin.motor.dokumen', ['motor' => $motor]);
    }
}
