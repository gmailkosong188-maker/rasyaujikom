<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Motor\StoreMotorRequest;
use App\Models\Motor;
use App\Services\MotorFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul motor sisi pemilik untuk API.
 *
 * Query selalu dibatasi ke motor milik user yang login, dan validasi
 * memakai StoreMotorRequest yang sama dengan website (status motor baru
 * selalu `pending`, menunggu verifikasi admin).
 */
class MotorController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Motor::class);

        $status = $request->query('status');

        $motor = $request->user()->motors()
            ->with('tarifRental')
            ->when(
                in_array($status, Motor::STATUSES, true),
                fn ($q) => $q->where('status', $status)
            )
            ->latest()
            ->get();

        return $this->sukses(
            $motor->map(fn (Motor $m) => $this->dataMotor($m))->values(),
            'Data motor berhasil diambil.'
        );
    }

    public function store(StoreMotorRequest $request, MotorFileService $files): JsonResponse
    {
        $data = $request->validated();

        $motor = DB::transaction(fn () => Motor::create([
            'pemilik_id' => $request->user()->id,
            'merk' => $data['merk'],
            'tipe_cc' => $data['tipe_cc'],
            'no_plat' => $data['no_plat'],
            // Pemilik tidak dapat menetapkan status: selalu pending.
            'status' => Motor::STATUS_PENDING,
            'photo' => $files->simpanFoto($request->file('photo')),
            'dokumen_kepemilikan' => $files->simpanDokumen($request->file('dokumen_kepemilikan')),
        ]));

        return $this->sukses(
            $this->dataMotor($motor->load('tarifRental')),
            'Motor berhasil didaftarkan dan menunggu verifikasi admin.',
            201
        );
    }

    /**
     * Nonaktifkan motor agar tidak tampil di katalog penyewa
     * dan tidak dapat dibooking. Riwayat penyewaan tetap tersimpan.
     */
    public function nonaktifkan(Motor $motor): JsonResponse
    {
        $this->authorize('nonaktifkan', $motor);

        if (! $motor->canBeNonaktif()) {
            return $this->gagal(
                'Motor tidak dapat dinonaktifkan karena masih memiliki penyewaan yang berjalan.',
                [], 422
            );
        }

        $motor->update(['is_aktif' => Motor::NONAKTIF]);

        return $this->sukses(
            $this->dataMotor($motor->fresh()),
            "Motor {$motor->no_plat} telah dinonaktifkan."
        );
    }

    /**
     * Aktifkan kembali motor sehingga dapat muncul di katalog penyewa
     * (jika seluruh syarat penyewaan lainnya terpenuhi).
     */
    public function aktifkan(Motor $motor): JsonResponse
    {
        $this->authorize('aktifkan', $motor);

        $motor->update(['is_aktif' => Motor::AKTIF]);

        return $this->sukses(
            $this->dataMotor($motor->fresh()),
            "Motor {$motor->no_plat} telah diaktifkan kembali."
        );
    }
}
