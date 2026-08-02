@extends('mails.layout')
@section('title', 'Pengajuan Peminjaman Dibatalkan')
@section('content')
<span class="badge badge-danger">Dibatalkan</span>
<h1>Pengajuan Peminjaman Dibatalkan</h1>
<p>Halo <strong>{{ $namaInventaris }}</strong>, pengajuan peminjaman peralatan berikut telah <strong>dibatalkan</strong> oleh pemohon:</p>
@include('mails.partials.peminjaman-detail')
<div class="note-box">
    <p><strong>Alasan Pembatalan:</strong></p>
    <p class="quote">"{{ $alasan }}"</p>
</div>
<p>Sistem telah otomatis memperbarui status pengajuan, silakan meninjau stok peralatan terkait.</p>
@endsection
