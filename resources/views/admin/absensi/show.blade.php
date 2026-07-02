@extends('layouts.app')
@section('title', 'Detail Presensi')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Detail Presensi</h1></div>
        <a href="{{ route('admin.absensi.index') }}" class="toolbar-btn neutral">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

        {{-- Info --}}
        <div class="form-card">
            <h3><i class="bx bx-info-circle"></i> Informasi Presensi</h3>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <div class="form-label">Operator</div>
                    <div style="font-weight:600;color:var(--dark);margin-top:4px;">{{ $absensi->user->nama_user }}</div>
                    <div style="font-size:12px;color:var(--dark-grey);">{{ $absensi->user->nohp ?? '-' }}</div>
                </div>
                <div>
                    <div class="form-label">Kegiatan</div>
                    <div style="color:var(--dark);margin-top:4px;">{{ $absensi->penjadwalan->judul_kegiatan }}</div>
                </div>
                <div>
                    <div class="form-label">Tanggal</div>
                    <div style="color:var(--dark);margin-top:4px;">{{ $absensi->tanggal->translatedFormat('l, d F Y') }}</div>
                </div>
                <div>
                    <div class="form-label">Waktu Kegiatan</div>
                    <div style="color:var(--dark);margin-top:4px;">
                        {{ \Carbon\Carbon::parse($absensi->penjadwalan->waktu_mulai)->format('H:i') }}
                        -
                        {{ \Carbon\Carbon::parse($absensi->penjadwalan->waktu_selesai)->format('H:i') }} WIB
                    </div>
                </div>
                <div>
                    <div class="form-label">Platform</div>
                    <div style="color:var(--dark);margin-top:4px;">{{ $absensi->penjadwalan->platform }}</div>
                </div>
                <div>
                    <div class="form-label">Keterangan Operator</div>
                    <div style="color:var(--dark);margin-top:4px;">{{ $absensi->keterangan ?? '-' }}</div>
                </div>
                <div>
                    <div class="form-label">Status Saat Ini</div>
                    <div style="margin-top:4px;"><span class="badge {{ $absensi->badge['class'] }}">{{ $absensi->badge['label'] }}</span></div>
                </div>
                <div>
                    <div class="form-label">Divalidasi</div>
                    <div style="margin-top:4px;">
                        @if($absensi->validated)
                            <span class="badge badge-active"><i class="bx bx-check"></i> Sudah</span>
                        @else
                            <span class="badge badge-warning"><i class="bx bx-time"></i> Belum</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px;">
            {{-- Update status --}}
            <div class="form-card">
                <h3><i class="bx bx-edit-alt"></i> Update Status</h3>
                <form action="{{ route('admin.absensi.updateStatus', $absensi->id_absensi) }}" method="POST">
                    @csrf
                    <div class="form-group" style="margin-bottom:16px;">
                        <label class="form-label">Pilih Status Baru</label>
                        <select name="status" class="form-select">
                            @foreach(['hadir','izin_disetujui','sakit_disetujui','alpha','ditolak'] as $s)
                                <option value="{{ $s }}" {{ $absensi->status==$s?'selected':'' }}>
                                    {{ ucfirst(str_replace('_',' ',$s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-submit" style="width:100%;justify-content:center;">
                        <i class="bx bx-check"></i> Simpan Status
                    </button>
                </form>
            </div>

            {{-- Dokumentasi --}}
            @if($absensi->dokumentasi->count())
            <div class="form-card">
                <h3><i class="bx bx-image"></i> Dokumentasi / Bukti ({{ $absensi->dokumentasi->count() }})</h3>
                <div class="foto-grid" style="display:flex;flex-wrap:wrap;gap:10px;">
                    @foreach($absensi->dokumentasi as $dok)
                    <a href="{{ $dok->url }}" target="_blank" style="display:block;">
                        <img src="{{ $dok->url }}" alt="Dokumentasi"
                             style="width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--grey);">
                        @if($dok->keterangan)
                            <p style="font-size:11px;color:var(--dark-grey);margin-top:4px;text-align:center;">{{ $dok->keterangan }}</p>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
            @else
            <div class="form-card">
                <h3><i class="bx bx-image"></i> Dokumentasi</h3>
                <p style="color:var(--dark-grey);text-align:center;padding:20px 0;">Tidak ada dokumentasi</p>
            </div>
            @endif
        </div>
    </div>
</main>
@endsection
