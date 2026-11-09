@extends('layouts.app')

@section('title', $title ?? 'Dashboard')

@section('body')
    @php
        $user = auth()->user();
        $roleClass = 'badge-' . $user->role;
        $roleLabel = ucfirst($user->role);
    @endphp

    <div class="navbar">
        <a class="brand" href="{{ route($user->dashboardRoute()) }}">Sistem Penyewaan Motor</a>
        <nav>
            <span class="badge {{ $roleClass }}">{{ $roleLabel }}</span>
            <span class="muted">{{ $user->nama }}</span>
            <form class="inline-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost" style="padding:.35rem .8rem;font-size:.85rem">
                    Logout
                </button>
            </form>
        </nav>
    </div>

    <div class="container">
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <x-menu />

        @yield('dashboard')
    </div>
@endsection
