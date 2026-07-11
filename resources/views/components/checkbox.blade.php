@props(['name', 'checked' => false])
@php
    $isChecked = old($name) !== null ? (bool) old($name) : $checked;
@endphp
{{--
    Checkbox custom - input asli disembunyikan (sr-only, tetap fokusable/accessible),
    kotak & centang divisualkan lewat <span> yang bereaksi ke state checked lewat
    :has() di <label> (bukan peer-checked biasa, karena ikon centangnya nested di
    dalam <span> - bukan sibling langsung dari input - jadi peer-checked tidak akan
    kena ke elemen senested itu, sedangkan group-has-[:checked] bisa karena :has()
    mencocokkan turunan di kedalaman berapa pun.
--}}
<label class="group inline-flex items-center gap-2 text-[13px] text-text-muted cursor-pointer select-none">
    <input type="checkbox" name="{{ $name }}" value="1"
        {{ $attributes->merge(['class' => 'sr-only']) }}
        @if($isChecked) checked @endif>
    <span class="w-[18px] h-[18px] rounded-md border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center transition-colors duration-150 shrink-0 group-has-[:checked]:bg-primary group-has-[:checked]:border-primary group-has-[:focus-visible]:ring-2 group-has-[:focus-visible]:ring-[rgba(0,102,255,0.35)]">
        <i class="bx bx-check text-white text-xs leading-none invisible group-has-[:checked]:visible"></i>
    </span>
    <span>{{ $slot }}</span>
</label>
