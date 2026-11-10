{{-- Kartu statistik dashboard: label, nilai, ikon, dan keterangan singkat. --}}
@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'navy'])

<div class="stat {{ $tone !== 'navy' ? 'stat-' . $tone : '' }}">
    @if ($icon)
        <span class="stat-icon">
            <x-icon :name="$icon" :size="19" />
        </span>
    @endif
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    @if ($hint)
        <div class="stat-hint">{{ $hint }}</div>
    @endif
</div>
