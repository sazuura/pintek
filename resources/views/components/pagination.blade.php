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

    $pageLinkBase = 'min-w-8 h-8 px-2 inline-flex items-center justify-center rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark no-underline text-[13px] border border-transparent transition-colors duration-150 hover:bg-transparent hover:text-primary';
@endphp

@if($paginator->hasPages())
    <div class="py-4 px-5 flex items-center justify-between flex-wrap gap-2.5 border-t border-page-bg dark:border-page-bg-dark text-[13px] text-text-muted">
        <span>
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} {{ $label }}
            @endif
        </span>
        <div class="flex gap-1">
            @if($paginator->onFirstPage())
                <span class="{{ $pageLinkBase }} opacity-40 pointer-events-none"><i class="bx bx-chevron-left"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="{{ $pageLinkBase }}"><i class="bx bx-chevron-left"></i></a>
            @endif

            @foreach($pages as $p)
                @if($p === '...')
                    <span class="{{ $pageLinkBase }} bg-transparent border-transparent cursor-default text-text-muted hover:bg-transparent hover:text-text-muted">…</span>
                @else
                    <a href="{{ $paginator->url($p) }}"
                        class="{{ $pageLinkBase }} {{ $current == $p ? 'bg-primary dark:bg-primary text-white font-semibold hover:bg-primary hover:text-white' : '' }}">{{ $p }}</a>
                @endif
            @endforeach

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="{{ $pageLinkBase }}"><i class="bx bx-chevron-right"></i></a>
            @else
                <span class="{{ $pageLinkBase }} opacity-40 pointer-events-none"><i class="bx bx-chevron-right"></i></span>
            @endif
        </div>
    </div>
@endif
