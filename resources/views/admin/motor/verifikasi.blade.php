@extends('layouts.dashboard')

@section('title', 'Verifikasi Motor')

@section('dashboard')
    <div class="card">
        <h1 style="margin:0">Verifikasi Motor</h1>
        <p class="muted" style="margin:.25rem 0 0">
            Motor berstatus <strong>pending</strong> menunggu pemeriksaan. Terima menjadi
            <strong>tersedia</strong> atau tolak bila data tidak sesuai.
        </p>
    </div>

    @if ($motor->isEmpty())
        <div class="card">
            <p class="empty" style="margin:0">Tidak ada motor yang menunggu verifikasi.</p>
        </div>
    @else
        <div class="card">
            <table class="data">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>No Plat</th>
                        <th>Merk</th>
                        <th>CC</th>
                        <th>Pemilik</th>
                        <th>Diajukan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($motor as $m)
                        <tr>
                            <td>
                                @if ($m->photo)
                                    <img src="{{ $m->urlPhoto() }}" alt="{{ $m->merk }}"
                                        style="width:52px;height:38px;object-fit:cover;border-radius:.3rem">
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td>{{ $m->no_plat }}</td>
                            <td>{{ $m->merk }}</td>
                            <td>{{ $m->tipe_cc }}</td>
                            <td>{{ $m->pemilik?->nama ?? '-' }}</td>
                            <td class="muted">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                    <a href="{{ route('admin.motor.show', $m) }}" class="btn btn-ghost btn-sm">Periksa</a>

                                    @can('verifikasi', $m)
                                        <form method="POST" action="{{ route('admin.motor.terima', $m) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm">Terima</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.motor.tolak', $m) }}" class="inline"
                                            onsubmit="return confirm('Tolak motor {{ $m->no_plat }}?')">
                                            @csrf
                                            <input type="hidden" name="alasan" value="Data tidak sesuai">
                                            <button type="submit" class="btn btn-danger btn-sm">Tolak</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$motor" />
        </div>
    @endif
@endsection
