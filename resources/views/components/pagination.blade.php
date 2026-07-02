@if ($paginator->hasPages())
<div class="pagination-clean">
    @if ($paginator->onFirstPage())
        <span class="page-btn disabled"><i class="bx bx-chevron-left"></i></span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="page-btn"><i class="bx bx-chevron-left"></i></a>
    @endif

    @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
        @if ($page == $paginator->currentPage())
            <span class="page-btn active">{{ $page }}</span>
        @else
            <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="page-btn"><i class="bx bx-chevron-right"></i></a>
    @else
        <span class="page-btn disabled"><i class="bx bx-chevron-right"></i></span>
    @endif
</div>
@endif
