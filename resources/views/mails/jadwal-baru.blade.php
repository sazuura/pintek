@extends('mails.layout')
@section('title', 'Jadwal Rapat Baru')
@section('content')
<span class="badge badge-info">Jadwal Baru</span>
<h1>Anda Ditugaskan Menangani Rapat</h1>
<p>Halo <strong>{{ $namaOperator }}</strong>, Anda ditugaskan untuk menangani rapat <strong>{{ $judulKegiatan }}</strong> berikut:</p>
@include('mails.partials.jadwal-detail')
<p>Harap cek sistem untuk detail lengkap dan konfirmasi kehadiran Anda.</p>
<div class="btn-wrap"><a href="{{ route('login') }}" class="btn">Buka Sistem</a></div>
@endsection
