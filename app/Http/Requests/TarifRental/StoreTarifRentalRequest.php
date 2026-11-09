<?php

namespace App\Http\Requests\TarifRental;

use App\Models\Motor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi penentuan tarif rental oleh admin.
 *
 * Aturan (spesifikasi §4.3 & §22):
 * - motor harus ada dan sudah diverifikasi admin (bukan pending/ditolak)
 * - satu motor hanya boleh punya satu tarif (motor_id unique)
 * - ketiga tarif wajib angka dan > 0
 * - tarif mingguan/bulanan tidak boleh lebih murah dari tarif harian
 */
class StoreTarifRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\TarifRental::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motor_id' => [
                'required',
                'integer',
                Rule::exists('motors', 'id'),
                Rule::unique('tarif_rentals', 'motor_id'),
            ],
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
            'motor_id.required' => 'Motor wajib dipilih.',
            'motor_id.exists' => 'Motor tidak ditemukan.',
            'motor_id.unique' => 'Motor ini sudah memiliki tarif rental. Gunakan menu ubah tarif.',
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
            'motor_id' => 'motor',
            'tarif_harian' => 'tarif harian',
            'tarif_mingguan' => 'tarif mingguan',
            'tarif_bulanan' => 'tarif bulanan',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $motor = Motor::find($this->input('motor_id'));

            // Motor yang belum diverifikasi belum boleh diberi tarif,
            // karena motor pending/ditolak tidak dapat disewakan.
            if ($motor && in_array($motor->status, [Motor::STATUS_PENDING, Motor::STATUS_DITOLAK], true)) {
                $validator->errors()->add(
                    'motor_id',
                    'Motor berstatus "'.$motor->status.'" belum dapat diberi tarif sebelum diverifikasi admin.'
                );
            }

            $this->validasiKonsistensiTarif($validator);
        });
    }

    /**
     * Tarif periode lebih panjang tidak boleh lebih mahal daripada
     * kelipatan tarif harian — sekaligus mencegah salah input.
     */
    protected function validasiKonsistensiTarif(Validator $validator): void
    {
        $harian = (float) $this->input('tarif_harian');
        $mingguan = (float) $this->input('tarif_mingguan');
        $bulanan = (float) $this->input('tarif_bulanan');

        if ($harian <= 0) {
            return; // sudah ditangani rule gt:0
        }

        if ($mingguan > 0 && $mingguan < $harian) {
            $validator->errors()->add('tarif_mingguan', 'Tarif mingguan tidak boleh lebih kecil dari tarif harian.');
        }

        if ($bulanan > 0 && $bulanan < $mingguan) {
            $validator->errors()->add('tarif_bulanan', 'Tarif bulanan tidak boleh lebih kecil dari tarif mingguan.');
        }
    }
}
