<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Semua request pada grup middleware `api` diperlakukan sebagai JSON.
 *
 * Dengan begitu middleware `auth:sanctum` dan `role:*` yang sudah ada
 * mengembalikan 401/403 dalam bentuk JSON, bukan redirect ke halaman login.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
