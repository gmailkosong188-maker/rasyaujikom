@extends('layouts.dashboard')

@section('title', 'Riwayat Penyewaan')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Riwayat Penyewaan Saya</h1>
        <p class="muted" style="margin:.25rem 0 0">
            Periode {{ $dari->format('d/m/Y') }} – {{ $sampai->format('d/m/Y') }}.
            Hanya menampilkan penyewaan milik Anda.
        </p>

        <div style="margin-top:1.25rem">
            <x-rentang-laporan :dari="$dari" :sampai="$sampai" :action="route('penyewa.laporan.index')" />
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Ringkasan</h2>
        <div class="stats">
            <x-stat label="Penyewaan Aktif" :value="$penyewaanAktif" />
            <x-stat label="Penyewaan Selesai" :value="$penyewaanSelesai" />
            <x-stat label="Total Pembayaran" :value="'Rp '.number_format($totalPembayaran, 0, ',', '.')" />
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Riwayat Penyewaan</h2>
        @if ($penyewaan->isEmpty())
            <p class="empty" style="margin:0">Belum ada penyewaan pada periode ini.</p>
        @else
            <table class="data">
                <thead>
                    <tr><th>#</th><th>Motor</th><th>Periode</th><th>Durasi</th><th>Harga</th><th>Status</th><th>Pengembalian</th></tr>
                </thead>
                <tbody>
                    @foreach ($penyewaan as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>
                                {{ $p->motor?->merk ?? '-' }}
                                <span class="muted">({{ $p->motor?->no_plat ?? '-' }})</span>
                            </td>
                            <td>{{ $p->tanggal_mulai->format('d/m/Y') }} – {{ $p->tanggal_selesai->format('d/m/Y') }}</td>
                            <td>{{ ucfirst($p->tipe_durasi) }}</td>
                            <td class="money">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                            <td><x-badge :value="$p->status" /></td>
                            <td>
                                @php
                                    $diajukan = $p->transaksis->contains('status', \App\Models\Transaksi::STATUS_DIKEMBALIKAN);
                                @endphp
                                @if ($p->status === \App\Models\Penyewaan::STATUS_SELESAI)
                                    <span class="badge badge-success">Selesai</span>
                                @elseif ($diajukan)
                                    <span class="badge badge-warn">Menunggu konfirmasi</span>
                                @elseif ($p->status === \App\Models\Penyewaan::STATUS_BERJALAN)
                                    <span class="muted">Belum diajukan</span>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2 class="section-title">Pembayaran</h2>
        @if ($pembayaran->isEmpty())
            <p class="empty" style="margin:0">Belum ada pembayaran pada periode ini.</p>
        @else
            <table class="data">
                <thead>
                    <tr><th>#</th><th>Tanggal</th><th>Motor</th><th>Jumlah</th><th>Metode</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($pembayaran as $t)
                        <tr>
                            <td>{{ $t->id }}</td>
                            <td>{{ $t->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $t->penyewaan?->motor?->no_plat ?? '-' }}</td>
                            <td class="money">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                            <td>{{ ucfirst($t->metode_pembayaran) }}</td>
                            <td><x-badge :value="$t->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
