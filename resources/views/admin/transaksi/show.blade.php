@extends('layouts.dashboard')

@section('title', 'Detail Pembayaran')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Pembayaran #{{ $transaksi->id }} <x-badge :value="$transaksi->status" /></h1>
                <p class="muted" style="margin:.25rem 0 0">
                    Penyewaan #{{ $transaksi->penyewaan?->id }} &middot;
                    {{ $transaksi->penyewaan?->motor?->no_plat ?? '-' }}
                </p>
            </div>
            <a href="{{ route('admin.transaksi.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Penyewa</div>
                <div class="value">{{ $transaksi->penyewaan?->penyewa?->nama ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Kontak</div>
                <div class="value">{{ $transaksi->penyewaan?->penyewa?->no_tlpn ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Pemilik Motor</div>
                <div class="value">{{ $transaksi->penyewaan?->motor?->pemilik?->nama ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Jumlah Dibayar</div>
                <div class="value money">Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Harga Penyewaan</div>
                <div class="value money">Rp {{ number_format($transaksi->penyewaan?->harga ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Metode</div>
                <div class="value">{{ ucfirst($transaksi->metode_pembayaran) }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tanggal Transaksi</div>
                <div class="value">{{ $transaksi->tanggal->format('d/m/Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Status Penyewaan</div>
                <div class="value"><x-badge :value="$transaksi->penyewaan?->status ?? 'pending'" /></div>
            </div>
        </div>

        @if ((float) $transaksi->jumlah !== (float) ($transaksi->penyewaan?->harga ?? 0))
            <div class="alert alert-error" style="margin-top:1.25rem">
                Nominal transaksi berbeda dari harga penyewaan. Periksa kembali sebelum verifikasi.
            </div>
        @endif
    </div>

    @can('proses', $transaksi)
        <div class="card">
            <h2 class="section-title">Verifikasi Pembayaran</h2>

            <form method="POST" action="{{ route('admin.transaksi.proses', $transaksi) }}" class="filter-bar">
                @csrf
                @method('PATCH')

                <div class="field">
                    <label for="status">Ubah status menjadi</label>
                    <select id="status" name="status" required>
                        <option value="">-- Pilih --</option>
                        @foreach (\App\Models\Transaksi::STATUSES as $s)
                            @continue($s === $transaksi->status)
                            <option value="{{ $s }}" {{ old('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-sm">Simpan Status</button>
            </form>

            <p class="hint" style="margin-bottom:0">
                Status <strong>dibayar</strong> akan mengonfirmasi penyewaan dan menandai motor
                <strong>disewa</strong>. Status <strong>ditolak</strong> mengembalikan penyewaan ke
                <strong>pending</strong> agar penyewa dapat membayar ulang.
            </p>
        </div>
    @endcan
@endsection
