@extends('layouts.dashboard')

@section('title', 'Dashboard Pemilik')

{{--
    Lingkup data: HANYA motor & pendapatan milik user yang login.
    Query dibatasi lewat $user->motors() / motors.pemilik_id = user ini.
--}}
@section('dashboard')
    <div class="page-head">
        <div>
            <p class="page-eyebrow">Ringkasan Kepemilikan</p>
            <h1 class="page-title">Dashboard Pemilik</h1>
            <p class="page-desc">Pantau performa motor dan pendapatan bagi hasil Anda.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('pemilik.motor.index') }}" class="btn btn-ghost btn-sm">
                <x-icon name="bike" :size="16" /> Motor Saya
            </a>
            <a href="{{ route('pemilik.motor.create') }}" class="btn btn-sm">
                <x-icon name="plus" :size="16" /> Tambah Motor
            </a>
        </div>
    </div>

    <div class="stats">
        <x-stat label="Motor Saya" :value="$jumlahMotor" icon="bike" hint="{{ $motorPending }} menunggu verifikasi" />
        <x-stat label="Motor Tersedia" :value="$motorTersedia" icon="check" tone="green" hint="Siap disewakan" />
        <x-stat label="Sedang Disewa" :value="$motorDisewa" icon="clock" tone="blue" hint="Sedang dipakai penyewa" />
        <x-stat label="Pendapatan Pemilik"
            :value="'Rp ' . number_format($pendapatan, 0, ',', '.')" icon="wallet" tone="green"
            hint="Bagi hasil yang sudah dicairkan admin" />
    </div>

    <div class="dashboard-grid dashboard-grid-2">
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Riwayat Penyewaan</h2>
                    <p class="card-sub">Penyewaan terbaru atas motor Anda.</p>
                </div>
                <a href="{{ route('pemilik.penyewaan.index') }}" class="link-more">
                    Lihat semua <x-icon name="arrow-right" :size="15" />
                </a>
            </div>

            @if ($penyewaanTerbaru->isEmpty())
                <x-empty-state title="Belum ada penyewaan"
                    description="Riwayat penyewaan akan tampil setelah motor Anda disewa." icon="receipt" compact />
            @else
                <div class="table-scroll">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Motor</th>
                                <th>Penyewa</th>
                                <th>Periode</th>
                                <th>Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penyewaanTerbaru as $sewa)
                                <tr>
                                    <td>
                                        <span class="cell-title">{{ $sewa->motor?->merk ?? '-' }}</span>
                                        <span class="cell-sub">{{ $sewa->motor?->no_plat ?? '-' }}</span>
                                    </td>
                                    <td>{{ $sewa->penyewa?->nama ?? '-' }}</td>
                                    <td class="money">
                                        {{ $sewa->tanggal_mulai?->format('d/m/Y') }} –
                                        {{ $sewa->tanggal_selesai?->format('d/m/Y') }}
                                    </td>
                                    <td class="money">Rp {{ number_format($sewa->harga, 0, ',', '.') }}</td>
                                    <td><x-badge :value="$sewa->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Ringkasan Bagi Hasil</h2>
                    <p class="card-sub">Pendapatan Anda dari penyewaan selesai.</p>
                </div>
            </div>

            <div class="summary-list">
                <div class="summary-row">
                    <span>Sudah dicairkan</span>
                    <strong>Rp {{ number_format($pendapatan, 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Menunggu pencairan</span>
                    <strong>Rp {{ number_format($pendapatanTertunda, 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Bagi hasil tercatat</span>
                    <strong>{{ $jumlahBagiHasil }} transaksi</strong>
                </div>
                <div class="summary-row">
                    <span>Total penyewaan</span>
                    <strong>{{ $jumlahPenyewaan }} penyewaan</strong>
                </div>
            </div>

            @if ($bagiHasilTerbaru->isEmpty())
                <div class="panel-divider"></div>
                <x-empty-state title="Belum ada bagi hasil"
                    description="Bagi hasil muncul setelah penyewaan selesai dan dana dibagi." icon="pie" compact />
            @else
                <div class="panel-divider"></div>
                <div class="pending-list">
                    @foreach ($bagiHasilTerbaru as $bagi)
                        <div class="pending-item">
                            <span class="pending-icon"><x-icon name="pie" :size="18" /></span>
                            <div class="pending-meta">
                                <strong>{{ $bagi->penyewaan?->motor?->merk ?? '-' }}</strong>
                                <span>Rp {{ number_format($bagi->nominal_pemilik, 0, ',', '.') }} ·
                                    {{ $bagi->tanggal?->format('d M Y') ?? '-' }}</span>
                            </div>
                            @if ($bagi->isSettled())
                                <span class="badge badge-ok">Dicairkan</span>
                            @else
                                <span class="badge badge-warn">Menunggu</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="dashboard-grid dashboard-grid-even">
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Grafik Pendapatan</h2>
                    <p class="card-sub">Bagi hasil milik Anda 6 bulan terakhir.</p>
                </div>
            </div>

            <x-chart :labels="$grafikPendapatan['label']" :values="$grafikPendapatan['nilai']"
                empty-description="Grafik akan terisi setelah ada bagi hasil yang settle." />
        </section>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Motor Saya</h2>
                    <p class="card-sub">Kondisi motor yang Anda daftarkan.</p>
                </div>
                <a href="{{ route('pemilik.motor.index') }}" class="link-more">
                    Kelola <x-icon name="arrow-right" :size="15" />
                </a>
            </div>

            @if ($motorSaya->isEmpty())
                <x-empty-state title="Belum ada motor"
                    description="Daftarkan motor Anda agar bisa disewakan melalui sistem." icon="bike" compact>
                    <a href="{{ route('pemilik.motor.create') }}" class="btn btn-sm">Tambah Motor</a>
                </x-empty-state>
            @else
                <div class="pending-list">
                    @foreach ($motorSaya as $motor)
                        <div class="pending-item">
                            <span class="pending-icon"><x-icon name="bike" :size="18" /></span>
                            <div class="pending-meta">
                                <strong>{{ $motor->merk }} · {{ $motor->no_plat }}</strong>
                                <span>
                                    {{ $motor->tarifRental ? 'Rp ' . number_format($motor->tarifRental->tarif_harian, 0, ',', '.') . ' / hari' : 'Tarif belum ditentukan' }}
                                </span>
                            </div>
                            <x-badge :value="$motor->status" />
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
