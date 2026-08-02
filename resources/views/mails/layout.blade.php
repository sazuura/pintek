<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', config('app.name'))</title>
<style>
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    body { margin: 0; padding: 0; width: 100% !important; background-color: #eeeeee; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; }
    .wrapper { width: 100%; background-color: #eeeeee; padding: 32px 16px; }
    .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, .06); }
    .header { background-color: #0052CC; padding: 24px 32px; text-align: center; }
    .header img { height: 48px; width: auto; display: inline-block; }
    .header .app-name { color: #ffffff; font-size: 14px; font-weight: 600; margin-top: 10px; }
    .body { padding: 32px; color: #342E37; }
    .body h1 { font-size: 20px; margin: 0 0 16px; color: #342E37; }
    .body p { font-size: 14px; line-height: 1.6; margin: 0 0 16px; color: #342E37; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; margin-bottom: 16px; }
    .badge-info    { background: #E5F0FF; color: #0052CC; }
    .badge-success { background: #e6f9f0; color: #1abc9c; }
    .badge-warning { background: #fff4e5; color: #f39c12; }
    .badge-danger  { background: #fdecea; color: #e74c3c; }
    .info-table { width: 100%; border-collapse: collapse; background: #F9F9F9; border-radius: 8px; overflow: hidden; margin-bottom: 16px; }
    .info-table td { padding: 10px 16px; font-size: 13px; vertical-align: top; }
    .info-table td.label { color: #646464; width: 130px; white-space: nowrap; }
    .info-table td.value { color: #342E37; font-weight: 500; }
    .info-table tr:not(:last-child) td { border-bottom: 1px solid #eeeeee; }
    .note-box { background: #fdecea; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; }
    .note-box p { margin: 0; font-size: 13px; color: #c0392b; }
    .note-box .quote { font-style: italic; }
    .otp-code { text-align: center; font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #0052CC; background: #E5F0FF; border-radius: 12px; padding: 20px; margin: 8px 0 24px; }
    .btn-wrap { text-align: center; margin-top: 8px; }
    .btn { display: inline-block; background-color: #0066FF; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; }
    .footer { padding: 24px 32px; text-align: center; }
    .footer p { font-size: 12px; color: #888888; margin: 0 0 4px; line-height: 1.6; }
</style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="{{ $message->embed(public_path('img/logo.png')) }}" alt="Logo Diskominfotik">
                <div class="app-name">{{ config('app.name') }}</div>
            </div>
            <div class="body">
                @yield('content')
            </div>
            <div class="footer">
                <p>Pesan ini dikirim otomatis oleh Sistem Penjadwalan Diskominfotik Kabupaten Bandung Barat.</p>
                <p>Mohon tidak membalas email ini.</p>
            </div>
        </div>
    </div>
</body>
</html>
