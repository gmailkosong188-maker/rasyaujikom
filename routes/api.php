<?php

use App\Http\Controllers\Api\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\Admin\MotorController as AdminMotorController;
use App\Http\Controllers\Api\Admin\OwnerController as AdminOwnerController;
use App\Http\Controllers\Api\Admin\PencairanController as AdminPencairanController;
use App\Http\Controllers\Api\Admin\RenterController as AdminRenterController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MotorController;
use App\Http\Controllers\Api\Owner\MotorController as OwnerMotorController;
use App\Http\Controllers\Api\Owner\RevenueController as OwnerRevenueController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API
|--------------------------------------------------------------------------
|
| Autentikasi memakai token Laravel Sanctum (auth:sanctum). Semua endpoint
| menjaga aturan bisnis yang sama dengan website: validasi memakai Form
| Request yang ada, dan logikanya memakai Service/Model yang sudah dipakai
| halaman Blade.
|
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    /*
    |----------------------------------------------------------------------
    | Pemilik motor
    |----------------------------------------------------------------------
    */
    Route::prefix('owner')->middleware('role:pemilik')->group(function () {
        Route::get('motors', [OwnerMotorController::class, 'index']);
        Route::post('motors', [OwnerMotorController::class, 'store']);
        Route::post('motors/{motor}/aktifkan', [OwnerMotorController::class, 'aktifkan']);
        Route::post('motors/{motor}/nonaktifkan', [OwnerMotorController::class, 'nonaktifkan']);
        Route::get('revenue', [OwnerRevenueController::class, 'index']);
    });

    /*
    |----------------------------------------------------------------------
    | Penyewa
    |----------------------------------------------------------------------
    */
    Route::middleware('role:penyewa')->group(function () {
        Route::get('motors', [MotorController::class, 'index']);

        Route::get('bookings/history', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);

        Route::post('payments', [PaymentController::class, 'store']);
    });

    /*
    |----------------------------------------------------------------------
    | Admin
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::patch('motors/{motor}/verify', [AdminMotorController::class, 'verify']);
        Route::patch('motors/{motor}/reject', [AdminMotorController::class, 'tolak']);
        Route::patch('motors/{motor}/tolak', [AdminMotorController::class, 'tolak']);
        Route::post('motors/{motor}/reject', [AdminMotorController::class, 'tolak']);
        Route::post('motors/{motor}/tolak', [AdminMotorController::class, 'tolak']);
        Route::patch('bookings/{penyewaan}/confirm', [AdminBookingController::class, 'confirm']);
        Route::get('reports/revenue', [AdminReportController::class, 'revenue']);

        // CRUD user oleh admin.
        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users', [AdminUserController::class, 'store']);
        Route::get('users/{user}', [AdminUserController::class, 'show']);
        Route::put('users/{user}', [AdminUserController::class, 'update']);
        Route::delete('users/{user}', [AdminUserController::class, 'destroy']);

        // Monitoring pemilik motor.
        Route::get('owners', [AdminOwnerController::class, 'index']);
        Route::get('owners/{user}', [AdminOwnerController::class, 'show']);

        // Monitoring penyewa.
        Route::get('renters', [AdminRenterController::class, 'index']);
        Route::get('renters/{user}', [AdminRenterController::class, 'show']);

        // Pencairan dana bagi hasil.
        Route::get('pencairan', [AdminPencairanController::class, 'index']);
        Route::get('pencairan/riwayat', [AdminPencairanController::class, 'riwayat']);
        Route::patch('pencairan/{bagiHasil}/cairkan', [AdminPencairanController::class, 'cairkan']);
    });
});
