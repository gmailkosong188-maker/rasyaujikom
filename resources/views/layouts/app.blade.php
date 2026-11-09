<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Penyewaan Motor')</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --danger: #dc2626;
            --success: #16a34a;
            --border: #e5e7eb;
            --muted: #6b7280;
            --bg: #f3f4f6;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: #111827;
            line-height: 1.5;
        }

        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* ---- Layout ---- */
        .navbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            flex-wrap: wrap;
        }

        .navbar .brand { font-weight: 700; font-size: 1.05rem; color: #111827; }
        .navbar .brand:hover { text-decoration: none; }
        .navbar nav { display: flex; align-items: center; gap: 1rem; }
        .navbar nav a { font-size: .9rem; color: #374151; }

        .badge {
            display: inline-block;
            padding: .15rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .badge-admin { background: #dbeafe; color: #1e40af; }
        .badge-pemilik { background: #dcfce7; color: #166534; }
        .badge-penyewa { background: #fef3c7; color: #92400e; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-muted { background: #f3f4f6; color: #4b5563; }

        .container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: .6rem;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .card h1, .card h2 { margin-top: 0; }

        /* ---- Auth ---- */
        .auth-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: .6rem;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .04);
            overflow-wrap: break-word;
        }

        .auth-card h1 { margin: 0 0 .25rem; font-size: 1.4rem; }
        .auth-sub { color: var(--muted); font-size: .9rem; margin: 0 0 1.5rem; }

        /* ---- Forms ---- */
        .field { margin-bottom: 1rem; }
        .field label { display: block; font-size: .87rem; font-weight: 600; margin-bottom: .35rem; }

        .field input, .field select {
            width: 100%;
            padding: .55rem .7rem;
            border: 1px solid #d1d5db;
            border-radius: .4rem;
            font-size: .95rem;
            font-family: inherit;
            background: #fff;
            max-width: 100%;
        }

        /* Checkbox/radio jangan ikut melebar 100% seperti input teks. */
        .field input[type="checkbox"],
        .field input[type="radio"] {
            width: auto;
            max-width: none;
            flex: 0 0 auto;
        }

        .field-check { display: flex; align-items: center; gap: .5rem; }
        .field-check label { margin: 0; font-weight: 400; }

        .field input:focus, .field select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }

        .field .error { color: var(--danger); font-size: .8rem; margin-top: .3rem; }
        .field input.is-invalid, .field select.is-invalid { border-color: var(--danger); }

        .btn {
            display: inline-block;
            padding: .6rem 1.1rem;
            border-radius: .4rem;
            border: 1px solid transparent;
            font-size: .92rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
        }

        .btn:hover { background: var(--primary-dark); text-decoration: none; }
        .btn-block { width: 100%; text-align: center; }
        .btn-ghost { background: #fff; color: #374151; border-color: #d1d5db; }
        .btn-ghost:hover { background: #f9fafb; }

        /* ---- Alerts ---- */
        .alert {
            padding: .7rem 1rem;
            border-radius: .4rem;
            font-size: .88rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }

        .alert-success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .alert-info { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }

        .auth-footer { margin-top: 1.25rem; font-size: .87rem; color: var(--muted); text-align: center; }

        /* ---- Stats ---- */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; }

        .stat {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: .6rem;
            padding: 1rem 1.15rem;
        }

        .stat .label { font-size: .78rem; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
        .stat .value { font-size: 1.6rem; font-weight: 700; margin-top: .25rem; }

        .muted { color: var(--muted); font-size: .9rem; }
        .inline-form { display: inline; }
        .hint { font-size: .8rem; color: var(--muted); margin-top: .35rem; }

        /* ---- Tabel ---- */
        table.data { width: 100%; border-collapse: collapse; font-size: .88rem; }        table.data th {
            text-align: left; font-size: .75rem; text-transform: uppercase; letter-spacing: .03em;
            color: var(--muted); border-bottom: 1px solid var(--border); padding: .5rem .6rem;
        }
        table.data td { padding: .6rem; border-bottom: 1px solid #f3f4f6; }
        table.data tr:last-child td { border-bottom: none; }
        .empty { color: var(--muted); font-size: .88rem; padding: .5rem 0; }
        .money { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .section-title { font-size: 1rem; font-weight: 700; margin: 0 0 1rem; }

        /* ---- Navigasi menu role ---- */
        .menu { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
        .menu a {
            padding: .4rem .85rem; border-radius: .4rem; background: #fff;
            border: 1px solid var(--border); font-size: .85rem; color: #374151; font-weight: 600;
        }
        .menu a.active { background: var(--primary); border-color: var(--primary); color: #fff; }

        /* ---- Kartu motor ---- */
        .motor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
        .motor-card {
            background: #fff; border: 1px solid var(--border); border-radius: .6rem;
            overflow: hidden; display: flex; flex-direction: column;
        }
        .motor-thumb {
            height: 150px; background: #f3f4f6; display: flex; align-items: center;
            justify-content: center; overflow: hidden;
        }
        .motor-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .thumb-empty { color: var(--muted); font-size: .8rem; }
        .motor-body { padding: .85rem 1rem 1rem; }
        .motor-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .25rem; }
        .motor-price { margin-top: .55rem; font-weight: 600; }

        /* ---- Filter bar ---- */
        .filter-bar { display: flex; gap: .6rem; flex-wrap: wrap; align-items: flex-end; }
        .filter-bar .field { margin-bottom: 0; }
        .filter-bar input, .filter-bar select { min-width: 150px; }

        /* ---- Detail ---- */
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }

        /* ---- Grafik batang sederhana (laporan) ---- */
        .bar-chart { display: flex; align-items: flex-end; gap: .5rem; height: 140px; overflow-x: auto; padding-top: .5rem; }
        .bar-item { display: flex; flex-direction: column; justify-content: flex-end; align-items: center; min-width: 44px; height: 100%; }
        .bar-item .bar { width: 100%; max-width: 34px; background: #3b82f6; border-radius: .25rem .25rem 0 0; }
        .bar-item .bar-value { font-size: .7rem; font-weight: 600; margin-bottom: .2rem; }
        .bar-item .bar-label { font-size: .65rem; color: var(--muted); margin-top: .3rem; white-space: nowrap; }

        .detail-item .label { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
        .detail-item .value { font-weight: 600; margin-top: .15rem; }
        .doc-preview { max-width: 100%; border: 1px solid var(--border); border-radius: .4rem; margin-top: .5rem; }
        .pagination { display: flex; gap: .35rem; list-style: none; padding: 0; margin: 1.25rem 0 0; flex-wrap: wrap; }
        .pagination li a, .pagination li span {
            display: inline-block; padding: .35rem .7rem; border: 1px solid var(--border);
            border-radius: .35rem; font-size: .85rem; background: #fff; color: #374151;
        }
        .pagination li.active span { background: var(--primary); border-color: var(--primary); color: #fff; }
        .btn-sm { padding: .35rem .8rem; font-size: .83rem; }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #b91c1c; }
        .btn-row { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; }
        .inline { display: inline; }

        /* ---- Responsif (mobile) ---- */
        @media (max-width: 760px) {
            .navbar { height: auto; padding: .75rem 1rem; gap: .5rem; }
            .navbar nav { gap: .6rem; flex-wrap: wrap; }
            .container { padding: 1rem; }
            .card { padding: 1rem; }
            .stat .value { font-size: 1.3rem; }

            .auth-wrap { padding: 1rem; }
            .auth-card { padding: 1.25rem; max-width: 100%; }

            /* Tabel lebar -> bisa digeser horizontal, bukan memotong layout. */
            .card > table.data {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap;
            }

            .filter-bar { flex-direction: column; align-items: stretch; }
            .filter-bar .field, .filter-bar input, .filter-bar select { width: 100%; min-width: 0; }
            .filter-bar .btn { width: 100%; text-align: center; }

            .detail-grid { grid-template-columns: 1fr; }
            .motor-grid { grid-template-columns: 1fr; }
            .stats { grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); }
        }
    </style>
</head>

<body>
    @yield('body')
</body>

</html>
