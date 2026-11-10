{{-- Menu navigasi sidebar sesuai role user yang login. --}}
@php
    $user = auth()->user();
    $items = match ($user->role) {
        'admin' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard', 'match' => ['admin.dashboard']],
            ['label' => 'Data Motor', 'route' => 'admin.motor.index', 'icon' => 'bike', 'match' => ['admin.motor.index', 'admin.motor.show', 'admin.motor.dokumen']],
            ['label' => 'Verifikasi', 'route' => 'admin.motor.verifikasi', 'icon' => 'verify', 'match' => ['admin.motor.verifikasi']],
            ['label' => 'Tarif Rental', 'route' => 'admin.tarif.index', 'icon' => 'tag', 'match' => ['admin.tarif.*']],
            ['label' => 'Penyewaan', 'route' => 'admin.penyewaan.index', 'icon' => 'receipt', 'match' => ['admin.penyewaan.*']],
            ['label' => 'Pembayaran', 'route' => 'admin.transaksi.index', 'icon' => 'card', 'match' => ['admin.transaksi.*']],
            ['label' => 'Pengembalian', 'route' => 'admin.pengembalian.index', 'icon' => 'return', 'match' => ['admin.pengembalian.*']],
            ['label' => 'Bagi Hasil', 'route' => 'admin.bagi-hasil.index', 'icon' => 'pie', 'match' => ['admin.bagi-hasil.*']],
            ['label' => 'Laporan', 'route' => 'admin.laporan.index', 'icon' => 'chart', 'match' => ['admin.laporan.*']],
            ['label' => 'Data User', 'route' => 'admin.user.index', 'icon' => 'user', 'match' => ['admin.user.*']],
        ],
        'pemilik' => [
            ['label' => 'Dashboard', 'route' => 'pemilik.dashboard', 'icon' => 'dashboard', 'match' => ['pemilik.dashboard']],
            ['label' => 'Motor Saya', 'route' => 'pemilik.motor.index', 'icon' => 'bike', 'match' => ['pemilik.motor.index', 'pemilik.motor.show', 'pemilik.motor.edit']],
            ['label' => 'Tambah Motor', 'route' => 'pemilik.motor.create', 'icon' => 'plus-circle', 'match' => ['pemilik.motor.create']],
            ['label' => 'Tarif Rental', 'route' => 'pemilik.tarif.index', 'icon' => 'tag', 'match' => ['pemilik.tarif.*']],
            ['label' => 'Penyewaan', 'route' => 'pemilik.penyewaan.index', 'icon' => 'receipt', 'match' => ['pemilik.penyewaan.*']],
            ['label' => 'Laporan', 'route' => 'pemilik.laporan.index', 'icon' => 'chart', 'match' => ['pemilik.laporan.*']],
        ],
        'penyewa' => [
            ['label' => 'Dashboard', 'route' => 'penyewa.dashboard', 'icon' => 'dashboard', 'match' => ['penyewa.dashboard']],
            ['label' => 'Motor Tersedia', 'route' => 'penyewa.motor.index', 'icon' => 'bike', 'match' => ['penyewa.motor.*']],
            ['label' => 'Tarif Rental', 'route' => 'penyewa.tarif.index', 'icon' => 'tag', 'match' => ['penyewa.tarif.*']],
            ['label' => 'Penyewaan Saya', 'route' => 'penyewa.penyewaan.index', 'icon' => 'receipt', 'match' => ['penyewa.penyewaan.*']],
            ['label' => 'Pembayaran Saya', 'route' => 'penyewa.transaksi.index', 'icon' => 'card', 'match' => ['penyewa.transaksi.*']],
            ['label' => 'Riwayat', 'route' => 'penyewa.laporan.index', 'icon' => 'history', 'match' => ['penyewa.laporan.*']],
        ],
        default => [],
    };
@endphp

<nav class="sidebar-nav" aria-label="Navigasi utama">
    <p class="nav-section-label">Menu Utama</p>

    @foreach ($items as $item)
        @php
            $aktif = collect($item['match'] ?? [$item['route']])->contains(
                fn ($pola) => request()->routeIs($pola)
            );
        @endphp
        <a href="{{ route($item['route']) }}" class="nav-item {{ $aktif ? 'active' : '' }}"
            @if ($aktif) aria-current="page" @endif>
            <x-icon :name="$item['icon']" :size="18" />
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
