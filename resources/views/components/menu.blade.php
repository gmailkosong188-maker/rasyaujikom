{{-- Menu navigasi sesuai role user yang login. --}}
@php
    $user = auth()->user();
    $items = match ($user->role) {
        'admin' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
            ['label' => 'Data Motor', 'route' => 'admin.motor.index'],
            ['label' => 'Verifikasi', 'route' => 'admin.motor.verifikasi'],
            ['label' => 'Tarif Rental', 'route' => 'admin.tarif.index'],
            ['label' => 'Penyewaan', 'route' => 'admin.penyewaan.index'],
            ['label' => 'Pembayaran', 'route' => 'admin.transaksi.index'],
            ['label' => 'Pengembalian', 'route' => 'admin.pengembalian.index'],
            ['label' => 'Bagi Hasil', 'route' => 'admin.bagi-hasil.index'],
            ['label' => 'Laporan', 'route' => 'admin.laporan.index'],
        ],
        'pemilik' => [
            ['label' => 'Dashboard', 'route' => 'pemilik.dashboard'],
            ['label' => 'Motor Saya', 'route' => 'pemilik.motor.index'],
            ['label' => 'Tambah Motor', 'route' => 'pemilik.motor.create'],
            ['label' => 'Tarif Rental', 'route' => 'pemilik.tarif.index'],
            ['label' => 'Penyewaan', 'route' => 'pemilik.penyewaan.index'],
            ['label' => 'Laporan', 'route' => 'pemilik.laporan.index'],
        ],
        'penyewa' => [
            ['label' => 'Dashboard', 'route' => 'penyewa.dashboard'],
            ['label' => 'Motor Tersedia', 'route' => 'penyewa.motor.index'],
            ['label' => 'Tarif Rental', 'route' => 'penyewa.tarif.index'],
            ['label' => 'Penyewaan Saya', 'route' => 'penyewa.penyewaan.index'],
            ['label' => 'Pembayaran Saya', 'route' => 'penyewa.transaksi.index'],
            ['label' => 'Riwayat', 'route' => 'penyewa.laporan.index'],
        ],
        default => [],
    };
@endphp

<div class="menu">
    @foreach ($items as $item)
        <a href="{{ route($item['route']) }}"
            class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">{{ $item['label'] }}</a>
    @endforeach
</div>
