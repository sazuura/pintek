@props([
    'name',
    'label' => null,
    'required' => false,
    'hint' => null,
    'searchable' => false,
    'placeholder' => null,
    'id' => null,
])
@php
    $hasError = $errors->has($name);
    $fieldId = $id ?? $name;

    $base = 'h-10 pl-3 pr-9 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:outline-none border appearance-none cursor-pointer';
    $base .= $hasError
        ? ' border-danger-text focus:border-danger-text focus:shadow-[0_0_0_3px_rgba(231,76,60,0.15)]'
        : ' border-gray-300 dark:border-gray-700 focus:border-primary focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
    if ($searchable) $base .= ' searchable';
@endphp
<div class="flex flex-col gap-1.5">
    @if($label)
        <label for="{{ $fieldId }}" class="text-[13px] font-medium text-text dark:text-text-dark">
            {{ $label }}@if($required)<span class="text-danger-text ml-0.5">*</span>@endif
        </label>
    @endif
    <div class="relative">
        <select name="{{ $name }}" id="{{ $fieldId }}" @if($placeholder) data-placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge(['class' => $base]) }}
            @if($required) required @endif>
            @if($placeholder)
                <option value="" disabled {{ old($name) ? '' : 'selected' }}>{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>
        @unless($searchable)
            <i class="bx bx-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
        @endunless
    </div>
    @if($hint && !$hasError)
        <span class="text-xs text-text-muted">{{ $hint }}</span>
    @endif
    @error($name)
        <span class="text-xs text-danger-text">{{ $message }}</span>
    @enderror
</div>
