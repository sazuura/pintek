@extends('layouts.app')
@section('title', 'Jadwal Saya')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
<main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
        <div><h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Jadwal Saya</h1></div>
    </div>

    <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
        <div class="py-4 px-5 flex items-center justify-between border-b border-page-bg dark:border-page-bg-dark">
            <h3 class="text-[15px] font-semibold text-text dark:text-text-dark">Daftar Jadwal yang Ditugaskan</h3>
            <small class="text-text-muted">Tap baris untuk detail</small>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap"></th>
                        <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                        <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Judul Rapat <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                        <th class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Tanggal <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                        <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Waktu</th>
                        <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Platform</th>
                        <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jadwal as $index => $j)
                    @php
                        $uid        = 'jd-'.$j->id_penjadwalan;
                        $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                        $dibatalkan = $j->isDibatalkan();
                    @endphp
                    <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark" data-target="{{ $uid }}">
                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i></td>
                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $jadwal->firstItem() + $index }}</td>
                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                            <div class="font-medium">{{ $j->judul_kegiatan }}</div>
                            <div class="text-xs text-text-muted mt-0.5">{{ $j->keterangan }}</div>
                        </td>
                        <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">{{ $j->tanggal->translatedFormat('D, d M Y') }}</td>
                        <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                            {{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }}
                        </td>
                        <td class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                            @if(str_contains($j->platform,'Online'))
                                <x-badge variant="badge-info"><i class="bx bx-wifi"></i> Online</x-badge>
                            @else
                                <x-badge variant="badge-active"><i class="bx bx-building"></i> Offline</x-badge>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                            @if($dibatalkan)
                                <x-badge variant="badge-danger"><i class="bx bx-x-circle"></i> Dibatalkan</x-badge>
                            @elseif($sudahLewat)
                                <x-badge variant="badge-active"><i class="bx bx-check-double"></i> Selesai</x-badge>
                            @else
                                <x-badge variant="badge-info"><i class="bx bx-check-circle"></i> Aktif</x-badge>
                            @endif
                        </td>
                    </tr>
                    <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                        <td colspan="7" class="!p-0">
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
                                        <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB</p>
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
                                        <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Keterangan / Link</label>
                                        <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->keterangan ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                    <i class="bx bx-group text-lg text-primary mt-px shrink-0"></i>
                                    <div>
                                        <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Operator Bertugas</label>
                                        <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                    <i class="bx bxs-wrench text-lg text-primary mt-px shrink-0"></i>
                                    <div>
                                        <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Alat yang Perlu Disiapkan</label>
                                        <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $j->peralatanReferensi->pluck('nama_peralatan')->join(', ') ?: 'Tidak ada catatan alat' }}</p>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-text-muted">
                            <i class="bx bx-calendar-x text-4xl block mb-2"></i>
                            Belum ada jadwal yang ditugaskan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$jadwal" label="jadwal" />
    </div>

    {{-- Kartu jadwal - hanya tampil di mobile, tabel di atas tetap dipakai untuk tablet & desktop --}}
    <div class="hidden max-xs:flex flex-col gap-3 mb-4">
        @forelse($jadwal as $j)
            @php
                $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                $dibatalkan = $j->isDibatalkan();
            @endphp
            <div class="mobile-card bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-3.5 cursor-pointer"
                 data-open-jadwal-modal
                 data-judul="{{ $j->judul_kegiatan }}"
                 data-tanggal-full="{{ $j->tanggal->translatedFormat('l, d F Y') }}"
                 data-waktu="{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB"
                 data-platform="{{ $j->platform }}"
                 data-keterangan="{{ $j->keterangan ?? '-' }}"
                 data-operator="{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}"
                 data-alat="{{ $j->peralatanReferensi->pluck('nama_peralatan')->join(', ') ?: 'Tidak ada catatan alat' }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary-50 dark:bg-[#0d2a40] text-primary flex items-center justify-center text-lg shrink-0">
                        <i class="bx bx-calendar-event"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-text dark:text-text-dark truncate">{{ $j->judul_kegiatan }}</div>
                        <div class="text-xs text-text-muted whitespace-nowrap overflow-hidden text-ellipsis">{{ $j->keterangan ?: '-' }}</div>
                    </div>
                    <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                </div>
                <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                    <div class="flex items-center justify-between text-[13px] text-text-muted">
                        <span><i class="bx bx-calendar"></i> Tanggal</span>
                        <span class="text-text dark:text-text-dark font-medium">{{ $j->tanggal->translatedFormat('D, d M Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[13px] text-text-muted">
                        <span><i class="bx bx-desktop"></i> Platform</span>
                        @if(str_contains($j->platform, 'Online'))
                            <x-badge variant="badge-info"><i class="bx bx-wifi"></i> Online</x-badge>
                        @else
                            <x-badge variant="badge-active"><i class="bx bx-building"></i> Offline</x-badge>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-[13px] text-text-muted">
                        <span><i class="bx bx-flag"></i> Status</span>
                        @if($dibatalkan)
                            <x-badge variant="badge-danger"><i class="bx bx-x-circle"></i> Dibatalkan</x-badge>
                        @elseif($sudahLewat)
                            <x-badge variant="badge-active"><i class="bx bx-check-double"></i> Selesai</x-badge>
                        @else
                            <x-badge variant="badge-info"><i class="bx bx-check-circle"></i> Aktif</x-badge>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-10 text-center text-text-muted">
                <i class="bx bx-calendar-x text-4xl block mb-2"></i>
                Belum ada jadwal yang ditugaskan
            </div>
        @endforelse
    </div>
    <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
        <x-pagination :paginator="$jadwal" label="jadwal" />
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
</main>
@endsection

@push('scripts')
    <script>
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
                '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-note"></i><div><label class="' + labelClass + '">Keterangan / Link</label><p class="' + pClass + '">' + escapeHtml(d.keterangan) + '</p></div></div>' +
                '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-group"></i><div><label class="' + labelClass + '">Operator Bertugas</label><p class="' + pClass + '">' + escapeHtml(d.operator) + '</p></div></div>' +
                '<div class="' + detailRowFullClass + '"><i class="bx bxs-wrench text-lg text-primary mt-px shrink-0"></i><div><label class="' + labelClass + '">Alat yang Perlu Disiapkan</label><p class="' + pClass + '">' + escapeHtml(d.alat) + '</p></div></div>' +
                '</div>';

            document.getElementById('modalJadwalDetailBody').innerHTML = html;
            document.getElementById('modalJadwalDetail').classList.add('open');
        }

        document.querySelectorAll('[data-open-jadwal-modal]').forEach(function (el) {
            el.addEventListener('click', function () {
                bukaModalJadwalMobile(el);
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
