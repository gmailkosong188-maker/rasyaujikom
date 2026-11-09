<?php

namespace App\Http\Requests\Penyewaan;

use App\Models\Motor;
use App\Models\Penyewaan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi pembuatan penyewaan oleh penyewa.
 *
 * PENTING: `harga` TIDAK boleh dikirim/dipercaya dari form — harga
 * selalu dihitung server-side dari tarif_rentals (spesifikasi §12).
 */
class StorePenyewaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Penyewaan::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motor_id' => ['required', 'integer', Rule::exists('motors', 'id')],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tipe_durasi' => ['required', 'string', Rule::in(Penyewaan::TIPE_DURASI)],
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
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date' => 'Tanggal mulai tidak valid.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.date' => 'Tanggal selesai tidak valid.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'tipe_durasi.required' => 'Tipe durasi wajib dipilih.',
            'tipe_durasi.in' => 'Tipe durasi harus harian, mingguan, atau bulanan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motor_id' => 'motor',
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
            'tipe_durasi' => 'tipe durasi',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $motor = Motor::with('tarifRental')->find($this->input('motor_id'));

            if (! $motor) {
                return; // sudah ditangani rule exists
            }

            // Motor harus berstatus tersedia.
            if (! $motor->isTersedia()) {
                $validator->errors()->add(
                    'motor_id',
                    "Motor ini tidak tersedia untuk disewa (status: {$motor->status})."
                );

                return;
            }

            // Tarif wajib sudah ditentukan admin.
            if (! $motor->tarifRental) {
                $validator->errors()->add(
                    'motor_id',
                    'Motor ini belum memiliki tarif rental sehingga belum dapat disewa.'
                );

                return;
            }

            $mulai = $this->input('tanggal_mulai');
            $selesai = $this->input('tanggal_selesai');

            if (! $mulai || ! $selesai || $selesai < $mulai) {
                return; // sudah ditangani rule tanggal
            }

            // Cegah bentrok dengan penyewaan aktif pada motor yang sama.
            $bentrok = Penyewaan::query()
                ->where('motor_id', $motor->id)
                ->bentrok($mulai, $selesai)
                ->exists();

            if ($bentrok) {
                $validator->errors()->add(
                    'motor_id',
                    'Motor ini sudah disewa pada periode tersebut. Pilih tanggal atau motor lain.'
                );
            }
        });
    }
}
