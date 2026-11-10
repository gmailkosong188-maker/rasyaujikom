@extends('layouts.dashboard')

@section('title', 'Data User')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Data User</h1>
                <p class="muted" style="margin:.25rem 0 0">Seluruh user terdaftar di sistem.</p>
            </div>
            <a href="{{ route('admin.user.create') }}" class="btn btn-sm">Tambah User</a>
        </div>

        <form method="GET" action="{{ route('admin.user.index') }}" class="filter-bar" style="margin-top:1.25rem">
            <div class="field">
                <label for="cari">Cari</label>
                <input id="cari" type="text" name="cari" value="{{ $cari }}" placeholder="Nama, email, atau nomor telepon">
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="">Semua role</option>
                    @foreach (\App\Models\User::ROLES as $r)
                        <option value="{{ $r }}" {{ $role === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm">Filter</button>
            @if ($cari !== '' || $role)
                <a href="{{ route('admin.user.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        @if ($users->isEmpty())
            <p class="empty">Tidak ada user yang sesuai filter.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No. Telepon</th>
                        <th>Role</th>
                        <th>Jumlah Motor</th>
                        <th>Jumlah Penyewaan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td>{{ $u->nama }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->no_tlpn }}</td>
                            <td><x-badge :value="$u->role" /></td>
                            <td class="money">{{ $u->motors_count ?? 0 }}</td>
                            <td class="money">{{ $u->penyewaans_count ?? 0 }}</td>
                            <td>
                                <a href="{{ route('admin.user.edit', $u) }}" class="btn btn-ghost btn-sm">Edit</a>
                                <form method="POST" action="{{ route('admin.user.destroy', $u) }}" style="display:inline"
                                    onsubmit="return confirm('Yakin ingin menghapus user {{ $u->nama }}? Tindakan ini tidak dapat dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-pagination :paginator="$users" />
        @endif
    </div>
@endsection