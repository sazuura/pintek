@extends('layouts.app')
@section('title', 'Peminjaman Saya')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
    @php
        $roleAktif = auth()->user()->role;
        $bisaUbah  = auth()->user()->punyaAkses('peminjaman', 'ubah');
    @endphp
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Peminjaman Saya</h1>
            </div>
            @if(auth()->user()->punyaAkses('peminjaman', 'tambah'))
                <a href="{{ route($roleAktif . '.peminjaman.create') }}"
                    class="h-9 px-4 rounded-full bg-primary text-surface dark:text-surface-dark flex justify-center items-center gap-2.5 font-medium">
                    <i class="bx bx-plus"></i><span class="text">Ajukan Peminjaman</span>
                </a>
            @endif
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route($roleAktif . '.peminjaman.index') }}" class="contents">
                <select name="status" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Menunggu</option>
                    <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="dikembalikan" {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>Dikembalikan
                    </option>
                    <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                @if(request('status'))
                    <a href="{{ route($roleAktif . '.peminjaman.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
            <div class="py-4 px-5 flex items-center justify-between border-b border-page-bg dark:border-page-bg-dark">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark">Riwayat Pengajuan</h3>
                <small class="text-text-muted">Tap baris untuk detail</small>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap"></th>
                            <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                            <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Keperluan <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Tgl Pinjam <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Rencana Kembali</th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Tgl Kembali</th>
                            <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                            <th class="w-[100px] py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peminjaman as $index => $p)
                            @php $uid = 'pm-' . $p->id_peminjaman; @endphp
                            <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark {{ $p->isDibatalkan() ? 'opacity-60' : '' }}" data-target="{{ $uid }}">
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i></td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $peminjaman->firstItem() + $index }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="font-medium">{{ $p->keperluan }}</div>
                                    <div class="text-xs text-text-muted mt-0.5">
                                        {{ $p->items->count() }} item peralatan
                                    </div>
                                </td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-left">{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}</td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-left">{{ $p->tanggal_kembali_rencana->translatedFormat('l, d F Y') }}</td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-left">{{ $p->tanggal_kembali_aktual?->translatedFormat('l, d F Y') ?? '-' }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                    <x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge>
                                </td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                    @if($bisaUbah && $p->isMenunggu())
                                        <div class="flex gap-1.5 items-center justify-center">
                                            <a href="{{ route($roleAktif . '.peminjaman.edit', $p->id_peminjaman) }}"
                                                class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-warning dark:bg-warning-dark text-warning-text"
                                                title="Edit Pengajuan"><i class="bx bx-edit"></i></a>
                                            <button type="button"
                                                class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 shrink-0 hover:opacity-80 bg-danger dark:bg-danger-dark text-danger-text"
                                                title="Batalkan Pengajuan"
                                                onclick="bukaBatalkanPengajuan('{{ route($roleAktif . '.peminjaman.batalkan', $p->id_peminjaman) }}')">
                                                <i class="bx bx-block"></i>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                                <td colspan="8" class="!p-0">
                                    <div class="flex flex-wrap items-start gap-x-8 gap-y-2.5 py-[18px] px-4">
                                        <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                            <i class="bx bx-calendar-check text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Rencana Kembali</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $p->tanggal_kembali_rencana->translatedFormat('l, d F Y') }}</p>
                                            </div>
                                        </div>
                                        @if($p->tanggal_kembali_aktual)
                                            <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                                <i class="bx bx-undo text-lg text-primary mt-px shrink-0"></i>
                                                <div>
                                                    <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Dikembalikan</label>
                                                    <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $p->tanggal_kembali_aktual->translatedFormat('l, d F Y') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        @if($p->penjadwalan)
                                            <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                                <i class="bx bx-calendar-event text-lg text-primary mt-px shrink-0"></i>
                                                <div>
                                                    <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Terkait Jadwal</label>
                                                    <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $p->penjadwalan->judul_kegiatan }} ({{ $p->penjadwalan->tanggal->format('d/m/Y') }})</p>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                            <i class="bx bx-note text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Catatan Inventaris</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $p->catatan_inventaris ?? '-' }}</p>
                                            </div>
                                        </div>
                                        @if($p->isDibatalkan())
                                            <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                                <i class="bx bx-error-circle text-lg text-danger-text mt-px shrink-0"></i>
                                                <div>
                                                    <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Alasan Pembatalan</label>
                                                    <p class="text-danger-text m-0 font-medium text-[13px] break-words">{{ $p->alasan_batal }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Daftar Peralatan - tabel group, konsisten dengan tab Peralatan Digunakan di Laporan --}}
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
                                                                <div class="font-medium mb-[3px]">{{ $item->peralatan->nama_peralatan ?? '-' }}</div>
                                                                <div class="text-xs text-text-muted">{{ $item->peralatan->gedung ?? '-' }}</div>
                                                            </td>
                                                            <td class="py-4 px-5 text-sm text-text dark:text-text-dark align-middle text-center font-semibold">{{ $item->jumlah }}</td>
                                                            <td class="py-4 px-5 text-sm align-middle text-center"><x-badge :variant="$item->badge['class']">{{ $item->badge['label'] }}</x-badge></td>
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
                                <td colspan="8" class="text-center py-10 text-text-muted">
                                    <i class="bx bx-briefcase text-4xl block mb-2"></i>
                                    Belum ada pengajuan peminjaman
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
                <div class="mobile-card bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-3.5 cursor-pointer {{ $p->isDibatalkan() ? 'opacity-60' : '' }}"
                     data-open-peminjaman-modal
                     data-keperluan="{{ $p->keperluan }}"
                     data-tanggal-pinjam="{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}"
                     data-tanggal-kembali="{{ $p->tanggal_kembali_rencana->translatedFormat('l, d F Y') }}"
                     data-tanggal-kembali-aktual="{{ $p->tanggal_kembali_aktual?->translatedFormat('l, d F Y') }}"
                     data-peralatan="{{ $p->items->map(fn($i) => $i->peralatan->nama_peralatan . ' (x' . $i->jumlah . ')')->join(', ') }}"
                     data-jadwal="{{ $p->penjadwalan ? $p->penjadwalan->judul_kegiatan . ' (' . $p->penjadwalan->tanggal->format('d/m/Y') . ')' : '' }}"
                     data-catatan="{{ $p->catatan_inventaris ?? '-' }}"
                     data-dibatalkan="{{ $p->isDibatalkan() ? '1' : '' }}"
                     data-alasan-batal="{{ $p->alasan_batal }}"
                     @if($bisaUbah && $p->isMenunggu())
                         data-edit-url="{{ route($roleAktif . '.peminjaman.edit', $p->id_peminjaman) }}"
                         data-batalkan-url="{{ route($roleAktif . '.peminjaman.batalkan', $p->id_peminjaman) }}"
                     @endif>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-primary-50 dark:bg-[#0d2a40] text-primary flex items-center justify-center text-lg shrink-0">
                            <i class="bx bx-briefcase"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-text dark:text-text-dark truncate">{{ $p->keperluan }}</div>
                            <div class="text-xs text-text-muted whitespace-nowrap overflow-hidden text-ellipsis">{{ $p->items->count() }} item peralatan</div>
                        </div>
                        <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                    </div>
                    <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                            <span><i class="bx bx-calendar"></i> Tgl Pinjam</span>
                            <span class="text-text dark:text-text-dark font-medium">{{ $p->tanggal_pinjam->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                            <span><i class="bx bx-calendar-check"></i> Tgl Kembali</span>
                            <span class="text-text dark:text-text-dark font-medium">{{ $p->tanggal_kembali_rencana->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                            <span><i class="bx bx-flag"></i> Status</span>
                            <x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-10 text-center text-text-muted">
                    <i class="bx bx-briefcase text-4xl block mb-2"></i>
                    Belum ada pengajuan peminjaman
                </div>
            @endforelse
        </div>
        <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
            <x-pagination :paginator="$peminjaman">{{ $peminjaman->total() }} total pengajuan</x-pagination>
        </div>

        {{-- Modal detail pengajuan, dipakai kartu mobile --}}
        <div id="modalPeminjamanDetail"
            class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
            <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
                <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                    <h3 id="modalPeminjamanDetailLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">Detail Pengajuan</h3>
                    <button type="button"
                        class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                        onclick="document.getElementById('modalPeminjamanDetail').classList.remove('open')"><i class="bx bx-x"></i></button>
                </div>
                <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3" id="modalPeminjamanDetailBody"></div>
            </div>
        </div>

        {{-- Modal konfirmasi batalkan pengajuan - satu instance dipakai bareng oleh semua baris/kartu --}}
        <x-modal-konfirmasi id="modalBatalkanPengajuan" title="Batalkan Pengajuan" icon="bx-error" icon-class="text-danger-text">
            <div class="bg-danger dark:bg-danger-dark rounded-[10px] py-3.5 px-4">
                <div class="text-[13px] font-semibold text-[#c0392b]">
                    <i class="bx bx-error"></i> Batalkan pengajuan - notif WA akan dikirim ke inventaris
                </div>
            </div>
            <form id="formBatalkanPengajuan" method="POST">
                @csrf
                <input type="text" name="alasan_batal" id="inputAlasanBatalPengajuan"
                    class="w-full h-9 px-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans focus:border-primary focus:outline-none"
                    placeholder="Alasan pembatalan (wajib)" required>
                <div class="flex justify-end gap-2.5 mt-3">
                    <button type="button" data-modal-close
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                    <button type="submit"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white">
                        <i class="bx bx-block"></i> Konfirmasi Batalkan
                    </button>
                </div>
            </form>
        </x-modal-konfirmasi>
    </main>
@endsection

@push('scripts')
    <script>
        // Modal konfirmasi batalkan pengajuan (komponen global modal-konfirmasi) - satu
        // instance dipakai bareng oleh baris tabel & kartu mobile, tinggal ganti action
        // form-nya ke URL pengajuan yang mau dibatalkan tiap kali dibuka. Kalau dipanggil
        // dari dalam modal detail (kartu mobile), tutup dulu modal detail-nya supaya tidak
        // ada dua overlay bertumpuk.
        function bukaBatalkanPengajuan(url) {
            var detailModal = document.getElementById('modalPeminjamanDetail');
            if (detailModal) detailModal.classList.remove('open');

            document.getElementById('formBatalkanPengajuan').action = url;
            document.getElementById('inputAlasanBatalPengajuan').value = '';
            bukaModalKonfirmasi('modalBatalkanPengajuan');
        }

        // Modal detail pengajuan untuk kartu mobile - isinya sama dengan dropdown detail di tabel desktop.
        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        function bukaModalPeminjamanMobile(card) {
            var d = card.dataset;
            document.getElementById('modalPeminjamanDetailLabel').textContent = d.keperluan;

            var detailRowClass = 'flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]';
            var detailRowFullClass = 'flex items-start gap-2.5 min-w-0 flex-[2_1_260px]';
            var iconClass = 'bx text-lg text-primary mt-px shrink-0';
            var labelClass = 'text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5';
            var pClass = 'text-text dark:text-text-dark m-0 font-medium text-[13px] break-words';

            var html = '<div class="flex flex-wrap items-start gap-x-8 gap-y-2.5">' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-calendar"></i><div><label class="' + labelClass + '">Tanggal Pinjam</label><p class="' + pClass + '">' + escapeHtml(d.tanggalPinjam) + '</p></div></div>' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-calendar-check"></i><div><label class="' + labelClass + '">Rencana Kembali</label><p class="' + pClass + '">' + escapeHtml(d.tanggalKembali) + '</p></div></div>';

            if (d.tanggalKembaliAktual) {
                html += '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-undo"></i><div><label class="' + labelClass + '">Dikembalikan</label><p class="' + pClass + '">' + escapeHtml(d.tanggalKembaliAktual) + '</p></div></div>';
            }

            html += '<div class="' + detailRowFullClass + '"><i class="bx bxs-wrench text-lg text-primary mt-px shrink-0"></i><div><label class="' + labelClass + '">Peralatan</label><p class="' + pClass + '">' + escapeHtml(d.peralatan) + '</p></div></div>';

            if (d.jadwal) {
                html += '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-calendar-event"></i><div><label class="' + labelClass + '">Terkait Jadwal</label><p class="' + pClass + '">' + escapeHtml(d.jadwal) + '</p></div></div>';
            }

            html += '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-note"></i><div><label class="' + labelClass + '">Catatan Inventaris</label><p class="' + pClass + '">' + escapeHtml(d.catatan) + '</p></div></div>';

            if (d.dibatalkan) {
                html += '<div class="' + detailRowFullClass + '"><i class="bx bx-error-circle text-lg text-danger-text mt-px shrink-0"></i><div><label class="' + labelClass + '">Alasan Pembatalan</label><p class="text-danger-text m-0 font-medium text-[13px] break-words">' + escapeHtml(d.alasanBatal) + '</p></div></div>';
            }
            html += '</div>';

            if (d.batalkanUrl) {
                html += '<div class="flex gap-2">' +
                    '<a href="' + d.editUrl + '" class="h-8 px-3.5 rounded-lg bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 text-xs font-sans cursor-pointer no-underline inline-flex items-center gap-1.5 font-medium transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark"><i class="bx bx-edit"></i> Edit Pengajuan</a>' +
                    '<button type="button" id="btnBatalkanPengajuanMobile" class="h-8 px-3.5 rounded-lg border-none text-xs font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white"><i class="bx bx-block"></i> Batalkan Pengajuan</button>' +
                    '</div>';
            }

            document.getElementById('modalPeminjamanDetailBody').innerHTML = html;
            document.getElementById('modalPeminjamanDetail').classList.add('open');

            var btnBatal = document.getElementById('btnBatalkanPengajuanMobile');
            if (btnBatal) {
                btnBatal.addEventListener('click', function () {
                    bukaBatalkanPengajuan(d.batalkanUrl);
                });
            }
        }

        document.querySelectorAll('[data-open-peminjaman-modal]').forEach(function (el) {
            el.addEventListener('click', function () {
                bukaModalPeminjamanMobile(el);
            });
        });

        document.getElementById('modalPeminjamanDetail').addEventListener('click', function (e) {
            if (e.target.id === 'modalPeminjamanDetail') e.currentTarget.classList.remove('open');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.getElementById('modalPeminjamanDetail').classList.remove('open');
        });
    </script>
@endpush
