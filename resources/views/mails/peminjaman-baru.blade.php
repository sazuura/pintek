@extends('mails.layout')
@section('title', 'Pengajuan Peminjaman Baru')
@section('content')
<span class="badge badge-info">Pengajuan Baru</span>
<h1>Ada Pengajuan Peminjaman Peralatan</h1>
<p>Halo <strong>{{ $namaInventaris }}</strong>, ada pengajuan peminjaman peralatan dari <strong>{{ $gedung }}</strong>:</p>
@include('mails.partials.peminjaman-detail')
<p>Silakan login ke sistem untuk menyetujui atau menolak pengajuan ini.</p>
<div class="btn-wrap"><a href="{{ route('login') }}" class="btn">Buka Sistem</a></div>
@endsection
