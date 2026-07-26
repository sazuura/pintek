{{--
Digabung dari admin/jadwal/index.blade.php (kelola penuh) dan
operator/jadwal/index.blade.php (baca saja, jadwal miliknya sendiri) - satu file,
tombol aksi dan toolbar muncul/hilang berdasar hak akses dinamis (lihat
docs/plans/planning-role-akses-dinamis.md §5). Variabel bisaUbah/bisaTambah dikirim
dari controller (bukan dihitung inline di sini) supaya sudah tersedia sebelum
dipakai di judul halaman di bawah.
--}}
@extends('layouts.app')
@section('title', $bisaUbah ? 'Data Jadwal' : 'Jadwal Saya')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
    @php $roleAktif = auth()->user()->role; @endphp
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">
                    {{ $bisaUbah ? 'Data Jadwal Rapat' : 'Jadwal Saya' }}</h1>
            </div>
            @if($bisaTambah)
                <a href="{{ route($roleAktif . '.jadwal.create') }}"
                    class="h-9 px-4 rounded-full bg-primary text-surface dark:text-surface-dark flex justify-center items-center gap-2.5 font-medium">
                    <i class="bx bx-plus"></i><span class="text">Tambah Jadwal</span>
                </a>
            @endif
        </div>

        {{-- Toolbar filter tampil untuk semua role - operator (baca saja) juga bisa
             search/filter jadwal miliknya, query-nya ditangani PenjadwalanController::index(). --}}
        <div
            class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route($roleAktif . '.jadwal.index') }}" class="contents">
                <div class="relative flex-1 min-w-[180px] max-w-[300px]">
                    <i
                        class="bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
                    <input type="text" name="search" data-live-search="#hasil-jadwal" autocomplete="off"
                        placeholder="Cari judul, platform..." value="{{ request('search') }}"
                        class="w-full h-9 pl-[34px] pr-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans transition-colors duration-200 focus:border-primary focus:outline-none focus:bg-surface dark:focus:bg-surface-dark">
                </div>
                <select name="platform" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Platform</option>
                    <option value="Online" {{ request('platform') == 'Online' ? 'selected' : '' }}>Online</option>
                    <option value="Offline" {{ request('platform') == 'Offline' ? 'selected' : '' }}>Offline</option>
                </select>
                <select name="status" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                @if(request()->hasAny(['search', 'platform', 'status']))
                    <a href="{{ route($roleAktif . '.jadwal.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
        </div>

        <div id="hasil-jadwal">
            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
                <div class="py-4 px-5 flex items-center justify-between border-b border-page-bg dark:border-page-bg-dark">
                    <h3 class="text-[15px] font-semibold text-text dark:text-text-dark">
                        {{ $bisaUbah ? 'Daftar Jadwal' : 'Daftar Jadwal yang Ditugaskan' }}</h3>
                    <small class="text-text-muted">Tap baris untuk detail</small>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th
                                    class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">
                                </th>
                                <th
                                    class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">
                                    #</th>
                                <th
                                    class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">
                                    Judul Rapat <span
                                        class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span>
                                </th>
                                <th
                                    class="max-md:hidden group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">
                                    Tanggal <span
                                        class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span>
                                </th>
                                <th
                                    class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">
                                    Waktu</th>
                                <th
                                    class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">
                                    Platform</th>
                                <th
                                    class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">
                                    Status</th>
                                @if($bisaUbah)
                                    <th
                                        class="w-[100px] py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">
                                        Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php $kolomTotal = $bisaUbah ? 8 : 7; @endphp
                            @forelse($jadwal as $index => $j)
                                @php
                                    $uid = 'jd-' . $j->id_penjadwalan;
                                    $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                                    $dibatalkan = $j->isDibatalkan();
                                @endphp

                                <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark {{ $dibatalkan ? 'opacity-60' : '' }}"
                                    data-target="{{ $uid }}">
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i
                                            class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i>
                                    </td>
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                        {{ $jadwal->firstItem() + $index }}</td>
                                    <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                        <div class="font-medium">{{ $j->judul_kegiatan }}</div>
                                        <div class="text-xs text-text-muted mt-0.5">
                                            @if($bisaUbah)
                                                {{ $j->operators->count() . ' operator' }}
                                            @elseif(!$sudahLewat && !$dibatalkan && str_starts_with($j->keterangan ?? '', 'http'))
                                                <a href="{{ $j->keterangan }}" target="_blank" rel="noopener"
                                                    onclick="event.stopPropagation()"
                                                    class="text-primary hover:underline">{{ $j->keterangan }}</a>
                                            @else
                                                {{ $j->keterangan }}
                                            @endif
                                        </div>
                                    </td>
                                    <td
                                        class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-left">
                                        {{ $j->tanggal->translatedFormat('D, d M Y') }}</td>
                                    <td
                                        class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                        {{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }}
                                    </td>
                                    <td
                                        class="max-md:hidden py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                        @if(str_contains($j->platform, 'Online'))
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
                                    @if($bisaUbah)
                                        <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center">
                                            <div class="flex gap-1.5 items-center justify-center">
                                                @if(!$dibatalkan)
                                                    @if($sudahLewat)
                                                        <a href="{{ route($roleAktif . '.jadwal.show', $j->id_penjadwalan) }}"
                                                            class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-primary-50 dark:bg-[#0d2a40] text-primary"><i
                                                                class="bx bx-show"></i></a>
                                                    @else
                                                        <a href="{{ route($roleAktif . '.jadwal.edit', $j->id_penjadwalan) }}"
                                                            class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-warning dark:bg-warning-dark text-warning-text"><i
                                                                class="bx bx-edit"></i></a>
                                                        <button type="button"
                                                            class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 shrink-0 hover:opacity-80 bg-danger dark:bg-danger-dark text-danger-text"
                                                            title="Batalkan Jadwal"
                                                            onclick="bukaBatalkanJadwal('{{ route($roleAktif . '.jadwal.batalkan', $j->id_penjadwalan) }}')">
                                                            <i class="bx bx-block"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>

                                {{-- Accordion detail --}}
                                <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row"
                                    id="{{ $uid }}">
                                    <td colspan="{{ $kolomTotal }}" class="!p-0">
                                        <div class="flex flex-wrap items-start gap-x-8 gap-y-2.5 py-[18px] px-4">
                                            <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                                <i class="bx bx-note text-lg text-primary mt-px shrink-0"></i>
                                                <div>
                                                    <label
                                                        class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Keterangan{{ $bisaUbah ? '' : ' / Link' }}</label>
                                                    @if(!$sudahLewat && !$dibatalkan && str_starts_with($j->keterangan ?? '', 'http'))
                                                        <a href="{{ $j->keterangan }}" target="_blank" rel="noopener"
                                                            class="text-primary hover:underline font-medium text-[13px] break-all inline-flex items-center gap-1">
                                                            {{ $j->keterangan }}
                                                            <i class="bx bx-link-external text-xs"></i>
                                                        </a>
                                                    @else
                                                        <p
                                                            class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">
                                                            {{ $j->keterangan ?? '-' }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($j->lokasi_fisik)
                                                <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                                    <i class="bx bx-map text-lg text-primary mt-px shrink-0"></i>
                                                    <div>
                                                        <label
                                                            class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Lokasi
                                                            Fisik</label>
                                                        <p
                                                            class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">
                                                            {{ $j->lokasi_fisik }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                                <i class="bx bx-group text-lg text-primary mt-px shrink-0"></i>
                                                <div>
                                                    <label
                                                        class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Operator{{ $bisaUbah ? '' : ' Bertugas' }}</label>
                                                    <p
                                                        class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">
                                                        {{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}</p>
                                                </div>
                                            </div>
                                            @if($dibatalkan)
                                                <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                                    <i class="bx bx-error-circle text-lg text-danger-text mt-px shrink-0"></i>
                                                    <div>
                                                        <label
                                                            class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Alasan
                                                            Pembatalan</label>
                                                        <p class="text-danger-text m-0 font-medium text-[13px] break-words">
                                                            {{ $j->alasan_batal }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="px-4 pb-4">
                                            <div class="flex items-center justify-between mb-2.5 px-0.5">
                                                <span
                                                    class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-text dark:text-text-dark"><i
                                                        class="bx bx-wrench text-primary text-[15px]"></i> Daftar
                                                    Peralatan</span>
                                            </div>
                                            <div
                                                class="bg-surface dark:bg-surface-dark rounded-[10px] shadow-[0_1px_4px_rgba(0,0,0,0.06)] overflow-hidden">
                                                <table class="w-full border-collapse">
                                                    <thead>
                                                        <tr>
                                                            <th
                                                                class="pl-5 py-3 px-5 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg/50 dark:bg-page-bg-dark/50 whitespace-nowrap">
                                                                Nama Alat</th>
                                                            <th
                                                                class="w-[90px] py-3 px-5 text-[11px] uppercase tracking-[0.5px] text-text-muted text-center bg-page-bg/50 dark:bg-page-bg-dark/50 whitespace-nowrap">
                                                                Jumlah</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($j->peralatanReferensi as $alat)
                                                        <tr class="border-b border-page-bg dark:border-page-bg-dark last:border-b-0 hover:bg-page-bg dark:hover:bg-page-bg-dark">
                                                            <td class="pl-5 py-4 px-5 text-sm text-text dark:text-text-dark align-middle">
                                                                <div class="flex items-center gap-2">
                                                                    <i class="bx bx-package text-primary text-base"></i>
                                                                    <div>
                                                                        <div class="font-medium mb-[3px]">{{ $alat->nama_peralatan }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="py-4 px-5 text-sm text-text dark:text-text-dark align-middle text-center font-semibold">{{ $alat->pivot->jumlah }}</td>
                                                        </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="2"
                                                                    class="py-4 px-5 text-sm text-text-muted text-center">Tidak ada
                                                                    catatan alat</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="{{ $kolomTotal }}" class="text-center py-10 text-text-muted">
                                        <i class="bx bx-calendar-x text-4xl block mb-2"></i>
                                        {{ $bisaUbah ? 'Belum ada jadwal' : 'Belum ada jadwal yang ditugaskan' }}
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
                    <div class="mobile-card bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-3.5 {{ $bisaUbah ? '' : 'cursor-pointer' }}"
                        {{ $bisaUbah ? '' : 'data-open-jadwal-modal' }} data-judul="{{ $j->judul_kegiatan }}"
                        data-tanggal-full="{{ $j->tanggal->translatedFormat('l, d F Y') }}"
                        data-waktu="{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB"
                        data-platform="{{ $j->platform }}" data-keterangan="{{ $j->keterangan ?? '-' }}"
                        data-jadwal-selesai="{{ ($sudahLewat || $dibatalkan) ? '1' : '' }}"
                        data-lokasi-fisik="{{ $j->lokasi_fisik }}"
                        data-operator="{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}" @if($bisaUbah)
                        data-dibatalkan="{{ $dibatalkan ? '1' : '' }}" data-alasan-batal="{{ $j->alasan_batal }}" @else
                            data-alat="{{ $j->peralatanReferensi->pluck('nama_peralatan')->join(', ') ?: 'Tidak ada catatan alat' }}"
                        @endif>
                        <div class="flex items-center gap-3 {{ $bisaUbah ? 'cursor-pointer' : '' }}" @if($bisaUbah)
                        data-open-jadwal-modal @endif>
                            @unless($bisaUbah)
                                <div
                                    class="w-10 h-10 rounded-lg bg-primary-50 dark:bg-[#0d2a40] text-primary flex items-center justify-center text-lg shrink-0">
                                    <i class="bx bx-calendar-event"></i>
                                </div>
                            @endunless
                            <div class="flex-1 min-w-0">
                                <div
                                    class="font-semibold text-sm text-text dark:text-text-dark {{ $bisaUbah ? '' : 'truncate' }}">
                                    {{ $j->judul_kegiatan }}</div>
                                <div class="text-xs text-text-muted whitespace-nowrap overflow-hidden text-ellipsis">
                                    @if($bisaUbah)
                                        {{ $j->operators->count() . ' operator' }}
                                    @elseif(!$sudahLewat && !$dibatalkan && str_starts_with($j->keterangan ?? '', 'http'))
                                        <a href="{{ $j->keterangan }}" target="_blank" rel="noopener"
                                            onclick="event.stopPropagation()"
                                            class="text-primary hover:underline">{{ $j->keterangan }}</a>
                                    @else
                                        {{ $j->keterangan ?: '-' }}
                                    @endif
                                </div>
                            </div>
                            <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                        </div>
                        <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                            <div class="flex items-center justify-between text-[13px] text-text-muted">
                                <span>{{ $bisaUbah ? '' : '' }}<i class="bx bx-calendar"></i> Tanggal</span>
                                <span
                                    class="{{ $bisaUbah ? '' : 'text-text dark:text-text-dark font-medium' }}">{{ $j->tanggal->translatedFormat('D, d M Y') }}</span>
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
                        @if($bisaUbah && !$dibatalkan)
                            <div class="flex gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark [&>form]:contents">
                                @if($sudahLewat)
                                    <a href="{{ route($roleAktif . '.jadwal.show', $j->id_penjadwalan) }}"
                                        class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-primary-50 dark:bg-[#0d2a40] text-primary"><i
                                            class="bx bx-show"></i></a>
                                @else
                                    <a href="{{ route($roleAktif . '.jadwal.edit', $j->id_penjadwalan) }}"
                                        class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80 bg-warning dark:bg-warning-dark text-warning-text"><i
                                            class="bx bx-edit"></i></a>
                                    <button type="button" title="Batalkan Jadwal"
                                        onclick="bukaBatalkanJadwal('{{ route($roleAktif . '.jadwal.batalkan', $j->id_penjadwalan) }}')"
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
                        {{ $bisaUbah ? 'Belum ada jadwal' : 'Belum ada jadwal yang ditugaskan' }}
                    </div>
                @endforelse
            </div>
            <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
                <x-pagination :paginator="$jadwal" label="jadwal" />
            </div>
        </div>

        {{-- Modal detail jadwal, dipakai kartu mobile --}}
        <div id="modalJadwalDetail"
            class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
            <div
                class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
                <div
                    class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                    <h3 id="modalJadwalDetailLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">
                        Detail Jadwal</h3>
                    <button type="button"
                        class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                        onclick="document.getElementById('modalJadwalDetail').classList.remove('open')"><i
                            class="bx bx-x"></i></button>
                </div>
                <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3" id="modalJadwalDetailBody"></div>
            </div>
        </div>

        @if($bisaUbah)
            {{-- Modal konfirmasi batalkan jadwal - satu instance dipakai bareng oleh semua baris/kartu --}}
            <x-modal-konfirmasi id="modalBatalkanJadwal" title="Batalkan Jadwal" icon="bx-error" icon-class="text-danger-text">
                <div class="bg-danger dark:bg-danger-dark rounded-[10px] py-3.5 px-4">
                    <div class="text-[13px] font-semibold text-[#c0392b]">
                        <i class="bx bx-error"></i> Batalkan jadwal ini? notif WA akan dikirim ke semua operator
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
        @endif
    </main>
@endsection

@push('scripts')
    <script>
        @if($bisaUbah)
            // Modal konfirmasi batalkan jadwal (komponen global modal-konfirmasi) - satu
            // instance dipakai bareng oleh semua baris tabel & kartu mobile, tinggal ganti
            // action form-nya ke URL jadwal yang mau dibatalkan tiap kali dibuka.
            function bukaBatalkanJadwal(url) {
                document.getElementById('formBatalkanJadwal').action = url;
                document.getElementById('inputAlasanBatalJadwal').value = '';
                bukaModalKonfirmasi('modalBatalkanJadwal');
            }
        @endif

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

            var keteranganIsLink = d.jadwalSelesai !== '1' && typeof d.keterangan === 'string' && d.keterangan.indexOf('http') === 0;
            var keteranganHrefSafe = (d.keterangan || '').replace(/"/g, '&quot;');
            var keteranganHtml = keteranganIsLink
                ? '<a href="' + keteranganHrefSafe + '" target="_blank" rel="noopener" class="text-primary hover:underline font-medium text-[13px] break-all inline-flex items-center gap-1">' + escapeHtml(d.keterangan) + ' <i class="bx bx-link-external text-xs"></i></a>'
                : '<p class="' + pClass + '">' + escapeHtml(d.keterangan) + '</p>';

            var html = '<div class="flex flex-wrap items-start gap-x-8 gap-y-2.5">' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-calendar"></i><div><label class="' + labelClass + '">Tanggal</label><p class="' + pClass + '">' + escapeHtml(d.tanggalFull) + '</p></div></div>' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-time-five"></i><div><label class="' + labelClass + '">Waktu</label><p class="' + pClass + '">' + escapeHtml(d.waktu) + '</p></div></div>' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-desktop"></i><div><label class="' + labelClass + '">Platform</label><p class="' + pClass + '">' + escapeHtml(d.platform) + '</p></div></div>' +
                '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-note"></i><div><label class="' + labelClass + '">Keterangan</label>' + keteranganHtml + '</div></div>' +
                (d.lokasiFisik ? '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-map"></i><div><label class="' + labelClass + '">Lokasi Fisik</label><p class="' + pClass + '">' + escapeHtml(d.lokasiFisik) + '</p></div></div>' : '') +
                '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-group"></i><div><label class="' + labelClass + '">Operator</label><p class="' + pClass + '">' + escapeHtml(d.operator) + '</p></div></div>';

            @if($bisaUbah)
                if (d.dibatalkan) {
                    html += '<div class="' + detailRowFullClass + '"><i class="bx bx-error-circle text-lg text-danger-text mt-px shrink-0"></i><div><label class="' + labelClass + '">Alasan Pembatalan</label><p class="text-danger-text m-0 font-medium text-[13px] break-words">' + escapeHtml(d.alasanBatal) + '</p></div></div>';
                }
            @else
                html += '<div class="' + detailRowFullClass + '"><i class="bx bxs-wrench text-lg text-primary mt-px shrink-0"></i><div><label class="' + labelClass + '">Alat yang Perlu Disiapkan</label><p class="' + pClass + '">' + escapeHtml(d.alat) + '</p></div></div>';
            @endif
            html += '</div>';

            document.getElementById('modalJadwalDetailBody').innerHTML = html;
            document.getElementById('modalJadwalDetail').classList.add('open');
        }

        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-open-jadwal-modal]');
            if (!el) return;
            var card = el.closest('.mobile-card');
            if (card) bukaModalJadwalMobile(card);
        });

        document.getElementById('modalJadwalDetail').addEventListener('click', function (e) {
            if (e.target.id === 'modalJadwalDetail') e.currentTarget.classList.remove('open');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.getElementById('modalJadwalDetail').classList.remove('open');
        });
    </script>
@endpush