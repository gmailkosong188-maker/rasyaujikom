@extends('layouts.dashboard')

@section('title', 'Motor Tersedia')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Motor Tersedia</h1>
        <p class="muted" style="margin:.25rem 0 0">Hanya motor yang sudah diverifikasi admin yang tampil di sini.</p>

        <form method="GET" action="{{ route('penyewa.motor.index') }}" class="filter-bar" style="margin-top:1.25rem">
            <div class="field">
                <label for="cari">Cari</label>
                <input id="cari" type="text" name="cari" value="{{ $cari }}" placeholder="Merk motor">
            </div>
            <div class="field">
                <label for="tipe_cc">Tipe CC</label>
                <select id="tipe_cc" name="tipe_cc">
                    <option value="">Semua CC</option>
                    @foreach ($tipeCc as $c)
                        <option value="{{ $c }}" {{ $cc === $c ? 'selected' : '' }}>{{ $c }} CC</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm">Cari</button>
            @if ($cari !== '' || $cc !== '')
                <a href="{{ route('penyewa.motor.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    @if ($motor->isEmpty())
        <div class="card">
            <p class="empty" style="margin:0">Belum ada motor tersedia saat ini.</p>
        </div>
    @else
        <div class="motor-grid">
            @foreach ($motor as $m)
                <x-motor-card :motor="$m">
                    <a href="{{ route('penyewa.motor.show', $m) }}" class="btn btn-sm" style="margin-top:.75rem">
                        Lihat Detail
                    </a>
                </x-motor-card>
            @endforeach
        </div>

        <x-pagination :paginator="$motor" />
    @endif
@endsection
