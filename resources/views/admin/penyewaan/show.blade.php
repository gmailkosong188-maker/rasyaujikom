@extends('layouts.dashboard')

@section('title', 'Detail Penyewaan')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Penyewaan #{{ $penyewaan->id }} <x-badge :value="$penyewaan->status" /></h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $penyewaan->penyewa?->nama }} &middot; {{ $penyewaan->motor?->merk }}
                    ({{ $penyewaan->motor?->no_plat }})
                </p>
            </div>
            <a href="{{ route('admin.penyewaan.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Penyewa</div>
                <div class="value">{{ $penyewaan->penyewa?->nama ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Kontak Penyewa</div>
                <div class="value">{{ $penyewaan->penyewa?->no_tlpn ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Pemilik Motor</div>
                <div class="value">{{ $penyewaan->motor?->pemilik?->nama ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Periode</div>
                <div class="value">
                    {{ $penyewaan->tanggal_mulai->format('d/m/Y') }} – {{ $penyewaan->tanggal_selesai->format('d/m/Y') }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Tipe Durasi</div>
                <div class="value">{{ ucfirst($penyewaan->tipe_durasi) }} ({{ $penyewaan->jumlahPeriode() }} periode)</div>
            </div>
            <div class="detail-item">
                <div class="label">Harga</div>
                <div class="value money">Rp {{ number_format($penyewaan->harga, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Status Motor Saat Ini</div>
                <div class="value"><x-badge :value="$penyewaan->motor?->status ?? 'pending'" /></div>
            </div>
        </div>
    </div>

    @can('proses', $penyewaan)
        <div class="card">
            <h2 class="section-title">Proses Status</h2>

            @if (empty($statusTersedia))
                <p class="muted" style="margin:0">
                    Penyewaan berstatus <strong>{{ ucfirst($penyewaan->status) }}</strong> sudah final.
                </p>
            @else
                <form method="POST" action="{{ route('admin.penyewaan.proses', $penyewaan) }}" class="filter-bar">
                    @csrf
                    @method('PATCH')
                    <div class="field">
                        <label for="status">Ubah status menjadi</label>
                        <select id="status" name="status" required>
                            <option value="">-- Pilih --</option>
                            @foreach ($statusTersedia as $s)
                                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm">Simpan Status</button>
                </form>
                <p class="hint" style="margin-bottom:0">
                    Konfirmasi/berjalan akan menandai motor sebagai <strong>disewa</strong>;
                    selesai/dibatalkan akan mengembalikan motor menjadi <strong>tersedia</strong>.
                </p>
            @endif
        </div>
    @endcan

    <div class="card">
        <h2 class="section-title">Pembayaran</h2>
        @if ($penyewaan->transaksis->isEmpty())
            <p class="empty" style="margin:0">Belum ada transaksi pembayaran.</p>
        @else
            <table class="data">
                <thead><tr><th>Tanggal</th><th>Jumlah</th><th>Metode</th><th>Status</th></tr></thead>
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
@endsection
