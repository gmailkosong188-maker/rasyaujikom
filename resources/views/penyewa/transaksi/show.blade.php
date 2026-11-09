@extends('layouts.dashboard')

@section('title', 'Detail Pembayaran')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Pembayaran #{{ $transaksi->id }} <x-badge :value="$transaksi->status" /></h1>
                <p class="muted" style="margin:.25rem 0 0">
                    Penyewaan #{{ $transaksi->penyewaan?->id }} &middot;
                    {{ $transaksi->penyewaan?->motor?->no_plat ?? '-' }}
                </p>
            </div>
            <a href="{{ route('penyewa.transaksi.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        @if ($transaksi->status === \App\Models\Transaksi::STATUS_PENDING)
            <div class="alert alert-info" style="margin-top:1.25rem">
                Pembayaran tercatat dan menunggu verifikasi admin.
            </div>
        @elseif ($transaksi->status === \App\Models\Transaksi::STATUS_DIBAYAR)
            <div class="alert alert-success" style="margin-top:1.25rem">Pembayaran telah diverifikasi admin.</div>
        @elseif ($transaksi->status === \App\Models\Transaksi::STATUS_DITOLAK)
            <div class="alert alert-error" style="margin-top:1.25rem">
                Pembayaran ditolak. Silakan hubungi admin atau lakukan pembayaran ulang.
            </div>
        @else
            <div class="alert alert-error" style="margin-top:1.25rem">Dana telah dikembalikan.</div>
        @endif

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Jumlah</div>
                <div class="value money">Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Metode Pembayaran</div>
                <div class="value">{{ ucfirst($transaksi->metode_pembayaran) }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tanggal Transaksi</div>
                <div class="value">{{ $transaksi->tanggal->format('d/m/Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Harga Penyewaan</div>
                <div class="value money">Rp {{ number_format($transaksi->penyewaan?->harga ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Periode Sewa</div>
                <div class="value">
                    {{ $transaksi->penyewaan?->tanggal_mulai?->format('d/m/Y') }} –
                    {{ $transaksi->penyewaan?->tanggal_selesai?->format('d/m/Y') }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Status Penyewaan</div>
                <div class="value"><x-badge :value="$transaksi->penyewaan?->status ?? 'pending'" /></div>
            </div>
        </div>
    </div>
@endsection
