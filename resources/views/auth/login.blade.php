@extends('layouts.app')

@section('title', 'Masuk')

@section('body')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Masuk</h1>
            <p class="auth-sub">Sistem Penyewaan Motor</p>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0;padding-left:1.1rem">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        class="{{ $errors->has('email') ? 'is-invalid' : '' }}" required autofocus
                        autocomplete="username">
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password"
                        class="{{ $errors->has('password') ? 'is-invalid' : '' }}" required
                        autocomplete="current-password">
                    @error('password')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field field-check">
                    <input id="remember" type="checkbox" name="remember" value="1">
                    <label for="remember">Ingat saya</label>
                </div>

                <button type="submit" class="btn btn-block">Masuk</button>
            </form>

            <div class="auth-footer">
                Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
            </div>
        </div>
    </div>
@endsection
