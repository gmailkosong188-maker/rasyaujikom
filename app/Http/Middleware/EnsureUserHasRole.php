<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route berdasarkan role user.
 *
 * Dipakai di route group:
 *   ->middleware('role:admin')
 *   ->middleware('role:pemilik')
 *   ->middleware('role:penyewa')
 *
 * Authorization dilakukan di server (bukan hanya menyembunyikan menu
 * di Blade) sesuai spesifikasi §19 dan §21.
 */
class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  role yang diizinkan
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Belum login -> arahkan ke halaman login.
        if (! $user instanceof User) {
            return redirect()->guest(route('login'));
        }

        // Role tidak cocok -> tolak (403) atau arahkan ke dashboard miliknya.
        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }

            return redirect()
                ->route($user->dashboardRoute())
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
