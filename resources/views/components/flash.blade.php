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
@endphp

@foreach($messages as $type => $message)
    @if($message)
        <div class="flash-toast flash-{{ $type }}" role="alert">
            <i class="bx {{ $icons[$type] }}"></i>
            <span>{{ $message }}</span>
            <button onclick="this.parentElement.remove()" class="flash-close">&times;</button>
        </div>
    @endif
@endforeach

<style>
    .flash-toast {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .75rem 1.1rem;
        border-radius: 10px;
        font-size: .875rem;
        font-weight: 500;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .12);
        animation: flash-in .25s ease;
        max-width: 360px;
    }

    .flash-success {
        background: #d4edda;
        color: #1a6b30;
        border-left: 4px solid #28a745;
    }

    .flash-error {
        background: #f8d7da;
        color: #842029;
        border-left: 4px solid #dc3545;
    }

    .flash-warning {
        background: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }

    .flash-info {
        background: #d0e8ff;
        color: #0a4a8a;
        border-left: 4px solid #3c91e6;
    }

    .flash-close {
        margin-left: auto;
        background: none;
        border: none;
        font-size: 1.1rem;
        cursor: pointer;
        opacity: .6;
        color: inherit;
        line-height: 1;
        padding: 0 0 0 .5rem;
    }

    .flash-close:hover {
        opacity: 1;
    }

    @keyframes flash-in {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    // Auto dismiss setelah 4 detik
    document.querySelectorAll('.flash-toast').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });
</script>