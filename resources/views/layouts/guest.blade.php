<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'Diskominfotik') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>

<body class="flex justify-center items-center min-h-screen bg-page-bg font-sans">
    <x-flash />

    <div class="w-full max-w-[420px] bg-surface rounded-2xl shadow-[0_8px_32px_rgba(0,0,0,0.10)] pt-7 px-5 pb-6 xs:pt-9 xs:px-8 xs:pb-8 m-4">

        {{-- Header: logo besar + nama sistem --}}
        <div class="flex flex-col items-center text-center mb-7 gap-2">
            <img src="{{ asset('img/logo.png') }}" alt="Logo Diskominfotik" width="284" height="284" class="h-[130px] xs:h-[200px] w-auto object-contain">
            <h2 class="text-lg font-bold text-primary m-0">DISKOMINFOTIK</h2>
            <small class="text-xs text-text-muted">Kabupaten Bandung Barat</small>
        </div>

        {{-- Konten login form dari login.blade.php --}}
        {{ $slot }}

    </div>

    @vite(['resources/js/login.js'])
</body>

</html>