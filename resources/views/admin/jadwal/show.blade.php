@extends('layouts.app')
@section('title', 'Detail Jadwal')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Detail Jadwal</h1></div>
        <a href="{{ route('admin.jadwal.index') }}" class="toolbar-btn neutral">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="form-card">
        <h3><i class="bx bx-info-circle"></i> {{ $jadwal->judul_kegiatan }}</h3>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Tanggal</label>
                <p style="color:var(--dark);margin:0;">{{ $jadwal->tanggal->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="form-group">
                <label class="form-label">Waktu</label>
                <p style="color:var(--dark);margin:0;">{{ \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') }} WIB</p>
            </div>
            <div class="form-group">
                <label class="form-label">Platform</label>
                <p style="color:var(--dark);margin:0;">{{ $jadwal->platform }}</p>
            </div>
            <div class="form-group">
                <label class="form-label">Keterangan</label>
                <p style="color:var(--dark);margin:0;">{{ $jadwal->keterangan ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-card">
            <h3><i class="bx bxs-group"></i> Operator Bertugas</h3>
            @forelse($jadwal->absensi as $a)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--grey);">
                <div>
                    <div style="font-weight:500;color:var(--dark);">{{ $a->user->nama_user }}</div>
                    <div style="font-size:12px;color:var(--dark-grey);">{{ $a->user->nohp ?? '-' }}</div>
                </div>
                <span class="badge {{ $a->badge['class'] }}">{{ $a->badge['label'] }}</span>
            </div>
            @empty
            <p style="color:var(--dark-grey);text-align:center;padding:20px 0;">Tidak ada operator</p>
            @endforelse
        </div>

        <div class="form-card">
            <h3><i class="bx bxs-wrench"></i> Peralatan Digunakan</h3>
            @forelse($jadwal->jadwalPeralatan as $jp)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--grey);">
                <div>
                    <div style="font-weight:500;color:var(--dark);">{{ $jp->peralatan->nama_peralatan }}</div>
                    <div style="font-size:12px;color:var(--dark-grey);">{{ $jp->peralatan->gedung }} · x{{ $jp->jumlah }}</div>
                </div>
                @if($jp->sudahDipasang())
                    <span class="badge badge-active"><i class="bx bx-check"></i> Terpasang</span>
                @else
                    <span class="badge badge-warning"><i class="bx bx-time"></i> Belum</span>
                @endif
            </div>
            @empty
            <p style="color:var(--dark-grey);text-align:center;padding:20px 0;">Tidak ada peralatan</p>
            @endforelse
        </div>
    </div>
</main>
@endsection
