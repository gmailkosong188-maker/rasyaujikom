@extends('layouts.dashboard')

@section('title', 'Edit Motor')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Edit Motor</h1>
                <p class="muted" style="margin:.25rem 0 0">{{ $motor->no_plat }} &middot; <x-badge :value="$motor->status" /></p>
            </div>
            <a href="{{ route('pemilik.motor.show', $motor) }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
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

        <form method="POST" action="{{ route('pemilik.motor.update', $motor) }}" enctype="multipart/form-data"
            style="margin-top:1.25rem;max-width:560px">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="merk">Merk Motor</label>
                <input id="merk" type="text" name="merk" value="{{ old('merk', $motor->merk) }}"
                    class="{{ $errors->has('merk') ? 'is-invalid' : '' }}" required>
                @error('merk')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="tipe_cc">Tipe CC</label>
                <select id="tipe_cc" name="tipe_cc" class="{{ $errors->has('tipe_cc') ? 'is-invalid' : '' }}" required>
                    @foreach ($tipeCc as $cc)
                        <option value="{{ $cc }}"
                            {{ old('tipe_cc', $motor->tipe_cc) === $cc ? 'selected' : '' }}>{{ $cc }} CC</option>
                    @endforeach
                </select>
                @error('tipe_cc')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="no_plat">Nomor Plat</label>
                <input id="no_plat" type="text" name="no_plat" value="{{ old('no_plat', $motor->no_plat) }}"
                    class="{{ $errors->has('no_plat') ? 'is-invalid' : '' }}" required>
                @error('no_plat')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="photo">Foto Motor (opsional)</label>
                @if ($motor->photo)
                    <img src="{{ $motor->urlPhoto() }}" alt="{{ $motor->merk }}" class="doc-preview"
                        style="max-width:220px;margin-bottom:.4rem">
                @endif
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                    class="{{ $errors->has('photo') ? 'is-invalid' : '' }}">
                @error('photo')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">Biarkan kosong bila tidak ingin mengganti foto.</div>
            </div>

            <div class="field">
                <label for="dokumen_kepemilikan">Dokumen Kepemilikan (opsional)</label>
                <input id="dokumen_kepemilikan" type="file" name="dokumen_kepemilikan"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    class="{{ $errors->has('dokumen_kepemilikan') ? 'is-invalid' : '' }}">
                @error('dokumen_kepemilikan')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">
                    Mengganti dokumen pada motor yang sudah tersedia akan mengembalikan status ke
                    <strong>pending</strong> untuk diverifikasi ulang.
                </div>
            </div>

            <button type="submit" class="btn">Simpan Perubahan</button>
        </form>
    </div>
@endsection
