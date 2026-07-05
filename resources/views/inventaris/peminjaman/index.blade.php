@extends('layouts.app')
@section('title', 'Kelola Peminjaman')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Kelola Peminjaman</h1>
                <div class="flex items-center gap-1.5 mt-1 text-[13px] text-text-muted">
                    <i class="bx bx-building"></i> Semua Lokasi
                </div>
            </div>
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('inventaris.peminjaman.index') }}" class="contents">
                <select name="status"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Menunggu</option>
                    <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="dikembalikan" {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>Dikembalikan
                    </option>
                </select>
                <select name="id_user"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Operator</option>
                    @foreach($operatorList as $op)
                        <option value="{{ $op->id_user }}" {{ request('id_user') == $op->id_user ? 'selected' : '' }}>
                            {{ $op->nama_user }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-primary text-white">
                    <i class="bx bx-filter"></i> Filter</button>
                @if(request('status') || request('id_user'))
                    <a href="{{ route('inventaris.peminjaman.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
        </div>

        @php
            $actionClass = 'w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80';
        @endphp

        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden">
            <div class="py-4 px-5 flex items-center justify-between border-b border-page-bg dark:border-page-bg-dark">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark">Daftar Pengajuan</h3>
                <small class="text-text-muted">Semua Peralatan</small>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                            <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Pemohon <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Keperluan</th>
                            <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Tgl Pinjam <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Peralatan</th>
                            <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                            <th class="w-[100px] py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peminjaman as $index => $p)
                            @php $uid = 'inv-pm-' . $p->id_peminjaman; @endphp
                            <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark" data-target="{{ $uid }}">
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $peminjaman->firstItem() + $index }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="font-medium">{{ $p->user->nama_user }}</div>
                                    <div class="text-xs text-text-muted">{{ $p->user->nohp ?? '-' }}</div>
                                </td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $p->keperluan }}</td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $p->tanggal_pinjam->format('d/m/Y') }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="flex flex-col gap-[3px]">
                                        @foreach($p->items->filter(fn($i) => $i->peralatan->gedung) as $item)
                                            <span class="text-xs">{{ $item->peralatan->nama_peralatan }}
                                                x{{ $item->jumlah }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="flex items-center gap-1.5">
                                        <x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge>
                                        <i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="flex gap-1.5 items-center">
                                        @if($p->isMenunggu())
                                            <form action="{{ route('inventaris.peminjaman.approve', $p->id_peminjaman) }}"
                                                method="POST" class="contents">
                                                @csrf
                                                <button type="submit" title="Setujui"
                                                    class="{{ $actionClass }} bg-success dark:bg-success-dark text-success-text"
                                                    onclick="return confirm('Setujui pengajuan ini?')">
                                                    <i class="bx bx-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" title="Tolak"
                                                class="{{ $actionClass }} bg-danger dark:bg-danger-dark text-danger-text"
                                                onclick="toggleTolak('tolak-{{ $p->id_peminjaman }}')">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        @elseif($p->isDisetujui())
                                            <form action="{{ route('inventaris.peminjaman.kembali', $p->id_peminjaman) }}"
                                                method="POST" class="contents">
                                                @csrf
                                                <button type="submit" title="Konfirmasi Kembali"
                                                    class="{{ $actionClass }} bg-primary-50 dark:bg-[#0d2a40] text-primary"
                                                    onclick="return confirm('Konfirmasi peralatan sudah dikembalikan?')">
                                                    <i class="bx bx-revision"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Accordion detail --}}
                            <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                                <td colspan="7" class="!p-0">
                                    <div class="p-4 grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-2.5 text-[13px]">
                                        <div>
                                            <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Pemohon</label>
                                            <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->user->nama_user }} · {{ $p->user->nohp ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Keperluan</label>
                                            <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->keperluan }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Tanggal Pinjam</label>
                                            <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Rencana Kembali</label>
                                            <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->tanggal_kembali_rencana->translatedFormat('l, d F Y') }}</p>
                                        </div>
                                        @if($p->tanggal_kembali_aktual)
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Dikembalikan</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->tanggal_kembali_aktual->translatedFormat('l, d F Y') }}</p>
                                            </div>
                                        @endif
                                        <div>
                                            <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Catatan</label>
                                            <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->catatan_inventaris ?? '-' }}</p>
                                        </div>
                                        @if($p->penjadwalan)
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Terkait Jadwal</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->penjadwalan->judul_kegiatan }} ({{ $p->penjadwalan->tanggal->format('d/m/Y') }})</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Form tolak inline --}}
                                    <div id="tolak-{{ $p->id_peminjaman }}"
                                        class="hidden py-3 px-4 border-t border-page-bg dark:border-page-bg-dark bg-page-bg dark:bg-page-bg-dark">
                                        <form action="{{ route('inventaris.peminjaman.reject', $p->id_peminjaman) }}"
                                            method="POST" class="flex gap-2 items-center flex-wrap">
                                            @csrf
                                            <input type="text" name="catatan_inventaris"
                                                class="flex-1 min-w-[200px] h-9 px-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans focus:border-primary focus:outline-none"
                                                placeholder="Alasan penolakan (wajib)" required>
                                            <button type="submit"
                                                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white">
                                                <i class="bx bx-x"></i> Tolak
                                            </button>
                                            <button type="button"
                                                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark"
                                                onclick="toggleTolak('tolak-{{ $p->id_peminjaman }}')">Batal</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10 text-text-muted">
                                    <i class="bx bx-cart-alt text-4xl block mb-2"></i>
                                    Tidak ada pengajuan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$peminjaman">{{ $peminjaman->total() }} total pengajuan</x-pagination>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        function toggleTolak(id) {
            var el = document.getElementById(id);
            el.classList.toggle('hidden');
        }
    </script>
@endpush
