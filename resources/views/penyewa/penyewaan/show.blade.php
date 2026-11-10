@extends('layouts.dashboard')

@section('title', 'Detail Penyewaan')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Penyewaan #{{ $penyewaan->id }} <x-badge :value="$penyewaan->status" /></h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $penyewaan->motor?->merk }} &middot; {{ $penyewaan->motor?->no_plat }}
                </p>
            </div>
            <a href="{{ route('penyewa.penyewaan.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        @if ($penyewaan->status === \App\Models\Penyewaan::STATUS_PENDING)
            <div class="alert alert-info" style="margin-top:1.25rem">
                Menunggu konfirmasi admin. Setelah dikonfirmasi, lakukan pembayaran sesuai instruksi admin.
            </div>
        @elseif ($penyewaan->status === \App\Models\Penyewaan::STATUS_DIKONFIRMASI)
            <div class="alert alert-success" style="margin-top:1.25rem">
                Penyewaan dikonfirmasi admin. Silakan lanjutkan pembayaran.
            </div>
        @elseif ($penyewaan->status === \App\Models\Penyewaan::STATUS_BERJALAN)
            <div class="alert alert-success" style="margin-top:1.25rem">Masa sewa sedang berjalan.</div>
        @elseif ($penyewaan->status === \App\Models\Penyewaan::STATUS_SELESAI)
            <div class="alert alert-success" style="margin-top:1.25rem">Penyewaan telah selesai.</div>
        @else
            <div class="alert alert-error" style="margin-top:1.25rem">Penyewaan dibatalkan.</div>
        @endif

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Tanggal Mulai</div>
                <div class="value">{{ $penyewaan->tanggal_mulai->format('d/m/Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tanggal Selesai</div>
                <div class="value">{{ $penyewaan->tanggal_selesai->format('d/m/Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tipe Durasi</div>
                <div class="value">{{ ucfirst($penyewaan->tipe_durasi) }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Jumlah Periode</div>
                <div class="value">{{ $penyewaan->jumlahPeriode() }} {{ $penyewaan->tipe_durasi }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Harga</div>
                <div class="value money">Rp {{ number_format($penyewaan->harga, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Pemilik Motor</div>
                <div class="value">{{ $penyewaan->motor?->pemilik?->nama ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Pembayaran</h2>

        @if ($penyewaan->transaksis->isEmpty())
            <p class="empty" style="margin:0">
                Belum ada pembayaran.
            </p>
        @else
            <table class="data">
                <thead>
                    <tr><th>Tanggal</th><th>Jumlah</th><th>Metode</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($penyewaan->transaksis as $t)
                        <tr>
                            <td>{{ $t->tanggal->format('d/m/Y') }}</td>
                            <td class="money">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                            <td>{{ ucfirst($t->metode_pembayaran) }}</td>
                            <td><x-badge :value="$t->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @can('batalkan', $penyewaan)
        <div class="card">
            <form method="POST" action="{{ route('penyewa.penyewaan.batalkan', $penyewaan) }}" class="inline"
                onsubmit="return confirm('Batalkan penyewaan ini?')">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-danger btn-sm">Batalkan Penyewaan</button>
            </form>
        </div>
    @endcan

    @if ($penyewaan->transaksis->isEmpty()
            && $penyewaan->status === \App\Models\Penyewaan::STATUS_DIKONFIRMASI)
        <div class="card">
            <a href="{{ route('penyewa.transaksi.create', ['penyewaan_id' => $penyewaan->id]) }}" class="btn">
                Lakukan Pembayaran
            </a>
        </div>
    @endif

    @can('ajukanPengembalian', $penyewaan)
        @php
            $sudahDiajukan = $penyewaan->transaksis
                ->where('status', \App\Models\Transaksi::STATUS_DIKEMBALIKAN)
                ->isNotEmpty();
        @endphp

        <div class="card">
            <h2 class="section-title">Pengembalian Motor</h2>

            @if ($sudahDiajukan)
                <p class="muted" style="margin:0">
                    Pengembalian sudah diajukan dan <strong>menunggu pemeriksaan admin</strong>.
                </p>
            @else
                <p class="hint" style="margin-top:0">
                    Sudah mengembalikan motor ke admin? Ajukan pengembalian agar admin dapat memeriksa
                    dan mengonfirmasi.
                </p>
                <form method="POST" action="{{ route('penyewa.penyewaan.kembalikan', $penyewaan) }}"
                    onsubmit="return confirm('Ajukan pengembalian motor sekarang?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn">Ajukan Pengembalian</button>
                </form>
            @endif
        </div>
    @endcan
@endsection
