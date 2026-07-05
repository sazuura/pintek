@php
    $messages = [
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
    ];
    $icons = [
        'success' => 'bx-check-circle',
        'error' => 'bx-error-circle',
        'warning' => 'bx-error',
        'info' => 'bx-info-circle',
    ];
    // Warna toast ini berbeda dari token badge (success/danger/warning) di tema global --
    // memang set warna terpisah, dipertahankan persis seperti CSS aslinya, bukan dikonsolidasi.
    $styles = [
        'success' => 'bg-[#d4edda] text-[#1a6b30] border-l-4 border-[#28a745]',
        'error'   => 'bg-[#f8d7da] text-[#842029] border-l-4 border-[#dc3545]',
        'warning' => 'bg-[#fff3cd] text-[#856404] border-l-4 border-[#ffc107]',
        'info'    => 'bg-[#d0e8ff] text-[#0a4a8a] border-l-4 border-primary',
    ];
    $toastBase = 'fixed top-5 right-5 z-[9999] flex items-center gap-[0.6rem] py-3 px-[1.1rem] rounded-[10px] text-sm font-medium shadow-[0_4px_16px_rgba(0,0,0,0.12)] animate-flash-in max-w-[360px]';
@endphp

@foreach($messages as $type => $message)
    @if($message)
        <div class="{{ $toastBase }} {{ $styles[$type] }}" role="alert" data-flash-toast>
            <i class="bx {{ $icons[$type] }}"></i>
            <span>{{ $message }}</span>
            <button onclick="this.parentElement.remove()"
                class="ml-auto bg-transparent border-0 text-[1.1rem] cursor-pointer opacity-60 hover:opacity-100 text-inherit leading-none p-0 pl-2">&times;</button>
        </div>
    @endif
@endforeach

<script>
    // Auto dismiss setelah 4 detik
    document.querySelectorAll('[data-flash-toast]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });
</script>
