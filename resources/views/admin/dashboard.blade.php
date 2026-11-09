@extends('layouts.dashboard')

@section('title', 'Dashboard Admin')

{{-- Lingkup data: SELURUH sistem (admin). --}}
@section('dashboard')
    <div class="card">
        <h1>Dashboard Admin</h1>
        <p class="muted" style="margin:0">Ringkasan operasional seluruh sistem.</p>
    </div>

    <div class="stats">
        <x-stat label="Jumlah Motor" :value="$jumlahMotor" />
        <x-stat label="Motor Tersedia" :value="$motorTersedia" />
        <x-stat label="Motor Disewa" :value="$motorDisewa" />
        <x-stat label="Jumlah Penyewaan" :value="$jumlahPenyewaan" />
        <x-stat label="Total Pembayaran" :value="'Rp ' . number_format($totalPembayaran, 0, ',', '.')"
            hint="Transaksi berstatus dibayar" />
    </div>

    <div class="stats" style="margin-top:1rem">
        <x-stat label="Total User" :value="$jumlahUser" />
        <x-stat label="Menunggu Verifikasi" :value="$motorPending" />
        <x-stat label="Perawatan" :value="$motorPerawatan" />
        <x-stat label="Penyewaan Aktif" :value="$penyewaanAktif" />
        <x-stat label="Motor Ditolak" :value="$motorDitolak" />
    </div>

    <div class="card" style="margin-top:1.25rem">
        <h2 class="section-title">Motor Terbaru</h2>

        @if ($motorTerbaru->isEmpty())
            <p class="empty">Belum ada motor terdaftar.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>No Plat</th>
                        <th>Merk</th>
                        <th>CC</th>
                        <th>Pemilik</th>
                        <th>Tarif Harian</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($motorTerbaru as $motor)
                        <tr>
                            <td>{{ $motor->no_plat }}</td>
                            <td>{{ $motor->merk }}</td>
                            <td>{{ $motor->tipe_cc }}</td>
                            <td>{{ $motor->pemilik?->nama ?? '-' }}</td>
                            <td class="money">
                                {{ $motor->tarifRental ? 'Rp ' . number_format($motor->tarifRental->tarif_harian, 0, ',', '.') : 'Belum ditentukan' }}
                            </td>
                            <td><x-badge :value="$motor->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
