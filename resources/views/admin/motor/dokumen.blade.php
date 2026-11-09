@extends('layouts.dashboard')

@section('title', 'Dokumen Kepemilikan')

@section('dashboard')
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 style="margin:0">Dokumen Kepemilikan</h1>
                <p class="muted" style="margin:.25rem 0 0">
                    {{ $motor->merk }} &middot; {{ $motor->no_plat }} &middot; {{ $motor->pemilik?->nama }}
                </p>
            </div>
            <a href="{{ route('admin.motor.show', $motor) }}" class="btn btn-ghost btn-sm">&laquo; Detail Motor</a>
        </div>

        @if ($motor->dokumen_kepemilikan)
            @php($ext = strtolower(pathinfo($motor->dokumen_kepemilikan, PATHINFO_EXTENSION)))

            @if ($ext === 'pdf')
                <p style="margin-top:1.25rem">
                    <a href="{{ $motor->urlDokumen() }}" target="_blank" rel="noopener" class="btn">Buka Dokumen PDF</a>
                </p>
            @else
                <img src="{{ $motor->urlDokumen() }}" alt="Dokumen kepemilikan {{ $motor->no_plat }}"
                    class="doc-preview" style="margin-top:1.25rem;max-width:640px">
            @endif
        @else
            <p class="empty" style="margin-top:1rem">Pemilik belum mengunggah dokumen.</p>
        @endif
    </div>
@endsection
