@extends('layouts.app')
@section('title', 'Peralatan Saya')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Peralatan Saya</h1>
            </div>
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)] max-md:flex-col max-md:items-stretch">
            <form method="GET" action="{{ route('admin.peralatan.index') }}" class="contents">
                <div class="relative flex-1 min-w-[180px] max-w-[300px] max-md:max-w-full">
                    <i class="bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
                    <input type="text" name="search" data-live-search="#hasil-peralatan" autocomplete="off" placeholder="Cari nama / kode barang..." value="{{ request('search') }}"
                        class="w-full h-9 pl-[34px] pr-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans transition-colors duration-200 focus:border-primary focus:outline-none focus:bg-surface dark:focus:bg-surface-dark">
                </div>
                <select name="status" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="tidak_tersedia" {{ request('status') == 'tidak_tersedia' ? 'selected' : '' }}>Tidak
                        Tersedia
                    </option>
                    <option value="kritis" {{ request('status') == 'kritis' ? 'selected' : '' }}>Hampir Habis</option>
                </select>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.peralatan.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
                <div class="ml-auto flex gap-2 items-center max-md:ml-0 max-md:w-full">
                    <span class="text-[13px] text-text-muted">{{ $peralatan->total() }} peralatan</span>
                </div>
            </form>
        </div>

        <div id="hasil-peralatan">
        @if($peralatan->count())
            <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] max-md:grid-cols-2 max-xs:!grid-cols-1 gap-4">
                @foreach($peralatan as $item)
                    <div class="bg-surface dark:bg-surface-dark rounded-xl overflow-hidden shadow-card transition-[transform,box-shadow] duration-200 flex flex-col hover:-translate-y-[3px] hover:shadow-[0_6px_20px_rgba(0,0,0,0.10)]">
                        @if($item->foto)
                            <img src="{{ Storage::url($item->foto) }}" alt="{{ $item->nama_peralatan }}" class="w-full aspect-[4/3] object-cover bg-page-bg dark:bg-page-bg-dark">
                        @else
                            <div class="w-full aspect-[4/3] bg-page-bg dark:bg-page-bg-dark flex items-center justify-center text-text-muted text-4xl"><i class="bx bx-package"></i></div>
                        @endif
                        <div class="p-3.5 flex flex-col gap-2 flex-1">
                            {{-- Kode barang --}}
                            @if($item->kode_barang)
                                <div class="text-[11px] text-text-muted flex items-center gap-1">
                                    <i class="bx bx-barcode"></i> {{ $item->kode_barang }}
                                </div>
                            @endif

                            <div class="text-sm font-semibold text-text dark:text-text-dark leading-tight">{{ $item->nama_peralatan }}</div>

                            @if($item->lokasi_detail)
                                <div class="text-xs text-text-muted flex items-center gap-1">
                                    <i class="bx bx-map-pin"></i> {{ $item->lokasi_detail }}
                                </div>
                            @endif

                            {{-- Stok breakdown --}}
                            <div class="text-xs text-text-muted flex flex-col gap-[3px]">
                                <div class="flex justify-between">
                                    <span>Total stok</span>
                                    <span class="font-semibold text-text dark:text-text-dark">{{ $item->stok }}</span>
                                </div>
                                @if($item->rusak > 0)
                                    <div class="flex justify-between">
                                        <span>Rusak</span>
                                        <span class="text-[#e74c3c]">{{ $item->rusak }}</span>
                                    </div>
                                @endif
                                @if($item->perbaikan > 0)
                                    <div class="flex justify-between">
                                        <span>Perbaikan</span>
                                        <span class="text-[#f39c12]">{{ $item->perbaikan }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between border-t border-page-bg dark:border-page-bg-dark pt-[3px] mt-0.5">
                                    <span class="font-medium">Tersedia</span>
                                    <span class="font-bold text-[15px] text-text dark:text-text-dark">{{ $item->stok_tersedia }}</span>
                                </div>
                            </div>

                            <x-badge :variant="$item->statusBadgeClass">{{ $item->statusLabel }}</x-badge>
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
                <p>Tidak ada peralatan terdaftar untuk {{ $gedung }}</p>
            </div>
        @endif
        </div>
    </main>
@endsection
