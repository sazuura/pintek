@extends('layouts.app')
@section('title', 'Daftar Peralatan')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Daftar Peralatan</h1>
            </div>
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)] max-md:flex-col max-md:items-stretch">
            <form method="GET" action="{{ route('operator.peralatan.index') }}" class="contents">
                <div class="relative flex-1 min-w-[180px] max-w-[300px] max-md:max-w-full">
                    <i class="bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
                    <input type="text" name="search" placeholder="Cari nama peralatan..." value="{{ request('search') }}"
                        class="w-full h-9 pl-[34px] pr-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans transition-colors duration-200 focus:border-primary focus:outline-none focus:bg-surface dark:focus:bg-surface-dark">
                </div>
                <select name="gedung"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Gedung</option>
                    @foreach($gedungList as $g)
                        <option value="{{ $g }}" {{ request('gedung') == $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-primary text-white">
                    <i class="bx bx-filter"></i> Filter</button>
                @if(request()->hasAny(['search', 'gedung']))
                    <a href="{{ route('operator.peralatan.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
                <div class="ml-auto flex gap-2 items-center max-md:ml-0 max-md:w-full">
                    <span class="text-[13px] text-text-muted">{{ $peralatan->total() }} peralatan</span>
                </div>
            </form>
        </div>

        @if($peralatan->count())
            <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] max-md:grid-cols-2 max-xs:grid-cols-1 gap-4">
                @foreach($peralatan as $item)
                    <div class="bg-surface dark:bg-surface-dark rounded-xl overflow-hidden shadow-card transition-[transform,box-shadow] duration-200 flex flex-col hover:-translate-y-[3px] hover:shadow-[0_6px_20px_rgba(0,0,0,0.10)]">
                        @if($item->foto)
                            <img src="{{ Storage::url($item->foto) }}" alt="{{ $item->nama_peralatan }}" class="w-full aspect-[4/3] object-cover bg-page-bg dark:bg-page-bg-dark">
                        @else
                            <div class="w-full aspect-[4/3] bg-page-bg dark:bg-page-bg-dark flex items-center justify-center text-text-muted text-4xl"><i class="bx bx-package"></i></div>
                        @endif
                        <div class="p-3.5 flex flex-col gap-2 flex-1">
                            <div class="text-sm font-semibold text-text dark:text-text-dark leading-tight">{{ $item->nama_peralatan }}</div>
                            <div class="text-xs text-text-muted flex items-center gap-1">
                                <i class="bx bx-building"></i> {{ $item->gedung }}
                            </div>
                            @if($item->lokasi_detail)
                                <div class="text-xs text-text-muted flex items-center gap-1">
                                    <i class="bx bx-map-pin"></i> {{ $item->lokasi_detail }}
                                </div>
                            @endif
                            <div class="text-[13px] text-text dark:text-text-dark flex items-center justify-between">
                                <span class="text-[13px] text-text-muted">Stok tersedia</span>
                                <span class="font-bold text-lg text-text dark:text-text-dark">{{ $item->stok_tersedia }}</span>
                            </div>
                            <x-badge :variant="$item->statusBadgeClass">{{ $item->statusLabel }}</x-badge>
                        </div>
                        {{-- Footer: tombol pinjam shortcut --}}
                        <div class="py-2.5 px-3.5 border-t border-page-bg dark:border-page-bg-dark flex gap-1.5">
                            @if($item->stok_tersedia > 0)
                                <a href="{{ route('operator.peminjaman.create', ['id_peralatan' => $item->id_peralatan]) }}"
                                    class="flex-1 justify-center h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-primary text-white">
                                    <i class="bx bx-cart-add"></i> Pinjam
                                </a>
                            @else
                                <span class="flex-1 justify-center h-9 px-3.5 rounded-lg text-[13px] font-sans inline-flex items-center gap-1.5 font-medium cursor-not-allowed opacity-50 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                                    Stok Habis
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 bg-surface dark:bg-surface-dark rounded-xl shadow-card">
                <x-pagination :paginator="$peralatan" label="peralatan" />
            </div>
        @else
            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-[60px] text-center text-text-muted">
                <i class="bx bx-package text-5xl block mb-3"></i>
                <p>Tidak ada peralatan ditemukan</p>
            </div>
        @endif
    </main>
@endsection
