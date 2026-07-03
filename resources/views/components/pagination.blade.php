@props(['paginator', 'label' => 'data'])

@php
    $current    = $paginator->currentPage();
    $last       = $paginator->lastPage();
    $onEachSide = 1;

    $pages = collect();
    if ($last <= 7) {
        $pages = collect(range(1, $last));
    } else {
        $pages->push(1);
        if ($current - $onEachSide > 2) {
            $pages->push('...');
        }
        foreach (range(max(2, $current - $onEachSide), min($last - 1, $current + $onEachSide)) as $p) {
            $pages->push($p);
        }
        if ($current + $onEachSide < $last - 1) {
            $pages->push('...');
        }
        $pages->push($last);
    }
@endphp

@if($paginator->hasPages())
    <div class="pagination-wrap">
        <span>
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} {{ $label }}
            @endif
        </span>
        <div class="pagination-links">
            @if($paginator->onFirstPage())
                <span class="page-link disabled"><i class="bx bx-chevron-left"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="page-link"><i class="bx bx-chevron-left"></i></a>
            @endif

            @foreach($pages as $p)
                @if($p === '...')
                    <span class="page-link ellipsis">…</span>
                @else
                    <a href="{{ $paginator->url($p) }}" class="page-link {{ $current == $p ? 'active' : '' }}">{{ $p }}</a>
                @endif
            @endforeach

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="page-link"><i class="bx bx-chevron-right"></i></a>
            @else
                <span class="page-link disabled"><i class="bx bx-chevron-right"></i></span>
            @endif
        </div>
    </div>
@endif
