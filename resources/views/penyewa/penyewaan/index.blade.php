@extends('layouts.dashboard')

@section('title', 'Penyewaan Saya')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Penyewaan Saya</h1>
                <p class="muted" style="margin:.25rem 0 0">Riwayat dan status penyewaan Anda.</p>
            </div>
            <a href="{{ route('penyewa.motor.index') }}" class="btn btn-sm">Sewa Motor</a>
        </div>

        <form method="GET" action="{{ route('penyewa.penyewaan.index') }}" class="filter-bar" style="margin-top:1.25rem">
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
                <a href="{{ route('penyewa.penyewaan.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        @if ($penyewaan->isEmpty())
            <p class="empty" style="margin:0">
                Anda belum memiliki penyewaan. <a href="{{ route('penyewa.motor.index') }}">Lihat motor tersedia</a>.
            </p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Motor</th>
                        <th>Periode</th>
                        <th>Durasi</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($penyewaan as $p)
                        <tr>
                            <td>
                                {{ $p->motor?->merk ?? '-' }}
                                <span class="muted">({{ $p->motor?->no_plat ?? '-' }})</span>
                            </td>
                            <td>{{ $p->tanggal_mulai->format('d/m/Y') }} – {{ $p->tanggal_selesai->format('d/m/Y') }}</td>
                            <td>{{ ucfirst($p->tipe_durasi) }}</td>
                            <td class="money">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                            <td><x-badge :value="$p->status" /></td>
                            <td><a href="{{ route('penyewa.penyewaan.show', $p) }}" class="btn btn-ghost btn-sm">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$penyewaan" />
        @endif
    </div>
@endsection
