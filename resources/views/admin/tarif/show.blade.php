@extends('layouts.dashboard')

@section('title', 'Detail Tarif Rental')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Detail Tarif Rental</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $tarif->motor?->no_plat }} &middot; {{ $tarif->motor?->merk }}
                    <x-badge :value="$tarif->motor?->status ?? 'pending'" />
                </p>
            </div>
            <a href="{{ route('admin.tarif.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Pemilik</div>
                <div class="value">{{ $tarif->motor?->pemilik?->nama ?? '-' }}</div>
            </div>
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
            <div class="detail-item">
                <div class="label">Dibuat</div>
                <div class="value">{{ $tarif->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Terakhir Diubah</div>
                <div class="value">{{ $tarif->updated_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Simulasi Biaya Sewa</h2>
        <p class="hint" style="margin-top:0">
            Contoh perhitungan yang nantinya dipakai modul penyewaan
            (periode dihitung inklusif, dibulatkan ke atas).
        </p>
        <table class="data">
            <thead>
                <tr><th>Durasi</th><th>Perhitungan</th><th>Biaya</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>1 hari (harian)</td>
                    <td>1 &times; tarif harian</td>
                    <td class="money">Rp {{ number_format($tarif->tarif_harian, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>7 hari (mingguan)</td>
                    <td>1 &times; tarif mingguan</td>
                    <td class="money">Rp {{ number_format($tarif->tarif_mingguan, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>30 hari (bulanan)</td>
                    <td>1 &times; tarif bulanan</td>
                    <td class="money">Rp {{ number_format($tarif->tarif_bulanan, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @can('update', $tarif)
        <div class="card">
            <div class="btn-row" style="margin-top:0">
                <a href="{{ route('admin.tarif.edit', $tarif) }}" class="btn btn-sm">Ubah Tarif</a>

                @can('delete', $tarif)
                    <form method="POST" action="{{ route('admin.tarif.destroy', $tarif) }}" class="inline"
                        onsubmit="return confirm('Hapus tarif motor {{ $tarif->motor?->no_plat }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Hapus Tarif</button>
                    </form>
                @else
                    <p class="hint" style="margin:0;align-self:center">
                        Tarif tidak dapat dihapus saat motor sedang disewa.
                    </p>
                @endcan
            </div>
        </div>
    @endcan
@endsection
