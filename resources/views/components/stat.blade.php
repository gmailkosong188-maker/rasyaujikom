{{-- Kartu statistik dashboard. --}}
@props(['label', 'value', 'hint' => null])

<div class="stat">
    <div class="label">{{ $label }}</div>
    <div class="value">{{ $value }}</div>
    @if ($hint)
        <div class="hint">{{ $hint }}</div>
    @endif
</div>
