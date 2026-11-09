@extends('layouts.dashboard')

@section('title', 'Pembayaran Saya')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Pembayaran Saya</h1>
        <p class="muted" style="margin:.25rem 0 0">Riwayat transaksi pembayaran penyewaan Anda.</p>

        <form method="GET" action="{{ route('penyewa.transaksi.index') }}" class="filter-bar" style="margin-top:1.25rem">
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
            @if ($status)
                <a href="{{ route('penyewa.transaksi.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        @if ($transaksi->isEmpty())
            <p class="empty" style="margin:0">Belum ada transaksi pembayaran.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Penyewaan</th>
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
                            <td>{{ $t->penyewaan?->tanggal_mulai?->format('d/m/Y') }} –
                                {{ $t->penyewaan?->tanggal_selesai?->format('d/m/Y') }}</td>
                            <td>{{ $t->penyewaan?->motor?->no_plat ?? '-' }}</td>
                            <td>{{ $t->tanggal->format('d/m/Y') }}</td>
                            <td class="money">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                            <td>{{ ucfirst($t->metode_pembayaran) }}</td>
                            <td><x-badge :value="$t->status" /></td>
                            <td><a href="{{ route('penyewa.transaksi.show', $t) }}" class="btn btn-ghost btn-sm">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$transaksi" />
        @endif
    </div>
@endsection
