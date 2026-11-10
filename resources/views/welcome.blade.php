@php
    /**
     * Ambil URL foto hanya jika berkasnya benar-benar ada di disk publik,
     * supaya tidak ada gambar rusak di landing page.
     */
    $fotoUrl = function ($motor) {
        if (! $motor || ! $motor->photo) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists($motor->photo)
            ? $motor->urlPhoto()
            : null;
    };

    try {
        // Aturan publik: hanya motor berstatus tersedia DAN sudah punya tarif.
        $motorPublik = \App\Models\Motor::query()
            ->tersedia()
            ->whereHas('tarifRental')
            ->with('tarifRental')
            ->orderByDesc('id')
            ->get();

        $totalPenyewaanSelesai = \App\Models\Penyewaan::query()
            ->where('status', \App\Models\Penyewaan::STATUS_SELESAI)
            ->count();

        $statistikTersedia = true;
    } catch (\Throwable $e) {
        $motorPublik = collect();
        $totalPenyewaanSelesai = 0;
        $statistikTersedia = false;
    }

    $totalTersedia = $motorPublik->count();
    $katalogMotor = $motorPublik->take(6);
    $heroMotor = $motorPublik->first(fn ($motor) => $fotoUrl($motor) !== null) ?? $motorPublik->first();
    $kategoriMotor = $motorPublik->pluck('tipe_cc')->filter()->unique()->count();
    $mitraPemilik = $motorPublik->pluck('pemilik_id')->filter()->unique()->count();

    // Harga di hero mengikuti unit yang ditampilkan, bukan tarif termurah armada.
    $hargaHero = (float) ($heroMotor?->tarifRental?->tarif_harian ?? 0);

    // Tarif terendah dari unit yang benar-benar tampil di katalog.
    $tarif = ['harian' => null, 'mingguan' => null, 'bulanan' => null];
    foreach ($motorPublik as $motor) {
        if (! $motor->tarifRental) {
            continue;
        }

        foreach (array_keys($tarif) as $key) {
            $nilai = (float) $motor->tarifRental->{'tarif_'.$key};
            if ($nilai > 0 && ($tarif[$key] === null || $nilai < $tarif[$key])) {
                $tarif[$key] = $nilai;
            }
        }
    }

    $statistik = [];
    if ($statistikTersedia) {
        $statistik[] = [
            'label' => 'Motor tersedia',
            'value' => $totalTersedia,
            'hint' => 'Unit siap diajukan sewa',
            'icon' => 'bike',
            'tone' => 'blue',
        ];

        if ($kategoriMotor > 0) {
            $statistik[] = [
                'label' => 'Kategori motor',
                'value' => $kategoriMotor,
                'hint' => 'Pilihan kapasitas mesin',
                'icon' => 'tag',
                'tone' => 'navy',
            ];
        }

        $statistik[] = [
            'label' => 'Penyewaan selesai',
            'value' => $totalPenyewaanSelesai,
            'hint' => $totalPenyewaanSelesai > 0 ? 'Penyewaan tuntas' : 'Belum ada penyewaan tuntas',
            'icon' => 'check',
            'tone' => 'green',
        ];

        if ($mitraPemilik > 0) {
            $statistik[] = [
                'label' => 'Mitra pemilik',
                'value' => $mitraPemilik,
                'hint' => 'Pemilik motor terdaftar',
                'icon' => 'users',
                'tone' => 'amber',
            ];
        }
    }

    $user = auth()->user();
    $accountUrl = $user ? route('dashboard') : route('login');
    $accountLabel = $user ? 'Dashboard' : 'Login';

    if (! $user) {
        $motorIndexUrl = route('login');
        $mulaiSewaUrl = route('register');
    } elseif ($user->role === 'penyewa') {
        $motorIndexUrl = route('penyewa.motor.index');
        $mulaiSewaUrl = route('penyewa.motor.index');
    } elseif ($user->role === 'pemilik') {
        $motorIndexUrl = route('pemilik.motor.index');
        $mulaiSewaUrl = route('dashboard');
    } elseif ($user->role === 'admin') {
        $motorIndexUrl = route('admin.motor.index');
        $mulaiSewaUrl = route('dashboard');
    } else {
        $motorIndexUrl = route('dashboard');
        $mulaiSewaUrl = route('dashboard');
    }

    $detailLabel = match (true) {
        ! $user => 'Masuk untuk Menyewa',
        $user->role === 'penyewa' => 'Lihat Detail',
        default => 'Buka Dashboard',
    };

    $rupiah = function ($angka) {
        if ($angka === null || (float) $angka <= 0) {
            return '—';
        }

        return 'Rp '.number_format((float) $angka, 0, ',', '.');
    };
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Rasya Rental — rental motor terverifikasi dengan tarif transparan, proses sewa terstruktur, dan pengelolaan armada yang rapi.">
    <meta name="theme-color" content="#0b1b33">
    <title>Rasya Rental — Sewa Motor Terverifikasi</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>

