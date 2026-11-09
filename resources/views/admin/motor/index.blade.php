@extends('layouts.dashboard')

@section('title', 'Data Motor')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Data Motor</h1>
                <p class="muted" style="margin:.25rem 0 0">Seluruh motor terdaftar di sistem.</p>
            </div>
            <a href="{{ route('admin.motor.verifikasi') }}" class="btn btn-ghost btn-sm">
                Menunggu Verifikasi ({{ $jumlahPending }})
            </a>
        </div>

        <form method="GET" action="{{ route('admin.motor.index') }}" class="filter-bar" style="margin-top:1.25rem">
            <div class="field">
                <label for="cari">Cari</label>
                <input id="cari" type="text" name="cari" value="{{ $cari }}" placeholder="Merk atau nomor plat">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Motor::STATUSES as $s)
                        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm">Filter</button>
            @if ($cari !== '' || $status)
                <a href="{{ route('admin.motor.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        @if ($motor->isEmpty())
            <p class="empty">Tidak ada motor yang sesuai filter.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>No Plat</th>
                        <th>Merk</th>
                        <th>CC</th>
                        <th>Pemilik</th>
                        <th>Tarif Harian</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($motor as $m)
                        <tr>
                            <td>
                                @if ($m->photo)
                                    <img src="{{ $m->urlPhoto() }}" alt="{{ $m->merk }}"
                                        style="width:52px;height:38px;object-fit:cover;border-radius:.3rem">
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td>{{ $m->no_plat }}</td>
                            <td>{{ $m->merk }}</td>
                            <td>{{ $m->tipe_cc }}</td>
                            <td>{{ $m->pemilik?->nama ?? '-' }}</td>
                            <td class="money">
                                {{ $m->tarifRental ? 'Rp ' . number_format($m->tarifRental->tarif_harian, 0, ',', '.') : 'Belum ditentukan' }}
                            </td>
                            <td><x-badge :value="$m->status" /></td>
                            <td>
                                <a href="{{ route('admin.motor.show', $m) }}" class="btn btn-ghost btn-sm">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$motor" />
        @endif
    </div>
@endsection
