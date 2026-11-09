@extends('layouts.dashboard')

@section('title', 'Detail Motor')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">{{ $motor->merk }} <x-badge :value="$motor->status" /></h1>
                <p class="muted" style="margin:.25rem 0 0">{{ $motor->no_plat }} &middot; {{ $motor->tipe_cc }} CC</p>
            </div>
            <a href="{{ route('penyewa.motor.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>
    </div>

    <div class="card">
        <div class="detail-grid">
            <div class="detail-item" style="grid-column: span 2">
                @if ($motor->photo)
                    <img src="{{ $motor->urlPhoto() }}" alt="{{ $motor->merk }}"
                        style="width:100%;max-width:420px;border-radius:.5rem;border:1px solid var(--border)">
                @else
                    <p class="empty">Foto belum tersedia.</p>
                @endif
            </div>

            <div>
                <div class="detail-item" style="margin-bottom:1rem">
                    <div class="label">Merk</div>
                    <div class="value">{{ $motor->merk }}</div>
                </div>
                <div class="detail-item" style="margin-bottom:1rem">
                    <div class="label">Tipe CC</div>
                    <div class="value">{{ $motor->tipe_cc }} CC</div>
                </div>
                <div class="detail-item" style="margin-bottom:1rem">
                    <div class="label">Nomor Plat</div>
                    <div class="value">{{ $motor->no_plat }}</div>
                </div>
                <div class="detail-item" style="margin-bottom:1rem">
                    <div class="label">Pemilik</div>
                    <div class="value">{{ $motor->pemilik?->nama ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Tarif Sewa</h2>

        @if ($motor->tarifRental)
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="label">Harian</div>
                    <div class="value money">Rp {{ number_format($motor->tarifRental->tarif_harian, 0, ',', '.') }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Mingguan</div>
                    <div class="value money">Rp {{ number_format($motor->tarifRental->tarif_mingguan, 0, ',', '.') }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Bulanan</div>
                    <div class="value money">Rp {{ number_format($motor->tarifRental->tarif_bulanan, 0, ',', '.') }}</div>
                </div>
            </div>
        @else
            <p class="empty" style="margin:0">Tarif untuk motor ini belum ditentukan admin.</p>
        @endif

        <p class="hint" style="margin-top:1rem">
            @if ($motor->tarifRental)
                <a href="{{ route('penyewa.penyewaan.create', ['motor_id' => $motor->id]) }}" class="btn">Sewa Motor Ini</a>
            @else
                Tarif untuk motor ini belum ditentukan admin, sehingga belum dapat disewa.
            @endif
        </p>
    </div>
@endsection
