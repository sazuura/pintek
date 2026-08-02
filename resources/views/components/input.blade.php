@props([
    'name',
    'label' => null,
    'type' => 'text',
    'required' => false,
    'hint' => null,
    'hintId' => null,
    'toggleable' => false,
    'id' => null,
])
@php
    $hasError = $errors->has($name);
    $value = old($name, $attributes->get('value'));
    $fieldId = $id ?? $name;

    $base = 'h-10 px-3 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:outline-none border';
    $base .= $hasError
        ? ' border-danger-text focus:border-danger-text focus:shadow-[0_0_0_3px_rgba(231,76,60,0.15)]'
        : ' border-gray-300 dark:border-gray-700 focus:border-primary focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]'; 
    if ($toggleable) $base .= ' pr-11';
@endphp
<div class="flex flex-col gap-1.5">
    @if($label)
        <label for="{{ $fieldId }}" class="text-[13px] font-medium text-text dark:text-text-dark">
            {{ $label }}@if($required)<span class="text-danger-text ml-0.5">*</span>@endif
        </label>
    @endif

    @if($type === 'textarea')
        <textarea name="{{ $name }}" id="{{ $fieldId }}"
            {{ $attributes->except(['value'])->merge(['class' => $base . ' h-auto py-2.5 resize-y min-h-[80px]']) }}
            @if($required) required @endif>{{ $value }}</textarea>
    @elseif($toggleable)
        <div class="relative password-wrap">
            <input type="password" name="{{ $name }}" id="{{ $fieldId }}" value="{{ $value }}"
                {{ $attributes->except(['value'])->merge(['class' => $base]) }}
                @if($required) required @endif>
            <button type="button"
                class="eye-btn absolute right-3 top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer text-text-muted text-lg flex items-center p-0 transition-colors duration-200 hover:text-primary"
                aria-label="Tampilkan password">
                <i class="bx bx-hide"></i>
            </button>
        </div>
    @else
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $fieldId }}" value="{{ $value }}"
            {{ $attributes->except(['value'])->merge(['class' => $base]) }}
            @if($required) required @endif>
    @endif

    @if($hint && !$hasError)
        <span class="text-xs text-text-muted" @if($hintId) id="{{ $hintId }}" @endif>{{ $hint }}</span>
    @endif
    @error($name)
        <span class="text-xs text-danger-text">{{ $message }}</span>
    @enderror
</div>
