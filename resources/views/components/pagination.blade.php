{{-- Paginasi sederhana tanpa dependensi Tailwind. --}}
@if ($paginator->hasPages())
    <ul class="pagination">
        @if ($paginator->onFirstPage())
            <li><span>&laquo; Sebelumnya</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}">&laquo; Sebelumnya</a></li>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            <li class="{{ $page == $paginator->currentPage() ? 'active' : '' }}">
                @if ($page == $paginator->currentPage())
                    <span>{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            </li>
        @endforeach

        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}">Berikutnya &raquo;</a></li>
        @else
            <li><span>Berikutnya &raquo;</span></li>
        @endif
    </ul>
@endif
