<?php

namespace App\Http\Requests\TarifRental;

use App\Models\TarifRental;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi ubah tarif rental.
 *
 * `motor_id` tidak dapat diubah — memindahkan tarif ke motor lain
 * akan meninggalkan motor lama tanpa tarif. Untuk itu buat tarif baru.
 */
class UpdateTarifRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tarif = $this->route('tarif');

        return $tarif instanceof TarifRental
            && ($this->user()?->can('update', $tarif) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tarif_harian' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'tarif_mingguan' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'tarif_bulanan' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tarif_harian.required' => 'Tarif harian wajib diisi.',
            'tarif_harian.numeric' => 'Tarif harian harus berupa angka.',
            'tarif_harian.gt' => 'Tarif harian harus lebih dari 0.',
            'tarif_mingguan.required' => 'Tarif mingguan wajib diisi.',
            'tarif_mingguan.numeric' => 'Tarif mingguan harus berupa angka.',
            'tarif_mingguan.gt' => 'Tarif mingguan harus lebih dari 0.',
            'tarif_bulanan.required' => 'Tarif bulanan wajib diisi.',
            'tarif_bulanan.numeric' => 'Tarif bulanan harus berupa angka.',
            'tarif_bulanan.gt' => 'Tarif bulanan harus lebih dari 0.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tarif_harian' => 'tarif harian',
            'tarif_mingguan' => 'tarif mingguan',
            'tarif_bulanan' => 'tarif bulanan',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $harian = (float) $this->input('tarif_harian');
            $mingguan = (float) $this->input('tarif_mingguan');
            $bulanan = (float) $this->input('tarif_bulanan');

            if ($harian <= 0) {
                return;
            }

            if ($mingguan > 0 && $mingguan < $harian) {
                $validator->errors()->add('tarif_mingguan', 'Tarif mingguan tidak boleh lebih kecil dari tarif harian.');
            }

            if ($bulanan > 0 && $bulanan < $mingguan) {
                $validator->errors()->add('tarif_bulanan', 'Tarif bulanan tidak boleh lebih kecil dari tarif mingguan.');
            }
        });
    }
}
