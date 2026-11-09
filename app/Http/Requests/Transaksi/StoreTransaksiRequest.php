<?php

namespace App\Http\Requests\Transaksi;

use App\Models\Penyewaan;
use App\Models\Transaksi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pembayaran oleh penyewa.
 *
 * PENTING: `jumlah` TIDAK diterima dari user. Nominal selalu diambil
 * dari Penyewaan::harga (spesifikasi §13). User hanya memilih metode
 * pembayaran.
 */
class StoreTransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaksi::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'penyewaan_id' => ['required', 'integer', Rule::exists('penyewaans', 'id')],
            'metode_pembayaran' => ['required', 'string', Rule::in(Transaksi::METODE)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'penyewaan_id.required' => 'Penyewaan tidak dikenali.',
            'penyewaan_id.exists' => 'Penyewaan tidak ditemukan.',
            'metode_pembayaran.required' => 'Metode pembayaran wajib dipilih.',
            'metode_pembayaran.in' => 'Metode pembayaran harus cash atau transfer.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'metode_pembayaran' => 'metode pembayaran',
        ];
    }

    /**
     * Aturan bisnis tambahan: penyewaan milik user ini, belum lunas,
     * dan statusnya memang boleh dibayar.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $penyewaan = Penyewaan::find($this->input('penyewaan_id'));

            if (! $penyewaan) {
                return;
            }

            // Penyewa hanya boleh membayar penyewaannya sendiri.
            if ($penyewaan->penyewa_id !== $this->user()->id) {
                $validator->errors()->add('penyewaan_id', 'Penyewaan ini bukan milik Anda.');

                return;
            }

            if (($penyewaan->harga ?? 0) <= 0) {
                $validator->errors()->add('penyewaan_id', 'Penyewaan ini tidak memiliki harga yang valid.');

                return;
            }

            if (in_array($penyewaan->status, [Penyewaan::STATUS_DIBATALKAN, Penyewaan::STATUS_SELESAI], true)) {
                $validator->errors()->add(
                    'penyewaan_id',
                    "Penyewaan berstatus \"{$penyewaan->status}\" tidak dapat dibayar."
                );

                return;
            }

            // Cegah pembayaran ganda: sudah ada transaksi lunas / menunggu.
            $sudahAda = $penyewaan->transaksis()
                ->whereIn('status', [Transaksi::STATUS_PENDING, Transaksi::STATUS_DIBAYAR])
                ->exists();

            if ($sudahAda) {
                $validator->errors()->add(
                    'penyewaan_id',
                    'Penyewaan ini sudah memiliki pembayaran. Hubungi admin bila ada masalah.'
                );
            }
        });
    }
}
