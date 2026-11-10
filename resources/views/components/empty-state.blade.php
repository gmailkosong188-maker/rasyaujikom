{{-- Empty state reusable: ikon, judul, deskripsi, dan slot aksi opsional. --}}
@props([
    'title' => 'Belum ada data',
    'description' => null,
    'icon' => 'inbox',
    'compact' => false,
])

<div class="empty-state {{ $compact ? 'compact' : '' }}">
    <span class="empty-icon">
        <x-icon :name="$icon" :size="24" />
    </span>
    <h3>{{ $title }}</h3>
    @if ($description)
        <p>{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        {{ $slot }}
    @endif
</div>
