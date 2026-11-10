@extends('layouts.dashboard')

@section('title', 'Laporan Pendapatan')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Laporan Pendapatan Saya</h1>
        <p class="muted" style="margin:.25rem 0 0">
            Periode {{ $dari->format('d/m/Y') }} – {{ $sampai->format('d/m/Y') }}.
            Hanya menampilkan data motor milik Anda.
        </p>

        <div style="margin-top:1.25rem">
            <x-rentang-laporan :dari="$dari" :sampai="$sampai" :action="route('pemilik.laporan.index')" />
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Ringkasan</h2>
        <div class="stats">
            <x-stat label="Jumlah Motor" :value="$jumlahMotor" />
            <x-stat label="Penyewaan" :value="$jumlahPenyewaan" />
            <x-stat label="Total Pendapatan" :value="'Rp '.number_format($totalPendapatan, 0, ',', '.')" />
            <x-stat label="Total Bagi Hasil" :value="'Rp '.number_format($totalBagiHasil, 0, ',', '.')"
                hint="Sudah dicairkan" />
            <x-stat label="Menunggu Pencairan" :value="'Rp '.number_format($totalMenungguPencairan, 0, ',', '.')"
                hint="Belum diserahkan admin" />
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Motor Disewakan</h2>
        @if ($motorDisewakan->isEmpty())
            <p class="empty" style="margin:0">Belum ada motor tersedia/disewa.</p>
        @else
            <table class="data">
                <thead>
                    <tr><th>Motor</th><th>No Plat</th><th>Status</th><th>Tarif Harian</th><th>Tarif Mingguan</th><th>Tarif Bulanan</th></tr>
                </thead>
                <tbody>
                    @foreach ($motorDisewakan as $m)
                        <tr>
                            <td>{{ $m->merk }}</td>
                            <td>{{ $m->no_plat }}</td>
                            <td><x-badge :value="$m->status" /></td>
                            <td class="money">{{ $m->tarifRental ? 'Rp '.number_format($m->tarifRental->tarif_harian, 0, ',', '.') : '-' }}</td>
                            <td class="money">{{ $m->tarifRental ? 'Rp '.number_format($m->tarifRental->tarif_mingguan, 0, ',', '.') : '-' }}</td>
                            <td class="money">{{ $m->tarifRental ? 'Rp '.number_format($m->tarifRental->tarif_bulanan, 0, ',', '.') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2 class="section-title">Pendapatan per Motor</h2>
        @if ($pendapatanPerMotor->isEmpty())
            <p class="empty" style="margin:0">Belum ada pendapatan pada periode ini.</p>
        @else
            <table class="data">
                <thead><tr><th>Motor</th><th>No Plat</th><th>Jumlah Transaksi</th><th>Total Pendapatan</th></tr></thead>
                <tbody>
                    @foreach ($pendapatanPerMotor as $row)
                        <tr>
                            <td>{{ $row->merk }}</td>
                            <td>{{ $row->no_plat }}</td>
                            <td>{{ $row->jumlah_transaksi }}</td>
                            <td class="money">Rp {{ number_format($row->total_pendapatan, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2 class="section-title">Bagi Hasil</h2>
        @if ($bagiHasil->isEmpty())
            <p class="empty" style="margin:0">Belum ada bagi hasil pada periode ini.</p>
        @else
            <table class="data">
                <thead>
                    <tr><th>#</th><th>Tanggal</th><th>Motor</th><th>Harga Sewa</th><th>Persentase</th><th>Bagian Anda</th><th>Status Pencairan</th></tr>
                </thead>
                <tbody>
                    @foreach ($bagiHasil as $b)
                        <tr>
                            <td>{{ $b->id }}</td>
                            <td>{{ $b->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $b->penyewaan?->motor?->no_plat ?? '-' }}</td>
                            <td class="money">Rp {{ number_format($b->penyewaan?->harga ?? 0, 0, ',', '.') }}</td>
                            <td>{{ $b->persentase_hasil_pemilik }}%</td>
                            <td class="money">Rp {{ number_format($b->nominal_pemilik, 0, ',', '.') }}</td>
                            <td>
                                @if ($b->isSettled())
                                    <span class="badge badge-ok">Sudah dicairkan</span>
                                    <div class="muted" style="font-size:.75rem">
                                        {{ $b->settled_at->format('d/m/Y H:i') }}
                                    </div>
                                @else
                                    <span class="badge badge-warn">Menunggu pencairan</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
