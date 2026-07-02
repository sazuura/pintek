@extends('layouts.app')
@section('title', 'Data Jadwal')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Data Jadwal Rapat</h1>
            </div>
            <a href="{{ route('admin.jadwal.create') }}" class="btn-download">
                <i class="bx bx-plus"></i><span class="text">Tambah Jadwal</span>
            </a>
        </div>

        <div class="content-toolbar">
            <form method="GET" action="{{ route('admin.jadwal.index') }}" style="display:contents;">
                <div class="toolbar-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="search" placeholder="Cari judul, platform..." value="{{ request('search') }}">
                </div>
                <select name="platform" class="toolbar-select">
                    <option value="">Semua Platform</option>
                    <option value="Online" {{ request('platform') == 'Online' ? 'selected' : '' }}>Online</option>
                    <option value="Offline" {{ request('platform') == 'Offline' ? 'selected' : '' }}>Offline</option>
                </select>
                <select name="status" class="toolbar-select">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
                @if(request()->hasAny(['search', 'platform', 'status']))
                    <a href="{{ route('admin.jadwal.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
        </div>

        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>Daftar Jadwal</h3>
                <small style="color:var(--dark-grey);">Tap baris untuk detail</small>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th class="sortable">Judul Kegiatan <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile sortable">Tanggal <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile">Waktu</th>
                            <th class="hide-mobile">Platform</th>
                            <th>Status</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwal as $index => $j)
                            @php
                                $uid = 'jd-' . $j->id_penjadwalan;
                                $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                                $dibatalkan = $j->isDibatalkan();
                            @endphp

                            <tr class="accordion-row {{ $dibatalkan ? 'row-inactive' : '' }}" data-target="{{ $uid }}">
                                <td>{{ $jadwal->firstItem() + $index }}</td>
                                <td>
                                    <div style="font-weight:500;">{{ $j->judul_kegiatan }}</div>
                                    <div style="font-size:12px;color:var(--dark-grey);margin-top:2px;">
                                        {{ $j->absensi->count() }} operator
                                    </div>
                                </td>
                                <td class="hide-mobile">{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                                <td class="hide-mobile">{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }}
                                </td>
                                <td class="hide-mobile">
                                    @if(str_contains($j->platform, 'Online'))
                                        <span class="badge badge-info"><i class="bx bx-wifi"></i> Online</span>
                                    @else
                                        <span class="badge badge-active"><i class="bx bx-building"></i> Offline</span>
                                    @endif
                                </td>
                                <td>
                                    @if($dibatalkan)
                                        <span class="badge badge-danger">
                                            <i class="bx bx-x-circle"></i> Dibatalkan
                                        </span>
                                    @elseif($sudahLewat)
                                        <span class="badge badge-active">
                                            <i class="bx bx-check-double"></i> Selesai
                                        </span>
                                    @else
                                        <span class="badge badge-info">
                                            <i class="bx bx-check-circle"></i> Aktif
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group">
                                        <i class="bx bx-chevron-down accordion-chevron"></i>
                                        @if(!$dibatalkan)
                                            @if($sudahLewat)
                                                <a href="{{ route('admin.jadwal.show', $j->id_penjadwalan) }}" class="btn-icon view"><i
                                                        class="bx bx-show"></i></a>
                                            @else
                                                <a href="{{ route('admin.jadwal.edit', $j->id_penjadwalan) }}" class="btn-icon edit"><i
                                                        class="bx bx-edit"></i></a>
                                                <button type="button" class="btn-icon delete" title="Batalkan Jadwal"
                                                    onclick="toggleBatal('batal-{{ $j->id_penjadwalan }}')">
                                                    <i class="bx bx-block"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Accordion detail --}}
                            <tr class="accordion-detail" id="{{ $uid }}">
                                <td colspan="7">
                                    <div class="accordion-detail-inner">
                                        <div class="detail-item">
                                            <label>Tanggal</label>
                                            <p>{{ $j->tanggal->translatedFormat('l, d F Y') }}</p>
                                        </div>
                                        <div class="detail-item">
                                            <label>Waktu</label>
                                            <p>{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB
                                            </p>
                                        </div>
                                        <div class="detail-item">
                                            <label>Platform</label>
                                            <p>{{ $j->platform }}</p>
                                        </div>
                                        <div class="detail-item">
                                            <label>Keterangan</label>
                                            <p>{{ $j->keterangan ?? '-' }}</p>
                                        </div>
                                        <div class="detail-item">
                                            <label>Operator</label>
                                            <p>{{ $j->absensi->map(fn($a) => $a->user->nama_user)->join(', ') ?: '-' }}</p>
                                        </div>
                                        <div class="detail-item">
                                            <label>Peralatan</label>
                                            <p>{{ $j->jadwalPeralatan->map(fn($jp) => $jp->peralatan->nama_peralatan . ' (x' . $jp->jumlah . ')')->join(', ') ?: '-' }}
                                            </p>
                                        </div>
                                        @if($dibatalkan)
                                            <div class="detail-item" style="grid-column:span 2;">
                                                <label>Alasan Pembatalan</label>
                                                <p style="color:#e74c3c;">{{ $j->alasan_batal }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Form batalkan inline --}}
                                    @if(!$dibatalkan && !$sudahLewat)
                                        <div id="batal-{{ $j->id_penjadwalan }}"
                                            style="display:none;padding:14px 16px;border-top:1px solid var(--grey);background:#fdecea;">
                                            <div style="font-size:13px;font-weight:600;color:#c0392b;margin-bottom:10px;">
                                                <i class="bx bx-error"></i> Batalkan Jadwal — notif WA akan dikirim ke semua
                                                operator
                                            </div>
                                            <form action="{{ route('admin.jadwal.batalkan', $j->id_penjadwalan) }}" method="POST"
                                                style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                                @csrf
                                                <input type="text" name="alasan_batal" class="form-input"
                                                    placeholder="Alasan pembatalan (wajib)" required
                                                    style="flex:1;min-width:200px;height:36px;">
                                                <button type="submit" class="toolbar-btn danger" style="height:36px;">
                                                    <i class="bx bx-block"></i> Batalkan
                                                </button>
                                                <button type="button" class="toolbar-btn neutral" style="height:36px;"
                                                    onclick="toggleBatal('batal-{{ $j->id_penjadwalan }}')">Batal</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--dark-grey);">
                                    <i class="bx bx-calendar-x" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                    Belum ada jadwal
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">
                <span>Menampilkan {{ $jadwal->firstItem() }}–{{ $jadwal->lastItem() }} dari {{ $jadwal->total() }}
                    data</span>
                <div class="pagination-links">
                    @if($jadwal->onFirstPage())
                        <span class="page-link disabled"><i class="bx bx-chevron-left"></i></span>
                    @else
                        <a href="{{ $jadwal->previousPageUrl() }}" class="page-link"><i class="bx bx-chevron-left"></i></a>
                    @endif
                    @foreach(range(1, $jadwal->lastPage()) as $p)
                        <a href="{{ $jadwal->url($p) }}"
                            class="page-link {{ $jadwal->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
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

@push('scripts')
    <script>
        function toggleBatal(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    </script>
@endpush