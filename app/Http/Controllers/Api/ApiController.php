<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Dasar seluruh controller API.
 *
 * Menyediakan format respons yang konsisten (spesifikasi §3) dan
 * transformer data agar password/kolom sensitif tidak pernah ikut
 * terkirim ke klien.
 */
abstract class ApiController extends Controller
{
    /**
     * Respons sukses.
     */
    protected function sukses(mixed $data = null, string $message = 'Data berhasil diambil', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Respons gagal.
     *
     * @param  array<string, array<int, string>|string>  $errors
     */
    protected function gagal(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);
    }

    /**
     * Data user yang aman dikirim lewat API (tanpa password/remember_token).
     *
     * @return array<string, mixed>
     */
    protected function dataUser(User $user): array
    {
        return [
            'id' => $user->id,
            'nama' => $user->nama,
            'email' => $user->email,
            'no_tlpn' => $user->no_tlpn,
            'role' => $user->role,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dataMotor(Motor $motor): array
    {
        $tarif = $motor->relationLoaded('tarifRental') ? $motor->tarifRental : null;
        $pemilik = $motor->relationLoaded('pemilik') ? $motor->pemilik : null;

        return [
            'id' => $motor->id,
            'pemilik_id' => $motor->pemilik_id,
            'merk' => $motor->merk,
            'tipe_cc' => $motor->tipe_cc,
            'no_plat' => $motor->no_plat,
            'status' => $motor->status,
            'is_aktif' => $motor->isAktif(),
            'photo_url' => $motor->urlPhoto(),
            'pemilik' => $pemilik ? ['id' => $pemilik->id, 'nama' => $pemilik->nama] : null,
            'tarif_rental' => $tarif ? [
                'tarif_harian' => (float) $tarif->tarif_harian,
                'tarif_mingguan' => (float) $tarif->tarif_mingguan,
                'tarif_bulanan' => (float) $tarif->tarif_bulanan,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dataPenyewaan(Penyewaan $penyewaan): array
    {
        $motor = $penyewaan->relationLoaded('motor') ? $penyewaan->motor : null;
        $penyewa = $penyewaan->relationLoaded('penyewa') ? $penyewaan->penyewa : null;

        return [
            'id' => $penyewaan->id,
            'penyewa_id' => $penyewaan->penyewa_id,
            'penyewa' => $penyewa ? [
                'id' => $penyewa->id,
                'nama' => $penyewa->nama,
                'email' => $penyewa->email,
            ] : null,
            'motor' => $motor ? $this->dataMotor($motor) : null,
            'tanggal_mulai' => $penyewaan->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $penyewaan->tanggal_selesai?->toDateString(),
            'tipe_durasi' => $penyewaan->tipe_durasi,
            'harga' => (float) $penyewaan->harga,
            'status' => $penyewaan->status,
            'transaksis' => $penyewaan->relationLoaded('transaksis')
                ? $penyewaan->transaksis->map(fn (Transaksi $t) => $this->dataTransaksi($t))->values()
                : [],
            'created_at' => $penyewaan->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dataTransaksi(Transaksi $transaksi): array
    {
        return [
            'id' => $transaksi->id,
            'penyewaan_id' => $transaksi->penyewaan_id,
            'jumlah' => (float) $transaksi->jumlah,
            'metode_pembayaran' => $transaksi->metode_pembayaran,
            'status' => $transaksi->status,
            'tanggal' => $transaksi->tanggal?->toDateString(),
        ];
    }
}
