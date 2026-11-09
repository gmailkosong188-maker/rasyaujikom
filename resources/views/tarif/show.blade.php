@extends('layouts.dashboard')

@section('title', 'Detail Tarif')

@section('dashboard')
    @php
        $backRoute = auth()->user()->isPemilik() ? 'pemilik.tarif.index' : 'penyewa.tarif.index';
    @endphp

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Detail Tarif</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $tarif->motor?->merk }} &middot; {{ $tarif->motor?->no_plat }} &middot;
                    {{ $tarif->motor?->tipe_cc }} CC
                </p>
            </div>
            <a href="{{ route($backRoute) }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Tarif Harian</div>
                <div class="value money">Rp {{ number_format($tarif->tarif_harian, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tarif Mingguan</div>
                <div class="value money">Rp {{ number_format($tarif->tarif_mingguan, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tarif Bulanan</div>
                <div class="value money">Rp {{ number_format($tarif->tarif_bulanan, 0, ',', '.') }}</div>
            </div>
        </div>

        <p class="hint" style="margin-top:1rem">
            Tarif hanya dapat diubah oleh admin. Untuk menyewa motor, buka halaman detail motor.
        </p>
    </div>
@endsection
