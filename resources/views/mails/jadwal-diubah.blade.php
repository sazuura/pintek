@extends('mails.layout')
@section('title', 'Jadwal Rapat Diubah')
@section('content')
<span class="badge badge-warning">Jadwal Diubah</span>
<h1>Detail Rapat Anda Diperbarui</h1>
<p>Halo <strong>{{ $namaOperator }}</strong>, detail rapat <strong>{{ $judulKegiatan }}</strong> yang Anda tangani telah diperbarui:</p>
@include('mails.partials.jadwal-detail')
<p>Harap cek sistem untuk detail terbaru.</p>
<div class="btn-wrap"><a href="{{ route('login') }}" class="btn">Buka Sistem</a></div>
@endsection
