@extends('layouts.dashboard')

@section('title', 'Pengembalian Motor')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Pengembalian Motor</h1>
        <p class="muted" style="margin:.25rem 0 0">
            Periksa motor yang diajukan penyewa, lalu konfirmasi pengembalian.
        </p>
    </div>

    <div class="card">
        <h2 class="section-title">Menunggu Konfirmasi ({{ $menunggu->count() }})</h2>

        @if ($menunggu->isEmpty())
            <p class="empty" style="margin:0">Tidak ada pengembalian yang menunggu konfirmasi.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Penyewa</th>
                        <th>Motor</th>
                        <th>Pemilik</th>
                        <th>Jadwal Selesai</th>
                        <th>Harga</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($menunggu as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->penyewa?->nama ?? '-' }}</td>
                            <td>
                                {{ $p->motor?->merk ?? '-' }}
                                <span class="muted">({{ $p->motor?->no_plat ?? '-' }})</span>
                            </td>
                            <td>{{ $p->motor?->pemilik?->nama ?? '-' }}</td>
                            <td>
                                {{ $p->tanggal_selesai->format('d/m/Y') }}
                                @if ($p->tanggal_selesai->isPast())
                                    <span class="badge badge-warn">Lewat jadwal</span>
                                @endif
                            </td>
                            <td class="money">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                            <td>
                                <div style="display:flex;gap:.35rem;flex-wrap:wrap">
                                    <a href="{{ route('admin.penyewaan.show', $p) }}" class="btn btn-ghost btn-sm">Detail</a>
                                    <form method="POST" action="{{ route('admin.pengembalian.konfirmasi', $p) }}"
                                        onsubmit="return confirm('Konfirmasi pengembalian penyewaan #{{ $p->id }}? Penyewaan akan selesai dan motor kembali tersedia.')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm">Konfirmasi</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="hint" style="margin-bottom:0">
                Konfirmasi akan mengubah penyewaan menjadi <strong>selesai</strong> dan motor menjadi
                <strong>tersedia</strong>.
            </p>
        @endif
    </div>

    <div class="card">
        <h2 class="section-title">Riwayat Pengembalian Terakhir</h2>

        @if ($selesai->isEmpty())
            <p class="empty" style="margin:0">Belum ada penyewaan yang selesai.</p>
        @else
            <table class="data">
                <thead>
                    <tr><th>#</th><th>Penyewa</th><th>Motor</th><th>Periode</th><th>Harga</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($selesai as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->penyewa?->nama ?? '-' }}</td>
                            <td>{{ $p->motor?->no_plat ?? '-' }}</td>
                            <td>{{ $p->tanggal_mulai->format('d/m/Y') }} – {{ $p->tanggal_selesai->format('d/m/Y') }}</td>
                            <td class="money">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                            <td><x-badge :value="$p->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
