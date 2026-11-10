<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Semua request API selalu dianggap meminta JSON.
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Endpoint /api/* selalu mengembalikan JSON dengan format konsisten,
        // termasuk saat request tidak mengirim header Accept.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Data yang dikirim tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Belum terautentikasi. Sertakan token API yang valid.',
                'errors' => (object) [],
            ], 401);
        });

        // Menangani 403/404/405 dan HttpException lain (termasuk hasil
        // konversi AuthorizationException & ModelNotFoundException oleh Laravel).
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            $message = match (true) {
                $e->getPrevious() instanceof ModelNotFoundException => 'Data tidak ditemukan.',
                $status === 403 => 'Akses ditolak.',
                $status === 404 => 'Endpoint tidak ditemukan.',
                default => $e->getMessage() ?: 'Permintaan tidak dapat diproses.',
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => (object) [],
            ], $status);
        });
    })->create();
