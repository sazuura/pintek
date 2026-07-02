@extends('layouts.app')
@section('title', 'Laporan')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Laporan & Rekap</h1></div>
    </div>

    {{-- Filter — berubah sesuai tab aktif --}}
    <div class="content-toolbar">
        <form method="GET" action="{{ route('admin.laporan.index') }}" id="filter-form" style="display:contents;">

            {{-- Input hidden: simpan tab aktif untuk filter & export --}}
            <input type="hidden" name="tab" id="active-tab-input" value="{{ request('tab', 'panel-presensi') }}">

            <label style="font-size:13px;color:var(--dark-grey);white-space:nowrap;">Dari</label>
            <input type="date" name="start" class="toolbar-select" value="{{ request('start') }}" style="height:36px;padding:0 10px;">
            <label style="font-size:13px;color:var(--dark-grey);white-space:nowrap;">s/d</label>
            <input type="date" name="end"   class="toolbar-select" value="{{ request('end') }}"   style="height:36px;padding:0 10px;">

            {{-- Filter operator — hanya tampil di tab presensi --}}
            <select name="operator" class="toolbar-select" id="filter-operator"
                style="{{ request('tab','panel-presensi') === 'panel-peralatan' ? 'display:none;' : '' }}">
                <option value="">Semua Operator</option>
                @foreach($operators as $op)
                    <option value="{{ $op->id_user }}" {{ request('operator')==$op->id_user?'selected':'' }}>
                        {{ $op->nama_user }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
            @if(request()->hasAny(['start','end','operator']))
                <a href="{{ route('admin.laporan.index', ['tab' => request('tab','panel-presensi')]) }}"
                   class="toolbar-btn neutral"><i class="bx bx-x"></i> Reset</a>
            @endif

            <div class="toolbar-right">
                {{-- Export — URL menyertakan tab aktif + filter yang sedang berlaku --}}
                <a id="btn-pdf" href="{{ route('admin.laporan.exportPdf', array_merge(request()->all(), ['tab' => request('tab','panel-presensi')])) }}"
                   class="toolbar-btn danger">
                    <i class="bx bxs-file-pdf"></i> PDF
                </a>
                <a id="btn-excel" href="{{ route('admin.laporan.exportExcel', array_merge(request()->all(), ['tab' => request('tab','panel-presensi')])) }}"
                   class="toolbar-btn success">
                    <i class="bx bxs-spreadsheet"></i> Excel
                </a>
            </div>
        </form>
    </div>

    {{-- Tab container --}}
    <div class="tab-container">
        <div class="tab-group" data-panels="laporan-panels">
            <button class="tab-btn {{ request('tab','panel-presensi') === 'panel-presensi' ? 'active' : '' }}"
                    data-tab="panel-presensi">
                <i class="bx bx-calendar-check"></i>
                Jadwal & Presensi
                <span class="tab-badge">{{ $absensi->count() }}</span>
            </button>
            <button class="tab-btn {{ request('tab') === 'panel-peralatan' ? 'active' : '' }}"
                    data-tab="panel-peralatan">
                <i class="bx bx-wrench"></i>
                Peralatan Digunakan
                <span class="tab-badge">{{ $jadwalPeralatan->count() }}</span>
            </button>
        </div>

        <div class="tab-panels" id="laporan-panels">

            {{-- Panel 1: Presensi --}}
            <div class="tab-panel {{ request('tab','panel-presensi') === 'panel-presensi' ? 'active' : '' }}"
                 id="panel-presensi">
                <div class="data-table-wrap" style="border-radius:0 0 12px 12px;box-shadow:none;border-top:none;">
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th class="sortable">Operator <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile sortable">Kegiatan <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile sortable">Tanggal <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile">Platform</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($absensi as $i => $a)
                                @php $uid = 'prs-'.$a->id_absensi; @endphp
                                <tr class="accordion-row" data-target="{{ $uid }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td style="font-weight:500;">{{ $a->user->nama_user }}</td>
                                    <td class="hide-mobile">{{ $a->penjadwalan->judul_kegiatan }}</td>
                                    <td class="hide-mobile">{{ $a->tanggal->format('d/m/Y') }}</td>
                                    <td class="hide-mobile">{{ str_contains($a->penjadwalan->platform,'Online') ? 'Online' : 'Offline' }}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span class="badge {{ $a->badge['class'] }}">{{ $a->badge['label'] }}</span>
                                            <i class="bx bx-chevron-down accordion-chevron"></i>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="accordion-detail" id="{{ $uid }}">
                                    <td colspan="6">
                                        <div class="accordion-detail-inner">
                                            <div class="detail-item"><label>Kegiatan</label><p>{{ $a->penjadwalan->judul_kegiatan }}</p></div>
                                            <div class="detail-item"><label>Tanggal</label><p>{{ $a->tanggal->translatedFormat('l, d F Y') }}</p></div>
                                            <div class="detail-item"><label>Platform</label><p>{{ $a->penjadwalan->platform }}</p></div>
                                            <div class="detail-item"><label>Keterangan</label><p>{{ $a->keterangan ?? '-' }}</p></div>
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
                </div>
            </div>

            {{-- Panel 2: Peralatan --}}
            <div class="tab-panel {{ request('tab') === 'panel-peralatan' ? 'active' : '' }}"
                 id="panel-peralatan">
                <div class="data-table-wrap" style="border-radius:0 0 12px 12px;box-shadow:none;border-top:none;">
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th class="sortable">Peralatan <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile">Gedung</th>
                                    <th class="hide-mobile sortable">Kegiatan <span class="sort-icon">⇅</span></th>
                                    <th class="hide-mobile sortable">Tanggal <span class="sort-icon">⇅</span></th>
                                    <th>Jml</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jadwalPeralatan as $i => $jp)
                                @php $uid = 'prl-'.$jp->id_jadwal_alat; @endphp
                                <tr class="accordion-row" data-target="{{ $uid }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td style="font-weight:500;">{{ $jp->peralatan->nama_peralatan }}</td>
                                    <td class="hide-mobile">{{ $jp->peralatan->gedung }}</td>
                                    <td class="hide-mobile">{{ $jp->penjadwalan->judul_kegiatan }}</td>
                                    <td class="hide-mobile">{{ $jp->penjadwalan->tanggal->format('d/m/Y') }}</td>
                                    <td>{{ $jp->jumlah }}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            @if($jp->sudahDipasang())
                                                <span class="badge badge-active"><i class="bx bx-check"></i> Terpasang</span>
                                            @else
                                                <span class="badge badge-warning">Belum</span>
                                            @endif
                                            <i class="bx bx-chevron-down accordion-chevron"></i>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="accordion-detail" id="{{ $uid }}">
                                    <td colspan="7">
                                        <div class="accordion-detail-inner">
                                            <div class="detail-item"><label>Kode Barang</label><p>{{ $jp->peralatan->kode_barang ?? '-' }}</p></div>
                                            <div class="detail-item"><label>Gedung</label><p>{{ $jp->peralatan->gedung }}</p></div>
                                            <div class="detail-item"><label>Kegiatan</label><p>{{ $jp->penjadwalan->judul_kegiatan }}</p></div>
                                            <div class="detail-item"><label>Tanggal</label><p>{{ $jp->penjadwalan->tanggal->translatedFormat('l, d F Y') }}</p></div>
                                            <div class="detail-item"><label>Jumlah</label><p>{{ $jp->jumlah }} unit</p></div>
                                            <div class="detail-item"><label>Status</label><p>{{ $jp->sudahDipasang() ? 'Sudah Dipasang' : 'Belum Dipasang' }}</p></div>
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
 *   2. Sembunyikan/tampilkan filter operator sesuai tab
 *   3. Update URL tombol export PDF & Excel
 */
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.tab-btn');
    if (!btn) return;

    var tabId          = btn.dataset.tab;
    var activeInput    = document.getElementById('active-tab-input');
    var filterOperator = document.getElementById('filter-operator');
    var btnPdf         = document.getElementById('btn-pdf');
    var btnExcel       = document.getElementById('btn-excel');

    // 1. Update hidden input
    if (activeInput) activeInput.value = tabId;

    // 2. Tampil/sembunyikan filter operator
    if (filterOperator) {
        filterOperator.style.display = tabId === 'panel-peralatan' ? 'none' : '';
        // Reset nilai operator jika pindah ke tab peralatan
        if (tabId === 'panel-peralatan') filterOperator.value = '';
    }

    // 3. Update URL export dengan tab yang aktif
    function updateExportUrl(el) {
        if (!el) return;
        var url    = new URL(el.href, window.location.origin);
        url.searchParams.set('tab', tabId);
        // Hapus operator jika tab peralatan
        if (tabId === 'panel-peralatan') url.searchParams.delete('operator');
        el.href = url.toString();
    }
    updateExportUrl(btnPdf);
    updateExportUrl(btnExcel);
});
</script>
@endpush