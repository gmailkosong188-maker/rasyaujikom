@extends('layouts.dashboard')

@section('title', 'Detail Penyewaan')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Penyewaan #{{ $penyewaan->id }} <x-badge :value="$penyewaan->status" /></h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $penyewaan->motor?->merk }} &middot; {{ $penyewaan->motor?->no_plat }}
                </p>
            </div>
            <a href="{{ route('pemilik.penyewaan.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Penyewa</div>
                <div class="value">{{ $penyewaan->penyewa?->nama ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Periode</div>
                <div class="value">
                    {{ $penyewaan->tanggal_mulai->format('d/m/Y') }} – {{ $penyewaan->tanggal_selesai->format('d/m/Y') }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Tipe Durasi</div>
                <div class="value">{{ ucfirst($penyewaan->tipe_durasi) }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Harga Sewa</div>
                <div class="value money">Rp {{ number_format($penyewaan->harga, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Status</div>
                <div class="value"><x-badge :value="$penyewaan->status" /></div>
            </div>
            @if ($penyewaan->bagiHasil)
                <div class="detail-item">
                    <div class="label">Bagi Hasil Anda</div>
                    <div class="value money">Rp {{ number_format($penyewaan->bagiHasil->nominal_pemilik, 0, ',', '.') }}</div>
                </div>
            @endif
        </div>

        <p class="hint" style="margin-top:1rem;margin-bottom:0">
            Pemilik tidak dapat mengubah penyewaan. Proses konfirmasi dan pengembalian dilakukan admin.
        </p>
    </div>
@endsection
