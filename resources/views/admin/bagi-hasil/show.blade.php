@extends('layouts.dashboard')

@section('title', 'Detail Bagi Hasil')

@section('dashboard')
    @php
        $penyewaan = $bagiHasil->penyewaan;
        $total = (float) $bagiHasil->nominal_pemilik + (float) $bagiHasil->nominal_admin;
    @endphp

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Bagi Hasil #{{ $bagiHasil->id }}</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    Penyewaan #{{ $bagiHasil->penyewaan_id }} &middot;
                    {{ $penyewaan?->motor?->merk }} ({{ $penyewaan?->motor?->no_plat }})
                </p>
            </div>
            <a href="{{ route('admin.bagi-hasil.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        @if ($bagiHasil->isMenungguPencairan())
            <form method="POST" action="{{ route('admin.bagi-hasil.cairkan', $bagiHasil) }}"
                style="margin-top:1rem"
                onsubmit="return confirm('Konfirmasi bahwa uang bagian pemilik (Rp {{ number_format($bagiHasil->nominal_pemilik, 0, ',', '.') }}) sudah benar-benar diserahkan?')">
                @csrf
                @method('PATCH')
                <div class="alert alert-warning" style="margin-bottom:.75rem">
                    Dana sebesar <strong>Rp {{ number_format($bagiHasil->nominal_pemilik, 0, ',', '.') }}</strong>
                    belum diserahkan kepada pemilik ({{ $penyewaan?->motor?->pemilik?->nama ?? '-' }}).
                    Tekan tombol setelah uang benar-benar diserahkan.
                </div>
                <button type="submit" class="btn btn-sm">Konfirmasi Pencairan</button>
            </form>
        @endif

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Pemilik Motor</div>
                <div class="value">{{ $penyewaan?->motor?->pemilik?->nama ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Penyewa</div>
                <div class="value">{{ $penyewaan?->penyewa?->nama ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Periode Sewa</div>
                <div class="value">
                    {{ $penyewaan?->tanggal_mulai?->format('d/m/Y') }} –
                    {{ $penyewaan?->tanggal_selesai?->format('d/m/Y') }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Harga Penyewaan</div>
                <div class="value money">Rp {{ number_format($penyewaan?->harga ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Status Pencairan</div>
                <div class="value">
                    @if ($bagiHasil->isSettled())
                        <span class="badge badge-success">Sudah dicairkan</span>
                    @else
                        <span class="badge badge-warn">Menunggu pencairan</span>
                    @endif
                </div>
            </div>
            @if ($bagiHasil->isSettled())
                <div class="detail-item">
                    <div class="label">Waktu Pencairan</div>
                    <div class="value">{{ $bagiHasil->settled_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Dicairkan Oleh</div>
                    <div class="value">{{ $bagiHasil->pencairan?->nama ?? '-' }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Rincian Pembagian</h2>

        <table class="data">
            <thead>
                <tr><th>Penerima</th><th>Persentase</th><th>Nominal</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Pemilik Motor {{ $penyewaan?->motor?->pemilik?->nama ? '('.$penyewaan->motor->pemilik->nama.')' : '' }}</td>
                    <td>{{ $bagiHasil->persentase_hasil_pemilik }}%</td>
                    <td class="money">Rp {{ number_format($bagiHasil->nominal_pemilik, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Admin / Pengelola</td>
                    <td>{{ $bagiHasil->persentase_hasil_admin }}%</td>
                    <td class="money">Rp {{ number_format($bagiHasil->nominal_admin, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>{{ $bagiHasil->persentase_hasil_pemilik + $bagiHasil->persentase_hasil_admin }}%</strong></td>
                    <td class="money"><strong>Rp {{ number_format($total, 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        @if ((float) $penyewaan?->harga > 0 && abs($total - (float) $penyewaan->harga) > 0.01)
            <div class="alert alert-error" style="margin-top:1rem">
                Total bagi hasil tidak sama dengan harga penyewaan. Periksa kembali data ini.
            </div>
        @endif
    </div>

    @if ($penyewaan && $penyewaan->transaksis->isNotEmpty())
        <div class="card">
            <h2 class="section-title">Sumber Pendapatan</h2>
            <table class="data">
                <thead><tr><th>#</th><th>Tanggal</th><th>Jumlah</th><th>Metode</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($penyewaan->transaksis as $t)
                        <tr>
                            <td>{{ $t->id }}</td>
                            <td>{{ $t->tanggal->format('d/m/Y') }}</td>
                            <td class="money">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                            <td>{{ ucfirst($t->metode_pembayaran) }}</td>
                            <td><x-badge :value="$t->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
