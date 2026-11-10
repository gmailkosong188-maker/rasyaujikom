@extends('layouts.dashboard')

@section('title', 'Detail Motor')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">{{ $motor->merk }} <x-badge :value="$motor->status" /></h1>
                <p class="muted" style="margin:.25rem 0 0">{{ $motor->no_plat }} &middot; {{ $motor->tipe_cc }} CC</p>
            </div>
            <a href="{{ route('admin.motor.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Pemilik</div>
                <div class="value">{{ $motor->pemilik?->nama ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Kontak Pemilik</div>
                <div class="value">{{ $motor->pemilik?->no_tlpn ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tarif Harian</div>
                <div class="value money">
                    {{ $motor->tarifRental ? 'Rp ' . number_format($motor->tarifRental->tarif_harian, 0, ',', '.') : 'Belum ditentukan' }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Tarif Mingguan</div>
                <div class="value money">
                    {{ $motor->tarifRental ? 'Rp ' . number_format($motor->tarifRental->tarif_mingguan, 0, ',', '.') : '-' }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Tarif Bulanan</div>
                <div class="value money">
                    {{ $motor->tarifRental ? 'Rp ' . number_format($motor->tarifRental->tarif_bulanan, 0, ',', '.') : '-' }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Jumlah Penyewaan</div>
                <div class="value">{{ $motor->penyewaans->count() }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Diajukan</div>
                <div class="value">{{ $motor->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        @if (! $motor->tarifRental)
            <div class="alert alert-info" style="margin-top:1.25rem;margin-bottom:0">
                Motor ini belum memiliki tarif rental, sehingga belum dapat menghitung biaya sewa.
            </div>
        @endif
    </div>

    <div class="card">
        <h2 class="section-title">Berkas</h2>

        @unless ($motor->photo || $motor->dokumen_kepemilikan)
            <p class="empty">Belum ada berkas diunggah.</p>
        @endunless

        @if ($motor->photo)
            <div class="detail-item" style="margin-bottom:1rem">
                <div class="label">Foto Motor</div>
                <img src="{{ $motor->urlPhoto() }}" alt="{{ $motor->merk }}" class="doc-preview" style="max-width:320px">
            </div>
        @endif

        @can('lihatDokumen', $motor)
            @if ($motor->dokumen_kepemilikan)
                <div class="detail-item">
                    <div class="label">Dokumen Kepemilikan</div>
                    <a href="{{ route('admin.motor.dokumen', $motor) }}" class="btn btn-ghost btn-sm" style="margin-top:.4rem">
                        Lihat Dokumen
                    </a>
                </div>
            @endif
        @endcan
    </div>

    @can('verifikasi', $motor)
        <div class="card">
            <h2 class="section-title">Verifikasi Motor</h2>

            @if ($motor->status === \App\Models\Motor::STATUS_PENDING)
                <div class="btn-row" style="margin-top:0">
                    <form method="POST" action="{{ route('admin.motor.terima', $motor) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn">Terima Motor</button>
                    </form>
                    <form method="POST" action="{{ route('admin.motor.tolak', $motor) }}" class="inline"
                        onsubmit="return confirm('Tolak motor ini?')">
                        @csrf
                        <button type="submit" class="btn btn-danger">Tolak Motor</button>
                    </form>
                </div>

                <p class="muted" style="margin-top:1rem">
                    Status motor ditentukan otomatis oleh sistem berdasarkan tindakan Terima atau Tolak.
                </p>
            @else
                <p class="muted" style="margin:0">
                    Motor ini tidak berstatus pending, sehingga verifikasi tidak dapat diulang.
                </p>
            @endif
        </div>
    @endcan

    @can('ubahStatus', $motor)
        <div class="card">
            <h2 class="section-title">Pengelolaan Status Operasional</h2>

            @if (empty($statusTersedia))
                <p class="muted" style="margin:0">
                    Status <strong>{{ ucfirst($motor->status) }}</strong> tidak memiliki transisi lain.
                </p>
            @else
                <form method="POST" action="{{ route('admin.motor.status', $motor) }}" class="filter-bar" style="margin-top:0">
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

                @error('status')
                    <div class="error" style="color:var(--danger);font-size:.82rem;margin-top:.4rem">{{ $message }}</div>
                @enderror
            @endif
        </div>
    @endcan

    <div class="card">
        <h2 class="section-title">Riwayat Penyewaan</h2>

        @if ($motor->penyewaans->isEmpty())
            <p class="empty">Belum ada penyewaan untuk motor ini.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Penyewa</th>
                        <th>Periode</th>
                        <th>Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($motor->penyewaans as $sewa)
                        <tr>
                            <td>{{ $sewa->penyewa?->nama ?? '-' }}</td>
                            <td>{{ $sewa->tanggal_mulai->format('d/m/Y') }} – {{ $sewa->tanggal_selesai->format('d/m/Y') }}</td>
                            <td class="money">Rp {{ number_format($sewa->harga, 0, ',', '.') }}</td>
                            <td><x-badge :value="$sewa->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
