@extends('layouts.dashboard')

@section('title', 'Tarif Rental Motor Saya')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Tarif Rental Motor Saya</h1>
        <p class="muted" style="margin:.25rem 0 0">
            Tarif ditentukan oleh admin. Halaman ini hanya untuk melihat.
        </p>
    </div>

    @if ($motorTanpaTarif->isNotEmpty())
        <div class="card">
            <h2 class="section-title">Motor Belum Bertarif</h2>
            <p class="hint" style="margin-top:0">
                Motor berikut sudah diverifikasi namun admin belum menentukan tarifnya.
            </p>
            <table class="data">
                <thead>
                    <tr><th>No Plat</th><th>Merk</th><th>CC</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($motorTanpaTarif as $m)
                        <tr>
                            <td>{{ $m->no_plat }}</td>
                            <td>{{ $m->merk }}</td>
                            <td>{{ $m->tipe_cc }}</td>
                            <td><x-badge :value="$m->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card">
        <h2 class="section-title">Daftar Tarif</h2>

        @if ($tarif->isEmpty())
            <p class="empty">Belum ada tarif untuk motor Anda.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>No Plat</th>
                        <th>Merk</th>
                        <th>Harian</th>
                        <th>Mingguan</th>
                        <th>Bulanan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tarif as $t)
                        <tr>
                            <td>{{ $t->motor?->no_plat ?? '-' }}</td>
                            <td>{{ $t->motor?->merk ?? '-' }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_harian, 0, ',', '.') }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_mingguan, 0, ',', '.') }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_bulanan, 0, ',', '.') }}</td>
                            <td>
                                <a href="{{ route('pemilik.tarif.show', $t) }}" class="btn btn-ghost btn-sm">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$tarif" />
        @endif
    </div>
@endsection
