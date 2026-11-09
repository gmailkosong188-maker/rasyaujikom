<?php

namespace App\Http\Requests\Motor;

use App\Models\Motor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah motor oleh pemilik.
 *
 * Status TIDAK bisa ditentukan bebas oleh pemilik: motor baru selalu
 * `pending` menunggu verifikasi admin (spesifikasi §9 & §23).
 */
class StoreMotorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Motor::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'merk' => ['required', 'string', 'max:100'],
            'tipe_cc' => ['required', 'string', Rule::in(Motor::TIPE_CC)],
            'no_plat' => ['required', 'string', 'max:15', 'unique:motors,no_plat'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'dokumen_kepemilikan' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'merk.required' => 'Merk motor wajib diisi.',
            'tipe_cc.required' => 'Tipe CC wajib dipilih.',
            'tipe_cc.in' => 'Tipe CC hanya boleh 100, 125, atau 150.',
            'no_plat.required' => 'Nomor plat wajib diisi.',
            'no_plat.unique' => 'Nomor plat sudah terdaftar.',
            'photo.required' => 'Foto motor wajib diunggah.',
            'photo.image' => 'Foto motor harus berupa gambar.',
            'photo.mimes' => 'Foto motor harus berformat jpg, jpeg, png, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'dokumen_kepemilikan.required' => 'Dokumen kepemilikan wajib diunggah.',
            'dokumen_kepemilikan.mimes' => 'Dokumen harus berformat gambar atau PDF.',
            'dokumen_kepemilikan.max' => 'Ukuran dokumen maksimal 4 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipe_cc' => 'tipe CC',
            'no_plat' => 'nomor plat',
            'photo' => 'foto motor',
            'dokumen_kepemilikan' => 'dokumen kepemilikan',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalisasi nomor plat agar "b 1234 xy" == "B 1234 XY".
        if ($this->filled('no_plat')) {
            $this->merge([
                'no_plat' => strtoupper(preg_replace('/\s+/', ' ', trim($this->input('no_plat')))),
            ]);
        }
    }
}
