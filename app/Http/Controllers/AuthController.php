<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Form login.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        return redirect()
            ->intended($request->user()->dashboardUrl())
            ->with('success', 'Selamat datang, '.$request->user()->nama.'!');
    }

    /**
     * Form registrasi (hanya pemilik & penyewa).
     */
    public function showRegister(): View
    {
        return view('auth.register', [
            'roles' => User::ROLE_REGISTRABLE,
        ]);
    }

    /**
     * Proses registrasi.
     *
     * Role sudah dibatasi oleh RegisterRequest sehingga `admin`
     * tidak dapat dipilih dari form maupun dikirim manual.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Lapisan pengaman kedua: buang role apa pun di luar daftar yang
        // boleh registrasi, walaupun validasi sudah menolaknya.
        if (! in_array($data['role'], User::ROLE_REGISTRABLE, true)) {
            abort(403, 'Akun admin hanya dapat dibuat oleh administrator.');
        }

        $user = DB::transaction(function () use ($data) {
            return User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'no_tlpn' => $data['no_tlpn'],
                'role' => $data['role'],
                // Cast 'hashed' pada model User meng-hash otomatis.
                'password' => $data['password'],
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route($user->dashboardRoute())
            ->with('success', 'Registrasi berhasil. Selamat datang, '.$user->nama.'!');
    }

    /**
     * Logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda telah keluar dari sistem.');
    }

    /**
     * Arahkan user yang sudah login ke dashboard sesuai role.
     */
    public function redirectToDashboard(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRoute());
    }

    /**
     * Batasi percobaan login untuk mencegah brute force.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}
