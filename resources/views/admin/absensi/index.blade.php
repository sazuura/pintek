@extends('layouts.app')
@section('title', 'Data Presensi')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Data Presensi</h1></div>
    </div>

    <div class="content-toolbar">
        <form method="GET" action="{{ route('admin.absensi.index') }}" style="display:contents;">
            <div class="toolbar-search">
                <i class="bx bx-search"></i>
                <input type="text" name="search" placeholder="Cari nama operator / kegiatan..." value="{{ request('search') }}">
            </div>
            <select name="status" class="toolbar-select">
                <option value="">Semua Status</option>
                <option value="hadir"           {{ request('status')=='hadir'           ?'selected':'' }}>Hadir</option>
                <option value="perlu_disetujui" {{ request('status')=='perlu_disetujui' ?'selected':'' }}>Perlu Disetujui</option>
                <option value="tidak_hadir"     {{ request('status')=='tidak_hadir'     ?'selected':'' }}>Tidak Hadir</option>
                <option value="pending"         {{ request('status')=='pending'         ?'selected':'' }}>Pending</option>
            </select>
            <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
            @if(request()->hasAny(['search','status']))
                <a href="{{ route('admin.absensi.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i> Reset</a>
            @endif
        </form>
    </div>

    <div class="data-table-wrap">
        <div class="data-table-head">
            <h3>Daftar Presensi</h3>
            <small style="color:var(--dark-grey);">Tap baris untuk detail & update status</small>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th class="sortable">Operator <span class="sort-icon">⇅</span></th>
                        <th class="hide-mobile sortable">Kegiatan <span class="sort-icon">⇅</span></th>
                        <th class="hide-mobile sortable">Tanggal <span class="sort-icon">⇅</span></th>
                        <th>Status</th>
                        <th style="width:60px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($absensi as $index => $a)
                    @php $uid = 'ab-'.$a->id_absensi; @endphp

                    <tr class="accordion-row" data-target="{{ $uid }}">
                        <td>{{ $absensi->firstItem() + $index }}</td>
                        <td>
                            <div style="font-weight:500;">{{ $a->user->nama_user }}</div>
                            <div style="font-size:12px;color:var(--dark-grey);">{{ $a->user->nohp ?? '-' }}</div>
                        </td>
                        <td class="hide-mobile">{{ $a->penjadwalan->judul_kegiatan }}</td>
                        <td class="hide-mobile">{{ $a->tanggal->translatedFormat('d M Y') }}</td>
                        <td><span class="badge {{ $a->badge['class'] }}">{{ $a->badge['label'] }}</span></td>
                        <td>
                            <div class="action-group">
                                <i class="bx bx-chevron-down accordion-chevron"></i>
                                <a href="{{ route('admin.absensi.show', $a->id_absensi) }}" class="btn-icon view">
                                    <i class="bx bx-show"></i>
                                </a>
                            </div>
                        </td>
                    </tr>

                    {{-- Accordion: detail + form update status inline --}}
                    <tr class="accordion-detail" id="{{ $uid }}">
                        <td colspan="6">
                            <div class="accordion-detail-inner">
                                <div class="detail-item">
                                    <label>Kegiatan</label>
                                    <p>{{ $a->penjadwalan->judul_kegiatan }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Tanggal</label>
                                    <p>{{ $a->tanggal->translatedFormat('l, d F Y') }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Platform</label>
                                    <p>{{ $a->penjadwalan->platform }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Keterangan Operator</label>
                                    <p>{{ $a->keterangan ?? '-' }}</p>
                                </div>
                                <div class="detail-item">
                                    <label>Divalidasi</label>
                                    <p>{{ $a->validated ? 'Ya' : 'Belum' }}</p>
                                </div>
                                {{-- Update status inline --}}
                                <div class="detail-item" style="grid-column:span 2;">
                                    <label>Update Status</label>
                                    <form action="{{ route('admin.absensi.updateStatus', $a->id_absensi) }}" method="POST"
                                          style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:4px;">
                                        @csrf
                                        <select name="status" class="toolbar-select">
                                            @foreach(['hadir','izin_disetujui','sakit_disetujui','alpha','ditolak'] as $s)
                                                <option value="{{ $s }}" {{ $a->status==$s?'selected':'' }}>
                                                    {{ ucfirst(str_replace('_',' ',$s)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="toolbar-btn primary">
                                            <i class="bx bx-check"></i> Simpan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:var(--dark-grey);">
                            <i class="bx bx-clipboard" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                            Belum ada data presensi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">
            <span>Menampilkan {{ $absensi->firstItem() }}–{{ $absensi->lastItem() }} dari {{ $absensi->total() }} data</span>
            <div class="pagination-links">
                @if($absensi->onFirstPage())
                    <span class="page-link disabled"><i class="bx bx-chevron-left"></i></span>
                @else
                    <a href="{{ $absensi->previousPageUrl() }}" class="page-link"><i class="bx bx-chevron-left"></i></a>
                @endif
                @foreach(range(1, $absensi->lastPage()) as $p)
                    <a href="{{ $absensi->url($p) }}" class="page-link {{ $absensi->currentPage()==$p?'active':'' }}">{{ $p }}</a>
                @endforeach
                @if($absensi->hasMorePages())
                    <a href="{{ $absensi->nextPageUrl() }}" class="page-link"><i class="bx bx-chevron-right"></i></a>
                @else
                    <span class="page-link disabled"><i class="bx bx-chevron-right"></i></span>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
