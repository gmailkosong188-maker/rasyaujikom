@props(['motor'])

<div class="motor-card">
    <div class="motor-thumb">
        @if ($motor->photo)
            <img src="{{ $motor->urlPhoto() }}" alt="{{ $motor->merk }}">
        @else
            <span class="thumb-empty">Tanpa foto</span>
        @endif
    </div>
    <div class="motor-body">
        <div class="motor-head">
            <strong>{{ $motor->merk }}</strong>
            <x-badge :value="$motor->status" />
        </div>
        <div class="muted">{{ $motor->no_plat }} &middot; {{ $motor->tipe_cc }} CC</div>
        @if (isset($motor->pemilik) && $motor->pemilik)
            <div class="muted">Pemilik: {{ $motor->pemilik->nama }}</div>
        @endif
        <div class="motor-price money">
            @if ($motor->tarifRental)
                Rp {{ number_format($motor->tarifRental->tarif_harian, 0, ',', '.') }} / hari
            @else
                <span class="muted">Tarif belum ditentukan</span>
            @endif
        </div>
        {{ $slot }}
    </div>
</div>
