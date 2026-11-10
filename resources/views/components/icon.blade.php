{{-- Ikon garis 24x24, reusable di seluruh dashboard. --}}
@props(['name' => 'inbox', 'size' => 20])

<svg {{ $attributes->merge([
    'class' => 'icon',
    'width' => $size,
    'height' => $size,
    'viewBox' => '0 0 24 24',
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => '1.8',
    'stroke-linecap' => 'round',
    'stroke-linejoin' => 'round',
    'aria-hidden' => 'true',
]) }}>
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="9" rx="1.6" />
            <rect x="14" y="3" width="7" height="5" rx="1.6" />
            <rect x="14" y="12" width="7" height="9" rx="1.6" />
            <rect x="3" y="16" width="7" height="5" rx="1.6" />
        @break

        @case('bike')
            <circle cx="5.5" cy="17.5" r="3" />
            <circle cx="18.5" cy="17.5" r="3" />
            <path d="M8.5 17.5h5l2-6.5H11l-1.5 4M15.5 11l2 6.5M14.5 7.5h3l1 2" />
        @break

        @case('verify')
            <path d="M12 3l7 3v5c0 4.4-3 7.4-7 9-4-1.6-7-4.6-7-9V6l7-3Z" />
            <path d="m9 12 2 2 4-4" />
        @break

        @case('tag')
            <path d="M3 12V4h8l9 9-8 8-9-9Z" />
            <circle cx="7.6" cy="7.6" r="1.2" />
        @break

        @case('receipt')
            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" />
            <path d="M9 8h6M9 12h6M9 16h3" />
        @break

        @case('card')
            <rect x="3" y="5.5" width="18" height="13.5" rx="2.4" />
            <path d="M3 10h18M7 15h4" />
        @break

        @case('return')
            <path d="M4 10h11a5 5 0 0 1 0 10H7" />
            <path d="m8 6-4 4 4 4" />
        @break

        @case('pie')
            <path d="M12 3a9 9 0 1 0 9 9h-9V3Z" />
            <path d="M14 3.5A8.5 8.5 0 0 1 20.5 10H14V3.5Z" />
        @break

        @case('chart')
            <path d="M4 20v-6M10 20V8M16 20v-9M22 20H2" />
        @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
        @break

        @case('plus-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v8M8 12h8" />
        @break

        @case('list')
            <path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01" />
        @break

        @case('history')
            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
            <path d="M3 4v4h4M12 8v4l3 2" />
        @break

        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.2-3.2" />
        @break

        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
        @break

        @case('close')
            <path d="M6 6l12 12M18 6 6 18" />
        @break

        @case('logout')
            <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3" />
            <path d="m10 17-5-5 5-5M5 12h11" />
        @break

        @case('users')
            <path d="M16 19v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V19" />
            <circle cx="9" cy="7" r="3.2" />
            <path d="M22 19v-1.5a4 4 0 0 0-3-3.87M16.5 4.1a3.2 3.2 0 0 1 0 6.2" />
        @break

        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        @break

        @case('wallet')
            <rect x="3" y="6" width="18" height="13" rx="2.4" />
            <path d="M3 10h18M16 14.5h2" />
        @break

        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2.4" />
            <path d="M8 3v4M16 3v4M3 10h18" />
        @break

        @case('inbox')
            <path d="M4 13h4l1.5 3h5L16 13h4" />
            <path d="M5.5 5h13l1.5 8v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5l2.5-8Z" />
        @break

        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6" />
        @break

        @case('check')
            <path d="m5 12 4 4L19 6" />
        @break

        @case('alert')
            <path d="M12 9v4M12 17h.01" />
            <path d="M10.3 3.9 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
        @break

        @case('file')
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" />
            <path d="M14 3v5h5" />
        @break

        @case('home')
            <path d="m3 11 9-8 9 8" />
            <path d="M5 10v10h14V10" />
        @break

        @default
            <path d="M12 8v8M8 12h8" />
    @endswitch
</svg>
