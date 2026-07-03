@extends('layouts.app')
@section('title', 'Daftar Peralatan')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Daftar Peralatan</h1>
            </div>
        </div>

        <div class="content-toolbar">
            <form method="GET" action="{{ route('operator.peralatan.index') }}" style="display:contents;">
                <div class="toolbar-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="search" placeholder="Cari nama peralatan..." value="{{ request('search') }}">
                </div>
                <select name="gedung" class="toolbar-select">
                    <option value="">Semua Gedung</option>
                    @foreach($gedungList as $g)
                        <option value="{{ $g }}" {{ request('gedung') == $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
                <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
                @if(request()->hasAny(['search', 'gedung']))
                    <a href="{{ route('operator.peralatan.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i>
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
                            <div class="peralatan-card-name">{{ $item->nama_peralatan }}</div>
                            <div class="peralatan-card-gedung">
                                <i class="bx bx-building"></i> {{ $item->gedung }}
                            </div>
                            @if($item->lokasi_detail)
                                <div class="peralatan-card-gedung" style="font-size:12px;">
                                    <i class="bx bx-map-pin"></i> {{ $item->lokasi_detail }}
                                </div>
                            @endif
                            <div class="peralatan-card-stok">
                                <span style="font-size:13px;color:var(--dark-grey);">Stok tersedia</span>
                                <span style="font-weight:700;font-size:18px;color:var(--dark);">{{ $item->stok_tersedia }}</span>
                            </div>
                            <span class="badge {{ $item->statusBadgeClass }}">{{ $item->statusLabel }}</span>
                        </div>
                        {{-- Footer: tombol pinjam shortcut --}}
                        <div class="peralatan-card-footer">
                            @if($item->stok_tersedia > 0)
                                <a href="{{ route('operator.peminjaman.create', ['id_peralatan' => $item->id_peralatan]) }}"
                                    class="toolbar-btn primary" style="flex:1;justify-content:center;font-size:13px;">
                                    <i class="bx bx-cart-add"></i> Pinjam
                                </a>
                            @else
                                <span class="toolbar-btn neutral"
                                    style="flex:1;justify-content:center;font-size:13px;cursor:not-allowed;opacity:.5;">
                                    Stok Habis
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:20px;" class="data-table-wrap">
                <x-pagination :paginator="$peralatan" label="peralatan" />
            </div>
        @else
            <div class="data-table-wrap" style="padding:60px;text-align:center;color:var(--dark-grey);">
                <i class="bx bx-package" style="font-size:48px;display:block;margin-bottom:12px;"></i>
                <p>Tidak ada peralatan ditemukan</p>
            </div>
        @endif
    </main>
@endsection