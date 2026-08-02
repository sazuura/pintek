@extends('mails.layout')
@section('title', 'Kode Verifikasi Reset Kata Sandi')
@section('content')
<span class="badge badge-info">Reset Kata Sandi</span>
<h1>Kode Verifikasi Anda</h1>
<p>Halo <strong>{{ $namaUser }}</strong>, gunakan kode berikut untuk mengatur ulang kata sandi akun Anda:</p>
<div class="otp-code">{{ $kode }}</div>
<p>Kode ini berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku dari Diskominfotik.</p>
<p>Kalau Anda tidak merasa meminta ini, abaikan email ini - kata sandi Anda tetap aman.</p>
@endsection
