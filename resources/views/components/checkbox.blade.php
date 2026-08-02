@props(['name', 'checked' => false])
@php
    $isChecked = old($name) !== null ? (bool) old($name) : $checked;
@endphp

<label class="relative inline-flex items-center gap-2.5 text-[13px] text-text-muted cursor-pointer select-none">
    <input type="checkbox" name="{{ $name }}" value="1"
        {{ $attributes->merge(['class' => 'peer sr-only']) }}
        @if($isChecked) checked @endif>
    <span class="w-6 h-6 rounded-md border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center transition-colors duration-150 shrink-0 peer-checked:bg-primary peer-checked:border-primary peer-focus-visible:ring-2 peer-focus-visible:ring-[rgba(0,102,255,0.35)]"></span>
    <i class="bx bx-check text-white text-base leading-none absolute left-[4px] top-1/2 -translate-y-1/2 invisible peer-checked:visible pointer-events-none"></i>
    <span>{{ $slot }}</span>
</label>
