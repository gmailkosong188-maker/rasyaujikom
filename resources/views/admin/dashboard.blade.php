@extends('layouts.dashboard')

@section('title', 'Dashboard Admin')

{{-- Lingkup data: SELURUH sistem (admin). --}}
@section('dashboard')
    <div class="page-head">
        <div>
            <p class="page-eyebrow">Ringkasan Sistem</p>
            <h1 class="page-title">Dashboard Admin</h1>
            <p class="page-desc">Pantau pengguna, motor, penyewaan, dan bagi hasil seluruh sistem.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.motor.verifikasi') }}" class="btn btn-ghost btn-sm">
                <x-icon name="verify" :size="16" /> Verifikasi Motor
            </a>
            <a href="{{ route('admin.penyewaan.index') }}" class="btn btn-sm">
                <x-icon name="receipt" :size="16" /> Data Penyewaan
            </a>
        </div>
    </div>

    <div class="stats">
        <x-stat label="Total Pengguna" :value="$jumlahUser" icon="users" hint="Semua akun terdaftar" />
        <x-stat label="Total Motor" :value="$jumlahMotor" icon="bike" tone="blue"
            hint="{{ $motorTersedia }} tersedia · {{ $motorDisewa }} disewa" />
        <x-stat label="Menunggu Verifikasi" :value="$motorPending" icon="verify" tone="amber"
            hint="Motor baru dari pemilik" />
        <x-stat label="Total Penyewaan" :value="$jumlahPenyewaan" icon="receipt" hint="{{ $penyewaanAktif }} masih aktif" />
        <x-stat label="Pendapatan Admin"
            :value="'Rp ' . number_format($bagianAdmin, 0, ',', '.')" icon="wallet" tone="green"
            hint="Bagi hasil yang sudah dicairkan" />
        <x-stat label="Dana Menunggu Pencairan"
            :value="'Rp ' . number_format($danaMenungguPencairan, 0, ',', '.')" icon="wallet" tone="amber"
            hint="{{ $jumlahMenungguPencairan }} catatan belum diserahkan" />
    </div>

    <div class="dashboard-grid dashboard-grid-2">
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Aktivitas Penyewaan Terbaru</h2>
                    <p class="card-sub">Penyewaan terakhir yang masuk ke sistem.</p>
                </div>
                <a href="{{ route('admin.penyewaan.index') }}" class="link-more">
                    Lihat semua <x-icon name="arrow-right" :size="15" />
                </a>
            </div>

            @if ($penyewaanTerbaru->isEmpty())
                <x-empty-state title="Belum ada penyewaan"
                    description="Aktivitas penyewaan akan muncul di sini setelah penyewa membuat pesanan."
                    icon="receipt" compact />
            @else
                <div class="table-scroll">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Penyewa</th>
                                <th>Motor</th>
                                <th>Periode</th>
                                <th>Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penyewaanTerbaru as $sewa)
                                <tr>
                                    <td>
                                        <span class="cell-title">{{ $sewa->penyewa?->nama ?? '-' }}</span>
                                        <span class="cell-sub">{{ ucfirst($sewa->tipe_durasi) }}</span>
                                    </td>
                                    <td>
                                        <span class="cell-title">{{ $sewa->motor?->merk ?? '-' }}</span>
                                        <span class="cell-sub">{{ $sewa->motor?->no_plat ?? '-' }}</span>
                                    </td>
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
                    <h2 class="card-title">Motor Menunggu Verifikasi</h2>
                    <p class="card-sub">{{ $motorPending }} motor perlu ditinjau.</p>
                </div>
                <a href="{{ route('admin.motor.verifikasi') }}" class="link-more">
                    Tinjau <x-icon name="arrow-right" :size="15" />
                </a>
            </div>

            @if ($motorMenungguVerifikasi->isEmpty())
                <x-empty-state title="Semua motor sudah ditinjau"
                    description="Tidak ada pengajuan motor yang menunggu verifikasi saat ini."
                    icon="check" compact />
            @else
                <div class="pending-list">
                    @foreach ($motorMenungguVerifikasi as $motor)
                        <div class="pending-item">
                            <span class="pending-icon"><x-icon name="bike" :size="18" /></span>
                            <div class="pending-meta">
                                <strong>{{ $motor->merk }} · {{ $motor->no_plat }}</strong>
                                <span>{{ $motor->pemilik?->nama ?? '-' }} · {{ $motor->tipe_cc }}</span>
                            </div>
                            <a href="{{ route('admin.motor.show', $motor) }}" class="btn btn-ghost btn-sm">Tinjau</a>
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
                    <p class="card-sub">Pembayaran lunas 6 bulan terakhir.</p>
                </div>
            </div>

            <x-chart :labels="$grafikPendapatan['label']" :values="$grafikPendapatan['nilai']"
                empty-description="Grafik akan terisi setelah ada pembayaran lunas." />
        </section>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Ringkasan Bagi Hasil</h2>
                    <p class="card-sub">Akumulasi bagi hasil yang sudah settle.</p>
                </div>
                <a href="{{ route('admin.bagi-hasil.index') }}" class="link-more">
                    Detail <x-icon name="arrow-right" :size="15" />
                </a>
            </div>

            @php
                $totalBagi = $bagianPemilik + $bagianAdmin;
                $persenAdmin = $totalBagi > 0 ? (int) round(($bagianAdmin / $totalBagi) * 100) : 0;
                $persenPemilik = $totalBagi > 0 ? 100 - $persenAdmin : 0;
            @endphp

            <div class="summary-list">
                <div class="summary-row">
                    <span>Bagian pemilik (sudah dicairkan)</span>
                    <strong>Rp {{ number_format($bagianPemilik, 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Bagian admin (sudah diterima)</span>
                    <strong>Rp {{ number_format($bagianAdmin, 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Menunggu pencairan</span>
                    <strong>Rp {{ number_format($danaMenungguPencairan, 0, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Bagi hasil tercatat</span>
                    <strong>{{ $bagiHasilTercatat }} transaksi</strong>
                </div>
            </div>

            @if ($totalBagi > 0)
                <div class="panel-divider"></div>
                <div class="split-bar" aria-hidden="true">
                    <span class="owner" style="width: {{ $persenPemilik }}%"></span>
                    <span class="admin" style="width: {{ $persenAdmin }}%"></span>
                </div>
                <div class="legend">
                    <span><i class="owner"></i>Pemilik {{ $persenPemilik }}%</span>
                    <span><i class="admin"></i>Admin {{ $persenAdmin }}%</span>
                </div>
            @else
                <div class="panel-divider"></div>
                <x-empty-state title="Belum ada bagi hasil"
                    description="Bagi hasil tercatat setelah penyewaan selesai dan dana dibagi."
                    icon="pie" compact />
            @endif
        </section>
    </div>
@endsection
