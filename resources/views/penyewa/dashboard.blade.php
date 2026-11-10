@extends('layouts.dashboard')

@section('title', 'Dashboard Penyewa')

{{--
    Lingkup data: HANYA penyewaan & pembayaran milik user yang login.
    Motor tersedia adalah data publik yang boleh dilihat semua penyewa.
--}}
@section('dashboard')
    <div class="page-head">
        <div>
            <p class="page-eyebrow">Ringkasan Sewa</p>
            <h1 class="page-title">Dashboard Penyewa</h1>
            <p class="page-desc">Pantau penyewaan, pembayaran, dan motor yang siap disewa.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('penyewa.motor.index') }}" class="btn btn-ghost btn-sm">
                <x-icon name="bike" :size="16" /> Motor Tersedia
            </a>
            <a href="{{ route('penyewa.penyewaan.create') }}" class="btn btn-sm">
                <x-icon name="plus" :size="16" /> Sewa Motor
            </a>
        </div>
    </div>

    <div class="stats">
        <x-stat label="Total Penyewaan" :value="$totalPenyewaan" icon="receipt" hint="Seluruh penyewaan Anda" />
        <x-stat label="Penyewaan Aktif" :value="$penyewaanAktif" icon="clock" tone="blue" hint="Berjalan atau menunggu" />
        <x-stat label="Menunggu Konfirmasi" :value="$menungguKonfirmasi" icon="alert" tone="amber"
            hint="Menunggu persetujuan admin" />
        <x-stat label="Total Dibayar" :value="'Rp ' . number_format($totalPembayaran, 0, ',', '.')" icon="wallet"
            tone="green" hint="Pembayaran berstatus lunas" />
    </div>

    <div class="dashboard-grid dashboard-grid-2">
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Riwayat Penyewaan</h2>
                    <p class="card-sub">Penyewaan terbaru milik Anda.</p>
                </div>
                <a href="{{ route('penyewa.penyewaan.index') }}" class="link-more">
                    Lihat semua <x-icon name="arrow-right" :size="15" />
                </a>
            </div>

            @if ($riwayat->isEmpty())
                <x-empty-state title="Belum ada penyewaan"
                    description="Mulai sewa motor pertama Anda dan riwayatnya akan tampil di sini." icon="receipt" compact>
                    <a href="{{ route('penyewa.motor.index') }}" class="btn btn-sm">Lihat Motor</a>
                </x-empty-state>
            @else
                <div class="table-scroll">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Motor</th>
                                <th>Periode</th>
                                <th>Durasi</th>
                                <th>Biaya</th>
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
                                        <span class="cell-title">{{ $sewa->motor?->merk ?? '-' }}</span>
                                        <span class="cell-sub">{{ $sewa->motor?->no_plat ?? '-' }}</span>
                                    </td>
                                    <td class="money">
                                        {{ $sewa->tanggal_mulai?->format('d/m/Y') }} –
                                        {{ $sewa->tanggal_selesai?->format('d/m/Y') }}
                                    </td>
                                    <td>{{ ucfirst($sewa->tipe_durasi) }}</td>
                                    <td class="money">Rp {{ number_format($sewa->harga, 0, ',', '.') }}</td>
                                    <td><x-badge :value="$sewa->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Motor Tersedia</h2>
                    <p class="card-sub">{{ $motorTersedia }} motor siap disewa.</p>
                </div>
                <a href="{{ route('penyewa.motor.index') }}" class="link-more">
                    Lihat semua <x-icon name="arrow-right" :size="15" />
                </a>
            </div>

            @if ($motorTersediaList->isEmpty())
                <x-empty-state title="Belum ada motor tersedia"
                    description="Motor yang sudah diverifikasi admin akan muncul di sini." icon="bike" compact />
            @else
                <div class="pending-list">
                    @foreach ($motorTersediaList as $motor)
                        <div class="pending-item">
                            <span class="pending-icon"><x-icon name="bike" :size="18" /></span>
                            <div class="pending-meta">
                                <strong>{{ $motor->merk }} · {{ $motor->no_plat }}</strong>
                                <span>
                                    {{ $motor->tarifRental ? 'Rp ' . number_format($motor->tarifRental->tarif_harian, 0, ',', '.') . ' / hari' : 'Tarif belum tersedia' }}
                                    · {{ $motor->pemilik?->nama ?? '-' }}
                                </span>
                            </div>
                            <a href="{{ route('penyewa.motor.show', $motor) }}" class="btn btn-ghost btn-sm">Detail</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="dashboard-grid dashboard-grid-even">
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Status Pembayaran</h2>
                    <p class="card-sub">Ringkasan pembayaran penyewaan Anda.</p>
                </div>
                <a href="{{ route('penyewa.transaksi.index') }}" class="link-more">
                    Semua transaksi <x-icon name="arrow-right" :size="15" />
                </a>
            </div>

            <div class="summary-list">
                <div class="summary-row">
                    <span>Sudah dibayar</span>
                    <strong>Rp {{ number_format($totalPembayaran, 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Menunggu pembayaran</span>
                    <strong>Rp {{ number_format($pembayaranPending, 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Transaksi tercatat</span>
                    <strong>{{ $pembayaranTerbaru->count() }} transaksi terbaru</strong>
                </div>
            </div>

            @if ($pembayaranTerbaru->isEmpty())
                <div class="panel-divider"></div>
                <x-empty-state title="Belum ada pembayaran"
                    description="Pembayaran akan tampil setelah Anda menyewa motor." icon="card" compact />
            @else
                <div class="panel-divider"></div>
                <div class="table-scroll">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Motor</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pembayaranTerbaru as $bayar)
                                <tr>
                                    <td>{{ $bayar->penyewaan?->motor?->merk ?? '-' }}</td>
                                    <td class="money">{{ $bayar->tanggal?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="money">Rp {{ number_format($bayar->jumlah, 0, ',', '.') }}</td>
                                    <td>{{ ucfirst($bayar->metode_pembayaran) }}</td>
                                    <td><x-badge :value="$bayar->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
