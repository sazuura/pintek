@extends('layouts.app')
@section('title', 'Laporan')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Laporan & Rekap</h1></div>
    </div>

    {{-- Filter - berubah sesuai tab aktif --}}
    <div class="content-toolbar">
        <form method="GET" action="{{ route('admin.laporan.index') }}" id="filter-form" style="display:contents;">

            {{-- Input hidden: simpan tab aktif untuk filter & export --}}
            <input type="hidden" name="tab" id="active-tab-input" value="{{ request('tab', 'panel-jadwal') }}">

            <label style="font-size:13px;color:var(--dark-grey);white-space:nowrap;">Dari</label>
            <input type="date" name="start" class="toolbar-select" value="{{ request('start') }}" style="height:36px;padding:0 10px;">
            <label style="font-size:13px;color:var(--dark-grey);white-space:nowrap;">s/d</label>
            <input type="date" name="end"   class="toolbar-select" value="{{ request('end') }}"   style="height:36px;padding:0 10px;">

            <select name="operator" class="toolbar-select">
                <option value="">Semua Operator</option>
                @foreach($operators as $op)
                    <option value="{{ $op->id_user }}" {{ request('operator')==$op->id_user?'selected':'' }}>
                        {{ $op->nama_user }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
            @if(request()->hasAny(['start','end','operator']))
                <a href="{{ route('admin.laporan.index', ['tab' => request('tab','panel-jadwal')]) }}"
                   class="toolbar-btn neutral"><i class="bx bx-x"></i> Reset</a>
            @endif

            <div class="toolbar-right">
                {{-- Export - URL menyertakan tab aktif + filter yang sedang berlaku --}}
                <a id="btn-pdf" href="{{ route('admin.laporan.exportPdf', array_merge(request()->except(['jadwal_page','peralatan_page']), ['tab' => request('tab','panel-jadwal')])) }}"
                   class="toolbar-btn danger">
                    <i class="bx bxs-file-pdf"></i> PDF
                </a>
                <a id="btn-excel" href="{{ route('admin.laporan.exportExcel', array_merge(request()->except(['jadwal_page','peralatan_page']), ['tab' => request('tab','panel-jadwal')])) }}"
                   class="toolbar-btn success">
                    <i class="bx bxs-spreadsheet"></i> Excel
                </a>
            </div>
        </form>
    </div>

    {{-- Tab container --}}
    <div class="tab-container">
        <div class="tab-group" data-panels="laporan-panels">
            <button class="tab-btn {{ request('tab','panel-jadwal') === 'panel-jadwal' ? 'active' : '' }}"
                    data-tab="panel-jadwal">
                <i class="bx bx-calendar-check"></i>
                Jadwal & Operator
                <span class="tab-badge">{{ $jadwal->total() }}</span>
            </button>
            <button class="tab-btn {{ request('tab') === 'panel-peralatan' ? 'active' : '' }}"
                    data-tab="panel-peralatan">
                <i class="bx bx-wrench"></i>
                Peralatan Digunakan
                <span class="tab-badge">{{ $peralatan->total() }}</span>
            </button>
        </div>

        <div class="tab-panels" id="laporan-panels">

            {{-- Panel 1: Jadwal & Operator --}}
            <div class="tab-panel {{ request('tab','panel-jadwal') === 'panel-jadwal' ? 'active' : '' }}"
                 id="panel-jadwal">
                <div class="data-table-wrap">
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:32px;"></th>
                                    <th style="width:40px;">#</th>
                                    <th class="sortable">Judul Rapat <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile sortable">Tanggal <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile">Platform</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jadwal as $i => $j)
                                @php
                                    $uid        = 'prs-'.$j->id_penjadwalan;
                                    $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                                    $dibatalkan = $j->isDibatalkan();
                                @endphp
                                <tr class="accordion-row" data-target="{{ $uid }}">
                                    <td style="text-align:center;"><i class="bx bx-chevron-down accordion-chevron"></i></td>
                                    <td>{{ $jadwal->firstItem() + $i }}</td>
                                    <td>
                                        <div style="font-weight:500;">{{ $j->judul_kegiatan }}</div>
                                        <div style="font-size:12px;color:var(--dark-grey);margin-top:2px;">
                                            {{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}
                                        </div>
                                    </td>
                                    <td class="hide-mobile">{{ $j->tanggal->translatedFormat('D, d/m/Y') }}</td>
                                    <td class="hide-mobile">{{ str_contains($j->platform,'Online') ? 'Online' : 'Offline' }}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            @if($dibatalkan)
                                                <span class="badge badge-danger">Dibatalkan</span>
                                            @elseif($sudahLewat)
                                                <span class="badge badge-active">Selesai</span>
                                            @else
                                                <span class="badge badge-info">Aktif</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr class="accordion-detail" id="{{ $uid }}">
                                    <td colspan="6">
                                        <div class="detail-panel">
                                            <div class="detail-row">
                                                <i class="bx bx-calendar"></i>
                                                <div><label>Tanggal</label><p>{{ $j->tanggal->translatedFormat('l, d F Y') }}</p></div>
                                            </div>
                                            <div class="detail-row">
                                                <i class="bx bx-desktop"></i>
                                                <div><label>Platform</label><p>{{ $j->platform }}</p></div>
                                            </div>
                                            <div class="detail-row full">
                                                <i class="bx bx-note"></i>
                                                <div><label>Keterangan</label><p>{{ $j->keterangan ?? '-' }}</p></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:40px;color:var(--dark-grey);">
                                        <i class="bx bx-clipboard" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-pagination :paginator="$jadwal" />
                </div>
            </div>

            {{-- Panel 2: Peralatan --}}
            <div class="tab-panel {{ request('tab') === 'panel-peralatan' ? 'active' : '' }}"
                 id="panel-peralatan">
                <div class="data-table-wrap">
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:32px;"></th>
                                    <th style="width:40px;">#</th>
                                    <th class="sortable">Peralatan <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile sortable">Peminjam <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile sortable">Tanggal Pinjam <span class="sort-icon">⇅</span></th>
                                    <th>Jml</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($peralatan as $i => $item)
                                @php $uid = 'prl-'.$item->id_item; @endphp
                                <tr class="accordion-row" data-target="{{ $uid }}">
                                    <td style="text-align:center;"><i class="bx bx-chevron-down accordion-chevron"></i></td>
                                    <td>{{ $peralatan->firstItem() + $i }}</td>
                                    <td>
                                        <div style="font-weight:500;">{{ $item->peralatan->nama_peralatan }}</div>
                                        <div style="font-size:12px;color:var(--dark-grey);margin-top:2px;">
                                            {{ $item->peralatan->gedung }}
                                        </div>
                                    </td>
                                    <td class="hide-mobile">{{ $item->peminjaman->user->nama_user ?? '-' }}</td>
                                    <td class="hide-mobile">{{ $item->peminjaman->tanggal_pinjam->translatedFormat('D, d/m/Y') }}</td>
                                    <td>{{ $item->jumlah }}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span class="badge {{ $item->peminjaman->badge['class'] }}">{{ $item->peminjaman->badge['label'] }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="accordion-detail" id="{{ $uid }}">
                                    <td colspan="7">
                                        <div class="detail-panel">
                                            <div class="detail-row">
                                                <i class="bx bx-id-card"></i>
                                                <div><label>Nomor Seri</label><p>{{ $item->peralatan->kode_barang ?? '-' }}</p></div>
                                            </div>
                                            <div class="detail-row">
                                                <i class="bx bx-calendar-check"></i>
                                                <div><label>Rencana Kembali</label><p>{{ $item->peminjaman->tanggal_kembali_rencana->translatedFormat('D, d/m/Y') }}</p></div>
                                            </div>
                                            <div class="detail-row full">
                                                <i class="bx bx-note"></i>
                                                <div><label>Keperluan</label><p>{{ $item->peminjaman->keperluan }}</p></div>
                                            </div>
                                            @if($item->peminjaman->penjadwalan)
                                                <div class="detail-row full">
                                                    <i class="bx bx-calendar"></i>
                                                    <div><label>Terkait Jadwal</label><p>{{ $item->peminjaman->penjadwalan->judul_kegiatan }} ({{ $item->peminjaman->penjadwalan->tanggal->format('d/m/Y') }})</p></div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:40px;color:var(--dark-grey);">
                                        <i class="bx bx-package" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-pagination :paginator="$peralatan" />
                </div>
            </div>

        </div>
    </div>

</main>
@endsection

@push('scripts')
<script>
/**
 * Saat tab diganti:
 *   1. Update hidden input "tab" agar filter form tahu tab mana yang aktif
 *   2. Update URL tombol export PDF & Excel
 */
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.tab-btn');
    if (!btn) return;

    var tabId       = btn.dataset.tab;
    var activeInput = document.getElementById('active-tab-input');
    var btnPdf      = document.getElementById('btn-pdf');
    var btnExcel    = document.getElementById('btn-excel');

    if (activeInput) activeInput.value = tabId;

    function updateExportUrl(el) {
        if (!el) return;
        var url = new URL(el.href, window.location.origin);
        url.searchParams.set('tab', tabId);
        el.href = url.toString();
    }
    updateExportUrl(btnPdf);
    updateExportUrl(btnExcel);
});
</script>
@endpush
