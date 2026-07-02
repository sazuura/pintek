@extends('layouts.app')
@section('title', 'Peralatan Saya')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Peralatan Saya</h1>
            </div>
        </div>

        <div class="content-toolbar">
            <form method="GET" action="{{ route('admin.peralatan.index') }}" style="display:contents;">
                <div class="toolbar-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="search" placeholder="Cari nama / kode barang..."
                        value="{{ request('search') }}">
                </div>
                <select name="status" class="toolbar-select">
                    <option value="">Semua Status</option>
                    <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="tidak_tersedia" {{ request('status') == 'tidak_tersedia' ? 'selected' : '' }}>Tidak
                        Tersedia
                    </option>
                    <option value="kritis" {{ request('status') == 'kritis' ? 'selected' : '' }}>Hampir Habis</option>
                </select>
                <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.peralatan.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i>
                        Reset</a>
                @endif
                <div class="toolbar-right">
                    <span style="font-size:13px;color:var(--dark-grey);">{{ $peralatan->total() }} peralatan</span>
                </div>
            </form>
        </div>

        @if($peralatan->count())
            <div class="peralatan-grid">
                @foreach($peralatan as $item)
                    <div class="peralatan-card">
                        @if($item->foto)
                            <img src="{{ Storage::url($item->foto) }}" alt="{{ $item->nama_peralatan }}" class="peralatan-card-img">
                        @else
                            <div class="peralatan-card-img-placeholder"><i class="bx bx-package"></i></div>
                        @endif
                        <div class="peralatan-card-body">
                            {{-- Kode barang --}}
                            @if($item->kode_barang)
                                <div style="font-size:11px;color:var(--dark-grey);display:flex;align-items:center;gap:4px;">
                                    <i class="bx bx-barcode"></i> {{ $item->kode_barang }}
                                </div>
                            @endif

                            <div class="peralatan-card-name">{{ $item->nama_peralatan }}</div>

                            @if($item->lokasi_detail)
                                <div class="peralatan-card-gedung">
                                    <i class="bx bx-map-pin"></i> {{ $item->lokasi_detail }}
                                </div>
                            @endif

                            {{-- Stok breakdown --}}
                            <div style="font-size:12px;color:var(--dark-grey);display:flex;flex-direction:column;gap:3px;">
                                <div style="display:flex;justify-content:space-between;">
                                    <span>Total stok</span>
                                    <span style="font-weight:600;color:var(--dark);">{{ $item->stok }}</span>
                                </div>
                                @if($item->rusak > 0)
                                    <div style="display:flex;justify-content:space-between;">
                                        <span>Rusak</span>
                                        <span style="color:#e74c3c;">{{ $item->rusak }}</span>
                                    </div>
                                @endif
                                @if($item->perbaikan > 0)
                                    <div style="display:flex;justify-content:space-between;">
                                        <span>Perbaikan</span>
                                        <span style="color:#f39c12;">{{ $item->perbaikan }}</span>
                                    </div>
                                @endif
                                <div
                                    style="display:flex;justify-content:space-between;border-top:1px solid var(--grey);padding-top:3px;margin-top:2px;">
                                    <span style="font-weight:500;">Tersedia</span>
                                    <span
                                        style="font-weight:700;font-size:15px;color:var(--dark);">{{ $item->stok_tersedia }}</span>
                                </div>
                            </div>

                            <span class="badge {{ $item->statusBadgeClass }}">{{ $item->statusLabel }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:20px;" class="data-table-wrap">
                <div class="pagination-wrap">
                    <span>Menampilkan {{ $peralatan->firstItem() }}–{{ $peralatan->lastItem() }} dari {{ $peralatan->total() }}
                        peralatan</span>
                    <div class="pagination-links">
                        @if($peralatan->onFirstPage())
                            <span class="page-link disabled"><i class="bx bx-chevron-left"></i></span>
                        @else
                            <a href="{{ $peralatan->previousPageUrl() }}" class="page-link"><i class="bx bx-chevron-left"></i></a>
                        @endif
                        @foreach(range(1, $peralatan->lastPage()) as $p)
                            <a href="{{ $peralatan->url($p) }}"
                                class="page-link {{ $peralatan->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
                        @endforeach
                        @if($peralatan->hasMorePages())
                            <a href="{{ $peralatan->nextPageUrl() }}" class="page-link"><i class="bx bx-chevron-right"></i></a>
                        @else
                            <span class="page-link disabled"><i class="bx bx-chevron-right"></i></span>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="data-table-wrap" style="padding:60px;text-align:center;color:var(--dark-grey);">
                <i class="bx bx-package" style="font-size:48px;display:block;margin-bottom:12px;"></i>
                <p>Tidak ada peralatan terdaftar untuk {{ $gedung }}</p>
            </div>
        @endif
    </main>
@endsection