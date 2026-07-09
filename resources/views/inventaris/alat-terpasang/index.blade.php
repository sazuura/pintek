@extends('layouts.app')
@section('title', 'Alat Terpasang')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Alat Terpasang</h1>
                <p class="text-sm text-text-muted m-0">Alat yang terpasang permanen di ruangan/gedung (di luar alat yang dipinjam untuk rapat).</p>
            </div>
            <a href="{{ route('inventaris.alat-terpasang.create') }}"
                class="h-9 px-4 rounded-full bg-primary text-surface dark:text-surface-dark flex justify-center items-center gap-2.5 font-medium">
                <i class="bx bx-plus"></i><span class="text">Tambah Alat</span>
            </a>
        </div>

        @if($ringkasan->isNotEmpty())
            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-4 mb-4">
                <h3 class="text-[13px] font-semibold text-text dark:text-text-dark mb-3 flex items-center gap-1.5">
                    <i class="bx bx-bar-chart-alt-2"></i> Ringkasan Penempatan per Alat
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-[13px] border-collapse">
                        <thead>
                            <tr class="text-left text-text-muted border-b border-page-bg dark:border-page-bg-dark">
                                <th class="py-1.5 pr-3 font-medium">Nama Alat</th>
                                <th class="py-1.5 px-3 font-medium text-right">Terpasang</th>
                                <th class="py-1.5 px-3 font-medium text-right">Tersimpan (Gudang)</th>
                                <th class="py-1.5 pl-3 font-medium text-right">Total Tersedia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ringkasan as $r)
                                <tr class="border-b border-page-bg dark:border-page-bg-dark last:border-0">
                                    <td class="py-1.5 pr-3 text-text dark:text-text-dark">{{ $r['nama_peralatan'] }}</td>
                                    <td class="py-1.5 px-3 text-right font-semibold text-primary">{{ $r['jumlah_terpasang'] }}</td>
                                    <td class="py-1.5 px-3 text-right text-text dark:text-text-dark">{{ $r['jumlah_tersimpan'] }}</td>
                                    <td class="py-1.5 pl-3 text-right text-text-muted">{{ $r['stok_tersedia'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)] max-md:flex-col max-md:items-stretch">
            <form method="GET" action="{{ route('inventaris.alat-terpasang.index') }}" class="contents">
                <div class="relative flex-1 min-w-[180px] max-w-[300px] max-md:max-w-full">
                    <i class="bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
                    <input type="text" name="search" data-live-search="#hasil-alat-terpasang" autocomplete="off" placeholder="Cari nama alat / gedung..." value="{{ request('search') }}"
                        class="w-full h-9 pl-[34px] pr-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans transition-colors duration-200 focus:border-primary focus:outline-none focus:bg-surface dark:focus:bg-surface-dark">
                </div>
                @if(request()->hasAny(['search']))
                    <a href="{{ route('inventaris.alat-terpasang.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
            <div class="ml-auto flex gap-2 items-center max-md:ml-0 max-md:w-full">
                <span class="text-[13px] text-text-muted">{{ $alat->total() }} alat</span>
            </div>
        </div>

        <div id="hasil-alat-terpasang">
        @if($alat->count())
            <div class="grid grid-cols-5 max-md:grid-cols-2 max-xs:!grid-cols-1 gap-4">
                @foreach($alat as $item)
                    <div class="bg-surface dark:bg-surface-dark rounded-xl overflow-hidden shadow-card transition-[transform,box-shadow] duration-200 flex flex-col hover:-translate-y-[3px] hover:shadow-[0_6px_20px_rgba(0,0,0,0.10)]">
                        @if($item->foto)
                            <img src="{{ $item->foto_url }}" alt="{{ $item->nama_alat }}" class="w-full aspect-[4/3] object-cover bg-page-bg dark:bg-page-bg-dark">
                        @else
                            <div class="w-full aspect-[4/3] bg-page-bg dark:bg-page-bg-dark flex items-center justify-center text-text-muted text-4xl">
                                <i class="bx bx-tv"></i>
                            </div>
                        @endif

                        <div class="p-3.5 flex flex-col gap-2 flex-1">
                            <div class="text-sm font-semibold text-text dark:text-text-dark leading-tight">{{ $item->nama_alat }}</div>

                            <div class="text-xs text-text-muted flex items-center gap-1">
                                <i class="bx bx-building"></i> {{ $item->gedung }}{{ $item->lokasi_detail ? ' - ' . $item->lokasi_detail : '' }}
                            </div>

                            <div class="text-[13px] text-text dark:text-text-dark flex items-center justify-between">
                                <span class="text-[13px] text-text-muted">Tanggal pasang</span>
                                <span class="font-medium text-text dark:text-text-dark">{{ $item->tanggal_pasang->translatedFormat('d M Y') }}</span>
                            </div>

                            <x-badge :variant="$item->kondisiBadgeClass">{{ $item->kondisiLabel }}</x-badge>
                        </div>

                        <div class="py-2.5 px-3.5 border-t border-page-bg dark:border-page-bg-dark flex gap-1.5">
                            <a href="{{ route('inventaris.alat-terpasang.edit', $item->id_alat_terpasang) }}"
                                class="flex-1 justify-center h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                                <i class="bx bx-edit"></i> Kelola
                            </a>
                            <form action="{{ route('inventaris.alat-terpasang.destroy', $item->id_alat_terpasang) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="w-9 h-9 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 shrink-0 hover:opacity-80 bg-danger dark:bg-danger-dark text-danger-text"
                                    onclick="return confirm('Hapus {{ $item->nama_alat }}?')" title="Hapus">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 bg-surface dark:bg-surface-dark rounded-xl shadow-card">
                <x-pagination :paginator="$alat" label="alat" />
            </div>

        @else
            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-[60px] text-center text-text-muted">
                <i class="bx bx-tv text-5xl block mb-3"></i>
                <p>Belum ada alat terpasang</p>
                <a href="{{ route('inventaris.alat-terpasang.create') }}"
                    class="mt-3 inline-flex h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-primary text-white">
                    <i class="bx bx-plus"></i> Tambah Sekarang
                </a>
            </div>
        @endif
        </div>
    </main>
@endsection
