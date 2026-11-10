@extends('layouts.app')

@section('title', $title ?? 'Dashboard')

@section('body')
    @php
        $user = auth()->user();
        $roleLabel = ucfirst($user->role);
        $inisial = mb_strtoupper(mb_substr($user->nama, 0, 1));

        // Pencarian header diarahkan ke daftar motor sesuai role.
        [$searchRoute, $searchPlaceholder] = match ($user->role) {
            'admin' => ['admin.motor.index', 'Cari motor, merk, atau nomor plat'],
            'pemilik' => ['pemilik.motor.index', 'Cari motor Anda'],
            default => ['penyewa.motor.index', 'Cari motor tersedia'],
        };
    @endphp

    <div class="app-shell" id="app-shell">
        <aside class="sidebar" id="sidebar" aria-label="Sidebar navigasi">
            <a class="sidebar-brand" href="{{ route($user->dashboardRoute()) }}">
                <span class="brand-logo">RM</span>
                <span class="brand-text">
                    <strong>Rental Motor</strong>
                    <small>Sistem Penyewaan &amp; Bagi Hasil</small>
                </span>
            </a>

            <x-menu />

            <nav class="sidebar-nav">
                <p class="nav-section-label">Lainnya</p>
                <a href="{{ route('home') }}" class="nav-item">
                    <x-icon name="home" :size="18" />
                    <span>Beranda</span>
                </a>
            </nav>

            <div class="sidebar-profile">
                <span class="avatar" aria-hidden="true">{{ $inisial }}</span>
                <span class="profile-meta">
                    <strong>{{ $user->nama }}</strong>
                    <span>{{ $roleLabel }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn" title="Logout" aria-label="Logout">
                        <x-icon name="logout" :size="17" />
                    </button>
                </form>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>

        <div class="main">
            <header class="topbar">
                <button type="button" class="menu-toggle" id="sidebar-toggle" aria-label="Buka menu navigasi"
                    aria-controls="sidebar" aria-expanded="false">
                    <x-icon name="menu" :size="19" />
                </button>

                <form class="topbar-search" method="GET" action="{{ route($searchRoute) }}" role="search">
                    <x-icon name="search" :size="17" />
                    <input type="search" name="cari" value="{{ request('cari') }}"
                        placeholder="{{ $searchPlaceholder }}" aria-label="Cari">
                </form>

                <div class="topbar-meta">
                    <span class="topbar-date">
                        <x-icon name="calendar" :size="16" />
                        {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                    </span>

                    <div class="topbar-user">
                        <span class="avatar" aria-hidden="true">{{ $inisial }}</span>
                        <span class="topbar-user-meta">
                            <span class="name">{{ $user->nama }}</span>
                            <span class="role">{{ $roleLabel }}</span>
                        </span>
                    </div>
                </div>
            </header>

            <main class="content">
                @if (session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @yield('dashboard')
            </main>
        </div>
    </div>

    <script>
        (function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const toggle = document.getElementById('sidebar-toggle');

            if (!sidebar || !overlay || !toggle) {
                return;
            }

            const setSidebar = (open) => {
                sidebar.classList.toggle('open', open);
                overlay.classList.toggle('show', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.classList.toggle('no-scroll', open);
            };

            toggle.addEventListener('click', () => {
                setSidebar(!sidebar.classList.contains('open'));
            });

            overlay.addEventListener('click', () => setSidebar(false));

            sidebar.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 1024) {
                        setSidebar(false);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setSidebar(false);
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 1024) {
                    setSidebar(false);
                }
            });
        })();
    </script>
@endsection
