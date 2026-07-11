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
                <select name="status" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Menunggu</option>
                    <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="dikembalikan" {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>Dikembalikan
                    </option>
                </select>
                <select name="id_user" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Operator</option>
                    @foreach($operatorList as $op)
                        <option value="{{ $op->id_user }}" {{ request('id_user') == $op->id_user ? 'selected' : '' }}>
                            {{ $op->nama_user }}
                        </option>
                    @endforeach
                </select>
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

        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
            <div class="py-4 px-5 flex items-center justify-between border-b border-page-bg dark:border-page-bg-dark">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark">Daftar Pengajuan</h3>
                <small class="text-text-muted">Semua Peralatan</small>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap"></th>
                            <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                            <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Pemohon <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Keperluan</th>
                            <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Tgl Pinjam <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Rencana Kembali</th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Tgl Kembali Aktual</th>
                            <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                            <th class="w-[100px] py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peminjaman as $index => $p)
                            @php $uid = 'inv-pm-' . $p->id_peminjaman; @endphp
                            <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark" data-target="{{ $uid }}">
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i></td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $peminjaman->firstItem() + $index }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="font-medium">{{ $p->user->nama_user }}</div>
                                    <div class="text-xs text-text-muted">{{ $p->user->nohp ?? '-' }}</div>
                                </td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $p->keperluan }}</td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">{{ $p->tanggal_pinjam->format('d/m/Y') }}</td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">{{ $p->tanggal_kembali_rencana->format('d/m/Y') }}</td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">{{ $p->tanggal_kembali_aktual?->format('d/m/Y') ?? '-' }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                    <x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge>
                                </td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                    <div class="flex gap-1.5 items-center justify-center">
                                        @if($p->isMenunggu())
                                            <button type="button" title="Setujui"
                                                class="{{ $actionClass }} bg-success dark:bg-success-dark text-success-text"
                                                onclick="bukaKonfirmasiSetujui('{{ route('inventaris.peminjaman.approve', $p->id_peminjaman) }}')">
                                                <i class="bx bx-check"></i>
                                            </button>
                                            <button type="button" title="Tolak"
                                                class="{{ $actionClass }} bg-danger dark:bg-danger-dark text-danger-text"
                                                onclick="bukaKonfirmasiTolak('{{ route('inventaris.peminjaman.reject', $p->id_peminjaman) }}')">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        @elseif($p->isDisetujui())
                                            <button type="button" title="Konfirmasi Kembali"
                                                class="{{ $actionClass }} bg-primary-50 dark:bg-[#0d2a40] text-primary"
                                                onclick="bukaKonfirmasiKembali('{{ route('inventaris.peminjaman.kembali', $p->id_peminjaman) }}')">
                                                <i class="bx bx-revision"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Accordion detail --}}
                            <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                                <td colspan="9" class="!p-0">
                                    <div class="p-4 grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3 text-[13px]">
                                        <div class="flex items-start gap-2">
                                            <i class="bx bx-comment-detail text-primary text-base mt-0.5 shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Catatan</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->catatan_inventaris ?? '-' }}</p>
                                            </div>
                                        </div>
                                        @if($p->penjadwalan)
                                            <div class="flex items-start gap-2">
                                                <i class="bx bx-calendar-event text-primary text-base mt-0.5 shrink-0"></i>
                                                <div>
                                                    <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Terkait Jadwal</label>
                                                    <p class="text-text dark:text-text-dark m-0 font-medium">{{ $p->penjadwalan->judul_kegiatan }} ({{ $p->penjadwalan->tanggal->format('d/m/Y') }})</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="px-4 pb-4">
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
                                <td colspan="9" class="text-center py-10 text-text-muted">
                                    <i class="bx bx-briefcase text-4xl block mb-2"></i>
                                    Tidak ada pengajuan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$peminjaman">{{ $peminjaman->total() }} total pengajuan</x-pagination>
        </div>

        {{-- Kartu pengajuan - hanya tampil di mobile, tabel di atas tetap dipakai untuk tablet & desktop --}}
        <div class="hidden max-xs:flex flex-col gap-3 mb-4">
            @forelse($peminjaman as $p)
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden">
                    <div class="p-4 flex flex-col gap-3 cursor-pointer"
                         data-open-pengajuan-modal
                         data-pemohon="{{ $p->user->nama_user }}"
                         data-nohp="{{ $p->user->nohp ?? '-' }}"
                         data-keperluan="{{ $p->keperluan }}"
                         data-catatan="{{ $p->catatan_inventaris ?? '-' }}"
                         data-jadwal="{{ $p->penjadwalan ? $p->penjadwalan->judul_kegiatan . ' (' . $p->penjadwalan->tanggal->format('d/m/Y') . ')' : '' }}"
                         data-badge-variant="{{ $p->badge['class'] }}"
                         data-badge-label="{{ $p->badge['label'] }}"
                         data-items='@json($p->items->map(fn($item) => ["nama" => $item->peralatan->nama_peralatan ?? "-", "gedung" => $item->peralatan->gedung ?? "-", "jumlah" => $item->jumlah]))'>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm text-text dark:text-text-dark">{{ $p->user->nama_user }}</div>
                                <div class="text-xs text-text-muted">{{ $p->user->nohp ?? '-' }}</div>
                            </div>
                            <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                        </div>
                        <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                            <div class="flex items-center justify-between gap-2 text-[13px] text-text-muted">
                                <span>Keperluan</span>
                                <span class="text-text dark:text-text-dark text-right">{{ $p->keperluan }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[13px] text-text-muted">
                                <span>Tanggal Pinjam</span>
                                <span class="text-text dark:text-text-dark">{{ $p->tanggal_pinjam->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-[13px] text-text-muted">
                                <span>Rencana Kembali</span>
                                <span class="text-text dark:text-text-dark">{{ $p->tanggal_kembali_rencana->format('d/m/Y') }}</span>
                            </div>
                            @if($p->tanggal_kembali_aktual)
                                <div class="flex items-center justify-between text-[13px] text-text-muted">
                                    <span>Tgl Kembali Aktual</span>
                                    <span class="text-text dark:text-text-dark">{{ $p->tanggal_kembali_aktual->format('d/m/Y') }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between text-[13px] text-text-muted">
                                <span>Status</span>
                                <x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge>
                            </div>
                        </div>
                    </div>
                    @if($p->isMenunggu() || $p->isDisetujui())
                        <div class="py-2.5 px-3.5 border-t border-page-bg dark:border-page-bg-dark flex gap-1.5">
                            @if($p->isMenunggu())
                                <button type="button"
                                    class="flex-1 justify-center h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-success dark:bg-success-dark text-success-text"
                                    onclick="bukaKonfirmasiSetujui('{{ route('inventaris.peminjaman.approve', $p->id_peminjaman) }}')">
                                    <i class="bx bx-check"></i> Setujui
                                </button>
                                <button type="button"
                                    class="flex-1 justify-center h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger dark:bg-danger-dark text-danger-text"
                                    onclick="bukaKonfirmasiTolak('{{ route('inventaris.peminjaman.reject', $p->id_peminjaman) }}')">
                                    <i class="bx bx-x"></i> Tolak
                                </button>
                            @elseif($p->isDisetujui())
                                <button type="button"
                                    class="flex-1 justify-center h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-primary-50 dark:bg-[#0d2a40] text-primary"
                                    onclick="bukaKonfirmasiKembali('{{ route('inventaris.peminjaman.kembali', $p->id_peminjaman) }}')">
                                    <i class="bx bx-revision"></i> Konfirmasi Kembali
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-10 text-center text-text-muted">
                    <i class="bx bx-briefcase text-4xl block mb-2"></i>
                    Tidak ada pengajuan
                </div>
            @endforelse
        </div>
        <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
            <x-pagination :paginator="$peminjaman" />
        </div>

        {{-- Modal detail pengajuan, dipakai kartu mobile --}}
        <div id="modalPengajuanDetail"
            class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
            <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
                <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                    <h3 id="modalPengajuanDetailLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">Detail Pengajuan</h3>
                    <button type="button"
                        class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                        onclick="document.getElementById('modalPengajuanDetail').classList.remove('open')"><i class="bx bx-x"></i></button>
                </div>
                <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3" id="modalPengajuanDetailBody"></div>
            </div>
        </div>

        {{-- Modal konfirmasi setuju & kembali - satu instance dipakai bareng oleh semua baris --}}
        <x-modal-konfirmasi id="modalKonfirmasiSetujui" title="Setujui Pengajuan" icon="bx-check-circle" icon-class="text-success-text">
            <div class="bg-success dark:bg-success-dark rounded-[10px] py-3.5 px-4">
                <div class="text-[13px] font-semibold text-success-text">
                    <i class="bx bx-check-circle"></i> Setujui pengajuan peminjaman ini?
                </div>
            </div>
            <form id="formKonfirmasiSetujui" method="POST">
                @csrf
                <div class="flex justify-end gap-2.5 mt-3">
                    <button type="button" data-modal-close
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                    <button type="submit"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-success-text text-white">
                        <i class="bx bx-check"></i> Setujui
                    </button>
                </div>
            </form>
        </x-modal-konfirmasi>

        <x-modal-konfirmasi id="modalKonfirmasiKembali" title="Konfirmasi Pengembalian" icon="bx-revision" icon-class="text-primary">
            <div class="bg-primary-50 dark:bg-[#0d2a40] rounded-[10px] py-3.5 px-4">
                <div class="text-[13px] font-semibold text-primary">
                    <i class="bx bx-info-circle"></i> Konfirmasi peralatan sudah dikembalikan?
                </div>
            </div>
            <form id="formKonfirmasiKembali" method="POST">
                @csrf
                <div class="flex justify-end gap-2.5 mt-3">
                    <button type="button" data-modal-close
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                    <button type="submit"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-primary text-white">
                        <i class="bx bx-revision"></i> Konfirmasi
                    </button>
                </div>
            </form>
        </x-modal-konfirmasi>

        <x-modal-konfirmasi id="modalKonfirmasiTolak" title="Tolak Pengajuan" icon="bx-x-circle" icon-class="text-danger-text">
            <div class="bg-danger dark:bg-danger-dark rounded-[10px] py-3.5 px-4">
                <div class="text-[13px] font-semibold text-danger-text">
                    <i class="bx bx-error"></i> Tolak pengajuan peminjaman ini?
                </div>
            </div>
            <form id="formKonfirmasiTolak" method="POST">
                @csrf
                <input type="text" name="catatan_inventaris" id="inputAlasanTolak"
                    class="w-full h-9 px-3 mt-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans focus:border-primary focus:outline-none"
                    placeholder="Alasan penolakan (wajib)" required>
                <div class="flex justify-end gap-2.5 mt-3">
                    <button type="button" data-modal-close
                        class="h-9 px-3.5 rounded-lg bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                    <button type="submit"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white">
                        <i class="bx bx-x"></i> Tolak
                    </button>
                </div>
            </form>
        </x-modal-konfirmasi>
    </main>
@endsection

@push('scripts')
    <script>
        function bukaKonfirmasiSetujui(url) {
            document.getElementById('formKonfirmasiSetujui').action = url;
            bukaModalKonfirmasi('modalKonfirmasiSetujui');
        }

        function bukaKonfirmasiTolak(url) {
            document.getElementById('formKonfirmasiTolak').action = url;
            document.getElementById('inputAlasanTolak').value = '';
            bukaModalKonfirmasi('modalKonfirmasiTolak');
        }

        function bukaKonfirmasiKembali(url) {
            document.getElementById('formKonfirmasiKembali').action = url;
            bukaModalKonfirmasi('modalKonfirmasiKembali');
        }

        // ── Modal detail pengajuan untuk kartu mobile ───────────────────────────
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

        function bukaModalPengajuan(card) {
            var d = card.dataset;
            document.getElementById('modalPengajuanDetailLabel').textContent = d.pemohon;

            var items = [];
            try { items = JSON.parse(d.items || '[]'); } catch (e) {}

            var itemsHtml = items.map(function (item) {
                return '<div class="flex items-center justify-between py-2.5 border-b border-page-bg dark:border-page-bg-dark last:border-b-0">' +
                    '<div class="flex items-center gap-2"><i class="bx bx-package text-primary text-base"></i><div><div class="font-medium text-[13px] text-text dark:text-text-dark">' + escapeHtml(item.nama) + '</div><div class="text-xs text-text-muted">' + escapeHtml(item.gedung) + '</div></div></div>' +
                    '<span class="text-sm font-semibold text-text dark:text-text-dark">x' + escapeHtml(item.jumlah) + '</span>' +
                    '</div>';
            }).join('');

            var html = '<div class="flex flex-wrap items-start gap-x-8 gap-y-2.5">' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-user"></i><div><label class="' + labelClass + '">Pemohon</label><p class="' + pClass + '">' + escapeHtml(d.pemohon) + ' &middot; ' + escapeHtml(d.nohp) + '</p></div></div>' +
                '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-note"></i><div><label class="' + labelClass + '">Keperluan</label><p class="' + pClass + '">' + escapeHtml(d.keperluan) + '</p></div></div>' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-check-circle"></i><div><label class="' + labelClass + '">Status</label><p class="' + pClass + '">' + badgeHtml(d.badgeVariant, d.badgeLabel) + '</p></div></div>' +
                '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-comment-detail"></i><div><label class="' + labelClass + '">Catatan</label><p class="' + pClass + '">' + escapeHtml(d.catatan) + '</p></div></div>' +
                (d.jadwal ? '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-calendar-event"></i><div><label class="' + labelClass + '">Terkait Jadwal</label><p class="' + pClass + '">' + escapeHtml(d.jadwal) + '</p></div></div>' : '') +
                '</div>' +
                '<div class="pt-3.5 mt-1 border-t border-page-bg dark:border-page-bg-dark">' +
                '<div class="text-[13px] font-semibold text-text dark:text-text-dark mb-1.5"><i class="bx bx-wrench text-primary"></i> Daftar Peralatan</div>' +
                itemsHtml +
                '</div>';

            document.getElementById('modalPengajuanDetailBody').innerHTML = html;
            document.getElementById('modalPengajuanDetail').classList.add('open');
        }

        document.querySelectorAll('[data-open-pengajuan-modal]').forEach(function (el) {
            el.addEventListener('click', function () { bukaModalPengajuan(el); });
        });

        var modalPengajuanOverlay = document.getElementById('modalPengajuanDetail');
        modalPengajuanOverlay.addEventListener('click', function (e) {
            if (e.target.id === 'modalPengajuanDetail') modalPengajuanOverlay.classList.remove('open');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') modalPengajuanOverlay.classList.remove('open');
        });
    </script>
@endpush
