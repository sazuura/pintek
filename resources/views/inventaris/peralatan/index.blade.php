@extends('layouts.app')
@section('title', 'Data Peralatan')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Data Peralatan</h1>
            </div>
            <a href="{{ route('inventaris.peralatan.create') }}" class="btn-download">
                <i class="bx bx-plus"></i><span class="text">Tambah Peralatan</span>
            </a>
        </div>

        <div class="content-toolbar">
            <form method="GET" action="{{ route('inventaris.peralatan.index') }}" style="display:contents;">
                <div class="toolbar-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="search" placeholder="Cari nama / kode barang..."
                        value="{{ request('search') }}">
                </div>
                {{-- <select name="gedung" class="toolbar-select">
                    <option value="">Semua Gedung</option>
                    @foreach($gedungList as $g)
                    <option value="{{ $g }}" {{ request('gedung')==$g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select> --}}
                <select name="status" class="toolbar-select">
                    <option value="">Semua Status</option>
                    <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="tidak_tersedia" {{ request('status') == 'tidak_tersedia' ? 'selected' : '' }}>Tidak
                        Tersedia
                    </option>
                </select>
                <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
                @if(request()->hasAny(['search', 'gedung', 'status']))
                    <a href="{{ route('inventaris.peralatan.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i>
                        Reset</a>
                @endif
            </form>
            <div class="toolbar-right">
                <span style="font-size:13px;color:var(--dark-grey);">{{ $peralatan->total() }} peralatan</span>
            </div>
        </div>

        {{-- Grid marketplace --}}
        @if($peralatan->count())
            <div class="peralatan-grid">
                @foreach($peralatan as $item)
                    <div class="peralatan-card">
                        {{-- Foto --}}
                        @if($item->foto)
                            <img src="{{ Storage::url($item->foto) }}" alt="{{ $item->nama_peralatan }}" class="peralatan-card-img">
                        @else
                            <div class="peralatan-card-img-placeholder">
                                <i class="bx bx-package"></i>
                            </div>
                        @endif

                        <div class="peralatan-card-body">
                            <div class="peralatan-card-name">{{ $item->nama_peralatan }}</div>

                            @if($item->kode_barang)
                                <div class="peralatan-card-gedung" style="font-size:11px;color:var(--dark-grey);">
                                    <i class="bx bx-barcode"></i> {{ $item->kode_barang }}
                                </div>
                            @endif

                            <div class="peralatan-card-gedung">
                                <i class="bx bx-building"></i> {{ $item->lokasi_detail }}
                            </div>

                            <div class="peralatan-card-stok">
                                <span style="font-size:13px;color:var(--dark-grey);">Stok tersedia</span>
                                <span style="font-weight:700;font-size:18px;color:var(--dark);">{{ $item->stok_tersedia }}</span>
                            </div>

                            <span class="badge {{ $item->statusBadgeClass }}">{{ $item->statusLabel }}</span>
                        </div>

                        <div class="peralatan-card-footer">
                            <a href="{{ route('inventaris.peralatan.edit', $item->id_peralatan) }}" class="toolbar-btn neutral"
                                style="flex:1;justify-content:center;font-size:13px;">
                                <i class="bx bx-edit"></i> Edit
                            </a>
                            <form action="{{ route('inventaris.peralatan.destroy', $item->id_peralatan) }}" method="POST"
                                style="flex:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon delete"
                                    onclick="return confirm('Hapus {{ $item->nama_peralatan }}?')" title="Hapus">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination untuk grid --}}
            <div style="margin-top:20px;" class="data-table-wrap">
                <x-pagination :paginator="$peralatan" label="peralatan" />
            </div>

        @else
            <div class="data-table-wrap" style="padding:60px;text-align:center;color:var(--dark-grey);">
                <i class="bx bx-package" style="font-size:48px;display:block;margin-bottom:12px;"></i>
                <p>Belum ada peralatan</p>
                <a href="{{ route('inventaris.peralatan.create') }}" class="toolbar-btn primary"
                    style="margin-top:12px;display:inline-flex;">
                    <i class="bx bx-plus"></i> Tambah Sekarang
                </a>
            </div>
        @endif
    </main>
@endsection