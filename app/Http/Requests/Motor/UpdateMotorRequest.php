<?php

namespace App\Http\Requests\Motor;

use App\Models\Motor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi edit motor oleh pemilik.
 *
 * `status` tidak diubah lewat form ini — perubahan status adalah
 * kewenangan admin atau proses bisnis lain (penyewaan/pengembalian).
 */
class UpdateMotorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $motor = $this->route('motor');

        return $motor instanceof Motor
            && ($this->user()?->can('update', $motor) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Motor $motor */
        $motor = $this->route('motor');

        return [
            'merk' => ['required', 'string', 'max:100'],
            'tipe_cc' => ['required', 'string', Rule::in(Motor::TIPE_CC)],
            'no_plat' => [
                'required', 'string', 'max:15',
                Rule::unique('motors', 'no_plat')->ignore($motor->id),
            ],
            // Saat edit, file opsional: hanya divalidasi jika diunggah ulang.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'dokumen_kepemilikan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'merk.required' => 'Merk motor wajib diisi.',
            'tipe_cc.in' => 'Tipe CC hanya boleh 100, 125, atau 150.',
            'no_plat.required' => 'Nomor plat wajib diisi.',
            'no_plat.unique' => 'Nomor plat sudah terdaftar.',
            'photo.image' => 'Foto motor harus berupa gambar.',
            'photo.mimes' => 'Foto motor harus berformat jpg, jpeg, png, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
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
        if ($this->filled('no_plat')) {
            $this->merge([
                'no_plat' => strtoupper(preg_replace('/\s+/', ' ', trim($this->input('no_plat')))),
            ]);
        }
    }
}
