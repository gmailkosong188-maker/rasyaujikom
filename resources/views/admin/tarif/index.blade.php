@extends('layouts.dashboard')

@section('title', 'Tarif Rental')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Tarif Rental</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    Satu motor hanya memiliki satu tarif aktif. Tarif digunakan sistem untuk menghitung biaya sewa.
                </p>
            </div>
            <a href="{{ route('admin.tarif.create') }}" class="btn btn-sm">+ Tambah Tarif</a>
        </div>

        <form method="GET" action="{{ route('admin.tarif.index') }}" class="filter-bar" style="margin-top:1.25rem">
            <div class="field">
                <label for="cari">Cari motor</label>
                <input id="cari" type="text" name="cari" value="{{ $cari }}" placeholder="Merk atau nomor plat">
            </div>
            <button type="submit" class="btn btn-sm">Cari</button>
            @if ($cari !== '')
                <a href="{{ route('admin.tarif.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    @if ($motorTanpaTarif->isNotEmpty())
        <div class="card">
            <h2 class="section-title">Motor Belum Bertarif ({{ $motorTanpaTarif->count() }})</h2>
            <p class="hint" style="margin-top:0">
                Motor ini sudah diverifikasi tetapi belum punya tarif, sehingga belum dapat disewa.
            </p>
            <table class="data">
                <thead>
                    <tr><th>No Plat</th><th>Merk</th><th>CC</th><th>Pemilik</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($motorTanpaTarif as $m)
                        <tr>
                            <td>{{ $m->no_plat }}</td>
                            <td>{{ $m->merk }}</td>
                            <td>{{ $m->tipe_cc }}</td>
                            <td>{{ $m->pemilik?->nama ?? '-' }}</td>
                            <td><x-badge :value="$m->status" /></td>
                            <td>
                                <a href="{{ route('admin.tarif.create', ['motor_id' => $m->id]) }}"
                                    class="btn btn-sm">Tentukan Tarif</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card">
        <h2 class="section-title">Daftar Tarif</h2>

        @if ($tarif->isEmpty())
            <p class="empty">Belum ada tarif rental yang ditentukan.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>No Plat</th>
                        <th>Merk</th>
                        <th>Pemilik</th>
                        <th>Harian</th>
                        <th>Mingguan</th>
                        <th>Bulanan</th>
                        <th>Diperbarui</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tarif as $t)
                        <tr>
                            <td>{{ $t->motor?->no_plat ?? '-' }}</td>
                            <td>{{ $t->motor?->merk ?? '-' }}</td>
                            <td>{{ $t->motor?->pemilik?->nama ?? '-' }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_harian, 0, ',', '.') }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_mingguan, 0, ',', '.') }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_bulanan, 0, ',', '.') }}</td>
                            <td class="muted">{{ $t->updated_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                    <a href="{{ route('admin.tarif.show', $t) }}" class="btn btn-ghost btn-sm">Detail</a>
                                    @can('update', $t)
                                        <a href="{{ route('admin.tarif.edit', $t) }}" class="btn btn-ghost btn-sm">Ubah</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$tarif" />
        @endif
    </div>
@endsection
