{{-- Badge status dengan warna sesuai tipe. --}}
@props(['value'])

@php
    $map = [
        // motor
        'pending' => 'badge-warn',
        'tersedia' => 'badge-ok',
        'disewa' => 'badge-info',
        'perawatan' => 'badge-warn',
        'ditolak' => 'badge-danger',
        // penyewaan
        'dikonfirmasi' => 'badge-info',
        'berjalan' => 'badge-info',
        'selesai' => 'badge-ok',
        'dibatalkan' => 'badge-danger',
        // transaksi
        'dibayar' => 'badge-ok',
        'dikembalikan' => 'badge-info',
    ];
    $class = $map[$value] ?? 'badge-muted';
@endphp

<span class="badge {{ $class }}">{{ ucfirst($value) }}</span>
