@extends('layouts.dashboard')

@section('title', 'Ubah User')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Ubah User</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $user->nama }} &middot; {{ $user->email }}
                </p>
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

        @if ($user->isAdminUtama())
            <div class="alert alert-warn" style="margin-top:1.25rem">
                <strong>Akun admin utama:</strong> Role tidak dapat diubah. Penghapusan ditolak untuk menjaga
                ketersediaan administrator sistem.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.user.update', $user) }}" style="margin-top:1rem;max-width:560px">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="nama">Nama</label>
                <input id="nama" type="text" name="nama" value="{{ old('nama', $user->nama) }}"
                    class="{{ $errors->has('nama') ? 'is-invalid' : '' }}" required placeholder="Nama lengkap">
                @error('nama')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                    class="{{ $errors->has('email') ? 'is-invalid' : '' }}" required placeholder="email@contoh.com">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="no_tlpn">Nomor Telepon</label>
                <input id="no_tlpn" type="text" name="no_tlpn" value="{{ old('no_tlpn', $user->no_tlpn) }}"
                    class="{{ $errors->has('no_tlpn') ? 'is-invalid' : '' }}" required placeholder="081234567890">
                @error('no_tlpn')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">Hanya angka, spasi, tanda +, dan - (8-20 digit).</div>
            </div>

            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role"
                    class="{{ $errors->has('role') ? 'is-invalid' : '' }}" required
                    @if ($user->isAdminUtama()) disabled @endif>
                    @foreach ($roles as $r)
                        <option value="{{ $r }}" {{ (old('role', $user->role) === $r) ? 'selected' : '' }}>
                            {{ ucfirst($r) }}
                        </option>
                    @endforeach
                </select>
                @error('role')<div class="error">{{ $message }}</div>@enderror
                @if ($user->isAdminUtama())
                    <div class="hint">Role admin utama tidak dapat diubah.</div>
                @endif
            </div>

            <div class="field">
                <label for="password">Password Baru <span class="muted">(opsional)</span></label>
                <input id="password" type="password" name="password"
                    class="{{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Isi hanya jika ingin mengubah password (minimal 8 karakter)">
                @error('password')<div class="error">{{ $message }}</div>@enderror
                <div class="hint">Kosongkan untuk mempertahankan password lama.</div>
            </div>

            <div class="field">
                <label for="password_confirmation">Konfirmasi Password Baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                    class="{{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Ulangi password baru">
            </div>

            <button type="submit" class="btn">Simpan Perubahan</button>
        </form>
    </div>
@endsection