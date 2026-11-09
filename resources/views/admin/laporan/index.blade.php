@extends('layouts.dashboard')

@section('title', 'Laporan')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Laporan Admin</h1>
        <p class="muted" style="margin:.25rem 0 0">
            Periode {{ $dari->format('d/m/Y') }} – {{ $sampai->format('d/m/Y') }}
        </p>

        <div style="margin-top:1.25rem">
            <x-rentang-laporan :dari="$dari" :sampai="$sampai" :action="route('admin.laporan.index')" />
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="card">
        <h2 class="section-title">Ringkasan</h2>
        <div class="stats">
            <x-stat label="Jumlah Motor" :value="$jumlahMotor" />
            <x-stat label="Motor Disewa" :value="$motorDisewa" />
            <x-stat label="Motor Tersedia" :value="$motorTersedia" />
            <x-stat label="Penyewaan" :value="$jumlahPenyewaan" />
            <x-stat label="Total Pendapatan" :value="'Rp '.number_format($totalPendapatan, 0, ',', '.')" />
            <x-stat label="Bagian Pemilik" :value="'Rp '.number_format($bagianPemilik, 0, ',', '.')" />
            <x-stat label="Bagian Admin" :value="'Rp '.number_format($bagianAdmin, 0, ',', '.')" />
        </div>
    </div>

    {{-- Grafik penyewaan --}}
    <div class="card">
        <h2 class="section-title">Grafik Penyewaan</h2>

        @foreach (['grafikHarian' => 'Harian', 'grafikMingguan' => 'Mingguan', 'grafikBulanan' => 'Bulanan'] as $key => $judul)
            @php $grafik = $$key; $maks = max(1, (int) (collect($grafik['nilai'])->max() ?? 0)); @endphp
            <h3 style="font-size:.95rem;margin:1rem 0 .5rem">{{ $judul }}</h3>

            @if (empty($grafik['label']))
                <p class="empty" style="margin:0">Tidak ada penyewaan pada periode ini.</p>
            @else
                <div class="bar-chart">
                    @foreach ($grafik['label'] as $i => $label)
                        @php $nilai = $grafik['nilai'][$i]; @endphp
                        <div class="bar-item">
                            <div class="bar-value">{{ $nilai }}</div>
                            <div class="bar" style="height: {{ max(4, round($nilai / $maks * 100)) }}%"></div>
                            <div class="bar-label">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>

    {{-- Motor per status --}}
    <div class="card">
        <h2 class="section-title">Motor per Status</h2>
        @if ($motorPerStatus->isEmpty())
            <p class="empty" style="margin:0">Belum ada data motor.</p>
        @else
            <table class="data">
                <thead><tr><th>Status</th><th>Jumlah</th></tr></thead>
                <tbody>
                    @foreach ($motorPerStatus as $status => $total)
                        <tr>
                            <td><x-badge :value="$status" /></td>
                            <td>{{ $total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Laporan pembayaran --}}
    <div class="card">
        <h2 class="section-title">Laporan Pembayaran</h2>
        @if ($pembayaran->isEmpty())
            <p class="empty" style="margin:0">Tidak ada pembayaran pada periode ini.</p>
        @else
            <table class="data">
                <thead>
                    <tr><th>#</th><th>Tanggal</th><th>Penyewa</th><th>Motor</th><th>Jumlah</th><th>Metode</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($pembayaran as $t)
                        <tr>
                            <td>{{ $t->id }}</td>
                            <td>{{ $t->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $t->penyewaan?->penyewa?->nama ?? '-' }}</td>
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

    {{-- Bagi hasil --}}
    <div class="card">
        <h2 class="section-title">Laporan Bagi Hasil</h2>
        @if ($bagiHasil->isEmpty())
            <p class="empty" style="margin:0">Tidak ada bagi hasil pada periode ini.</p>
        @else
            <table class="data">
                <thead>
                    <tr><th>#</th><th>Tanggal</th><th>Pemilik</th><th>Motor</th><th>Nominal Pemilik</th><th>Nominal Admin</th></tr>
                </thead>
                <tbody>
                    @foreach ($bagiHasil as $b)
                        <tr>
                            <td>{{ $b->id }}</td>
                            <td>{{ $b->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $b->penyewaan?->motor?->pemilik?->nama ?? '-' }}</td>
                            <td>{{ $b->penyewaan?->motor?->no_plat ?? '-' }}</td>
                            <td class="money">Rp {{ number_format($b->nominal_pemilik, 0, ',', '.') }}</td>
                            <td class="money">Rp {{ number_format($b->nominal_admin, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
