@props(['path' => null, 'alt' => '', 'icon' => 'bx-package', 'imgClass' => '', 'iconWrapClass' => ''])
@php
    $ada = $path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
@endphp
@if($ada)
    <img src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" alt="{{ $alt }}" class="{{ $imgClass }}">
@else
    <div class="{{ $iconWrapClass }}">
        <i class="bx {{ $icon }}"></i>
    </div>
@endif
