@extends('layouts.dashboard')

@section('title', 'Lakukan Pembayaran')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Lakukan Pembayaran</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    Penyewaan #{{ $penyewaan->id }} &middot; {{ $penyewaan->motor?->merk }}
                    ({{ $penyewaan->motor?->no_plat }})
                </p>
            </div>
            <a href="{{ route('penyewa.penyewaan.show', $penyewaan) }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-error" style="margin-top:1.25rem">
                <ul style="margin:0;padding-left:1.1rem">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="card">
        <h2 class="section-title">Rincian Biaya</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="label">Periode Sewa</div>
                <div class="value">
                    {{ $penyewaan->tanggal_mulai->format('d/m/Y') }} – {{ $penyewaan->tanggal_selesai->format('d/m/Y') }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Tipe Durasi</div>
                <div class="value">{{ ucfirst($penyewaan->tipe_durasi) }} ({{ $penyewaan->jumlahPeriode() }} periode)</div>
            </div>
            <div class="detail-item">
                <div class="label">Jumlah yang Harus Dibayar</div>
                <div class="value money">Rp {{ number_format($penyewaan->harga, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="alert alert-info" style="margin-top:1.25rem">
            Nominal pembayaran ditetapkan sistem dari harga penyewaan dan tidak dapat diubah.
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Metode Pembayaran</h2>

        <form method="POST" action="{{ route('penyewa.transaksi.store') }}" style="max-width:560px">
            @csrf
            {{-- Nominal TIDAK dikirim dari form --}}
            <input type="hidden" name="penyewaan_id" value="{{ $penyewaan->id }}">

            <div class="field">
                <label for="metode_pembayaran">Pilih Metode</label>
                <select id="metode_pembayaran" name="metode_pembayaran"
                    class="{{ $errors->has('metode_pembayaran') ? 'is-invalid' : '' }}" required>
                    <option value="" disabled {{ old('metode_pembayaran') ? '' : 'selected' }}>-- Pilih --</option>
                    @foreach ($metode as $m)
                        <option value="{{ $m }}" {{ old('metode_pembayaran') === $m ? 'selected' : '' }}>
                            {{ ucfirst($m) }}
                        </option>
                    @endforeach
                </select>
                @error('metode_pembayaran')<div class="error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn">Bayar Rp {{ number_format($penyewaan->harga, 0, ',', '.') }}</button>
        </form>
    </div>
@endsection
