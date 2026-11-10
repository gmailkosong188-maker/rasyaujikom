@extends('layouts.dashboard')

@section('title', 'Tambah User')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Tambah User</h1>
                <p class="muted" style="margin:.25rem 0 0">Buat akun baru untuk admin, pemilik, atau penyewa.</p>
            </div>
            <a href="{{ route('admin.user.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
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

        <form method="POST" action="{{ route('admin.user.store') }}" style="margin-top:1.25rem;max-width:560px">
            @csrf

            <div class="field">
                <label for="nama">Nama</label>
                <input id="nama" type="text" name="nama" value="{{ old('nama') }}"
                    class="{{ $errors->has('nama') ? 'is-invalid' : '' }}" required placeholder="Nama lengkap">
                @error('nama')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    class="{{ $errors->has('email') ? 'is-invalid' : '' }}" required placeholder="email@contoh.com">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="no_tlpn">Nomor Telepon</label>
                <input id="no_tlpn" type="text" name="no_tlpn" value="{{ old('no_tlpn') }}"
                    class="{{ $errors->has('no_tlpn') ? 'is-invalid' : '' }}" required placeholder="081234567890">
                @error('no_tlpn')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">Hanya angka, spasi, tanda +, dan - (8-20 digit).</div>
            </div>

            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role"
                    class="{{ $errors->has('role') ? 'is-invalid' : '' }}" required>
                    <option value="" disabled {{ !old('role') ? 'selected' : '' }}>-- Pilih role --</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r }}" {{ old('role') === $r ? 'selected' : '' }}>
                            {{ ucfirst($r) }}
                        </option>
                    @endforeach
                </select>
                @error('role')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password"
                    class="{{ $errors->has('password') ? 'is-invalid' : '' }}" required placeholder="Minimal 8 karakter">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Konfirmasi Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                    class="{{ $errors->has('password') ? 'is-invalid' : '' }}" required placeholder="Ulangi password">
            </div>

            <button type="submit" class="btn">Simpan User</button>
        </form>
    </div>
@endsection