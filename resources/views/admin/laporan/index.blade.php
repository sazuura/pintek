@extends('layouts.app')
@section('title', 'Laporan')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
        <div><h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Laporan & Rekap</h1></div>
    </div>

    {{-- Filter - berubah sesuai tab aktif --}}
    <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)] max-md:flex-col max-md:items-stretch">
        <form method="GET" action="{{ route('admin.laporan.index') }}" id="filter-form" class="contents">

            {{-- Input hidden: simpan tab aktif untuk filter & export --}}
            <input type="hidden" name="tab" id="active-tab-input" value="{{ request('tab', 'panel-jadwal') }}">

            <label class="text-[13px] text-text-muted whitespace-nowrap">Dari</label>
            <input type="date" name="start" value="{{ request('start') }}" onchange="this.form.submit()"
                class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
            <label class="text-[13px] text-text-muted whitespace-nowrap">s/d</label>
            <input type="date" name="end" value="{{ request('end') }}" onchange="this.form.submit()"
                class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">

            <select name="operator" onchange="this.form.submit()"
                class="searchable h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                <option value="">Semua Operator</option>
                @foreach($operators as $op)
                    <option value="{{ $op->id_user }}" {{ request('operator')==$op->id_user?'selected':'' }}>
                        {{ $op->nama_user }}
                    </option>
                @endforeach
            </select>

            @if(request()->hasAny(['start','end','operator']))
                <a href="{{ route('admin.laporan.index', ['tab' => request('tab','panel-jadwal')]) }}"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                    <i class="bx bx-x"></i> Reset</a>
            @endif

            <div class="ml-auto flex gap-2 items-center max-md:ml-0 max-md:w-full">
                {{-- Export - URL menyertakan tab aktif + filter yang sedang berlaku --}}
                <a id="btn-pdf" target="_blank" rel="noopener" href="{{ route('admin.laporan.exportPdf', array_merge(request()->except(['jadwal_page','peralatan_page']), ['tab' => request('tab','panel-jadwal')])) }}"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-danger-text text-white">
                    <i class="bx bxs-file-pdf"></i> PDF
                </a>
                <a id="btn-excel" href="{{ route('admin.laporan.exportExcel', array_merge(request()->except(['jadwal_page','peralatan_page']), ['tab' => request('tab','panel-jadwal')])) }}"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-[#1abc9c] text-white">
                    <i class="bx bxs-spreadsheet"></i> Excel
                </a>
            </div>
        </form>
    </div>

    @php
        $tabBtnClass = 'tab-btn group flex-1 min-w-0 inline-flex items-center justify-center gap-1.5 max-xs:gap-1 py-3.5 px-5 max-xs:px-2 text-[13px] max-xs:text-xs font-medium font-sans text-text-muted border-b-2 border-transparent -mb-0.5 cursor-pointer transition-colors duration-200 hover:text-primary [&.active]:text-primary [&.active]:border-primary [&.active]:font-semibold';
        $tabBadgeClass = 'shrink-0 text-[11px] font-semibold py-px px-[7px] rounded-full leading-[1.6] bg-page-bg dark:bg-page-bg-dark text-text-muted group-[.active]:bg-primary group-[.active]:text-white';
    @endphp

    {{-- Tab container --}}
    <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden">
        <div class="tab-group flex border-b-2 border-page-bg dark:border-page-bg-dark bg-surface dark:bg-surface-dark px-1 gap-0.5" data-panels="laporan-panels">
            <button class="{{ $tabBtnClass }} {{ request('tab','panel-jadwal') === 'panel-jadwal' ? 'active' : '' }}"
                    data-tab="panel-jadwal">
                <i class="bx bx-calendar-check text-base max-xs:hidden"></i>
                <span class="truncate">Jadwal & Operator</span>
                <span class="{{ $tabBadgeClass }}">{{ $jadwal->total() }}</span>
            </button>
            <button class="{{ $tabBtnClass }} {{ request('tab') === 'panel-peralatan' ? 'active' : '' }}"
                    data-tab="panel-peralatan">
                <i class="bx bx-wrench text-base max-xs:hidden"></i>
                <span class="truncate">Peralatan Digunakan</span>
                <span class="{{ $tabBadgeClass }}">{{ $peralatan->total() }}</span>
            </button>
        </div>

        <div class="p-4" id="laporan-panels">

            {{-- Panel 1: Jadwal & Operator --}}
            <div class="tab-panel [&:not(.active)]:hidden [&.active]:block {{ request('tab','panel-jadwal') === 'panel-jadwal' ? 'active' : '' }}"
                 id="panel-jadwal">
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap"></th>
                                    <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                                    <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Judul Rapat <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                    <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Tanggal <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                    <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Platform</th>
                                    <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jadwal as $i => $j)
                                @php
                                    $uid        = 'prs-'.$j->id_penjadwalan;
                                    $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                                    $dibatalkan = $j->isDibatalkan();
                                @endphp
                                <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark" data-target="{{ $uid }}">
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i></td>
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $jadwal->firstItem() + $i }}</td>
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                        <div class="font-medium">{{ $j->judul_kegiatan }}</div>
                                        <div class="text-xs text-text-muted mt-0.5">
                                            {{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}
                                        </div>
                                    </td>
                                    <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">{{ $j->tanggal->translatedFormat('D, d/m/Y') }}</td>
                                    <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                        @if(str_contains($j->platform,'Online'))
                                            <x-badge variant="badge-info"><i class="bx bx-wifi"></i> Online</x-badge>
                                        @else
                                            <x-badge variant="badge-active"><i class="bx bx-building"></i> Offline</x-badge>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            @if($dibatalkan)
                                                <x-badge variant="badge-danger">Dibatalkan</x-badge>
                                            @elseif($sudahLewat)
                                                <x-badge variant="badge-active">Selesai</x-badge>
                                            @else
                                                <x-badge variant="badge-info">Aktif</x-badge>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                                    <td colspan="6" class="!p-0">
                                        <div class="flex flex-wrap items-start gap-x-8 gap-y-2.5 py-[18px] px-4">
                                            <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                                <i class="bx bx-calendar text-lg text-primary mt-px shrink-0"></i>
                                                <div><label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Tanggal</label><p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->tanggal->translatedFormat('l, d F Y') }}</p></div>
                                            </div>
                                            <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                                <i class="bx bx-desktop text-lg text-primary mt-px shrink-0"></i>
                                                <div><label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Platform</label><p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->platform }}</p></div>
                                            </div>
                                            <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                                <i class="bx bx-note text-lg text-primary mt-px shrink-0"></i>
                                                <div><label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Keterangan</label><p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->keterangan ?? '-' }}</p></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-10 text-text-muted">
                                        <i class="bx bx-clipboard text-4xl block mb-2"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-pagination :paginator="$jadwal" />
                </div>

                {{-- Kartu jadwal - hanya tampil di mobile, tabel di atas tetap dipakai untuk tablet & desktop --}}
                <div class="hidden max-xs:flex flex-col gap-3 mb-4">
                    @forelse($jadwal as $j)
                        @php
                            $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                            $dibatalkan = $j->isDibatalkan();
                        @endphp
                        <div class="bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-3.5 cursor-pointer"
                             data-open-lap-jadwal-modal
                             data-judul="{{ $j->judul_kegiatan }}"
                             data-tanggal-full="{{ $j->tanggal->translatedFormat('l, d F Y') }}"
                             data-platform="{{ $j->platform }}"
                             data-keterangan="{{ $j->keterangan ?? '-' }}"
                             data-status="{{ $dibatalkan ? 'Dibatalkan' : ($sudahLewat ? 'Selesai' : 'Aktif') }}"
                             data-status-variant="{{ $dibatalkan ? 'badge-danger' : ($sudahLewat ? 'badge-active' : 'badge-info') }}">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm text-text dark:text-text-dark">{{ $j->judul_kegiatan }}</div>
                                    <div class="text-xs text-text-muted whitespace-nowrap overflow-hidden text-ellipsis">{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}</div>
                                </div>
                                <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                            </div>
                            <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                                <div class="flex items-center justify-between text-[13px] text-text-muted">
                                    <span>Tanggal</span>
                                    <span>{{ $j->tanggal->translatedFormat('D, d M Y') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[13px] text-text-muted">
                                    <span>Platform</span>
                                    <span>{{ str_contains($j->platform, 'Online') ? 'Online' : 'Offline' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[13px] text-text-muted">
                                    <span>Status</span>
                                    @if($dibatalkan)
                                        <x-badge variant="badge-danger">Dibatalkan</x-badge>
                                    @elseif($sudahLewat)
                                        <x-badge variant="badge-active">Selesai</x-badge>
                                    @else
                                        <x-badge variant="badge-info">Aktif</x-badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-10 text-center text-text-muted">
                            <i class="bx bx-clipboard text-4xl block mb-2"></i>
                            Tidak ada data
                        </div>
                    @endforelse
                </div>
                <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
                    <x-pagination :paginator="$jadwal" />
                </div>
            </div>

            {{-- Panel 2: Peralatan - dikelompokkan per peminjaman, daftar alatnya di dropdown --}}
            <div class="tab-panel [&:not(.active)]:hidden [&.active]:block {{ request('tab') === 'panel-peralatan' ? 'active' : '' }}"
                 id="panel-peralatan">
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap"></th>
                                    <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                                    <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Judul Rapat <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                    <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Peminjam <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                    <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Tanggal Pinjam <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($peralatan as $i => $p)
                                @php $uid = 'prl-'.$p->id_peminjaman; @endphp
                                <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark" data-target="{{ $uid }}">
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i></td>
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $peralatan->firstItem() + $i }}</td>
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                        <div class="font-medium">{{ $p->penjadwalan->judul_kegiatan ?? $p->keperluan }}</div>
                                        <div class="text-xs text-text-muted mt-0.5">
                                            {{ $p->items->count() }} peralatan
                                        </div>
                                    </td>
                                    <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $p->user->nama_user ?? '-' }}</td>
                                    <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">{{ $p->tanggal_pinjam->translatedFormat('D, d/m/Y') }}</td>
                                </tr>
                                <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                                    <td colspan="5" class="!p-0">
                                        <div class="p-4">
                                            <div class="flex items-center justify-between mb-2.5 px-0.5">
                                                <span class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-text dark:text-text-dark"><i class="bx bx-wrench text-primary text-[15px]"></i> Daftar Peralatan</span>
                                                <x-badge variant="badge-info">{{ $p->items->count() }} alat</x-badge>
                                            </div>
                                            <div class="bg-surface dark:bg-surface-dark rounded-[10px] shadow-[0_1px_4px_rgba(0,0,0,0.06)] overflow-hidden">
                                                <table class="w-full border-collapse">
                                                    <thead>
                                                        <tr>
                                                            <th class="pl-5 py-3 px-5 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg/50 dark:bg-page-bg-dark/50 whitespace-nowrap">Nama Alat</th>
                                                            <th class="w-[90px] py-3 px-5 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg/50 dark:bg-page-bg-dark/50 whitespace-nowrap">Jumlah</th>
                                                            <th class="w-[130px] py-3 px-5 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg/50 dark:bg-page-bg-dark/50 whitespace-nowrap">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($p->items as $item)
                                                            <tr class="border-b border-page-bg dark:border-page-bg-dark last:border-b-0 hover:bg-[#f5f8fb]">
                                                                <td class="pl-5 py-4 px-5 text-sm text-text dark:text-text-dark align-middle">
                                                                    <div class="font-medium mb-[3px]">{{ $item->peralatan->nama_peralatan ?? '-' }}</div>
                                                                    <div class="text-xs text-text-muted">{{ $item->peralatan->gedung ?? '-' }}</div>
                                                                </td>
                                                                <td class="py-4 px-5 text-sm text-text dark:text-text-dark align-middle text-center font-semibold">{{ $item->jumlah }}</td>
                                                                <td class="py-4 px-5 text-sm align-middle text-center"><x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-text-muted">
                                        <i class="bx bx-package text-4xl block mb-2"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-pagination :paginator="$peralatan" />
                </div>

                {{-- Kartu peminjaman - hanya tampil di mobile, tabel di atas tetap dipakai untuk tablet & desktop --}}
                <div class="hidden max-xs:flex flex-col gap-3 mb-4">
                    @forelse($peralatan as $p)
                        <div class="bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-3.5 cursor-pointer"
                             data-open-lap-peralatan-modal
                             data-judul="{{ $p->penjadwalan->judul_kegiatan ?? $p->keperluan }}"
                             data-peminjam="{{ $p->user->nama_user ?? '-' }}"
                             data-tanggal-pinjam="{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}"
                             data-badge-variant="{{ $p->badge['class'] }}"
                             data-badge-label="{{ $p->badge['label'] }}"
                             data-items='@json($p->items->map(fn($item) => ["nama" => $item->peralatan->nama_peralatan ?? "-", "gedung" => $item->peralatan->gedung ?? "-", "jumlah" => $item->jumlah]))'>
                            <div class="flex items-center gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm text-text dark:text-text-dark">{{ $p->penjadwalan->judul_kegiatan ?? $p->keperluan }}</div>
                                    <div class="text-xs text-text-muted whitespace-nowrap overflow-hidden text-ellipsis">{{ $p->items->count() }} peralatan</div>
                                </div>
                                <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                            </div>
                            <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                                <div class="flex items-center justify-between text-[13px] text-text-muted">
                                    <span>Peminjam</span>
                                    <span>{{ $p->user->nama_user ?? '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[13px] text-text-muted">
                                    <span>Tanggal Pinjam</span>
                                    <span>{{ $p->tanggal_pinjam->translatedFormat('D, d/m/Y') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[13px] text-text-muted">
                                    <span>Status</span>
                                    <x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-10 text-center text-text-muted">
                            <i class="bx bx-package text-4xl block mb-2"></i>
                            Tidak ada data
                        </div>
                    @endforelse
                </div>
                <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
                    <x-pagination :paginator="$peralatan" />
                </div>
            </div>

        </div>
    </div>

    {{-- Modal detail jadwal, dipakai kartu mobile panel Jadwal & Operator --}}
    <div id="modalLapJadwalDetail"
        class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
        <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
            <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                <h3 id="modalLapJadwalDetailLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">Detail Jadwal</h3>
                <button type="button"
                    class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                    onclick="document.getElementById('modalLapJadwalDetail').classList.remove('open')"><i class="bx bx-x"></i></button>
            </div>
            <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3" id="modalLapJadwalDetailBody"></div>
        </div>
    </div>

    {{-- Modal detail peminjaman peralatan, dipakai kartu mobile panel Peralatan Digunakan --}}
    <div id="modalLapPeralatanDetail"
        class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
        <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
            <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                <h3 id="modalLapPeralatanDetailLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">Detail Peminjaman</h3>
                <button type="button"
                    class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                    onclick="document.getElementById('modalLapPeralatanDetail').classList.remove('open')"><i class="bx bx-x"></i></button>
            </div>
            <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3" id="modalLapPeralatanDetailBody"></div>
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

// ── Modal detail untuk kartu mobile (kedua panel) ───────────────────────────
function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

// Peta warna badge - sumber kebenarannya ada di resources/views/components/badge.blade.php,
// diduplikasi di sini karena badge yang dibangun lewat JS (bukan Blade) tidak bisa memanggil komponen itu langsung.
var badgeColorMap = {
    'badge-active':   'bg-success dark:bg-success-dark text-success-text',
    'badge-warning':  'bg-warning dark:bg-warning-dark text-warning-text',
    'badge-danger':   'bg-danger dark:bg-danger-dark text-danger-text',
    'badge-info':     'bg-primary-50 dark:bg-[#0d2a40] text-primary',
    'badge-purple':   'bg-purple dark:bg-purple-dark text-purple-text',
    'badge-inactive': 'bg-inactive dark:bg-inactive-dark text-inactive-text dark:text-inactive-text-dark'
};
function badgeHtml(variant, label) {
    var color = badgeColorMap[variant] || '';
    return '<span class="inline-flex items-center gap-1 py-[3px] px-2.5 rounded-full text-xs font-medium whitespace-nowrap ' + color + '">' + escapeHtml(label) + '</span>';
}

var detailRowClass = 'flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]';
var detailRowFullClass = 'flex items-start gap-2.5 min-w-0 flex-[2_1_260px]';
var iconClass = 'bx text-lg text-primary mt-px shrink-0';
var labelClass = 'text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5';
var pClass = 'text-text dark:text-text-dark m-0 font-medium text-[13px] break-words';

function bukaModalLapJadwal(card) {
    var d = card.dataset;
    document.getElementById('modalLapJadwalDetailLabel').textContent = d.judul;

    var html = '<div class="flex flex-wrap items-start gap-x-8 gap-y-2.5">' +
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-calendar"></i><div><label class="' + labelClass + '">Tanggal</label><p class="' + pClass + '">' + escapeHtml(d.tanggalFull) + '</p></div></div>' +
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-desktop"></i><div><label class="' + labelClass + '">Platform</label><p class="' + pClass + '">' + escapeHtml(d.platform) + '</p></div></div>' +
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-check-circle"></i><div><label class="' + labelClass + '">Status</label><p class="' + pClass + '">' + badgeHtml(d.statusVariant, d.status) + '</p></div></div>' +
        '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-note"></i><div><label class="' + labelClass + '">Keterangan</label><p class="' + pClass + '">' + escapeHtml(d.keterangan) + '</p></div></div>' +
        '</div>';

    document.getElementById('modalLapJadwalDetailBody').innerHTML = html;
    document.getElementById('modalLapJadwalDetail').classList.add('open');
}

function bukaModalLapPeralatan(card) {
    var d = card.dataset;
    document.getElementById('modalLapPeralatanDetailLabel').textContent = d.judul;

    var items = [];
    try { items = JSON.parse(d.items || '[]'); } catch (e) {}

    var itemsHtml = items.map(function (item) {
        return '<div class="flex items-center justify-between py-2.5 border-b border-page-bg dark:border-page-bg-dark last:border-b-0">' +
            '<div><div class="font-medium text-[13px] text-text dark:text-text-dark">' + escapeHtml(item.nama) + '</div><div class="text-xs text-text-muted">' + escapeHtml(item.gedung) + '</div></div>' +
            '<span class="text-sm font-semibold text-text dark:text-text-dark">x' + escapeHtml(item.jumlah) + '</span>' +
            '</div>';
    }).join('');

    var html = '<div class="flex flex-wrap items-start gap-x-8 gap-y-2.5">' +
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-user"></i><div><label class="' + labelClass + '">Peminjam</label><p class="' + pClass + '">' + escapeHtml(d.peminjam) + '</p></div></div>' +
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-calendar"></i><div><label class="' + labelClass + '">Tanggal Pinjam</label><p class="' + pClass + '">' + escapeHtml(d.tanggalPinjam) + '</p></div></div>' +
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-check-circle"></i><div><label class="' + labelClass + '">Status</label><p class="' + pClass + '">' + badgeHtml(d.badgeVariant, d.badgeLabel) + '</p></div></div>' +
        '</div>' +
        '<div class="pt-3.5 mt-1 border-t border-page-bg dark:border-page-bg-dark">' +
        '<div class="text-[13px] font-semibold text-text dark:text-text-dark mb-1.5"><i class="bx bx-wrench text-primary"></i> Daftar Peralatan</div>' +
        itemsHtml +
        '</div>';

    document.getElementById('modalLapPeralatanDetailBody').innerHTML = html;
    document.getElementById('modalLapPeralatanDetail').classList.add('open');
}

document.querySelectorAll('[data-open-lap-jadwal-modal]').forEach(function (el) {
    el.addEventListener('click', function () { bukaModalLapJadwal(el); });
});
document.querySelectorAll('[data-open-lap-peralatan-modal]').forEach(function (el) {
    el.addEventListener('click', function () { bukaModalLapPeralatan(el); });
});

['modalLapJadwalDetail', 'modalLapPeralatanDetail'].forEach(function (id) {
    var overlay = document.getElementById(id);
    overlay.addEventListener('click', function (e) {
        if (e.target.id === id) overlay.classList.remove('open');
    });
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.getElementById('modalLapJadwalDetail').classList.remove('open');
        document.getElementById('modalLapPeralatanDetail').classList.remove('open');
    }
});
</script>
@endpush