<body class="landing-page">
    <svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <symbol id="motor-illustration" viewBox="0 0 560 420">
            <circle cx="280" cy="206" r="188" fill="#e8eef7" />
            <circle cx="432" cy="118" r="13" fill="#d5dfeb" />
            <circle cx="118" cy="302" r="8" fill="#d5dfeb" />
            <ellipse cx="285" cy="340" rx="170" ry="16" fill="#dbe5f1" />
            <circle cx="175" cy="300" r="54" fill="#0f1f38" />
            <circle cx="175" cy="300" r="30" fill="#f6f8fb" />
            <circle cx="175" cy="300" r="10" fill="#0f1f38" />
            <circle cx="395" cy="300" r="54" fill="#0f1f38" />
            <circle cx="395" cy="300" r="30" fill="#f6f8fb" />
            <circle cx="395" cy="300" r="10" fill="#0f1f38" />
            <path d="M128 268c0-22 18-40 40-40h48c22 0 40 18 40 40v28H128z" fill="#1c3a63" />
            <rect x="142" y="214" width="112" height="26" rx="13" fill="#12294a" />
            <path d="M238 276h92c14 0 24 10 24 24v6H238z" fill="#1c3a63" />
            <path d="M328 276l24-86c4-16 20-26 36-21l10 3-12 40-22 64z" fill="#1c3a63" />
            <path d="M350 244l32 54" stroke="#0f1f38" stroke-width="10" stroke-linecap="round" />
            <path d="M366 166l48-12" stroke="#0f1f38" stroke-width="12" stroke-linecap="round" />
            <circle cx="418" cy="152" r="9" fill="#0f1f38" />
            <circle cx="356" cy="216" r="14" fill="#f6f8fb" stroke="#0f1f38" stroke-width="6" />
            <path d="M352 270c16-10 38-10 56 0" stroke="#0f1f38" stroke-width="10" stroke-linecap="round" fill="none" />
            <path d="M124 280c-10 6-16 14-18 24" stroke="#0f1f38" stroke-width="10" stroke-linecap="round" fill="none" />
            <path d="M150 280h74" stroke="#f6f8fb" stroke-width="8" stroke-linecap="round" />
            <path d="M342 258c14-4 26-2 36 6" stroke="#8fb0d6" stroke-width="7" stroke-linecap="round" fill="none" />
        </symbol>
    </svg>

    <header class="site-header" id="site-header">
        <div class="landing-container site-nav">
            <a class="site-brand" href="{{ route('home') }}" aria-label="Rasya Rental, kembali ke beranda">
                <span class="brand-logo" aria-hidden="true">R</span>
                <span class="brand-text">
                    <strong>Rasya Rental</strong>
                    <small>Rental Motor &amp; Bagi Hasil</small>
                </span>
            </a>

            <nav class="landing-links" aria-label="Navigasi utama">
                <a href="#beranda">Beranda</a>
                <a href="#motor">Daftar Motor</a>
                <a href="#cara-sewa">Cara Sewa</a>
                <a href="#tentang">Tentang Kami</a>
            </nav>

            <div class="landing-actions">
                @guest
                    <a class="btn btn-ghost btn-sm" href="{{ route('login') }}">Login</a>
                    <a class="btn btn-sm" href="{{ route('register') }}">Daftar</a>
                @else
                    <a class="btn btn-sm" href="{{ $accountUrl }}">{{ $accountLabel }}</a>
                @endguest
            </div>

            <button class="nav-burger" id="nav-burger" type="button" aria-label="Buka menu navigasi" aria-controls="mobile-nav" aria-expanded="false">
                <x-icon name="menu" :size="20" />
            </button>
        </div>

        <div class="mobile-nav" id="mobile-nav">
            <nav class="landing-container mobile-nav-inner" aria-label="Navigasi mobile">
                <a href="#beranda">Beranda</a>
                <a href="#motor">Daftar Motor</a>
                <a href="#cara-sewa">Cara Sewa</a>
                <a href="#tentang">Tentang Kami</a>

                <div class="mobile-nav-actions">
                    @guest
                        <a class="btn btn-ghost" href="{{ route('login') }}">Login</a>
                        <a class="btn" href="{{ route('register') }}">Daftar</a>
                    @else
                        <a class="btn" href="{{ $accountUrl }}">{{ $accountLabel }}</a>
                    @endguest
                </div>
            </nav>
        </div>
    </header>

    <main class="landing-main">
        <section class="hero" id="beranda">
            <div class="landing-container hero-grid">
                <div class="hero-copy">
                    <span class="hero-eyebrow">
                        <x-icon name="verify" :size="14" />
                        Motor terverifikasi
                    </span>

                    <h1>Sewa motor tanpa ribet, <span class="hero-accent">perjalanan lebih bebas.</span></h1>

                    <p class="hero-lead">
                        Pilih motor yang sudah diverifikasi, lihat tarif harian sampai bulanan secara terbuka,
                        lalu ajukan sewa dalam beberapa langkah yang jelas.
                    </p>

                    <div class="hero-actions">
                        <a class="btn btn-lg" href="#motor">
                            Jelajahi Motor
                            <x-icon name="arrow-right" :size="16" />
                        </a>
                        <a class="btn btn-ghost btn-lg" href="{{ $mulaiSewaUrl }}">Mulai Sewa</a>
                    </div>

                    <ul class="hero-points">
                        <li>
                            <x-icon name="check" :size="15" />
                            Motor dicek admin
                        </li>
                        <li>
                            <x-icon name="check" :size="15" />
                            Tarif transparan
                        </li>
                        <li>
                            <x-icon name="check" :size="15" />
                            Status sewa terpantau
                        </li>
                    </ul>
                </div>

                <div class="hero-visual">
                    <div class="hero-frame">
                        @php $heroFoto = $fotoUrl($heroMotor); @endphp

                        @if ($heroFoto)
                            <img src="{{ $heroFoto }}" alt="Motor {{ $heroMotor->merk }} {{ $heroMotor->tipe_cc }} cc" loading="eager" decoding="async">
                        @else
                            <svg class="hero-illustration" viewBox="0 0 560 420" role="img" aria-label="Ilustrasi motor Rasya Rental">
                                <use href="#motor-illustration"></use>
                            </svg>
                        @endif

                        @if ($heroMotor)
                            <span class="hero-chip">
                                <x-icon name="verify" :size="13" />
                                Siap disewa
                            </span>
                        @endif
                    </div>

                    @if ($heroMotor)
                        <div class="hero-price">
                            <div class="hero-price-meta">
                                <span>Mulai dari</span>
                                <strong>
                                    @if ($hargaHero > 0)
                                        {{ $rupiah($hargaHero) }} <small>/ hari</small>
                                    @elseif ($tarif['harian'])
                                        {{ $rupiah($tarif['harian']) }} <small>/ hari</small>
                                    @else
                                        Tarif menyusul
                                    @endif
                                </strong>
                            </div>
                            <div class="hero-price-side">
                                <span class="badge badge-ok"><span class="dot"></span> Tersedia</span>
                                <small>{{ $heroMotor->merk }} · {{ $heroMotor->tipe_cc }} cc</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @if (count($statistik) > 0)
            <section class="landing-stats" aria-label="Statistik layanan Rasya Rental">
                <div class="landing-container">
                    <div class="stats">
                        @foreach ($statistik as $item)
                            <x-stat
                                :label="$item['label']"
                                :value="number_format($item['value'], 0, ',', '.')"
                                :hint="$item['hint']"
                                :icon="$item['icon']"
                                :tone="$item['tone']"
                            />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="landing-section" id="motor">
            <div class="landing-container">
                <div class="section-head">
                    <div>
                        <p class="section-eyebrow">Daftar motor</p>
                        <h2>Pilih motor untuk perjalananmu.</h2>
                        <p>
                            Hanya unit berstatus tersedia dan sudah memiliki tarif yang ditampilkan di sini.
                            Semua data diambil langsung dari sistem.
                        </p>
                    </div>

                    <div class="section-side">
                        <span class="badge badge-muted">
                            @if ($totalTersedia > 0)
                                {{ $totalTersedia }} unit tersedia
                            @else
                                Belum ada unit
                            @endif
                        </span>

                        @if ($totalTersedia > $katalogMotor->count())
                            <a class="link-more" href="{{ $motorIndexUrl }}">
                                Lihat semua motor
                                <x-icon name="arrow-right" :size="14" />
                            </a>
                        @endif
                    </div>
                </div>

                @if ($katalogMotor->isEmpty())
                    <div class="catalog-empty">
                        <x-empty-state
                            title="Belum ada motor siap disewa"
                            description="Semua unit sedang digunakan atau masih dalam proses verifikasi. Silakan cek kembali nanti."
                            icon="bike"
                        >
                            <a class="btn" href="{{ $user ? $accountUrl : route('register') }}">
                                {{ $user ? 'Buka Dashboard' : 'Daftar untuk Mulai' }}
                            </a>
                        </x-empty-state>
                    </div>
                @else
                    <div class="catalog-grid">
                        @foreach ($katalogMotor as $motor)
                            @php
                                $foto = $fotoUrl($motor);

                                if (! $user) {
                                    $detailUrl = route('login');
                                } elseif ($user->role === 'penyewa') {
                                    $detailUrl = route('penyewa.motor.show', $motor);
                                } else {
                                    $detailUrl = route('dashboard');
                                }
                            @endphp

                            <article class="motor-card">
                                <a class="motor-photo" href="{{ $detailUrl }}" aria-label="Lihat motor {{ $motor->merk }} {{ $motor->tipe_cc }} cc">
                                    @if ($foto)
                                        <img src="{{ $foto }}" alt="Motor {{ $motor->merk }} {{ $motor->no_plat }}" loading="lazy" decoding="async">
                                    @else
                                        <span class="photo-fallback" aria-hidden="true">
                                            <svg viewBox="0 0 560 420">
                                                <use href="#motor-illustration"></use>
                                            </svg>
                                        </span>
                                    @endif

                                    <span class="badge badge-ok motor-badge"><span class="dot"></span> Tersedia</span>
                                </a>

                                <div class="motor-body">
                                    <div class="motor-name">
                                        <h3>{{ $motor->merk }}</h3>
                                        <span class="cc-chip">{{ $motor->tipe_cc }} cc</span>
                                    </div>

                                    <p class="motor-plate">
                                        <x-icon name="tag" :size="13" />
                                        {{ $motor->no_plat }}
                                    </p>

                                    <dl class="rate-list">
                                        <div class="rate-row">
                                            <dt>Harian</dt>
                                            <dd>{{ $rupiah($motor->tarifRental->tarif_harian) }}</dd>
                                        </div>
                                        <div class="rate-row">
                                            <dt>Mingguan</dt>
                                            <dd>{{ $rupiah($motor->tarifRental->tarif_mingguan) }}</dd>
                                        </div>
                                        <div class="rate-row">
                                            <dt>Bulanan</dt>
                                            <dd>{{ $rupiah($motor->tarifRental->tarif_bulanan) }}</dd>
                                        </div>
                                    </dl>

                                    <a class="btn btn-ghost btn-sm motor-cta" href="{{ $detailUrl }}">
                                        {{ $detailLabel }}
                                        <x-icon name="arrow-right" :size="15" />
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="landing-section" id="cara-sewa">
            <div class="landing-container">
                <div class="section-head">
                    <div>
                        <p class="section-eyebrow">Cara sewa</p>
                        <h2>Empat langkah, motor siap jalan.</h2>
                        <p>Alur sewa mengikuti proses yang sudah berjalan di sistem, dari pengajuan sampai motor digunakan.</p>
                    </div>
                </div>

                <ol class="steps">
                    <li class="step">
                        <span class="step-icon"><x-icon name="bike" :size="20" /></span>
                        <span class="step-num">01</span>
                        <h3>Pilih Motor</h3>
                        <p>Lihat unit yang tersedia beserta tarif harian, mingguan, dan bulanannya.</p>
                    </li>
                    <li class="step">
                        <span class="step-icon"><x-icon name="calendar" :size="20" /></span>
                        <span class="step-num">02</span>
                        <h3>Ajukan Penyewaan</h3>
                        <p>Tentukan tanggal dan durasi sewa, lalu kirim pengajuan untuk diperiksa admin.</p>
                    </li>
                    <li class="step">
                        <span class="step-icon"><x-icon name="card" :size="20" /></span>
                        <span class="step-num">03</span>
                        <h3>Lakukan Pembayaran</h3>
                        <p>Setelah pengajuan dikonfirmasi admin, lanjutkan pembayaran sesuai tagihan.</p>
                    </li>
                    <li class="step">
                        <span class="step-icon"><x-icon name="check" :size="20" /></span>
                        <span class="step-num">04</span>
                        <h3>Ambil dan Gunakan</h3>
                        <p>Motor siap dipakai, dan pengembaliannya dikonfirmasi lewat sistem yang sama.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="landing-section" id="tentang">
            <div class="landing-container">
                <div class="section-head">
                    <div>
                        <p class="section-eyebrow">Tentang kami</p>
                        <h2>Dikelola rapi, dari verifikasi sampai bagi hasil.</h2>
                        <p>
                            Rasya Rental mempertemukan penyewa dengan pemilik motor dalam satu sistem:
                            armada diverifikasi, tarif ditetapkan admin, dan setiap transaksi tercatat.
                        </p>
                    </div>
                </div>

                <div class="features">
                    <div class="feature">
                        <span class="feature-icon"><x-icon name="bike" :size="20" /></span>
                        <div>
                            <h3>Pilihan Motor</h3>
                            <p>Setiap unit diperiksa admin sebelum berstatus tersedia dan bisa diajukan sewa.</p>
                        </div>
                    </div>

                    <div class="feature">
                        <span class="feature-icon"><x-icon name="tag" :size="20" /></span>
                        <div>
                            <h3>Tarif Transparan</h3>
                            <p>Tarif harian, mingguan, dan bulanan ditetapkan admin dan tampil apa adanya.</p>
                        </div>
                    </div>

                    <div class="feature">
                        <span class="feature-icon"><x-icon name="list" :size="20" /></span>
                        <div>
                            <h3>Proses Penyewaan Terstruktur</h3>
                            <p>Pengajuan dikonfirmasi admin lebih dulu, baru pembayaran dilakukan penyewa.</p>
                        </div>
                    </div>

                    <div class="feature">
                        <span class="feature-icon"><x-icon name="verify" :size="20" /></span>
                        <div>
                            <h3>Pengelolaan Rental Profesional</h3>
                            <p>Pengembalian motor dikonfirmasi dan bagi hasil pemilik tercatat di sistem.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-cta">
            <div class="landing-container">
                <div class="cta-band">
                    <h2>Siap jalan dengan motor pilihanmu?</h2>
                    <p>Buat akun, ajukan penyewaan, dan pantau status pembayaran serta pengembalian dalam satu dashboard.</p>

                    <div class="cta-actions">
                        @guest
                            <a class="btn btn-light btn-lg" href="{{ route('register') }}">Daftar Sekarang</a>
                        @else
                            <a class="btn btn-light btn-lg" href="{{ $accountUrl }}">Buka Dashboard</a>
                        @endguest
                        <a class="btn btn-outline-light btn-lg" href="#motor">Jelajahi Motor</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="landing-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a class="site-brand" href="{{ route('home') }}" aria-label="Rasya Rental, kembali ke beranda">
                        <span class="brand-logo" aria-hidden="true">R</span>
                        <span class="brand-text">
                            <strong>Rasya Rental</strong>
                            <small>Rental Motor &amp; Bagi Hasil</small>
                        </span>
                    </a>
                    <p>
                        Rental motor terverifikasi dengan tarif transparan, proses sewa terstruktur,
                        dan pengelolaan armada yang tercatat.
                    </p>
                </div>

                <div class="footer-col">
                    <h3>Navigasi</h3>
                    <ul>
                        <li><a href="#beranda">Beranda</a></li>
                        <li><a href="#motor">Daftar Motor</a></li>
                        <li><a href="#cara-sewa">Cara Sewa</a></li>
                        <li><a href="#tentang">Tentang Kami</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>Akun</h3>
                    <ul>
                        @guest
                            <li><a href="{{ route('login') }}">Login</a></li>
                            <li><a href="{{ route('register') }}">Daftar</a></li>
                        @else
                            <li><a href="{{ $accountUrl }}">Dashboard</a></li>
                            <li><a href="{{ $motorIndexUrl }}">Motor</a></li>
                        @endguest
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} Rasya Rental. Semua hak dilindungi.</span>
                <span>Sistem Rental Motor &amp; Bagi Hasil</span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            const header = document.getElementById('site-header');
            const burger = document.getElementById('nav-burger');
            const mobileNav = document.getElementById('mobile-nav');

            if (!header || !burger || !mobileNav) {
                return;
            }

            const closeNav = () => {
                mobileNav.classList.remove('open');
                burger.classList.remove('is-open');
                burger.setAttribute('aria-expanded', 'false');
            };

            burger.addEventListener('click', () => {
                const isOpen = mobileNav.classList.toggle('open');
                burger.classList.toggle('is-open', isOpen);
                burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            mobileNav.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', closeNav);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeNav();
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 900) {
                    closeNav();
                }
            });

            const onScroll = () => {
                header.classList.toggle('is-scrolled', window.scrollY > 6);
            };

            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        })();
    </script>
</body>

</html>
