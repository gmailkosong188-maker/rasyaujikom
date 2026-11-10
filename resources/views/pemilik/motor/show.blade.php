@extends('layouts.dashboard')

@section('title', 'Detail Motor')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">{{ $motor->merk }} <x-badge :value="$motor->status" /></h1>
                <p class="muted" style="margin:.25rem 0 0">{{ $motor->no_plat }} &middot; {{ $motor->tipe_cc }} CC</p>
            </div>
            <a href="{{ route('pemilik.motor.index') }}" class="btn btn-ghost btn-sm">&laquo; Kembali</a>
        </div>

        @if ($motor->status === \App\Models\Motor::STATUS_PENDING)
            <div class="alert alert-info" style="margin-top:1.25rem">
                Motor sedang menunggu verifikasi admin. Motor belum dapat disewa sampai diverifikasi.
            </div>
        @elseif ($motor->status === \App\Models\Motor::STATUS_DITOLAK)
            <div class="alert alert-error" style="margin-top:1.25rem">
                Motor ditolak admin. Perbaiki data motor lalu ajukan ulang.
            </div>
        @elseif ($motor->status === \App\Models\Motor::STATUS_TERSEDIA)
            <div class="alert alert-success" style="margin-top:1.25rem">
                Motor sudah diverifikasi dan dapat disewa.
                @unless ($motor->tarifRental)
                    Namun tarif rental belum ditentukan admin.
                @endunless
            </div>
        @endif

        <div class="detail-grid" style="margin-top:1.25rem">
            <div class="detail-item">
                <div class="label">Merk</div>
                <div class="value">{{ $motor->merk }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Tipe CC</div>
                <div class="value">{{ $motor->tipe_cc }} CC</div>
            </div>
            <div class="detail-item">
                <div class="label">Nomor Plat</div>
                <div class="value">{{ $motor->no_plat }}</div>
            </div>
            <div class="detail-item">
                <div class="label">Status Verifikasi</div>
                <div class="value"><x-badge :value="$motor->status" /></div>
            </div>
            <div class="detail-item">
                <div class="label">Tawarkan untuk Disewa</div>
                <div class="value">
                    <span class="{{ $motor->isAktif() ? 'badge-success' : 'badge-warning' }}">
                        {{ $motor->isAktif() ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Tarif Harian</div>
                <div class="value money">
                    {{ $motor->tarifRental ? 'Rp ' . number_format($motor->tarifRental->tarif_harian, 0, ',', '.') : 'Belum ditentukan' }}
                </div>
            </div>
            <div class="detail-item">
                <div class="label">Didaftarkan</div>
                <div class="value">{{ $motor->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Berkas</h2>

        <div class="detail-grid">
            @if ($motor->photo)
                <div class="detail-item">
                    <div class="label">Foto Motor</div>
                    <img src="{{ $motor->urlPhoto() }}" alt="{{ $motor->merk }}" class="doc-preview"
                        style="max-width:320px">
                </div>
            @endif

            @can('lihatDokumen', $motor)
                @if ($motor->dokumen_kepemilikan)
                    @php($ext = strtolower(pathinfo($motor->dokumen_kepemilikan, PATHINFO_EXTENSION)))
                    <div class="detail-item">
                        <div class="label">Dokumen Kepemilikan</div>
                        @if ($ext === 'pdf')
                            <a href="{{ $motor->urlDokumen() }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"
                                style="margin-top:.4rem">Buka Dokumen PDF</a>
                        @else
                            <img src="{{ $motor->urlDokumen() }}" alt="Dokumen kepemilikan" class="doc-preview"
                                style="max-width:320px">
                        @endif
                    </div>
                @endif
            @endcan
        </div>

        @unless ($motor->photo || $motor->dokumen_kepemilikan)
            <p class="empty">Belum ada berkas diunggah.</p>
        @endunless
    </div>

    <div class="card">
        <div class="btn-row" style="margin-top:0">
            @can('update', $motor)
                <a href="{{ route('pemilik.motor.edit', $motor) }}" class="btn btn-sm">Edit Motor</a>
            @endcan

            @if ($motor->isAktif() && $motor->canBeNonaktif())
                @can('nonaktifkan', $motor)
                    <form method="POST" action="{{ route('pemilik.motor.nonaktifkan', $motor) }}" class="inline"
                        onsubmit="return confirm('Nonaktifkan motor {{ $motor->no_plat }}? Motor tidak akan tampil di katalog penyewa.')">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">Nonaktifkan Motor</button>
                    </form>
                @endcan
            @else
                @can('aktifkan', $motor)
                    <form method="POST" action="{{ route('pemilik.motor.aktifkan', $motor) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">Aktifkan Motor</button>
                    </form>
                @endcan
            @endif

            @can('delete', $motor)
                <form method="POST" action="{{ route('pemilik.motor.destroy', $motor) }}" class="inline"
                    onsubmit="return confirm('Hapus motor {{ $motor->no_plat }}? Tindakan ini tidak dapat dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Hapus Motor</button>
                </form>
            @endcan
        </div>

        @cannot('delete', $motor)
            <p class="hint" style="margin-bottom:0">
                Motor yang sudah memiliki riwayat penyewaan tidak dapat dihapus agar histori tetap utuh.
            </p>
        @endcannot
    </div>
@endsection
