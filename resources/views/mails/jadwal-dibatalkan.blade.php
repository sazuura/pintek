@extends('mails.layout')
@section('title', 'Jadwal Rapat Dibatalkan')
@section('content')
<span class="badge badge-danger">Dibatalkan</span>
<h1>Penugasan Rapat Dibatalkan</h1>
<p>Halo <strong>{{ $namaOperator }}</strong>, agenda rapat <strong>{{ $judulKegiatan }}</strong> berikut yang sebelumnya ditugaskan kepada Anda telah <strong>dibatalkan</strong>:</p>
<table class="info-table">
    <tr><td class="label">Tanggal</td><td class="value">{{ $tanggal }}</td></tr>
    <tr><td class="label">Waktu</td><td class="value">{{ $waktuMulai }} - {{ $waktuSelesai }} WIB</td></tr>
    <tr><td class="label">Platform</td><td class="value">{{ $platform }}</td></tr>
    <tr><td class="label">Keterangan</td><td class="value">{{ $keterangan }}</td></tr>
</table>
<div class="note-box">
    <p><strong>Alasan Pembatalan:</strong></p>
    <p class="quote">"{{ $alasan }}"</p>
</div>
<p>Terima kasih atas perhatian Anda.</p>
@endsection
