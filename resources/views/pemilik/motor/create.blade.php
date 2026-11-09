@extends('layouts.dashboard')

@section('title', 'Tambah Motor')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Tambah Motor</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    Motor akan berstatus <strong>pending</strong> sampai diverifikasi admin.
                </p>
            </div>
            <a href="{{ route('pemilik.motor.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
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

        {{-- enctype wajib untuk upload berkas --}}
        <form method="POST" action="{{ route('pemilik.motor.store') }}" enctype="multipart/form-data"
            style="margin-top:1.25rem;max-width:560px">
            @csrf

            <div class="field">
                <label for="merk">Merk Motor</label>
                <input id="merk" type="text" name="merk" value="{{ old('merk') }}"
                    class="{{ $errors->has('merk') ? 'is-invalid' : '' }}" required placeholder="Contoh: Honda Beat">
                @error('merk')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="tipe_cc">Tipe CC</label>
                <select id="tipe_cc" name="tipe_cc" class="{{ $errors->has('tipe_cc') ? 'is-invalid' : '' }}" required>
                    <option value="" disabled {{ old('tipe_cc') ? '' : 'selected' }}>-- Pilih CC --</option>
                    @foreach ($tipeCc as $cc)
                        <option value="{{ $cc }}" {{ old('tipe_cc') === $cc ? 'selected' : '' }}>{{ $cc }} CC</option>
                    @endforeach
                </select>
                @error('tipe_cc')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="no_plat">Nomor Plat</label>
                <input id="no_plat" type="text" name="no_plat" value="{{ old('no_plat') }}"
                    class="{{ $errors->has('no_plat') ? 'is-invalid' : '' }}" required placeholder="B 1234 XY">
                @error('no_plat')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="photo">Foto Motor</label>
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                    class="{{ $errors->has('photo') ? 'is-invalid' : '' }}" required>
                @error('photo')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
            </div>

            <div class="field">
                <label for="dokumen_kepemilikan">Dokumen Kepemilikan</label>
                <input id="dokumen_kepemilikan" type="file" name="dokumen_kepemilikan"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    class="{{ $errors->has('dokumen_kepemilikan') ? 'is-invalid' : '' }}" required>
                @error('dokumen_kepemilikan')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">STNK/BPKB berupa gambar atau PDF. Maksimal 4 MB.</div>
            </div>

            <button type="submit" class="btn">Simpan &amp; Ajukan Verifikasi</button>
        </form>
    </div>
@endsection
