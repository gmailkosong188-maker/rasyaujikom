@extends('layouts.dashboard')

@section('title', 'Kelola Pembayaran')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Kelola Pembayaran</h1>
                <p class="muted" style="margin:.25rem 0 0">Verifikasi pembayaran dari penyewa.</p>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <span class="badge badge-warn">Pending: {{ $jumlahPending }}</span>
                <span class="badge">Diterima: Rp {{ number_format($totalDibayar, 0, ',', '.') }}</span>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.transaksi.index') }}" class="filter-bar" style="margin-top:1.25rem">
            <div class="field">
                <label for="cari">Cari</label>
                <input id="cari" type="text" name="cari" value="{{ $cari }}" placeholder="Nama penyewa atau nomor plat">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Transaksi::STATUSES as $s)
                        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm">Filter</button>
            @if ($cari !== '' || $status)
                <a href="{{ route('admin.transaksi.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        @if ($transaksi->isEmpty())
            <p class="empty" style="margin:0">Tidak ada transaksi yang sesuai filter.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Penyewa</th>
                        <th>Motor</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transaksi as $t)
                        <tr>
                            <td>{{ $t->id }}</td>
                            <td>{{ $t->penyewaan?->penyewa?->nama ?? '-' }}</td>
                            <td>{{ $t->penyewaan?->motor?->no_plat ?? '-' }}</td>
                            <td>{{ $t->tanggal->format('d/m/Y') }}</td>
                            <td class="money">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                            <td>{{ ucfirst($t->metode_pembayaran) }}</td>
                            <td><x-badge :value="$t->status" /></td>
                            <td><a href="{{ route('admin.transaksi.show', $t) }}" class="btn btn-ghost btn-sm">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$transaksi" />
        @endif
    </div>
@endsection
