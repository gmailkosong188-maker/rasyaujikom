<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi registrasi publik.
 *
 * `role` WAJIB salah satu dari ROLE_REGISTRABLE (pemilik/penyewa).
 * Dengan begitu user tidak bisa mendaftar sebagai admin, meskipun
 * mengirim field role=admin secara manual ke endpoint.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'no_tlpn' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'role' => ['required', 'string', Rule::in(User::ROLE_REGISTRABLE)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'no_tlpn.required' => 'Nomor telepon wajib diisi.',
            'no_tlpn.regex' => 'Nomor telepon hanya boleh berisi angka (8-20 digit).',
            'role.required' => 'Silakan pilih jenis akun.',
            'role.in' => 'Jenis akun yang dipilih tidak valid. Akun admin hanya dapat dibuat oleh administrator.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama',
            'no_tlpn' => 'nomor telepon',
        ];
    }
}
