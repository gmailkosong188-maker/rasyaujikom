@extends('layouts.dashboard')

@section('title', 'Dashboard Penyewa')

{{--
    Lingkup data: HANYA penyewaan & pembayaran milik user yang login.
    Motor tersedia adalah data publik yang boleh dilihat semua penyewa.
--}}
@section('dashboard')
    <div class="card">
        <h1>Dashboard Penyewa</h1>
        <p class="muted" style="margin:0">Ringkasan penyewaan Anda.</p>
    </div>

    <div class="stats">
        <x-stat label="Motor Tersedia" :value="$motorTersedia" hint="Siap disewa" />
        <x-stat label="Penyewaan Aktif" :value="$penyewaanAktif" />
        <x-stat label="Total Penyewaan" :value="$totalPenyewaan" />
        <x-stat label="Total Pembayaran" :value="'Rp ' . number_format($totalPembayaran, 0, ',', '.')"
            hint="Transaksi berstatus dibayar" />
        <x-stat label="Pembayaran Tertunda" :value="'Rp ' . number_format($pembayaranPending, 0, ',', '.')" />
    </div>

    <div class="card" style="margin-top:1.25rem">
        <h2 class="section-title">Riwayat Penyewaan</h2>

        @if ($riwayat->isEmpty())
            <p class="empty">Anda belum memiliki riwayat penyewaan.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Motor</th>
                        <th>Periode</th>
                        <th>Durasi</th>
                        <th>Harga</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($riwayat as $sewa)
                        @php
                            $transaksi = $sewa->transaksis->first();
                        @endphp
                        <tr>
                            <td>
                                {{ $sewa->motor?->merk ?? '-' }}
                                <span class="muted">({{ $sewa->motor?->no_plat ?? '-' }})</span>
                            </td>
                            <td>{{ $sewa->tanggal_mulai->format('d/m/Y') }} – {{ $sewa->tanggal_selesai->format('d/m/Y') }}</td>
                            <td>{{ ucfirst($sewa->tipe_durasi) }}</td>
                            <td class="money">Rp {{ number_format($sewa->harga, 0, ',', '.') }}</td>
                            <td>
                                @if ($transaksi)
                                    <x-badge :value="$transaksi->status" />
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td><x-badge :value="$sewa->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
