<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ config('app.name', 'Diskominfotik') }}</title>

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/adminhub.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body>
    <div class="login-card">

        {{-- Header: logo besar + nama sistem --}}
        <div class="login-header">
            <img src="{{ asset('img/logo.png') }}" alt="Logo Diskominfotik">
            <h2>DISKOMINFOTIK</h2>
            <small>Kabupaten Bandung Barat</small>
        </div>

        {{-- Konten login form dari login.blade.php --}}
        {{ $slot }}

    </div>

    <script src="{{ asset('js/login.js') }}"></script>
</body>

</html>