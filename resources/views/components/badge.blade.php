@props(['variant' => ''])
@php
    // buat status persegi panjang rounded
    $map = [
        'badge-active'   => 'bg-success dark:bg-success-dark text-success-text',
        'badge-warning'  => 'bg-warning dark:bg-warning-dark text-warning-text',
        'badge-danger'   => 'bg-danger dark:bg-danger-dark text-danger-text',
        'badge-info'     => 'bg-primary-50 dark:bg-[#0d2a40] text-primary',
        'badge-purple'   => 'bg-purple dark:bg-purple-dark text-purple-text',
        'badge-inactive' => 'bg-inactive dark:bg-inactive-dark text-inactive-text dark:text-inactive-text-dark',
    ];
    $colorClass = $map[$variant] ?? '';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 py-[3px] px-2.5 rounded-full text-xs font-medium whitespace-nowrap $colorClass"]) }}>{{ $slot }}</span>
