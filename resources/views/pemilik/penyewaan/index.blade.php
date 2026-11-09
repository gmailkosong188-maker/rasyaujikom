@extends('layouts.dashboard')

@section('title', 'Penyewaan Motor Saya')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Penyewaan Motor Saya</h1>
        <p class="muted" style="margin:.25rem 0 0">
            Penyewaan atas motor milik Anda. Halaman ini hanya untuk melihat.
        </p>

        <form method="GET" action="{{ route('pemilik.penyewaan.index') }}" class="filter-bar" style="margin-top:1.25rem">
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Penyewaan::STATUSES as $s)
                        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm">Filter</button>
            @if ($status)
                <a href="{{ route('pemilik.penyewaan.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        @if ($penyewaan->isEmpty())
            <p class="empty" style="margin:0">Belum ada penyewaan atas motor Anda.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Penyewa</th>
                        <th>Motor</th>
                        <th>Periode</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($penyewaan as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->penyewa?->nama ?? '-' }}</td>
                            <td>
                                {{ $p->motor?->merk ?? '-' }}
                                <span class="muted">({{ $p->motor?->no_plat ?? '-' }})</span>
                            </td>
                            <td>{{ $p->tanggal_mulai->format('d/m/Y') }} – {{ $p->tanggal_selesai->format('d/m/Y') }}</td>
                            <td class="money">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                            <td><x-badge :value="$p->status" /></td>
                            <td><a href="{{ route('pemilik.penyewaan.show', $p) }}" class="btn btn-ghost btn-sm">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$penyewaan" />
        @endif
    </div>
@endsection
