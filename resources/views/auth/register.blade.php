@extends('layouts.app')

@section('title', 'Daftar')

@section('body')
    <div class="auth-wrap">
        <div class="auth-card">
            <a href="{{ route('home') }}" class="auth-nav-link">← Kembali ke Beranda</a>

            <h1>Daftar</h1>
            <p class="auth-sub">Sistem Penyewaan Motor</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0;padding-left:1.1rem">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}">
                @csrf

                <div class="field">
                    <label for="nama">Nama Lengkap</label>
                    <input id="nama" type="text" name="nama" value="{{ old('nama') }}"
                        class="{{ $errors->has('nama') ? 'is-invalid' : '' }}" required autofocus>
                    @error('nama')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        class="{{ $errors->has('email') ? 'is-invalid' : '' }}" required autocomplete="username">
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="no_tlpn">Nomor Telepon</label>
                    <input id="no_tlpn" type="text" name="no_tlpn" value="{{ old('no_tlpn') }}"
                        class="{{ $errors->has('no_tlpn') ? 'is-invalid' : '' }}" required inputmode="numeric">
                    @error('no_tlpn')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="role">Daftar Sebagai</label>
                    <select id="role" name="role" class="{{ $errors->has('role') ? 'is-invalid' : '' }}" required>
                        <option value="" disabled {{ old('role') ? '' : 'selected' }}>-- Pilih jenis akun --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <div class="error">{{ $message }}</div>
                    @enderror
                    <div class="hint">Akun admin hanya dapat dibuat oleh administrator.</div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password"
                        class="{{ $errors->has('password') ? 'is-invalid' : '' }}" required
                        autocomplete="new-password">
                    @error('password')
                        <div class="error">{{ $message }}</div>
                    @enderror
                    <div class="hint">Minimal 8 karakter.</div>
                </div>

                <div class="field">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                        autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-block">Daftar</button>
            </form>

            <div class="auth-footer">
                Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
            </div>
        </div>
    </div>
@endsection
