<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Publik
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Guest (belum login)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated (semua role)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Pengalihan otomatis ke dashboard sesuai role.
    Route::get('/dashboard', [AuthController::class, 'redirectToDashboard'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        // Modul motor (admin): semua motor + verifikasi + ubah status.
        Route::get('/motor', [\App\Http\Controllers\Admin\MotorController::class, 'index'])->name('motor.index');
        Route::get('/motor/verifikasi', [\App\Http\Controllers\Admin\MotorController::class, 'verifikasi'])->name('motor.verifikasi');
        Route::get('/motor/{motor}', [\App\Http\Controllers\Admin\MotorController::class, 'show'])->name('motor.show');
        Route::get('/motor/{motor}/dokumen', [\App\Http\Controllers\Admin\MotorController::class, 'dokumen'])->name('motor.dokumen');
        Route::post('/motor/{motor}/terima', [\App\Http\Controllers\Admin\MotorController::class, 'terima'])->name('motor.terima');
        Route::post('/motor/{motor}/tolak', [\App\Http\Controllers\Admin\MotorController::class, 'tolak'])->name('motor.tolak');
        Route::patch('/motor/{motor}/status', [\App\Http\Controllers\Admin\MotorController::class, 'updateStatus'])->name('motor.status');

        // Modul tarif rental (admin): satu-satunya tempat penentuan tarif.
        Route::get('/tarif', [\App\Http\Controllers\Admin\TarifRentalController::class, 'index'])->name('tarif.index');
        Route::get('/tarif/create', [\App\Http\Controllers\Admin\TarifRentalController::class, 'create'])->name('tarif.create');
        Route::post('/tarif', [\App\Http\Controllers\Admin\TarifRentalController::class, 'store'])->name('tarif.store');
        Route::get('/tarif/{tarif}', [\App\Http\Controllers\Admin\TarifRentalController::class, 'show'])->name('tarif.show');
        Route::get('/tarif/{tarif}/edit', [\App\Http\Controllers\Admin\TarifRentalController::class, 'edit'])->name('tarif.edit');
        Route::put('/tarif/{tarif}', [\App\Http\Controllers\Admin\TarifRentalController::class, 'update'])->name('tarif.update');
        Route::delete('/tarif/{tarif}', [\App\Http\Controllers\Admin\TarifRentalController::class, 'destroy'])->name('tarif.destroy');

        // Modul penyewaan (admin): lihat & proses status.
        Route::get('/penyewaan', [\App\Http\Controllers\Admin\PenyewaanController::class, 'index'])->name('penyewaan.index');
        Route::get('/penyewaan/{penyewaan}', [\App\Http\Controllers\Admin\PenyewaanController::class, 'show'])->name('penyewaan.show');
        Route::patch('/penyewaan/{penyewaan}/status', [\App\Http\Controllers\Admin\PenyewaanController::class, 'proses'])->name('penyewaan.proses');

        // Modul pembayaran (admin): lihat & verifikasi transaksi.
        Route::get('/transaksi', [\App\Http\Controllers\Admin\TransaksiController::class, 'index'])->name('transaksi.index');
        Route::get('/transaksi/{transaksi}', [\App\Http\Controllers\Admin\TransaksiController::class, 'show'])->name('transaksi.show');
        Route::patch('/transaksi/{transaksi}/status', [\App\Http\Controllers\Admin\TransaksiController::class, 'proses'])->name('transaksi.proses');

        // Modul pengembalian motor (admin): pemeriksaan & konfirmasi.
        Route::get('/pengembalian', [\App\Http\Controllers\Admin\PengembalianController::class, 'index'])->name('pengembalian.index');
        Route::patch('/pengembalian/{penyewaan}/konfirmasi', [\App\Http\Controllers\Admin\PengembalianController::class, 'konfirmasi'])->name('pengembalian.konfirmasi');

        // Modul bagi hasil (admin): lihat, detail, dan catat settlement.
        Route::get('/bagi-hasil', [\App\Http\Controllers\Admin\BagiHasilController::class, 'index'])->name('bagi-hasil.index');
        Route::get('/bagi-hasil/{bagiHasil}', [\App\Http\Controllers\Admin\BagiHasilController::class, 'show'])->name('bagi-hasil.show');
        Route::post('/bagi-hasil/penyewaan/{penyewaan}', [\App\Http\Controllers\Admin\BagiHasilController::class, 'catat'])->name('bagi-hasil.catat');
        Route::patch('/bagi-hasil/{bagiHasil}/cairkan', [\App\Http\Controllers\Admin\BagiHasilController::class, 'cairkan'])->name('bagi-hasil.cairkan');

        // Modul laporan (admin): seluruh data sistem.
        Route::get('/laporan', [\App\Http\Controllers\Admin\LaporanController::class, 'index'])->name('laporan.index');

        // Modul user (admin): CRUD user.
        Route::get('/user', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('user.index');
        Route::get('/user/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('user.create');
        Route::post('/user', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('user.store');
        Route::get('/user/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('user.edit');
        Route::put('/user/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('user.update');
        Route::delete('/user/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('user.destroy');
    });

/*
|--------------------------------------------------------------------------
| Pemilik
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pemilik'])
    ->prefix('pemilik')
    ->name('pemilik.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'pemilik'])->name('dashboard');

        // Modul motor (pemilik): CRUD terbatas pada motor miliknya.
        Route::get('/motor', [\App\Http\Controllers\Pemilik\MotorController::class, 'index'])->name('motor.index');
        Route::get('/motor/create', [\App\Http\Controllers\Pemilik\MotorController::class, 'create'])->name('motor.create');
        Route::post('/motor', [\App\Http\Controllers\Pemilik\MotorController::class, 'store'])->name('motor.store');
        Route::get('/motor/{motor}', [\App\Http\Controllers\Pemilik\MotorController::class, 'show'])->name('motor.show');
        Route::get('/motor/{motor}/edit', [\App\Http\Controllers\Pemilik\MotorController::class, 'edit'])->name('motor.edit');
        Route::put('/motor/{motor}', [\App\Http\Controllers\Pemilik\MotorController::class, 'update'])->name('motor.update');
        Route::post('/motor/{motor}/aktifkan', [\App\Http\Controllers\Pemilik\MotorController::class, 'aktifkan'])->name('motor.aktifkan');
        Route::post('/motor/{motor}/nonaktifkan', [\App\Http\Controllers\Pemilik\MotorController::class, 'nonaktifkan'])->name('motor.nonaktifkan');
        Route::delete('/motor/{motor}', [\App\Http\Controllers\Pemilik\MotorController::class, 'destroy'])->name('motor.destroy');

        // Tarif rental (pemilik): READ-ONLY, hanya motor miliknya.
        Route::get('/tarif', [\App\Http\Controllers\TarifRentalViewController::class, 'pemilik'])->name('tarif.index');
        Route::get('/tarif/{tarif}', [\App\Http\Controllers\TarifRentalViewController::class, 'show'])->name('tarif.show');

        // Modul penyewaan (pemilik): READ-ONLY, hanya atas motor miliknya.
        Route::get('/penyewaan', [\App\Http\Controllers\Pemilik\PenyewaanController::class, 'index'])->name('penyewaan.index');
        Route::get('/penyewaan/{penyewaan}', [\App\Http\Controllers\Pemilik\PenyewaanController::class, 'show'])->name('penyewaan.show');

        // Modul laporan (pemilik): hanya data motor miliknya.
        Route::get('/laporan', [\App\Http\Controllers\Pemilik\LaporanController::class, 'index'])->name('laporan.index');
    });

/*
|--------------------------------------------------------------------------
| Penyewa
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:penyewa'])
    ->prefix('penyewa')
    ->name('penyewa.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'penyewa'])->name('dashboard');

        // Modul motor (penyewa): hanya motor tersedia.
        Route::get('/motor', [\App\Http\Controllers\Penyewa\MotorController::class, 'index'])->name('motor.index');
        Route::get('/motor/{motor}', [\App\Http\Controllers\Penyewa\MotorController::class, 'show'])->name('motor.show');

        // Tarif rental (penyewa): READ-ONLY, hanya motor tersedia.
        Route::get('/tarif', [\App\Http\Controllers\TarifRentalViewController::class, 'penyewa'])->name('tarif.index');
        Route::get('/tarif/{tarif}', [\App\Http\Controllers\TarifRentalViewController::class, 'show'])->name('tarif.show');

        // Modul penyewaan (penyewa): buat, lihat, batalkan miliknya sendiri.
        Route::get('/penyewaan', [\App\Http\Controllers\Penyewa\PenyewaanController::class, 'index'])->name('penyewaan.index');
        Route::get('/penyewaan/create', [\App\Http\Controllers\Penyewa\PenyewaanController::class, 'create'])->name('penyewaan.create');
        Route::post('/penyewaan', [\App\Http\Controllers\Penyewa\PenyewaanController::class, 'store'])->name('penyewaan.store');
        Route::get('/penyewaan/{penyewaan}', [\App\Http\Controllers\Penyewa\PenyewaanController::class, 'show'])->name('penyewaan.show');
        Route::patch('/penyewaan/{penyewaan}/batalkan', [\App\Http\Controllers\Penyewa\PenyewaanController::class, 'batalkan'])->name('penyewaan.batalkan');

        // Modul pembayaran (penyewa): buat & lihat transaksi miliknya.
        Route::get('/transaksi', [\App\Http\Controllers\Penyewa\TransaksiController::class, 'index'])->name('transaksi.index');
        Route::get('/transaksi/create', [\App\Http\Controllers\Penyewa\TransaksiController::class, 'create'])->name('transaksi.create');
        Route::post('/transaksi', [\App\Http\Controllers\Penyewa\TransaksiController::class, 'store'])->name('transaksi.store');
        Route::get('/transaksi/{transaksi}', [\App\Http\Controllers\Penyewa\TransaksiController::class, 'show'])->name('transaksi.show');

        // Modul pengembalian motor (penyewa): ajukan pengembalian.
        Route::patch('/penyewaan/{penyewaan}/kembalikan', [\App\Http\Controllers\Penyewa\PenyewaanController::class, 'ajukanPengembalian'])->name('penyewaan.kembalikan');

        // Modul laporan (penyewa): riwayat penyewaan & pembayaran miliknya.
        Route::get('/laporan', [\App\Http\Controllers\Penyewa\LaporanController::class, 'index'])->name('laporan.index');
    });
