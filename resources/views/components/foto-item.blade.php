@props(['path' => null, 'alt' => '', 'icon' => 'bx-package', 'imgClass' => '', 'iconWrapClass' => ''])
@php
    // Cek file benar-benar ada di storage, bukan cuma kolom foto terisi - path bisa
    // "nyangkut" merujuk file yang sudah terhapus/tidak pernah ke-upload, dan <img> src
    // yang gagal load tampil sebagai gambar rusak alih-alih fallback ikon.
    $ada = $path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
@endphp
@if($ada)
    <img src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" alt="{{ $alt }}" class="{{ $imgClass }}">
@else
    <div class="{{ $iconWrapClass }}">
        <i class="bx {{ $icon }}"></i>
    </div>
@endif
