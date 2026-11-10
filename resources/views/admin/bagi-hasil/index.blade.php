@extends('layouts.dashboard')

@section('title', 'Bagi Hasil')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Bagi Hasil</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    Pembagian pendapatan antara pemilik motor dan admin.
                    Persentase: <strong>{{ $persentasePemilik }}% pemilik</strong> /
                    <strong>{{ $persentaseAdmin }}% admin</strong>.
                </p>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <span class="badge">Pemilik diterima: Rp {{ number_format($totalNominalPemilik, 0, ',', '.') }}</span>
                <span class="badge">Admin diterima: Rp {{ number_format($totalNominalAdmin, 0, ',', '.') }}</span>
                <span class="badge badge-warning">Menunggu pencairan:
                    Rp {{ number_format($totalMenungguPencairan, 0, ',', '.') }}
                    ({{ $jumlahMenungguPencairan }} catatan)</span>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.bagi-hasil.index') }}" class="filter-bar" style="margin-top:1.25rem">
            <div class="field">
                <label for="cari">Cari</label>
                <input id="cari" type="text" name="cari" value="{{ $cari }}"
                    placeholder="Nama pemilik, penyewa, atau nomor plat">
            </div>
            <button type="submit" class="btn btn-sm">Filter</button>
            @if ($cari !== '')
                <a href="{{ route('admin.bagi-hasil.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    @if ($belumSettled->isNotEmpty())
        <div class="card">
            <h2 class="section-title">Penyewaan Selesai Belum Tercatat ({{ $belumSettled->count() }})</h2>
            <table class="data">
                <thead>
                    <tr><th>#</th><th>Penyewa</th><th>Motor</th><th>Pemilik</th><th>Harga</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($belumSettled as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->penyewa?->nama ?? '-' }}</td>
                            <td>{{ $p->motor?->no_plat ?? '-' }}</td>
                            <td>{{ $p->motor?->pemilik?->nama ?? '-' }}</td>
                            <td class="money">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.bagi-hasil.catat', $p) }}"
                                    onsubmit="return confirm('Catat bagi hasil untuk penyewaan #{{ $p->id }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm">Catat Bagi Hasil</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card">
        <h2 class="section-title">Riwayat Bagi Hasil</h2>

        @if ($bagiHasil->isEmpty())
            <p class="empty" style="margin:0">Belum ada bagi hasil yang tercatat.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Penyewaan</th>
                        <th>Pemilik</th>
                        <th>Tanggal</th>
                        <th>Nominal Pemilik</th>
                        <th>Nominal Admin</th>
                        <th>Total</th>
                        <th>Status Pencairan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bagiHasil as $b)
                        <tr>
                            <td>{{ $b->id }}</td>
                            <td>
                                #{{ $b->penyewaan_id }}
                                <span class="muted">({{ $b->penyewaan?->motor?->no_plat ?? '-' }})</span>
                            </td>
                            <td>{{ $b->penyewaan?->motor?->pemilik?->nama ?? '-' }}</td>
                            <td>{{ $b->tanggal->format('d/m/Y') }}</td>
                            <td class="money">Rp {{ number_format($b->nominal_pemilik, 0, ',', '.') }}
                                <span class="muted">({{ $b->persentase_hasil_pemilik }}%)</span>
                            </td>
                            <td class="money">Rp {{ number_format($b->nominal_admin, 0, ',', '.') }}
                                <span class="muted">({{ $b->persentase_hasil_admin }}%)</span>
                            </td>
                            <td class="money">Rp {{ number_format($b->nominal_pemilik + $b->nominal_admin, 0, ',', '.') }}</td>
                            <td>
                                @if ($b->isSettled())
                                    <x-badge value="selesai" />
                                    <div class="muted" style="font-size:.75rem">
                                        {{ $b->settled_at->format('d/m/Y H:i') }}
                                        @if ($b->pencairan)
                                            · oleh {{ $b->pencairan->nama }}
                                        @endif
                                    </div>
                                @else
                                    <x-badge value="pending" />
                                    <div class="muted" style="font-size:.75rem">Menunggu pencairan</div>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:.35rem;flex-wrap:wrap">
                                    <a href="{{ route('admin.bagi-hasil.show', $b) }}" class="btn btn-ghost btn-sm">Detail</a>
                                    @if ($b->isMenungguPencairan())
                                        <form method="POST" action="{{ route('admin.bagi-hasil.cairkan', $b) }}"
                                            onsubmit="return confirm('Konfirmasi bahwa uang bagian pemilik sudah benar-benar diserahkan?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm">Konfirmasi Pencairan</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$bagiHasil" />
        @endif
    </div>
@endsection
