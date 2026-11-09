@extends('layouts.dashboard')

@section('title', 'Motor Saya')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Motor Saya</h1>
                <p class="muted" style="margin:.25rem 0 0">Daftar motor yang Anda daftarkan.</p>
            </div>
            <a href="{{ route('pemilik.motor.create') }}" class="btn btn-sm">+ Tambah Motor</a>
        </div>

        <form method="GET" action="{{ route('pemilik.motor.index') }}" class="filter-bar" style="margin-top:1.25rem">
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
            @if ($status)
                <a href="{{ route('pemilik.motor.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        @if ($motor->isEmpty())
            <p class="empty">Anda belum mendaftarkan motor.
                <a href="{{ route('pemilik.motor.create') }}">Tambah motor sekarang</a>.
            </p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>No Plat</th>
                        <th>Merk</th>
                        <th>CC</th>
                        <th>Tarif Harian</th>
                        <th>Status Verifikasi</th>
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
                            <td class="money">
                                {{ $m->tarifRental ? 'Rp ' . number_format($m->tarifRental->tarif_harian, 0, ',', '.') : 'Belum ditentukan' }}
                            </td>
                            <td><x-badge :value="$m->status" /></td>
                            <td>
                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                    <a href="{{ route('pemilik.motor.show', $m) }}" class="btn btn-ghost btn-sm">Detail</a>
                                    @can('update', $m)
                                        <a href="{{ route('pemilik.motor.edit', $m) }}" class="btn btn-ghost btn-sm">Edit</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$motor" />
        @endif
    </div>
@endsection
