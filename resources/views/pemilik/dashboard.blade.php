@extends('layouts.dashboard')

@section('title', 'Dashboard Pemilik')

{{--
    Lingkup data: HANYA motor & pendapatan milik user yang login.
    Query dibatasi lewat $user->motors() / motors.pemilik_id = user ini.
--}}
@section('dashboard')
    <div class="card">
        <h1>Dashboard Pemilik</h1>
        <p class="muted" style="margin:0">Ringkasan motor dan pendapatan Anda.</p>
    </div>

    <div class="stats">
        <x-stat label="Motor Saya" :value="$jumlahMotor" />
        <x-stat label="Motor Tersedia" :value="$motorTersedia" />
        <x-stat label="Motor Disewa" :value="$motorDisewa" />
        <x-stat label="Pendapatan" :value="'Rp ' . number_format($pendapatan, 0, ',', '.')"
            hint="Bagi hasil yang sudah settled" />
    </div>

    <div class="stats" style="margin-top:1rem">
        <x-stat label="Menunggu Verifikasi" :value="$motorPending" />
        <x-stat label="Pendapatan Tertunda" :value="'Rp ' . number_format($pendapatanTertunda, 0, ',', '.')"
            hint="Belum settled" />
        <x-stat label="Total Penyewaan" :value="$jumlahPenyewaan" hint="Penyewaan atas motor Anda" />
    </div>

    <div class="card" style="margin-top:1.25rem">
        <h2 class="section-title">Motor Saya</h2>

        @if ($motorSaya->isEmpty())
            <p class="empty">Anda belum mendaftarkan motor.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>No Plat</th>
                        <th>Merk</th>
                        <th>CC</th>
                        <th>Tarif Harian</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($motorSaya as $motor)
                        <tr>
                            <td>{{ $motor->no_plat }}</td>
                            <td>{{ $motor->merk }}</td>
                            <td>{{ $motor->tipe_cc }}</td>
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
