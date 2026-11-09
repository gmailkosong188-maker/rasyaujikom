<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Motor\StoreMotorRequest;
use App\Http\Requests\Motor\UpdateMotorRequest;
use App\Models\Motor;
use App\Services\MotorFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Modul motor sisi pemilik.
 *
 * Semua aksi dibatasi ke motor milik user yang login — ditegakkan oleh
 * MotorPolicy dan diperkuat query `where('pemilik_id', ...)`.
 */
class MotorController extends Controller
{
    public function __construct(private readonly MotorFileService $files) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Motor::class);

        $status = $request->query('status');
        $user = $request->user();

        $motor = $user->motors()
            ->with('tarifRental')
            ->when(
                in_array($status, Motor::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pemilik.motor.index', [
            'motor' => $motor,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Motor::class);

        return view('pemilik.motor.create', [
            'tipeCc' => Motor::TIPE_CC,
        ]);
    }

    public function store(StoreMotorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $motor = DB::transaction(function () use ($request, $data) {
            return Motor::create([
                'pemilik_id' => $request->user()->id,
                'merk' => $data['merk'],
                'tipe_cc' => $data['tipe_cc'],
                'no_plat' => $data['no_plat'],
                // Pemilik tidak dapat menetapkan status: selalu pending.
                'status' => Motor::STATUS_PENDING,
                'photo' => $this->files->simpanFoto($request->file('photo')),
                'dokumen_kepemilikan' => $this->files->simpanDokumen($request->file('dokumen_kepemilikan')),
            ]);
        });

        return redirect()
            ->route('pemilik.motor.show', $motor)
            ->with('success', 'Motor berhasil didaftarkan dan menunggu verifikasi admin.');
    }

    public function show(Motor $motor): View
    {
        $this->authorize('view', $motor);

        $motor->load(['tarifRental', 'penyewaans']);

        return view('pemilik.motor.show', ['motor' => $motor]);
    }

    public function edit(Motor $motor): View
    {
        $this->authorize('update', $motor);

        return view('pemilik.motor.edit', [
            'motor' => $motor,
            'tipeCc' => Motor::TIPE_CC,
        ]);
    }

    public function update(UpdateMotorRequest $request, Motor $motor): RedirectResponse
    {
        $data = $request->validated();

        $statusSebelum = $motor->status;

        DB::transaction(function () use ($request, $data, $motor, $statusSebelum) {
            $payload = [
                'merk' => $data['merk'],
                'tipe_cc' => $data['tipe_cc'],
                'no_plat' => $data['no_plat'],
            ];

            if ($request->hasFile('photo')) {
                $payload['photo'] = $this->files->simpanFoto($request->file('photo'), $motor->photo);
            }

            if ($request->hasFile('dokumen_kepemilikan')) {
                $payload['dokumen_kepemilikan'] = $this->files->simpanDokumen(
                    $request->file('dokumen_kepemilikan'),
                    $motor->dokumen_kepemilikan
                );

                // Dokumen diganti -> perlu diverifikasi ulang oleh admin.
                if ($statusSebelum === Motor::STATUS_TERSEDIA) {
                    $payload['status'] = Motor::STATUS_PENDING;
                }
            }

            // Motor yang sebelumnya ditolak dan diperbaiki diajukan ulang.
            if ($statusSebelum === Motor::STATUS_DITOLAK) {
                $payload['status'] = Motor::STATUS_PENDING;
            }

            $motor->update($payload);
        });

        $pesan = 'Data motor berhasil diperbarui.';
        if ($motor->fresh()->status === Motor::STATUS_PENDING && $statusSebelum !== Motor::STATUS_PENDING) {
            $pesan .= ' Motor diajukan ulang dan menunggu verifikasi admin.';
        }

        return redirect()
            ->route('pemilik.motor.show', $motor)
            ->with('success', $pesan);
    }

    public function destroy(Motor $motor): RedirectResponse
    {
        $this->authorize('delete', $motor);

        $noPlat = $motor->no_plat;

        DB::transaction(function () use ($motor) {
            $this->files->hapus($motor->photo);
            $this->files->hapus($motor->dokumen_kepemilikan);
            $motor->delete();
        });

        return redirect()
            ->route('pemilik.motor.index')
            ->with('success', "Motor {$noPlat} berhasil dihapus.");
    }
}
