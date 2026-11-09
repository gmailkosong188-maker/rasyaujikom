@extends('layouts.dashboard')

@section('title', 'Tarif Rental')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Tarif Rental</h1>
        <p class="muted" style="margin:.25rem 0 0">
            Daftar tarif motor yang tersedia. Tarif ditentukan oleh admin.
        </p>
    </div>

    <div class="card">
        @if ($tarif->isEmpty())
            <p class="empty" style="margin:0">Belum ada motor tersedia yang memiliki tarif.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Motor</th>
                        <th>CC</th>
                        <th>Harian</th>
                        <th>Mingguan</th>
                        <th>Bulanan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tarif as $t)
                        <tr>
                            <td>
                                {{ $t->motor?->merk ?? '-' }}
                                <span class="muted">({{ $t->motor?->no_plat ?? '-' }})</span>
                            </td>
                            <td>{{ $t->motor?->tipe_cc ?? '-' }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_harian, 0, ',', '.') }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_mingguan, 0, ',', '.') }}</td>
                            <td class="money">Rp {{ number_format($t->tarif_bulanan, 0, ',', '.') }}</td>
                            <td>
                                <a href="{{ route('penyewa.tarif.show', $t) }}" class="btn btn-ghost btn-sm">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$tarif" />
        @endif
    </div>
@endsection
