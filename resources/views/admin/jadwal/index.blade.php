@extends('layouts.app')
@section('title', 'Data Jadwal')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Data Jadwal Rapat</h1>
            </div>
            <a href="{{ route('admin.jadwal.create') }}"
                class="h-9 px-4 rounded-full bg-primary text-surface dark:text-surface-dark flex justify-center items-center gap-2.5 font-medium">
                <i class="bx bx-plus"></i><span class="text">Tambah Jadwal</span>
            </a>
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('admin.jadwal.index') }}" class="contents">
                <div class="relative flex-1 min-w-[180px] max-w-[300px]">
                    <i class="bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
                    <input type="text" name="search" placeholder="Cari judul, platform..." value="{{ request('search') }}"
                        class="w-full h-9 pl-[34px] pr-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans transition-colors duration-200 focus:border-primary focus:outline-none focus:bg-surface dark:focus:bg-surface-dark">
                </div>
                <select name="platform"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Platform</option>
                    <option value="Online" {{ request('platform') == 'Online' ? 'selected' : '' }}>Online</option>
                    <option value="Offline" {{ request('platform') == 'Offline' ? 'selected' : '' }}>Offline</option>
                </select>
                <select name="status"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                <button type="submit"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-primary text-white">
                    <i class="bx bx-filter"></i> Filter</button>
                @if(request()->hasAny(['search', 'platform', 'status']))
                    <a href="{{ route('admin.jadwal.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
        </div>

        @php
            $badgeClass = 'inline-flex items-center gap-1 py-[3px] px-2.5 rounded-full text-xs font-medium whitespace-nowrap';
            $badgeInfo = $badgeClass . ' bg-primary-50 dark:bg-[#0d2a40] text-primary';
            $badgeActive = $badgeClass . ' bg-success dark:bg-success-dark text-success-text';
            $badgeDanger = $badgeClass . ' bg-danger dark:bg-danger-dark text-danger-text';
        @endphp

        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
            <div class="py-4 px-5 flex items-center justify-between border-b border-page-bg dark:border-page-bg-dark">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark">Daftar Jadwal</h3>
                <small class="text-text-muted">Tap baris untuk detail</small>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap"></th>
                            <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                            <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Judul Rapat <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Tanggal <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Waktu</th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Platform</th>
                            <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                            <th class="w-[100px] py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwal as $index => $j)
                            @php
                                $uid = 'jd-' . $j->id_penjadwalan;
                                $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                                $dibatalkan = $j->isDibatalkan();
                            @endphp

                            <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark {{ $dibatalkan ? 'opacity-60' : '' }}"
                                data-target="{{ $uid }}">
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i></td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $jadwal->firstItem() + $index }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="font-medium">{{ $j->judul_kegiatan }}</div>
                                    <div class="text-xs text-text-muted mt-0.5">
                                        {{ $j->operators->count() }} operator
                                    </div>
                                </td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $j->tanggal->translatedFormat('D, d M Y') }}</td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }}
                                </td>
                                <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    @if(str_contains($j->platform, 'Online'))
                                        <span class="{{ $badgeInfo }}"><i class="bx bx-wifi"></i> Online</span>
                                    @else
                                        <span class="{{ $badgeActive }}"><i class="bx bx-building"></i> Offline</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    @if($dibatalkan)
                                        <span class="{{ $badgeDanger }}">
                                            <i class="bx bx-x-circle"></i> Dibatalkan
                                        </span>
                                    @elseif($sudahLewat)
                                        <span class="{{ $badgeActive }}">
                                            <i class="bx bx-check-double"></i> Selesai
                                        </span>
                                    @else
                                        <span class="{{ $badgeInfo }}">
                                            <i class="bx bx-check-circle"></i> Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="flex gap-1.5 items-center">
                                        @if(!$dibatalkan)
                                            @if($sudahLewat)
                                                <a href="{{ route('admin.jadwal.show', $j->id_penjadwalan) }}"
                                                    class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-primary-50 dark:bg-[#0d2a40] text-primary"><i
                                                        class="bx bx-show"></i></a>
                                            @else
                                                <a href="{{ route('admin.jadwal.edit', $j->id_penjadwalan) }}"
                                                    class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-warning dark:bg-warning-dark text-warning-text"><i
                                                        class="bx bx-edit"></i></a>
                                                <button type="button"
                                                    class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 shrink-0 hover:opacity-80 bg-danger dark:bg-danger-dark text-danger-text"
                                                    title="Batalkan Jadwal"
                                                    onclick="bukaBatalkanJadwal('{{ route('admin.jadwal.batalkan', $j->id_penjadwalan) }}')">
                                                    <i class="bx bx-block"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Accordion detail --}}
                            <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                                <td colspan="8" class="!p-0">
                                    <div class="flex flex-wrap items-start gap-x-8 gap-y-2.5 py-[18px] px-4">
                                        <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                            <i class="bx bx-calendar text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Tanggal</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->tanggal->translatedFormat('l, d F Y') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                            <i class="bx bx-time-five text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Waktu</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                                                    {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                            <i class="bx bx-desktop text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Platform</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->platform }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                            <i class="bx bx-note text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Keterangan</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->keterangan ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                            <i class="bx bx-group text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Operator</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}</p>
                                            </div>
                                        </div>
                                        @if($dibatalkan)
                                            <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                                <i class="bx bx-error-circle text-lg text-danger-text mt-px shrink-0"></i>
                                                <div>
                                                    <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Alasan Pembatalan</label>
                                                    <p class="text-danger-text m-0 font-medium text-[13px] break-words">{{ $j->alasan_batal }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-text-muted">
                                    <i class="bx bx-calendar-x text-4xl block mb-2"></i>
                                    Belum ada jadwal
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$jadwal" />
        </div>

        {{-- Kartu jadwal - hanya tampil di mobile, tabel di atas tetap dipakai untuk tablet & desktop --}}
        <div class="hidden max-xs:flex flex-col gap-3 mb-4">
            @forelse($jadwal as $j)
                @php
                    $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                    $dibatalkan = $j->isDibatalkan();
                @endphp
                <div class="mobile-card bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-3.5"
                     data-judul="{{ $j->judul_kegiatan }}"
                     data-tanggal-full="{{ $j->tanggal->translatedFormat('l, d F Y') }}"
                     data-waktu="{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB"
                     data-platform="{{ $j->platform }}"
                     data-keterangan="{{ $j->keterangan ?? '-' }}"
                     data-operator="{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}"
                     data-dibatalkan="{{ $dibatalkan ? '1' : '' }}"
                     data-alasan-batal="{{ $j->alasan_batal }}">
                    <div class="flex items-center gap-3 cursor-pointer" data-open-jadwal-modal>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-text dark:text-text-dark">{{ $j->judul_kegiatan }}</div>
                            <div class="text-xs text-text-muted whitespace-nowrap overflow-hidden text-ellipsis">{{ $j->operators->count() }} operator</div>
                        </div>
                        <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                    </div>
                    <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                            <span>Tanggal</span>
                            <span>{{ $j->tanggal->translatedFormat('D, d M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                            <span>Platform</span>
                            @if(str_contains($j->platform, 'Online'))
                                <span class="{{ $badgeInfo }}"><i class="bx bx-wifi"></i> Online</span>
                            @else
                                <span class="{{ $badgeActive }}"><i class="bx bx-building"></i> Offline</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                            <span>Status</span>
                            @if($dibatalkan)
                                <span class="{{ $badgeDanger }}"><i class="bx bx-x-circle"></i> Dibatalkan</span>
                            @elseif($sudahLewat)
                                <span class="{{ $badgeActive }}"><i class="bx bx-check-double"></i> Selesai</span>
                            @else
                                <span class="{{ $badgeInfo }}"><i class="bx bx-check-circle"></i> Aktif</span>
                            @endif
                        </div>
                    </div>
                    @if(!$dibatalkan)
                        <div class="flex gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark [&>form]:contents">
                            @if($sudahLewat)
                                <a href="{{ route('admin.jadwal.show', $j->id_penjadwalan) }}"
                                    class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-primary-50 dark:bg-[#0d2a40] text-primary"><i class="bx bx-show"></i></a>
                            @else
                                <a href="{{ route('admin.jadwal.edit', $j->id_penjadwalan) }}"
                                    class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-warning dark:bg-warning-dark text-warning-text"><i class="bx bx-edit"></i></a>
                                <button type="button" title="Batalkan Jadwal"
                                    onclick="bukaBatalkanJadwal('{{ route('admin.jadwal.batalkan', $j->id_penjadwalan) }}')"
                                    class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 shrink-0 hover:opacity-80 bg-danger dark:bg-danger-dark text-danger-text">
                                    <i class="bx bx-block"></i>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-10 text-center text-text-muted">
                    <i class="bx bx-calendar-x text-4xl block mb-2"></i>
                    Belum ada jadwal
                </div>
            @endforelse
        </div>
        <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
            <x-pagination :paginator="$jadwal" />
        </div>

        {{-- Modal detail jadwal, dipakai kartu mobile --}}
        <div id="modalJadwalDetail"
            class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
            <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
                <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                    <h3 id="modalJadwalDetailLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">Detail Jadwal</h3>
                    <button type="button"
                        class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                        onclick="document.getElementById('modalJadwalDetail').classList.remove('open')"><i class="bx bx-x"></i></button>
                </div>
                <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3" id="modalJadwalDetailBody"></div>
            </div>
        </div>

        {{-- Modal konfirmasi batalkan jadwal - satu instance dipakai bareng oleh semua baris/kartu --}}
        <x-modal-konfirmasi id="modalBatalkanJadwal" title="Batalkan Jadwal" icon="bx-error" icon-class="text-danger-text">
            <div class="bg-danger dark:bg-danger-dark rounded-[10px] py-3.5 px-4">
                <div class="text-[13px] font-semibold text-[#c0392b]">
                    <i class="bx bx-error"></i> Batalkan jadwal ini - notif WA akan dikirim ke semua operator
                </div>
            </div>
            <form id="formBatalkanJadwal" method="POST">
                @csrf
                <input type="text" name="alasan_batal" id="inputAlasanBatalJadwal"
                    class="w-full h-9 px-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans focus:border-primary focus:outline-none"
                    placeholder="Alasan pembatalan (wajib)" required>
                <div class="flex justify-end gap-2.5 mt-3">
                    <button type="button" data-modal-close
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                    <button type="submit"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white">
                        <i class="bx bx-block"></i> Batalkan
                    </button>
                </div>
            </form>
        </x-modal-konfirmasi>
    </main>
@endsection

@push('scripts')
    <script>
        // Modal konfirmasi batalkan jadwal (komponen global modal-konfirmasi) - satu
        // instance dipakai bareng oleh semua baris tabel & kartu mobile, tinggal ganti
        // action form-nya ke URL jadwal yang mau dibatalkan tiap kali dibuka.
        function bukaBatalkanJadwal(url) {
            document.getElementById('formBatalkanJadwal').action = url;
            document.getElementById('inputAlasanBatalJadwal').value = '';
            bukaModalKonfirmasi('modalBatalkanJadwal');
        }

        // Modal detail jadwal untuk kartu mobile - isinya sama dengan dropdown detail di tabel desktop.
        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        function bukaModalJadwalMobile(card) {
            var d = card.dataset;
            document.getElementById('modalJadwalDetailLabel').textContent = d.judul;

            var detailRowClass = 'flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]';
            var detailRowFullClass = 'flex items-start gap-2.5 min-w-0 flex-[2_1_260px]';
            var iconClass = 'bx text-lg text-primary mt-px shrink-0';
            var labelClass = 'text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5';
            var pClass = 'text-text dark:text-text-dark m-0 font-medium text-[13px] break-words';

            var html = '<div class="flex flex-wrap items-start gap-x-8 gap-y-2.5">' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-calendar"></i><div><label class="' + labelClass + '">Tanggal</label><p class="' + pClass + '">' + escapeHtml(d.tanggalFull) + '</p></div></div>' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-time-five"></i><div><label class="' + labelClass + '">Waktu</label><p class="' + pClass + '">' + escapeHtml(d.waktu) + '</p></div></div>' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-desktop"></i><div><label class="' + labelClass + '">Platform</label><p class="' + pClass + '">' + escapeHtml(d.platform) + '</p></div></div>' +
                '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-note"></i><div><label class="' + labelClass + '">Keterangan</label><p class="' + pClass + '">' + escapeHtml(d.keterangan) + '</p></div></div>' +
                '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-group"></i><div><label class="' + labelClass + '">Operator</label><p class="' + pClass + '">' + escapeHtml(d.operator) + '</p></div></div>';

            if (d.dibatalkan) {
                html += '<div class="' + detailRowFullClass + '"><i class="bx bx-error-circle text-lg text-danger-text mt-px shrink-0"></i><div><label class="' + labelClass + '">Alasan Pembatalan</label><p class="text-danger-text m-0 font-medium text-[13px] break-words">' + escapeHtml(d.alasanBatal) + '</p></div></div>';
            }
            html += '</div>';

            document.getElementById('modalJadwalDetailBody').innerHTML = html;
            document.getElementById('modalJadwalDetail').classList.add('open');
        }

        document.querySelectorAll('[data-open-jadwal-modal]').forEach(function (el) {
            el.addEventListener('click', function () {
                var card = el.closest('.mobile-card');
                if (card) bukaModalJadwalMobile(card);
            });
        });

        document.getElementById('modalJadwalDetail').addEventListener('click', function (e) {
            if (e.target.id === 'modalJadwalDetail') e.currentTarget.classList.remove('open');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.getElementById('modalJadwalDetail').classList.remove('open');
        });
    </script>
@endpush
