<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard per role.
 *
 * Setiap aksi hanya membaca data yang boleh diakses role tersebut;
 * batas akses ditegakkan lagi oleh middleware `role:*` pada route.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function admin(Request $request): View
    {
        return view('admin.dashboard', [
            'user' => $request->user(),
            ...$this->dashboard->admin(),
        ]);
    }

    public function pemilik(Request $request): View
    {
        return view('pemilik.dashboard', [
            'user' => $request->user(),
            ...$this->dashboard->pemilik($request->user()),
        ]);
    }

    public function penyewa(Request $request): View
    {
        return view('penyewa.dashboard', [
            'user' => $request->user(),
            ...$this->dashboard->penyewa($request->user()),
        ]);
    }
}
