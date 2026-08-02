@extends('mails.layout')
@section('title', 'Pengajuan Peminjaman Diubah')
@section('content')
<span class="badge badge-warning">Pengajuan Diubah</span>
<h1>Pengajuan Peminjaman Diperbarui</h1>
<p>Halo <strong>{{ $namaInventaris }}</strong>, pengajuan peminjaman peralatan dari <strong>{{ $gedung }}</strong> berikut telah diperbarui oleh pemohon:</p>
@include('mails.partials.peminjaman-detail')
<p>Silakan login ke sistem untuk meninjau perubahan ini.</p>
<div class="btn-wrap"><a href="{{ route('login') }}" class="btn">Buka Sistem</a></div>
@endsection
