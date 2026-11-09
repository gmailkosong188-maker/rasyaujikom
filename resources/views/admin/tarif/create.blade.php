@extends('layouts.dashboard')

@section('title', 'Tambah Tarif Rental')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Tambah Tarif Rental</h1>
                <p class="muted" style="margin:.25rem 0 0">Tentukan tarif untuk motor yang sudah diverifikasi.</p>
            </div>
            <a href="{{ route('admin.tarif.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
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

        @if ($motor->isEmpty())
            <div class="alert alert-info" style="margin-top:1.25rem">
                Semua motor yang sudah diverifikasi telah memiliki tarif. Verifikasi motor baru terlebih dahulu
                di halaman <a href="{{ route('admin.motor.verifikasi') }}">Verifikasi Motor</a>.
            </div>
        @else
            <form method="POST" action="{{ route('admin.tarif.store') }}" style="margin-top:1.25rem;max-width:560px">
                @csrf

                <div class="field">
                    <label for="motor_id">Motor</label>
                    <select id="motor_id" name="motor_id" class="{{ $errors->has('motor_id') ? 'is-invalid' : '' }}" required>
                        <option value="" disabled {{ old('motor_id', $motorTerpilih) ? '' : 'selected' }}>-- Pilih motor --</option>
                        @foreach ($motor as $m)
                            <option value="{{ $m->id }}"
                                {{ (string) old('motor_id', $motorTerpilih) === (string) $m->id ? 'selected' : '' }}>
                                {{ $m->no_plat }} — {{ $m->merk }} ({{ $m->tipe_cc }} CC) — {{ $m->pemilik?->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('motor_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="tarif_harian">Tarif Harian (Rp)</label>
                    <input id="tarif_harian" type="number" name="tarif_harian" min="1" step="1000"
                        value="{{ old('tarif_harian') }}" class="{{ $errors->has('tarif_harian') ? 'is-invalid' : '' }}"
                        required placeholder="50000">
                    @error('tarif_harian')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="tarif_mingguan">Tarif Mingguan (Rp)</label>
                    <input id="tarif_mingguan" type="number" name="tarif_mingguan" min="1" step="1000"
                        value="{{ old('tarif_mingguan') }}" class="{{ $errors->has('tarif_mingguan') ? 'is-invalid' : '' }}"
                        required placeholder="300000">
                    @error('tarif_mingguan')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="tarif_bulanan">Tarif Bulanan (Rp)</label>
                    <input id="tarif_bulanan" type="number" name="tarif_bulanan" min="1" step="1000"
                        value="{{ old('tarif_bulanan') }}" class="{{ $errors->has('tarif_bulanan') ? 'is-invalid' : '' }}"
                        required placeholder="1000000">
                    @error('tarif_bulanan')<div class="error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn">Simpan Tarif</button>
            </form>
        @endif
    </div>
@endsection
