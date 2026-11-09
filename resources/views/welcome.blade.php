@php
    try {
        $semuaMotorTersedia = \App\Models\Motor::query()
            ->tersedia()
            ->with('tarifRental')
            ->latest()
            ->get();
    } catch (\Throwable $e) {
        $semuaMotorTersedia = collect();
    }

    $motorTersedia = $semuaMotorTersedia->take(3);
    $totalTersedia = $semuaMotorTersedia->count();
    $heroMotor = $semuaMotorTersedia->first(fn ($motor) => filled($motor->photo)) ?? $semuaMotorTersedia->first();

    $tarif = ['harian' => null, 'mingguan' => null, 'bulanan' => null];
    foreach ($semuaMotorTersedia as $motor) {
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

    $user = auth()->user();
    $accountUrl = $user ? route('dashboard') : route('login');
    $accountLabel = $user ? 'Dashboard' : 'Login';
    $motorIndexUrl = $user && $user->role === 'penyewa'
        ? route('penyewa.motor.index')
        : $accountUrl;

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
    <meta name="description" content="Rasya Rental menyediakan rental motor terawat dengan tarif transparan dan proses sewa yang mudah di Jakarta.">
    <meta name="theme-color" content="#fbfaf7">
    <title>Rasya Rental — Sewa Motor Lebih Mudah</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800" rel="stylesheet">

    <style>
        :root {
            --bg: #fbfaf7;
            --surface: #ffffff;
            --surface-soft: #f4f1eb;
            --text: #1b1b1b;
            --muted: #66635e;
            --muted-light: #6f6a63;
            --border: #e7e2d9;
            --border-strong: #d8d2c7;
            --accent: #e15b2a;
            --accent-dark: #b33f16;
            --accent-hover: #f06a38;
            --accent-soft: #fbe9e1;
            --green: #2f8a4b;
            --radius: 18px;
            --radius-lg: 24px;
            --container: 1160px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            background: var(--bg);
            color: var(--text);
            font-family: 'Manrope', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        img,
        svg {
            display: block;
            max-width: 100%;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
            overflow-wrap: break-word;
        }

        [id] {
            scroll-margin-top: 88px;
        }

        ::selection {
            background: var(--accent-soft);
            color: var(--text);
        }

        .container {
            width: min(100% - 2.5rem, var(--container));
            margin-inline: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            padding: .8rem 1.2rem;
            border: 1px solid transparent;
            border-radius: 999px;
            font-family: inherit;
            font-size: .88rem;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: .01em;
            white-space: nowrap;
            cursor: pointer;
            transition: transform .2s ease, background .2s ease, border-color .2s ease, color .2s ease, box-shadow .2s ease;
        }

        .btn-primary {
            background: var(--accent);
            color: #1b1b1b;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            background: var(--accent-hover);
            box-shadow: 0 10px 22px rgba(225, 91, 42, .2);
        }

        .btn-secondary {
            background: transparent;
            border-color: var(--border-strong);
            color: var(--text);
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            border-color: var(--accent);
            background: var(--surface);
            color: var(--accent-dark);
        }

        .eyebrow {
            margin: 0 0 .75rem;
            color: var(--accent-dark);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .14em;
            line-height: 1.4;
            text-transform: uppercase;
        }

        :focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid var(--border);
            background: rgba(251, 250, 247, .94);
            backdrop-filter: blur(12px);
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            height: 72px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            flex: 0 0 auto;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -.025em;
            line-height: 1;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            border-radius: 10px;
            background: var(--accent);
            color: #1b1b1b;
            font-size: .95rem;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.8rem;
            margin-left: auto;
            color: #4b4842;
            font-size: .88rem;
            font-weight: 600;
        }

        .nav-links a {
            padding: .25rem 0;
            transition: color .2s ease;
        }

        .nav-links a:hover {
            color: var(--accent-dark);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .nav-login {
            padding: .65rem 1.05rem;
            font-size: .84rem;
        }

        .nav-toggle {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .nav-burger {
            display: none;
            place-items: center;
            gap: 4px;
            width: 42px;
            height: 42px;
            padding: 0;
            border: 1px solid var(--border-strong);
            border-radius: 12px;
            background: var(--surface);
            cursor: pointer;
        }

        .nav-burger span {
            display: block;
            width: 18px;
            height: 2px;
            border-radius: 2px;
            background: var(--text);
            transition: transform .2s ease, opacity .2s ease;
        }

        .mobile-nav {
            display: none;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
            box-shadow: 0 18px 30px rgba(27, 27, 27, .06);
        }

        .mobile-nav-inner {
            display: flex;
            flex-direction: column;
            padding-top: .5rem;
            padding-bottom: 1.25rem;
        }

        .mobile-nav-inner > a:not(.btn) {
            padding: .8rem 0;
            border-bottom: 1px solid var(--border);
            font-size: .95rem;
            font-weight: 600;
        }

        .mobile-nav-inner > .btn {
            width: 100%;
            margin-top: 1rem;
        }

        .hero {
            padding: 4.5rem 0 4rem;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
            gap: 4rem;
            align-items: center;
        }

        .hero-copy,
        .hero-visual {
            min-width: 0;
        }

        .hero h1 {
            margin: 0 0 1.1rem;
            font-size: clamp(2.1rem, 3.8vw, 3.4rem);
            font-weight: 800;
            letter-spacing: -.045em;
            line-height: 1.06;
            text-wrap: balance;
        }

        .hero h1 span {
            color: var(--accent-dark);
        }

        .hero-lead {
            max-width: 46ch;
            margin: 0 0 1.6rem;
            color: var(--muted);
            font-size: 1.02rem;
            line-height: 1.65;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-bottom: 1.5rem;
        }

        .hero-points {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem 1.4rem;
            margin: 0;
            padding: 0;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 600;
            list-style: none;
        }

        .hero-points li {
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .hero-points svg {
            width: 15px;
            height: 15px;
            flex: 0 0 auto;
            color: var(--green);
        }

        .hero-visual {
            position: relative;
        }

        .hero-media {
            position: relative;
            overflow: hidden;
            aspect-ratio: 4 / 3;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: var(--surface-soft);
        }

        .hero-media img,
        .hero-illustration {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-illustration {
            display: block;
        }

        .availability {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .42rem .7rem;
            border: 1px solid rgba(27, 27, 27, .08);
            border-radius: 999px;
            background: rgba(255, 255, 255, .94);
            color: var(--text);
            font-size: .72rem;
            font-weight: 700;
            backdrop-filter: blur(6px);
        }

        .dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            flex: 0 0 auto;
            border-radius: 50%;
            background: var(--green);
        }

        .hero-caption {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .9rem .2rem 0;
            color: var(--muted);
            font-size: .85rem;
        }

        .hero-caption strong {
            color: var(--text);
            font-size: .95rem;
            font-weight: 800;
        }

        .hero-caption small {
            color: var(--muted);
            font-size: .75rem;
            font-weight: 600;
        }

        .section {
            padding: 4.5rem 0;
        }

        .section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section-head > div {
            min-width: 0;
        }

        .section-head h2 {
            margin: 0 0 .55rem;
            font-size: clamp(1.7rem, 2.8vw, 2.4rem);
            font-weight: 800;
            letter-spacing: -.035em;
            line-height: 1.12;
            text-wrap: balance;
        }

        .section-head p:not(.eyebrow) {
            max-width: 55ch;
            margin: 0;
            color: var(--muted);
            font-size: .95rem;
        }

        .section-side {
            display: flex;
            flex: 0 0 auto;
            flex-direction: column;
            align-items: flex-end;
            gap: .65rem;
        }

        .count {
            display: inline-flex;
            align-items: center;
            padding: .4rem .7rem;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--surface);
            color: var(--muted);
            font-size: .75rem;
            font-weight: 700;
        }

        .text-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            color: var(--accent-dark);
            font-size: .85rem;
            font-weight: 800;
            white-space: nowrap;
            transition: gap .2s ease;
        }

        .text-link:hover {
            gap: .55rem;
        }

        .text-link svg {
            width: 15px;
            height: 15px;
        }

        .motor-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .motor-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .motor-card:hover {
            transform: translateY(-3px);
            border-color: var(--border-strong);
            box-shadow: 0 16px 34px rgba(27, 27, 27, .07);
        }

        .motor-media {
            position: relative;
            display: block;
            overflow: hidden;
            aspect-ratio: 4 / 3;
            background: var(--surface-soft);
        }

        .motor-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .motor-placeholder {
            display: grid;
            place-items: center;
            width: 100%;
            height: 100%;
            background: var(--surface-soft);
        }

        .motor-placeholder svg {
            width: 60%;
            height: auto;
        }

        .motor-status {
            position: absolute;
            top: .8rem;
            left: .8rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .6rem;
            border: 1px solid rgba(27, 27, 27, .08);
            border-radius: 999px;
            background: rgba(255, 255, 255, .94);
            color: var(--text);
            font-size: .68rem;
            font-weight: 700;
            backdrop-filter: blur(6px);
        }

        .motor-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 1.1rem 1.15rem 1.2rem;
        }

        .motor-title {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .motor-title h3 {
            margin: 0;
            font-size: 1.08rem;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.25;
        }

        .cc {
            flex: 0 0 auto;
            padding: .28rem .5rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-dark);
            font-size: .68rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .motor-plate {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin: .4rem 0 0;
            color: var(--muted);
            font-size: .8rem;
        }

        .motor-plate svg {
            width: 14px;
            height: 14px;
            flex: 0 0 auto;
        }

        .price-list {
            margin-top: .9rem;
            border-top: 1px solid var(--border);
        }

        .price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .42rem 0;
            font-size: .78rem;
        }

        .price-row + .price-row {
            border-top: 1px solid #f1ede7;
        }

        .price-row span {
            color: var(--muted);
        }

        .price-row strong {
            color: var(--text);
            font-size: .82rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .price-empty {
            margin: .9rem 0 0;
            padding: .65rem 0;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: .8rem;
        }

        .card-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-top: auto;
            padding-top: .9rem;
            border-top: 1px solid var(--border);
            color: var(--accent-dark);
            font-size: .82rem;
            font-weight: 800;
            transition: gap .2s ease;
        }

        .price-list + .card-link,
        .price-empty + .card-link {
            margin-top: .9rem;
        }

        .card-link:hover {
            gap: .55rem;
        }

        .card-link svg {
            width: 15px;
            height: 15px;
        }

        .rate-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1px;
            overflow: hidden;
            margin-top: 1.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--border);
        }

        .rate-item {
            display: flex;
            flex-direction: column;
            gap: .15rem;
            padding: 1.1rem 1.25rem;
            background: var(--surface);
        }

        .rate-label {
            color: var(--muted-light);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .rate-item strong {
            color: var(--text);
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .rate-item small {
            color: var(--muted);
            font-size: .7rem;
            font-weight: 600;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3rem 1.5rem;
            border: 1px dashed var(--border-strong);
            border-radius: var(--radius-lg);
            background: var(--surface);
            text-align: center;
        }

        .empty-icon {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            border-radius: 50%;
            background: var(--surface-soft);
            color: var(--accent-dark);
        }

        .empty-icon svg {
            width: 32px;
            height: 32px;
        }

        .empty-state h3 {
            margin: 0 0 .4rem;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .empty-state p {
            max-width: 42ch;
            margin: 0 0 1.25rem;
            color: var(--muted);
            font-size: .88rem;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 2rem;
            margin: 2.25rem 0 0;
            padding: 0;
            list-style: none;
        }

        .step {
            min-width: 0;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-strong);
        }

        .step-number {
            display: inline-block;
            margin-bottom: .7rem;
            color: var(--accent-dark);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .12em;
        }

        .step h3 {
            margin: 0 0 .35rem;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .step p {
            margin: 0;
            color: var(--muted);
            font-size: .85rem;
            line-height: 1.55;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 2rem 3rem;
            margin-top: 2.25rem;
        }

        .feature {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            min-width: 0;
        }

        .feature-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            border-radius: 12px;
            background: var(--accent-soft);
            color: var(--accent-dark);
        }

        .feature-icon svg {
            width: 21px;
            height: 21px;
        }

        .feature h3 {
            margin: 0 0 .25rem;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .feature p {
            margin: 0;
            color: var(--muted);
            font-size: .85rem;
            line-height: 1.55;
        }

        .cta-section {
            padding: 2rem 0 4.5rem;
        }

        .cta-inner {
            padding: 3rem 2rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: var(--surface-soft);
            text-align: center;
        }

        .cta-inner h2 {
            max-width: 20ch;
            margin: 0 auto .6rem;
            font-size: clamp(1.6rem, 3vw, 2.35rem);
            font-weight: 800;
            letter-spacing: -.04em;
            line-height: 1.12;
            text-wrap: balance;
        }

        .cta-inner p:not(.eyebrow) {
            max-width: 48ch;
            margin: 0 auto 1.5rem;
            color: var(--muted);
            font-size: .95rem;
        }

        .site-footer {
            padding: 2.75rem 0 1.75rem;
            border-top: 1px solid var(--border);
            background: var(--surface-soft);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        .footer-brand p {
            max-width: 42ch;
            margin: .8rem 0 0;
            color: var(--muted);
            font-size: .88rem;
            line-height: 1.6;
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .8rem 1.5rem;
            color: #4b4842;
            font-size: .85rem;
            font-weight: 700;
        }

        .footer-links a:hover {
            color: var(--accent-dark);
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            color: var(--muted-light);
            font-size: .75rem;
        }

        @media (max-width: 960px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }

            .hero-copy {
                max-width: 640px;
            }

            .hero-visual {
                width: 100%;
                max-width: 560px;
            }

            .motor-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 880px) {
            .nav-links,
            .nav-login {
                display: none;
            }

            .nav-burger {
                display: grid;
            }

            #nav-toggle:checked ~ .mobile-nav {
                display: block;
            }

            #nav-toggle:checked ~ .nav .nav-burger span:nth-child(1) {
                transform: translateY(6px) rotate(45deg);
            }

            #nav-toggle:checked ~ .nav .nav-burger span:nth-child(2) {
                opacity: 0;
            }

            #nav-toggle:checked ~ .nav .nav-burger span:nth-child(3) {
                transform: translateY(-6px) rotate(-45deg);
            }

            #nav-toggle:focus-visible ~ .nav .nav-burger {
                outline: 2px solid var(--accent);
                outline-offset: 3px;
            }
        }

        @media (max-width: 760px) {
            .section-head {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .section-side {
                align-items: flex-start;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .rate-strip {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }

            .footer-links {
                justify-content: flex-start;
            }
        }

        @media (max-width: 680px) {
            .motor-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .container {
                width: min(100% - 2rem, var(--container));
            }

            .nav {
                height: 64px;
            }

            .hero {
                padding: 3rem 0 2.75rem;
            }

            .hero h1 {
                font-size: clamp(2rem, 9vw, 2.6rem);
            }

            .hero-lead {
                font-size: .95rem;
            }

            .hero-actions .btn {
                flex: 1 1 auto;
            }

            .section {
                padding: 3.25rem 0;
            }

            .section-head {
                margin-bottom: 1.5rem;
            }

            .steps {
                gap: 1.25rem;
            }

            .features {
                gap: 1.5rem;
            }

            .cta-section {
                padding: 1.5rem 0 3.25rem;
            }

            .cta-inner {
                padding: 2.25rem 1.25rem;
            }

            .hero-caption {
                font-size: .8rem;
            }
        }

        @media (max-width: 380px) {
            .brand {
                font-size: 1rem;
            }

            .brand-mark {
                width: 32px;
                height: 32px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions .btn {
                width: 100%;
            }

            .hero-points {
                flex-direction: column;
                gap: .5rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true">
        <symbol id="scooter-illustration" viewBox="0 0 560 420">
            <circle cx="280" cy="210" r="190" fill="#f4ede6" />
            <ellipse cx="285" cy="335" rx="165" ry="18" fill="#e9ded2" />
            <circle cx="175" cy="300" r="54" fill="#1f1f1f" />
            <circle cx="175" cy="300" r="30" fill="#fbf7f1" />
            <circle cx="175" cy="300" r="10" fill="#1f1f1f" />
            <circle cx="395" cy="300" r="54" fill="#1f1f1f" />
            <circle cx="395" cy="300" r="30" fill="#fbf7f1" />
            <circle cx="395" cy="300" r="10" fill="#1f1f1f" />
            <path d="M128 268c0-22 18-40 40-40h48c22 0 40 18 40 40v28H128z" fill="#e15b2a" />
            <rect x="142" y="214" width="112" height="26" rx="13" fill="#2b2b2b" />
            <path d="M238 276h92c14 0 24 10 24 24v6H238z" fill="#e15b2a" />
            <path d="M328 276l24-86c4-16 20-26 36-21l10 3-12 40-22 64z" fill="#e15b2a" />
            <path d="M350 244l32 54" stroke="#2b2b2b" stroke-width="10" stroke-linecap="round" />
            <path d="M366 166l48-12" stroke="#2b2b2b" stroke-width="12" stroke-linecap="round" />
            <circle cx="418" cy="152" r="9" fill="#2b2b2b" />
            <circle cx="356" cy="216" r="14" fill="#fbf7f1" stroke="#2b2b2b" stroke-width="6" />
            <path d="M352 270c16-10 38-10 56 0" stroke="#2b2b2b" stroke-width="10" stroke-linecap="round" fill="none" />
            <path d="M124 280c-10 6-16 14-18 24" stroke="#2b2b2b" stroke-width="10" stroke-linecap="round" fill="none" />
            <path d="M150 280h74" stroke="#fbf7f1" stroke-width="8" stroke-linecap="round" />
        </symbol>
    </svg>

    <header class="site-header">
        <input type="checkbox" id="nav-toggle" class="nav-toggle">

        <div class="container nav">
            <a class="brand" href="{{ route('home') }}" aria-label="Rasya Rental, kembali ke beranda">
                <span class="brand-mark" aria-hidden="true">R</span>
                <span>Rasya Rental</span>
            </a>

            <nav class="nav-links" aria-label="Navigasi utama">
                <a href="#beranda">Beranda</a>
                <a href="#motor">Motor</a>
                <a href="#cara-sewa">Cara Sewa</a>
                <a href="#tarif">Tarif</a>
            </nav>

            <div class="nav-actions">
                <a class="btn btn-primary nav-login" href="{{ $accountUrl }}">{{ $accountLabel }}</a>
                <label class="nav-burger" for="nav-toggle" aria-label="Buka menu" aria-controls="mobile-nav">
                    <span></span>
                    <span></span>
                    <span></span>
                </label>
            </div>
        </div>

        <div class="mobile-nav" id="mobile-nav">
            <nav class="container mobile-nav-inner" aria-label="Navigasi mobile">
                <a href="#beranda">Beranda</a>
                <a href="#motor">Motor</a>
                <a href="#cara-sewa">Cara Sewa</a>
                <a href="#tarif">Tarif</a>
                <a class="btn btn-primary" href="{{ $accountUrl }}">{{ $accountLabel }}</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero" id="beranda">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Rental motor di Jakarta</p>
                    <h1>Sewa Motor Lebih Mudah.<br><span>Jalan Lebih Bebas.</span></h1>
                    <p class="hero-lead">Rasya Rental menyediakan motor terawat dengan tarif transparan dan proses sewa yang cepat, tanpa ribet.</p>

                    <div class="hero-actions">
                        <a class="btn btn-primary" href="#motor">Lihat Motor</a>
                        <a class="btn btn-secondary" href="#cara-sewa">Cara Sewa</a>
                    </div>

                    <ul class="hero-points">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m5 12 4 4L19 6" />
                            </svg>
                            Armada terawat
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m5 12 4 4L19 6" />
                            </svg>
                            Tarif transparan
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m5 12 4 4L19 6" />
                            </svg>
                            Proses cepat
                        </li>
                    </ul>
                </div>

                <div class="hero-visual">
                    <div class="hero-media">
                        @if ($heroMotor && $heroMotor->photo)
                            <img src="{{ $heroMotor->urlPhoto() }}" alt="Motor {{ $heroMotor->merk }}" loading="eager" decoding="async">
                        @else
                            <svg class="hero-illustration" viewBox="0 0 560 420" role="img" aria-label="Ilustrasi motor Rasya Rental">
                                <use href="#scooter-illustration"></use>
                            </svg>
                        @endif

                        @if ($heroMotor)
                            <span class="availability"><span class="dot"></span> Tersedia</span>
                        @endif
                    </div>

                    @if ($tarif['harian'])
                        <div class="hero-caption">
                            <span>Mulai dari</span>
                            <strong>{{ $rupiah($tarif['harian']) }} <small>/ hari</small></strong>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="section" id="motor">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Motor tersedia</p>
                        <h2>Pilih motor untuk perjalananmu.</h2>
                        <p>Unit terverifikasi dengan tarif jelas. Data ditampilkan langsung dari sistem Rasya Rental.</p>
                    </div>

                    <div class="section-side">
                        <span class="count">
                            @if ($totalTersedia > 0)
                                {{ $totalTersedia }} unit tersedia
                            @else
                                Belum ada unit
                            @endif
                        </span>

                        @if ($totalTersedia > $motorTersedia->count())
                            <a class="text-link" href="{{ $motorIndexUrl }}">
                                Lihat semua motor
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                @if ($motorTersedia->isEmpty())
                    <div class="empty-state">
                        <span class="empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="5.5" cy="17.5" r="3" />
                                <circle cx="18.5" cy="17.5" r="3" />
                                <path d="M8.5 17.5h5l2-6.5H11l-1.5 4M15.5 11l2 6.5M14.5 7.5h3l1 2" />
                            </svg>
                        </span>
                        <h3>Belum ada motor tersedia</h3>
                        <p>Semua unit sedang disewa atau dalam proses verifikasi. Silakan cek kembali nanti.</p>
                        <a class="btn btn-primary" href="{{ $accountUrl }}">{{ $user ? 'Buka Dashboard' : 'Masuk untuk Melihat' }}</a>
                    </div>
                @else
                    <div class="motor-grid">
                        @foreach ($motorTersedia as $motor)
                            @php
                                if (! $user) {
                                    $detailUrl = route('login');
                                } elseif ($user->role === 'penyewa') {
                                    $detailUrl = route('penyewa.motor.show', $motor);
                                } else {
                                    $detailUrl = route('dashboard');
                                }
                            @endphp

                            <article class="motor-card">
                                <a class="motor-media" href="{{ $detailUrl }}" aria-label="Lihat motor {{ $motor->merk }}">
                                    @if ($motor->photo)
                                        <img src="{{ $motor->urlPhoto() }}" alt="Motor {{ $motor->merk }} {{ $motor->no_plat }}" loading="lazy" decoding="async">
                                    @else
                                        <span class="motor-placeholder" aria-hidden="true">
                                            <svg viewBox="0 0 560 420">
                                                <use href="#scooter-illustration"></use>
                                            </svg>
                                        </span>
                                    @endif

                                    <span class="motor-status"><span class="dot"></span> Tersedia</span>
                                </a>

                                <div class="motor-body">
                                    <div class="motor-title">
                                        <h3>{{ $motor->merk }}</h3>
                                        <span class="cc">{{ $motor->tipe_cc }} cc</span>
                                    </div>

                                    <p class="motor-plate">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="5" width="18" height="14" rx="3" />
                                            <path d="M7 9h4M7 13h2M15 9h2M13 15h4" />
                                        </svg>
                                        {{ $motor->no_plat }}
                                    </p>

                                    @if ($motor->tarifRental)
                                        <div class="price-list">
                                            <div class="price-row">
                                                <span>Harian</span>
                                                <strong>{{ $rupiah($motor->tarifRental->tarif_harian) }}</strong>
                                            </div>
                                            <div class="price-row">
                                                <span>Mingguan</span>
                                                <strong>{{ $rupiah($motor->tarifRental->tarif_mingguan) }}</strong>
                                            </div>
                                            <div class="price-row">
                                                <span>Bulanan</span>
                                                <strong>{{ $rupiah($motor->tarifRental->tarif_bulanan) }}</strong>
                                            </div>
                                        </div>
                                    @else
                                        <p class="price-empty">Tarif belum ditentukan</p>
                                    @endif

                                    <a class="card-link" href="{{ $detailUrl }}">
                                        Lihat Motor
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 12h14M13 6l6 6-6 6" />
                                        </svg>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                <div class="rate-strip" id="tarif">
                    <div class="rate-item">
                        <span class="rate-label">Harian</span>
                        <strong>{{ $tarif['harian'] ? $rupiah($tarif['harian']) : 'Sesuai unit' }}</strong>
                        <small>mulai dari</small>
                    </div>
                    <div class="rate-item">
                        <span class="rate-label">Mingguan</span>
                        <strong>{{ $tarif['mingguan'] ? $rupiah($tarif['mingguan']) : 'Sesuai unit' }}</strong>
                        <small>mulai dari</small>
                    </div>
                    <div class="rate-item">
                        <span class="rate-label">Bulanan</span>
                        <strong>{{ $tarif['bulanan'] ? $rupiah($tarif['bulanan']) : 'Sesuai unit' }}</strong>
                        <small>mulai dari</small>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="cara-sewa">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Cara sewa</p>
                        <h2>Empat langkah, motor siap jalan.</h2>
                        <p>Proses sewa dibuat singkat dan jelas, dari memilih motor sampai mulai berkendara.</p>
                    </div>
                </div>

                <ol class="steps">
                    <li class="step">
                        <span class="step-number">01</span>
                        <h3>Pilih Motor</h3>
                        <p>Temukan motor yang sesuai kebutuhan dan lokasi.</p>
                    </li>
                    <li class="step">
                        <span class="step-number">02</span>
                        <h3>Tentukan Durasi</h3>
                        <p>Pilih sewa harian, mingguan, atau bulanan.</p>
                    </li>
                    <li class="step">
                        <span class="step-number">03</span>
                        <h3>Lakukan Pembayaran</h3>
                        <p>Selesaikan pembayaran melalui metode yang tersedia.</p>
                    </li>
                    <li class="step">
                        <span class="step-number">04</span>
                        <h3>Motor Siap Digunakan</h3>
                        <p>Ambil motor dan mulai perjalananmu.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="section" id="kenapa">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Kenapa Rasya Rental?</p>
                        <h2>Rental yang simpel, tanpa ribet.</h2>
                        <p>Kami fokus pada hal yang paling penting: motor siap pakai, harga jelas, dan proses yang mudah.</p>
                    </div>
                </div>

                <div class="features">
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="5.5" cy="17.5" r="3" />
                                <circle cx="18.5" cy="17.5" r="3" />
                                <path d="M8.5 17.5h5l2-6.5H11l-1.5 4M15.5 11l2 6.5M14.5 7.5h3l1 2" />
                            </svg>
                        </span>
                        <div>
                            <h3>Pilihan Motor</h3>
                            <p>Beragam motor terawat untuk kebutuhan harian hingga bulanan.</p>
                        </div>
                    </div>

                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z" />
                                <circle cx="8" cy="8" r="1.5" />
                            </svg>
                        </span>
                        <div>
                            <h3>Tarif Transparan</h3>
                            <p>Harga jelas sejak awal, tanpa biaya tersembunyi.</p>
                        </div>
                    </div>

                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 6h10a4 4 0 0 1 4 4v8" />
                                <path d="m14 14 4 4 4-4" />
                                <circle cx="5" cy="18" r="2" />
                            </svg>
                        </span>
                        <div>
                            <h3>Proses Mudah</h3>
                            <p>Pemesanan sederhana dan status sewa mudah dipantau.</p>
                        </div>
                    </div>

                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m13 2-9 12h7l-1 8 9-12h-7l1-8Z" />
                            </svg>
                        </span>
                        <div>
                            <h3>Pemesanan Cepat</h3>
                            <p>Pilih motor, tentukan durasi, lalu lanjutkan pembayaran.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <div class="cta-inner">
                    <p class="eyebrow">Siap berkendara?</p>
                    <h2>Siap Jalan? Pilih Motor Favoritmu.</h2>
                    <p>Armada terawat, tarif transparan, dan proses pemesanan yang cepat.</p>
                    <a class="btn btn-primary" href="#motor">Lihat Motor</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a class="brand" href="{{ route('home') }}" aria-label="Rasya Rental, kembali ke beranda">
                        <span class="brand-mark" aria-hidden="true">R</span>
                        <span>Rasya Rental</span>
                    </a>
                    <p>Rental motor terawat dengan tarif transparan dan proses sewa yang mudah.</p>
                </div>

                <nav class="footer-links" aria-label="Navigasi footer">
                    <a href="#beranda">Beranda</a>
                    <a href="#motor">Motor</a>
                    <a href="#cara-sewa">Cara Sewa</a>
                    <a href="{{ $accountUrl }}">{{ $accountLabel }}</a>
                </nav>
            </div>

            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} Rasya Rental. Semua hak dilindungi.</span>
                <span>Jakarta, Indonesia</span>
            </div>
        </div>
    </footer>

    <script>
        const navToggle = document.getElementById('nav-toggle');

        document.querySelectorAll('.mobile-nav a').forEach((link) => {
            link.addEventListener('click', () => {
                navToggle.checked = false;
            });
        });
    </script>
</body>
</html>
