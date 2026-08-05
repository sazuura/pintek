@extends('layouts.app')
@section('title', 'Laporan Peralatan')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
<main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
        <div><h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Laporan Peralatan</h1></div>
    </div>

    <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)] max-md:flex-col max-md:items-stretch">
        <form method="GET" action="{{ route('inventaris.laporan.index') }}" id="filter-form" class="contents">

            <input type="hidden" name="tab" id="active-tab-input" value="{{ request('tab', 'panel-stok') }}">

            <div class="relative flex-1 min-w-[180px] max-w-[300px] max-md:max-w-full">
                <i class="bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
                <input type="text" name="search" data-live-search="#laporan-panels" autocomplete="off" placeholder="Cari nama alat..." value="{{ request('search') }}"
                    class="w-full h-9 pl-[34px] pr-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans transition-colors duration-200 focus:border-primary focus:outline-none focus:bg-surface dark:focus:bg-surface-dark">
            </div>

            <label class="text-[13px] text-text-muted whitespace-nowrap">Dari</label>
            <input type="date" name="start" value="{{ request('start') }}" onchange="this.form.submit()"
                class="max-md:w-full h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
            <label class="text-[13px] text-text-muted whitespace-nowrap">s/d</label>
            <input type="date" name="end" value="{{ request('end') }}" onchange="this.form.submit()"
                class="max-md:w-full h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">

            <select name="gedung" onchange="this.form.submit()"
                class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                <option value="">Semua Gedung</option>
                @foreach($gedungList as $g)
                    <option value="{{ $g }}" {{ request('gedung') == $g ? 'selected' : '' }}>{{ $g }}</option>
                @endforeach
            </select>

            <select name="kondisi" onchange="this.form.submit()"
                class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                <option value="">Semua Kondisi</option>
                <option value="baik" {{ request('kondisi') == 'baik' ? 'selected' : '' }}>Baik</option>
                <option value="rusak" {{ request('kondisi') == 'rusak' ? 'selected' : '' }}>Rusak</option>
            </select>

            <select name="status" onchange="this.form.submit()"
                class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                <option value="">Semua Status</option>
                <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                <option value="kritis" {{ request('status') == 'kritis' ? 'selected' : '' }}>Hampir Habis</option>
                <option value="tidak_tersedia" {{ request('status') == 'tidak_tersedia' ? 'selected' : '' }}>Tidak Tersedia</option>
            </select>

            @if(request()->hasAny(['search','gedung','kondisi','status','start','end']))
                <a href="{{ route('inventaris.laporan.index', ['tab' => request('tab','panel-stok')]) }}"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                    <i class="bx bx-x"></i> Reset</a>
            @endif

            <div class="ml-auto flex gap-2 items-center max-md:ml-0 max-md:w-full">

                <a id="btn-pdf" target="_blank" rel="noopener" href="{{ route('inventaris.laporan.exportPdf', array_merge(request()->except(['stok_page','terpasang_page','peminjaman_page']), ['tab' => request('tab','panel-stok')])) }}"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-danger-text text-white">
                    <i class="bx bxs-file-pdf"></i> PDF
                </a>
                <a id="btn-excel" href="{{ route('inventaris.laporan.exportExcel', array_merge(request()->except(['stok_page','terpasang_page','peminjaman_page']), ['tab' => request('tab','panel-stok')])) }}"
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

    <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden">
        <div class="tab-group flex border-b-2 border-page-bg dark:border-page-bg-dark bg-surface dark:bg-surface-dark px-1 gap-0.5" data-panels="laporan-panels">
            <button class="{{ $tabBtnClass }} {{ request('tab','panel-stok') === 'panel-stok' ? 'active' : '' }}"
                    data-tab="panel-stok">
                <i class="bx bx-package text-base max-xs:hidden"></i>
                <span class="truncate">Stok Peralatan</span>
                <span class="{{ $tabBadgeClass }}">{{ $stok->total() }}</span>
            </button>
            <button class="{{ $tabBtnClass }} {{ request('tab') === 'panel-peminjaman' ? 'active' : '' }}"
                    data-tab="panel-peminjaman">
                <i class="bx bx-history text-base max-xs:hidden"></i>
                <span class="truncate">Riwayat Peminjaman</span>
                <span class="{{ $tabBadgeClass }}">{{ $peminjaman->total() }}</span>
            </button>
        </div>

        <div class="p-4" id="laporan-panels" data-skel>

            <div class="tab-panel [&:not(.active)]:hidden [&.active]:block {{ request('tab','panel-stok') === 'panel-stok' ? 'active' : '' }}"
                 id="panel-stok">
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                                    <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Nama Alat <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                    <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Lokasi</th>
                                    <th class="w-[90px] group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Stok <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                    <th class="w-[90px] py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Rusak</th>
                                    <th class="w-[90px] py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Tersedia</th>
                                    <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stok as $i => $p)
                                    <tr class="border-b border-page-bg dark:border-page-bg-dark last:border-b-0 hover:bg-page-bg dark:hover:bg-page-bg-dark">
                                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $stok->firstItem() + $i }}</td>
                                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                            <div class="font-medium">{{ $p->nama_peralatan }}</div>
                                            @if($p->kode_barang)
                                                <div class="text-xs text-text-muted">{{ $p->kode_barang }}</div>
                                            @endif
                                        </td>
                                        <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $p->gedung }}</td>
                                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center font-semibold">{{ $p->stok }}</td>
                                        <td class="py-3.5 px-4 text-sm align-middle text-center {{ $p->rusak > 0 ? 'text-[#e74c3c] font-semibold' : 'text-text-muted' }}">{{ $p->rusak ?? 0 }}</td>
                                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center font-semibold">{{ $p->stok_tersedia }}</td>
                                        <td class="py-3.5 px-4 align-middle text-center"><x-badge :variant="$p->statusBadgeClass">{{ $p->statusLabel }}</x-badge></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-10 text-text-muted">
                                            <i class="bx bx-package text-4xl block mb-2"></i>
                                            Tidak ada data
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-pagination :paginator="$stok" />
                </div>

                <div class="hidden max-xs:flex flex-col gap-3 mb-4">
                    @forelse($stok as $p)
                        <div class="bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-semibold text-sm text-text dark:text-text-dark truncate">{{ $p->nama_peralatan }}</div>
                                    <div class="text-xs text-text-muted">{{ $p->kode_barang ?? '-' }} &middot; {{ $p->gedung }}</div>
                                </div>
                                <x-badge :variant="$p->statusBadgeClass">{{ $p->statusLabel }}</x-badge>
                            </div>
                            <div class="flex items-center justify-between text-[13px] text-text-muted pt-2.5 border-t border-page-bg dark:border-page-bg-dark">
                                <span>Stok: <strong class="text-text dark:text-text-dark">{{ $p->stok }}</strong></span>
                                <span>Rusak: <strong class="{{ $p->rusak > 0 ? 'text-[#e74c3c]' : 'text-text dark:text-text-dark' }}">{{ $p->rusak ?? 0 }}</strong></span>
                                <span>Tersedia: <strong class="text-text dark:text-text-dark">{{ $p->stok_tersedia }}</strong></span>
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
                    <x-pagination :paginator="$stok" />
                </div>
            </div>

            <div class="tab-panel [&:not(.active)]:hidden [&.active]:block {{ request('tab') === 'panel-peminjaman' ? 'active' : '' }}"
                 id="panel-peminjaman">
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap"></th>
                                    <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                                    <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Judul / Keperluan <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                    <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Peminjam</th>
                                    <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Tgl Pinjam <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                                    <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($peminjaman as $i => $p)
                                    @php $uid = 'lap-pm-' . $p->id_peminjaman; @endphp
                                    <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark" data-target="{{ $uid }}">
                                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i></td>
                                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $peminjaman->firstItem() + $i }}</td>
                                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                            <div class="font-medium">{{ $p->penjadwalan->judul_kegiatan ?? $p->keperluan }}</div>
                                            <div class="text-xs text-text-muted mt-0.5">{{ $p->items->count() }} peralatan</div>
                                        </td>
                                        <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $p->user->nama_user ?? '-' }}</td>
                                        <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}</td>
                                        <td class="py-3.5 px-4 align-middle text-center"><x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge></td>
                                    </tr>
                                    <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                                        <td colspan="6" class="!p-0">
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
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($p->items as $item)
                                                                <tr class="border-b border-page-bg dark:border-page-bg-dark last:border-b-0 hover:bg-page-bg dark:hover:bg-page-bg-dark">
                                                                    <td class="pl-5 py-4 px-5 text-sm text-text dark:text-text-dark align-middle">
                                                                        <div class="flex items-center gap-2">
                                                                            <i class="bx bx-package text-primary text-base"></i>
                                                                            <div>
                                                                                <div class="font-medium mb-[3px]">{{ $item->peralatan->nama_peralatan ?? '-' }}</div>
                                                                                <div class="text-xs text-text-muted">{{ $item->peralatan->gedung ?? '-' }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td class="py-4 px-5 text-sm text-text dark:text-text-dark align-middle text-center font-semibold">{{ $item->jumlah }}</td>
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
                                        <td colspan="6" class="text-center py-10 text-text-muted">
                                            <i class="bx bx-history text-4xl block mb-2"></i>
                                            Tidak ada data
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-pagination :paginator="$peminjaman" />
                </div>

                <div class="hidden max-xs:flex flex-col gap-3 mb-4">
                    @forelse($peminjaman as $p)
                        <div class="bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-semibold text-sm text-text dark:text-text-dark truncate">{{ $p->penjadwalan->judul_kegiatan ?? $p->keperluan }}</div>
                                    <div class="text-xs text-text-muted">{{ $p->user->nama_user ?? '-' }} &middot; {{ $p->items->count() }} peralatan</div>
                                </div>
                                <x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge>
                            </div>
                            <div class="flex items-center justify-between text-[13px] text-text-muted pt-2.5 border-t border-page-bg dark:border-page-bg-dark">
                                <span>Tanggal Pinjam</span>
                                <span class="text-text dark:text-text-dark">{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-10 text-center text-text-muted">
                            <i class="bx bx-history text-4xl block mb-2"></i>
                            Tidak ada data
                        </div>
                    @endforelse
                </div>
                <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
                    <x-pagination :paginator="$peminjaman" />
                </div>
            </div>

        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
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
