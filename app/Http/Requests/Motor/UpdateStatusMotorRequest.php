<?php

namespace App\Http\Requests\Motor;

use App\Models\Motor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi perubahan status motor oleh admin.
 *
 * Menolak status yang tidak dikenal dan transisi yang tidak sah
 * (mis. `disewa` -> `pending`).
 */
class UpdateStatusMotorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $motor = $this->route('motor');

        return $motor instanceof Motor
            && ($this->user()?->can('ubahStatus', $motor) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Motor::STATUSES)],
            'alasan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
            'alasan.max' => 'Alasan maksimal 500 karakter.',
        ];
    }

    /**
     * Transisi status harus sah menurut aturan bisnis.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Motor $motor */
            $motor = $this->route('motor');
            $tujuan = $this->input('status');

            if ($tujuan && ! $motor->bolehBerubahKe($tujuan)) {
                $validator->errors()->add(
                    'status',
                    "Status tidak dapat diubah dari \"{$motor->status}\" ke \"{$tujuan}\"."
                );
            }
        });
    }
}
