@extends('layouts.dashboard')

@section('title', 'Ubah Tarif Rental')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Ubah Tarif Rental</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $tarif->motor?->no_plat }} &middot; {{ $tarif->motor?->merk }}
                </p>
            </div>
            <a href="{{ route('admin.tarif.show', $tarif) }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
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

        <div class="alert alert-info" style="margin-top:1.25rem">
            Perubahan tarif <strong>tidak</strong> mengubah harga penyewaan yang sudah terjadi — harga sewa
            disimpan pada saat transaksi dibuat.
        </div>

        <form method="POST" action="{{ route('admin.tarif.update', $tarif) }}" style="margin-top:1rem;max-width:560px">
            @csrf
            @method('PUT')

            <div class="field">
                <label>Motor</label>
                <input type="text" value="{{ $tarif->motor?->no_plat }} — {{ $tarif->motor?->merk }}" disabled>
                <div class="hint">Motor tidak dapat diubah. Hapus tarif ini lalu buat tarif baru bila perlu.</div>
            </div>

            <div class="field">
                <label for="tarif_harian">Tarif Harian (Rp)</label>
                <input id="tarif_harian" type="number" name="tarif_harian" min="1" step="1000"
                    value="{{ old('tarif_harian', (int) $tarif->tarif_harian) }}"
                    class="{{ $errors->has('tarif_harian') ? 'is-invalid' : '' }}" required>
                @error('tarif_harian')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="tarif_mingguan">Tarif Mingguan (Rp)</label>
                <input id="tarif_mingguan" type="number" name="tarif_mingguan" min="1" step="1000"
                    value="{{ old('tarif_mingguan', (int) $tarif->tarif_mingguan) }}"
                    class="{{ $errors->has('tarif_mingguan') ? 'is-invalid' : '' }}" required>
                @error('tarif_mingguan')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="tarif_bulanan">Tarif Bulanan (Rp)</label>
                <input id="tarif_bulanan" type="number" name="tarif_bulanan" min="1" step="1000"
                    value="{{ old('tarif_bulanan', (int) $tarif->tarif_bulanan) }}"
                    class="{{ $errors->has('tarif_bulanan') ? 'is-invalid' : '' }}" required>
                @error('tarif_bulanan')<div class="error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn">Simpan Perubahan</button>
        </form>
    </div>
@endsection
