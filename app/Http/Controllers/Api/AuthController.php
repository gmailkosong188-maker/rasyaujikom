<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Autentikasi API berbasis token Laravel Sanctum.
 *
 * Aturan registrasi tetap sama dengan website: role hanya pemilik/penyewa,
 * admin tidak dapat mendaftar sendiri (User::ROLE_REGISTRABLE).
 */
class AuthController extends ApiController
{
    /**
     * Registrasi pengguna baru + token API.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Lapisan pengaman kedua, sama seperti AuthController web.
        if (! in_array($data['role'], User::ROLE_REGISTRABLE, true)) {
            return $this->gagal('Akun admin hanya dapat dibuat oleh administrator.', [
                'role' => ['Jenis akun yang dipilih tidak valid.'],
            ], 403);
        }

        $user = DB::transaction(fn () => User::create([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'no_tlpn' => $data['no_tlpn'],
            'role' => $data['role'],
            // Cast 'hashed' pada model User meng-hash otomatis.
            'password' => $data['password'],
        ]));

        $token = $user->createToken($this->namaToken($request))->plainTextToken;

        return $this->sukses([
            'user' => $this->dataUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Registrasi berhasil.', 201);
    }

    /**
     * Login pengguna + token API.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return $this->gagal('Email atau password salah.', [
                'email' => ['Email atau password salah.'],
            ], 401);
        }

        /** @var User $user */
        $user = $request->user();
        $token = $user->createToken($this->namaToken($request))->plainTextToken;

        return $this->sukses([
            'user' => $this->dataUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil.');
    }

    /**
     * Logout: hapus token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->sukses(null, 'Logout berhasil. Token API telah dihapus.');
    }

    private function namaToken(Request $request): string
    {
        return $request->userAgent() ?: 'api-token';
    }
}
