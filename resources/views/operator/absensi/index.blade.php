@extends('layouts.app')
@section('title', 'Presensi Saya')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Presensi Saya</h1></div>
    </div>

    {{-- Jadwal aktif / mendatang --}}
    @forelse($jadwalAktif as $a)
    @php
        $jadwal = $a->penjadwalan;
        $now    = \Carbon\Carbon::now('Asia/Jakarta');
        $start  = $jadwal->startDateTime;
        $end    = $jadwal->endDateTime;
        $sedangBerlangsung = $start && $end && $now->between($start, $end);
        $hariIni = $start && $now->toDateString() === $start->toDateString();
    @endphp

    <div class="form-card" style="margin-bottom:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
            <div>
                <div style="font-size:16px;font-weight:600;color:var(--dark);">{{ $jadwal->judul_kegiatan }}</div>
                <div style="font-size:13px;color:var(--dark-grey);margin-top:4px;">
                    {{ $jadwal->tanggal->translatedFormat('l, d F Y') }}
                    · {{ \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i') }}
                    - {{ \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') }} WIB
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                @if($sedangBerlangsung)
                    <span class="badge badge-active" style="animation:pulse 1.5s infinite;">
                        <i class="bx bx-radio-circle-marked"></i> Sedang Berlangsung
                    </span>
                @elseif($hariIni)
                    <span class="badge badge-warning"><i class="bx bx-time"></i> Hari Ini</span>
                @endif
                <span class="badge {{ $a->badge['class'] }}">{{ $a->badge['label'] }}</span>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:16px;font-size:13px;">
            <div>
                <span style="color:var(--dark-grey);">Platform</span>
                <div style="font-weight:500;margin-top:2px;">{{ $jadwal->platform }}</div>
            </div>
            <div>
                <span style="color:var(--dark-grey);">Keterangan / Link</span>
                <div style="font-weight:500;margin-top:2px;">{{ $jadwal->keterangan ?? '-' }}</div>
            </div>
        </div>

        {{-- Peralatan yang harus dipasang --}}
        @if($jadwal->jadwalPeralatan->count())
        <div style="margin-bottom:16px;">
            <div style="font-size:13px;font-weight:600;color:var(--dark);margin-bottom:8px;">
                <i class="bx bxs-wrench" style="color:var(--blue);"></i> Peralatan yang Harus Dipasang
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($jadwal->jadwalPeralatan as $jp)
                <div style="background:var(--grey);border-radius:8px;padding:8px 12px;display:flex;align-items:center;gap:8px;font-size:13px;">
                    <span style="font-weight:500;">{{ $jp->peralatan->nama_peralatan }}</span>
                    <span style="color:var(--dark-grey);">x{{ $jp->jumlah }}</span>
                    <span style="color:var(--dark-grey);">·</span>
                    <span style="color:var(--dark-grey);font-size:12px;">{{ $jp->peralatan->gedung }}</span>
                    @if($jp->sudahDipasang())
                        <span class="badge badge-active" style="font-size:11px;"><i class="bx bx-check"></i> Terpasang</span>
                    @else
                        <form action="{{ route('operator.peralatan.konfirmasi', $jp->id_jadwal_alat) }}" method="POST" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="toolbar-btn primary" style="height:26px;padding:0 10px;font-size:11px;"
                                onclick="return confirm('Konfirmasi peralatan {{ $jp->peralatan->nama_peralatan }} sudah dipasang?')">
                                <i class="bx bx-check"></i> Konfirmasi
                            </button>
                        </form>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Form isi presensi --}}
        @if(!$a->isFinal())
        <div style="border-top:1px solid var(--grey);padding-top:14px;">
            <div style="font-size:13px;font-weight:600;color:var(--dark);margin-bottom:10px;">
                <i class="bx bx-edit" style="color:var(--blue);"></i> Isi Presensi
            </div>
            <form action="{{ route('operator.absensi.store') }}" method="POST"
                  style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                @csrf
                <input type="hidden" name="id_absensi" value="{{ $a->id_absensi }}">
                <select name="status" class="toolbar-select" style="min-width:130px;">
                    <option value="" disabled {{ $a->isPending()?'selected':'' }}>-- Pilih Status --</option>
                    <option value="hadir" {{ $a->isHadir()?'selected':'' }}
                        {{ !$sedangBerlangsung ? 'disabled' : '' }}>
                        Hadir {{ !$sedangBerlangsung ? '(hanya saat berlangsung)' : '' }}
                    </option>
                    <option value="izin"  {{ $a->status=='izin'?'selected':'' }}
                        {{ $hariIni ? 'disabled' : '' }}>
                        Izin {{ $hariIni ? '(hanya H-1)' : '' }}
                    </option>
                    <option value="sakit" {{ $a->status=='sakit'?'selected':'' }}
                        {{ $hariIni ? 'disabled' : '' }}>
                        Sakit {{ $hariIni ? '(hanya H-1)' : '' }}
                    </option>
                </select>
                <input type="text" name="keterangan" class="toolbar-select"
                    value="{{ $a->keterangan }}" placeholder="Keterangan (opsional)"
                    style="flex:1;min-width:200px;">
                <button type="submit" class="toolbar-btn primary">
                    <i class="bx bx-save"></i> Simpan
                </button>
            </form>
            <small style="color:var(--dark-grey);display:block;margin-top:6px;">
                Hadir: saat jadwal berlangsung · Izin/Sakit: maksimal H-1 sebelum jadwal
            </small>
        </div>
        @else
        <div style="border-top:1px solid var(--grey);padding-top:12px;font-size:13px;color:var(--dark-grey);">
            <i class="bx bx-lock-alt"></i> Presensi sudah divalidasi, tidak dapat diubah.
        </div>
        @endif
    </div>
    @empty
    <div class="form-card" style="text-align:center;padding:40px;color:var(--dark-grey);">
        <i class="bx bx-calendar-check" style="font-size:48px;display:block;margin-bottom:12px;color:var(--grey);"></i>
        <p style="font-size:15px;">Tidak ada jadwal aktif atau mendatang.</p>
        <a href="{{ route('operator.jadwal.index') }}" class="toolbar-btn primary" style="margin-top:14px;display:inline-flex;">
            Lihat Semua Jadwal
        </a>
    </div>
    @endforelse

    {{-- Riwayat presensi --}}
    <div style="margin-top:20px;">
        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>Riwayat Presensi</h3>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="sortable">Kegiatan <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile sortable">Tanggal <span class="sort-icon">⇅</span></th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riwayat as $index => $r)
                        @php $uid = 'rw-'.$r->id_absensi; @endphp
                        <tr class="accordion-row" data-target="{{ $uid }}">
                            <td>{{ $riwayat->firstItem() + $index }}</td>
                            <td style="font-weight:500;">{{ $r->penjadwalan->judul_kegiatan }}</td>
                            <td class="hide-mobile">{{ $r->tanggal->format('d/m/Y') }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span class="badge {{ $r->badge['class'] }}">{{ $r->badge['label'] }}</span>
                                    <i class="bx bx-chevron-down accordion-chevron"></i>
                                </div>
                            </td>
                        </tr>
                        <tr class="accordion-detail" id="{{ $uid }}">
                            <td colspan="4">
                                <div class="accordion-detail-inner">
                                    <div class="detail-item"><label>Tanggal</label><p>{{ $r->tanggal->translatedFormat('l, d F Y') }}</p></div>
                                    <div class="detail-item"><label>Platform</label><p>{{ $r->penjadwalan->platform }}</p></div>
                                    <div class="detail-item"><label>Keterangan</label><p>{{ $r->keterangan ?? '-' }}</p></div>
                                    <div class="detail-item"><label>Divalidasi</label><p>{{ $r->validated ? 'Ya' : 'Belum' }}</p></div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:30px;color:var(--dark-grey);">Belum ada riwayat</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">
                <span>{{ $riwayat->total() }} total presensi</span>
                <div class="pagination-links">
                    @if($riwayat->onFirstPage())
                        <span class="page-link disabled"><i class="bx bx-chevron-left"></i></span>
                    @else
                        <a href="{{ $riwayat->previousPageUrl() }}" class="page-link"><i class="bx bx-chevron-left"></i></a>
                    @endif
                    @foreach(range(1, $riwayat->lastPage()) as $p)
                        <a href="{{ $riwayat->url($p) }}" class="page-link {{ $riwayat->currentPage()==$p?'active':'' }}">{{ $p }}</a>
                    @endforeach
                    @if($riwayat->hasMorePages())
                        <a href="{{ $riwayat->nextPageUrl() }}" class="page-link"><i class="bx bx-chevron-right"></i></a>
                    @else
                        <span class="page-link disabled"><i class="bx bx-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<style>
@keyframes pulse {
    0%,100% { opacity:1; }
    50%      { opacity:.6; }
}
</style>
@endpush
