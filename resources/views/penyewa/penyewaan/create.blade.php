@extends('layouts.dashboard')

@section('title', 'Buat Penyewaan')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Buat Penyewaan</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $motor->merk }} &middot; {{ $motor->no_plat }} &middot; {{ $motor->tipe_cc }} CC
                </p>
            </div>
            <a href="{{ route('penyewa.motor.show', $motor) }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
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

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Tarif Harian</div>
                <div class="value money">Rp {{ number_format($motor->tarifRental->tarif_harian, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tarif Mingguan</div>
                <div class="value money">Rp {{ number_format($motor->tarifRental->tarif_mingguan, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tarif Bulanan</div>
                <div class="value money">Rp {{ number_format($motor->tarifRental->tarif_bulanan, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Data Penyewaan</h2>

        <form method="POST" action="{{ route('penyewa.penyewaan.store') }}" style="max-width:560px">
            @csrf
            {{-- motor_id dikirim sebagai identifier; harga TIDAK dikirim — dihitung server --}}
            <input type="hidden" name="motor_id" value="{{ $motor->id }}">

            <div class="field">
                <label for="tanggal_mulai">Tanggal Mulai</label>
                <input id="tanggal_mulai" type="date" name="tanggal_mulai" min="{{ $hariIni }}"
                    value="{{ old('tanggal_mulai', $hariIni) }}"
                    class="{{ $errors->has('tanggal_mulai') ? 'is-invalid' : '' }}" required>
                @error('tanggal_mulai')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="tanggal_selesai">Tanggal Selesai</label>
                <input id="tanggal_selesai" type="date" name="tanggal_selesai" min="{{ $hariIni }}"
                    value="{{ old('tanggal_selesai', $hariIni) }}"
                    class="{{ $errors->has('tanggal_selesai') ? 'is-invalid' : '' }}" required>
                @error('tanggal_selesai')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">Tanggal selesai tidak boleh lebih awal dari tanggal mulai.</div>
            </div>

            <div class="field">
                <label for="tipe_durasi">Tipe Durasi</label>
                <select id="tipe_durasi" name="tipe_durasi" class="{{ $errors->has('tipe_durasi') ? 'is-invalid' : '' }}" required>
                    @foreach ($tipeDurasi as $t)
                        <option value="{{ $t }}" {{ old('tipe_durasi') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
                @error('tipe_durasi')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">Harga dihitung otomatis oleh sistem dari tarif motor.</div>
            </div>

            <button type="submit" class="btn">Buat Penyewaan</button>
        </form>
    </div>
@endsection
