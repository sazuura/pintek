@extends('layouts.app')
@section('title', 'Jadwal Saya')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Jadwal Saya</h1></div>
    </div>

    <div class="data-table-wrap">
        <div class="data-table-head">
            <h3>Daftar Jadwal yang Ditugaskan</h3>
            <small style="color:var(--dark-grey);">Tap baris untuk detail</small>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th class="sortable">Kegiatan <span class="sort-icon">⇅</span></th>
                        <th class="hide-mobile sortable">Tanggal <span class="sort-icon">⇅</span></th>
                        <th class="hide-mobile">Waktu</th>
                        <th class="hide-mobile">Platform</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jadwal as $index => $j)
                    @php
                        $absensi = $j->absensi->firstWhere('id_user', auth()->user()->id_user);
                        $uid     = 'jd-'.$j->id_penjadwalan;
                    @endphp
                    <tr class="accordion-row" data-target="{{ $uid }}">
                        <td>{{ $jadwal->firstItem() + $index }}</td>
                        <td>
                            <div style="font-weight:500;">{{ $j->judul_kegiatan }}</div>
                            <div style="font-size:12px;color:var(--dark-grey);">{{ $j->keterangan }}</div>
                        </td>
                        <td class="hide-mobile">{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="hide-mobile">
                            {{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }}
                        </td>
                        <td class="hide-mobile">
                            @if(str_contains($j->platform,'Online'))
                                <span class="badge badge-info"><i class="bx bx-wifi"></i> Online</span>
                            @else
                                <span class="badge badge-active"><i class="bx bx-building"></i> Offline</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                @if($absensi)
                                    <span class="badge {{ $absensi->badge['class'] }}">{{ $absensi->badge['label'] }}</span>
                                @else
                                    <span class="badge badge-inactive">-</span>
                                @endif
                                <i class="bx bx-chevron-down accordion-chevron"></i>
                            </div>
                        </td>
                    </tr>
                    <tr class="accordion-detail" id="{{ $uid }}">
                        <td colspan="6">
                            <div class="accordion-detail-inner">
                                <div class="detail-item">
                                    <label>Tanggal</label>
                                    <p>{{ $j->tanggal->translatedFormat('l, d F Y') }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Waktu</label>
                                    <p>{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB</p>
                                </div>
                                <div class="detail-item">
                                    <label>Platform</label>
                                    <p>{{ $j->platform }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Keterangan / Link</label>
                                    <p>{{ $j->keterangan ?? '-' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Peralatan</label>
                                    <p>{{ $j->jadwalPeralatan->map(fn($jp)=>$jp->peralatan->nama_peralatan.' (x'.$jp->jumlah.')')->join(', ') ?: '-' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Status Presensi</label>
                                    <p>{{ $absensi ? $absensi->badge['label'] : '-' }}</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:var(--dark-grey);">
                            <i class="bx bx-calendar-x" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                            Belum ada jadwal yang ditugaskan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            <span>Menampilkan {{ $jadwal->firstItem() }}–{{ $jadwal->lastItem() }} dari {{ $jadwal->total() }} jadwal</span>
            <div class="pagination-links">
                @if($jadwal->onFirstPage())
                    <span class="page-link disabled"><i class="bx bx-chevron-left"></i></span>
                @else
                    <a href="{{ $jadwal->previousPageUrl() }}" class="page-link"><i class="bx bx-chevron-left"></i></a>
                @endif
                @foreach(range(1, $jadwal->lastPage()) as $p)
                    <a href="{{ $jadwal->url($p) }}" class="page-link {{ $jadwal->currentPage()==$p?'active':'' }}">{{ $p }}</a>
                @endforeach
                @if($jadwal->hasMorePages())
                    <a href="{{ $jadwal->nextPageUrl() }}" class="page-link"><i class="bx bx-chevron-right"></i></a>
                @else
                    <span class="page-link disabled"><i class="bx bx-chevron-right"></i></span>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
