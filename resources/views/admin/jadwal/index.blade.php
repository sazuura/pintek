@extends('layouts.app')
@section('title', 'Data Jadwal')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Data Jadwal Rapat</h1>
            </div>
            <a href="{{ route('admin.jadwal.create') }}" class="btn-download">
                <i class="bx bx-plus"></i><span class="text">Tambah Jadwal</span>
            </a>
        </div>

        <div class="content-toolbar">
            <form method="GET" action="{{ route('admin.jadwal.index') }}" style="display:contents;">
                <div class="toolbar-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="search" placeholder="Cari judul, platform..." value="{{ request('search') }}">
                </div>
                <select name="platform" class="toolbar-select">
                    <option value="">Semua Platform</option>
                    <option value="Online" {{ request('platform') == 'Online' ? 'selected' : '' }}>Online</option>
                    <option value="Offline" {{ request('platform') == 'Offline' ? 'selected' : '' }}>Offline</option>
                </select>
                <select name="status" class="toolbar-select">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
                @if(request()->hasAny(['search', 'platform', 'status']))
                    <a href="{{ route('admin.jadwal.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
        </div>

        <div class="data-table-wrap table-desktop-only">
            <div class="data-table-head">
                <h3>Daftar Jadwal</h3>
                <small style="color:var(--dark-grey);">Tap baris untuk detail</small>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:32px;"></th>
                            <th style="width:40px;">#</th>
                            <th class="sortable">Judul Rapat <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile sortable">Tanggal <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile">Waktu</th>
                            <th class="hide-mobile">Platform</th>
                            <th>Status</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwal as $index => $j)
                            @php
                                $uid = 'jd-' . $j->id_penjadwalan;
                                $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                                $dibatalkan = $j->isDibatalkan();
                            @endphp

                            <tr class="accordion-row {{ $dibatalkan ? 'row-inactive' : '' }}" data-target="{{ $uid }}">
                                <td style="text-align:center;"><i class="bx bx-chevron-down accordion-chevron"></i></td>
                                <td>{{ $jadwal->firstItem() + $index }}</td>
                                <td>
                                    <div style="font-weight:500;">{{ $j->judul_kegiatan }}</div>
                                    <div style="font-size:12px;color:var(--dark-grey);margin-top:2px;">
                                        {{ $j->operators->count() }} operator
                                    </div>
                                </td>
                                <td class="hide-mobile">{{ $j->tanggal->translatedFormat('D, d M Y') }}</td>
                                <td class="hide-mobile">{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }}
                                </td>
                                <td class="hide-mobile">
                                    @if(str_contains($j->platform, 'Online'))
                                        <span class="badge badge-info"><i class="bx bx-wifi"></i> Online</span>
                                    @else
                                        <span class="badge badge-active"><i class="bx bx-building"></i> Offline</span>
                                    @endif
                                </td>
                                <td>
                                    @if($dibatalkan)
                                        <span class="badge badge-danger">
                                            <i class="bx bx-x-circle"></i> Dibatalkan
                                        </span>
                                    @elseif($sudahLewat)
                                        <span class="badge badge-active">
                                            <i class="bx bx-check-double"></i> Selesai
                                        </span>
                                    @else
                                        <span class="badge badge-info">
                                            <i class="bx bx-check-circle"></i> Aktif
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group">
                                        @if(!$dibatalkan)
                                            @if($sudahLewat)
                                                <a href="{{ route('admin.jadwal.show', $j->id_penjadwalan) }}" class="btn-icon view"><i
                                                        class="bx bx-show"></i></a>
                                            @else
                                                <a href="{{ route('admin.jadwal.edit', $j->id_penjadwalan) }}" class="btn-icon edit"><i
                                                        class="bx bx-edit"></i></a>
                                                <button type="button" class="btn-icon delete" title="Batalkan Jadwal"
                                                    onclick="bukaBatalkan('{{ $uid }}', 'batal-{{ $j->id_penjadwalan }}')">
                                                    <i class="bx bx-block"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Accordion detail --}}
                            <tr class="accordion-detail" id="{{ $uid }}">
                                <td colspan="8">
                                    <div class="detail-panel">
                                        <div class="detail-row">
                                            <i class="bx bx-calendar"></i>
                                            <div>
                                                <label>Tanggal</label>
                                                <p>{{ $j->tanggal->translatedFormat('l, d F Y') }}</p>
                                            </div>
                                        </div>
                                        <div class="detail-row">
                                            <i class="bx bx-time-five"></i>
                                            <div>
                                                <label>Waktu</label>
                                                <p>{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} -
                                                    {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB
                                                </p>
                                            </div>
                                        </div>
                                        <div class="detail-row">
                                            <i class="bx bx-desktop"></i>
                                            <div>
                                                <label>Platform</label>
                                                <p>{{ $j->platform }}</p>
                                            </div>
                                        </div>
                                        <div class="detail-row full">
                                            <i class="bx bx-note"></i>
                                            <div>
                                                <label>Keterangan</label>
                                                <p>{{ $j->keterangan ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="detail-row full">
                                            <i class="bx bx-group"></i>
                                            <div>
                                                <label>Operator</label>
                                                <p>{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}</p>
                                            </div>
                                        </div>
                                        @if($dibatalkan)
                                            <div class="detail-row full danger">
                                                <i class="bx bx-error-circle"></i>
                                                <div>
                                                    <label>Alasan Pembatalan</label>
                                                    <p>{{ $j->alasan_batal }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Form batalkan inline --}}
                                    @if(!$dibatalkan && !$sudahLewat)
                                        <div id="batal-{{ $j->id_penjadwalan }}" class="inline-confirm-form"
                                            style="display:none;padding:14px 16px;border-top:1px solid var(--grey);background:#fdecea;">
                                            <div style="font-size:13px;font-weight:600;color:#c0392b;margin-bottom:10px;">
                                                <i class="bx bx-error"></i> Batalkan Jadwal - notif WA akan dikirim ke semua
                                                operator
                                            </div>
                                            <form action="{{ route('admin.jadwal.batalkan', $j->id_penjadwalan) }}" method="POST"
                                                style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                                @csrf
                                                <input type="text" name="alasan_batal" class="form-input"
                                                    placeholder="Alasan pembatalan (wajib)" required
                                                    style="flex:1;min-width:200px;height:36px;">
                                                <button type="submit" class="toolbar-btn danger" style="height:36px;">
                                                    <i class="bx bx-block"></i> Batalkan
                                                </button>
                                                <button type="button" class="toolbar-btn neutral" style="height:36px;"
                                                    onclick="tutupBatalkan('batal-{{ $j->id_penjadwalan }}')">Batal</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:40px;color:var(--dark-grey);">
                                    <i class="bx bx-calendar-x" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                    Belum ada jadwal
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$jadwal" />
            </div>
        </div>

        {{-- Kartu jadwal - hanya tampil di mobile, tabel di atas tetap dipakai untuk tablet & desktop --}}
        <div class="mobile-card-list">
            @forelse($jadwal as $j)
                @php
                    $sudahLewat = \Carbon\Carbon::parse($j->tanggal->format('Y-m-d') . ' ' . $j->waktu_selesai)->isPast();
                    $dibatalkan = $j->isDibatalkan();
                @endphp
                <div class="mobile-card"
                     data-judul="{{ $j->judul_kegiatan }}"
                     data-tanggal-full="{{ $j->tanggal->translatedFormat('l, d F Y') }}"
                     data-waktu="{{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }} WIB"
                     data-platform="{{ $j->platform }}"
                     data-keterangan="{{ $j->keterangan ?? '-' }}"
                     data-operator="{{ $j->operators->pluck('nama_user')->join(', ') ?: '-' }}"
                     data-dibatalkan="{{ $dibatalkan ? '1' : '' }}"
                     data-alasan-batal="{{ $j->alasan_batal }}"
                     @if(!$dibatalkan && !$sudahLewat) data-batalkan-url="{{ route('admin.jadwal.batalkan', $j->id_penjadwalan) }}" @endif>
                    <div class="mobile-card-top" data-open-jadwal-modal>
                        <div class="mobile-card-info">
                            <div class="mobile-card-name">{{ $j->judul_kegiatan }}</div>
                            <div class="mobile-card-sub">{{ $j->operators->count() }} operator</div>
                        </div>
                        <i class="bx bx-chevron-right mobile-card-arrow"></i>
                    </div>
                    <div class="mobile-card-meta">
                        <div class="mobile-card-meta-row">
                            <span>Tanggal</span>
                            <span>{{ $j->tanggal->translatedFormat('D, d M Y') }}</span>
                        </div>
                        <div class="mobile-card-meta-row">
                            <span>Platform</span>
                            @if(str_contains($j->platform, 'Online'))
                                <span class="badge badge-info"><i class="bx bx-wifi"></i> Online</span>
                            @else
                                <span class="badge badge-active"><i class="bx bx-building"></i> Offline</span>
                            @endif
                        </div>
                        <div class="mobile-card-meta-row">
                            <span>Status</span>
                            @if($dibatalkan)
                                <span class="badge badge-danger"><i class="bx bx-x-circle"></i> Dibatalkan</span>
                            @elseif($sudahLewat)
                                <span class="badge badge-active"><i class="bx bx-check-double"></i> Selesai</span>
                            @else
                                <span class="badge badge-info"><i class="bx bx-check-circle"></i> Aktif</span>
                            @endif
                        </div>
                    </div>
                    @if(!$dibatalkan)
                        <div class="mobile-card-actions">
                            @if($sudahLewat)
                                <a href="{{ route('admin.jadwal.show', $j->id_penjadwalan) }}" class="btn-icon view"><i class="bx bx-show"></i></a>
                            @else
                                <a href="{{ route('admin.jadwal.edit', $j->id_penjadwalan) }}" class="btn-icon edit"><i class="bx bx-edit"></i></a>
                                <button type="button" class="btn-icon delete" data-open-jadwal-modal data-batalkan-trigger title="Batalkan Jadwal">
                                    <i class="bx bx-block"></i>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="data-table-wrap" style="padding:40px;text-align:center;color:var(--dark-grey);">
                    <i class="bx bx-calendar-x" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                    Belum ada jadwal
                </div>
            @endforelse
        </div>
        <div class="data-table-wrap mobile-pagination">
            <x-pagination :paginator="$jadwal" />
        </div>

        {{-- Modal detail jadwal, dipakai kartu mobile --}}
        <div id="modalJadwalDetail" class="detail-modal-overlay">
            <div class="detail-modal">
                <div class="detail-modal-header">
                    <h3 id="modalJadwalDetailLabel">Detail Jadwal</h3>
                    <button type="button" class="detail-modal-close" onclick="document.getElementById('modalJadwalDetail').classList.remove('open')"><i class="bx bx-x"></i></button>
                </div>
                <div class="detail-modal-body" id="modalJadwalDetailBody"></div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        // Buka baris detail (dropdown) sekaligus tampilkan form batalkan,
        // tanpa perlu klik dropdown-nya dulu.
        function bukaBatalkan(uid, formId) {
            document.querySelectorAll('tr.accordion-detail.open').forEach(function (d) {
                d.classList.remove('open');
                d.querySelectorAll('.inline-confirm-form').forEach(function (f) {
                    f.style.display = 'none';
                });
            });
            document.querySelectorAll('tr.accordion-row.open').forEach(function (r) {
                r.classList.remove('open');
            });

            var detail = document.getElementById(uid);
            var row    = document.querySelector('tr.accordion-row[data-target="' + uid + '"]');
            if (detail) detail.classList.add('open');
            if (row) row.classList.add('open');

            var form = document.getElementById(formId);
            if (form) form.style.display = 'block';
        }

        // Tutup form batalkan saja (baris detail tetap terbuka).
        function tutupBatalkan(formId) {
            var form = document.getElementById(formId);
            if (form) form.style.display = 'none';
        }

        // Modal detail jadwal untuk kartu mobile - isinya sama dengan dropdown detail di tabel desktop,
        // ditambah form batalkan kalau dibuka lewat tombol Batalkan.
        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function bukaModalJadwalMobile(card, tampilkanFormBatal) {
            var d = card.dataset;
            document.getElementById('modalJadwalDetailLabel').textContent = d.judul;

            var html = '<div class="detail-panel">' +
                '<div class="detail-row"><i class="bx bx-calendar"></i><div><label>Tanggal</label><p>' + escapeHtml(d.tanggalFull) + '</p></div></div>' +
                '<div class="detail-row"><i class="bx bx-time-five"></i><div><label>Waktu</label><p>' + escapeHtml(d.waktu) + '</p></div></div>' +
                '<div class="detail-row"><i class="bx bx-desktop"></i><div><label>Platform</label><p>' + escapeHtml(d.platform) + '</p></div></div>' +
                '<div class="detail-row full"><i class="bx bx-note"></i><div><label>Keterangan</label><p>' + escapeHtml(d.keterangan) + '</p></div></div>' +
                '<div class="detail-row full"><i class="bx bx-group"></i><div><label>Operator</label><p>' + escapeHtml(d.operator) + '</p></div></div>';

            if (d.dibatalkan) {
                html += '<div class="detail-row full danger"><i class="bx bx-error-circle"></i><div><label>Alasan Pembatalan</label><p>' + escapeHtml(d.alasanBatal) + '</p></div></div>';
            }
            html += '</div>';

            if (d.batalkanUrl) {
                html += '<div class="jadwal-cancel-form" id="jadwalCancelFormMobile"' + (tampilkanFormBatal ? '' : ' style="display:none;"') + '>' +
                    '<div class="jadwal-cancel-form-title"><i class="bx bx-error"></i> Batalkan Jadwal - notif WA akan dikirim ke semua operator</div>' +
                    '<form action="' + d.batalkanUrl + '" method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">' +
                        '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                        '<input type="text" name="alasan_batal" class="form-input" placeholder="Alasan pembatalan (wajib)" required style="flex:1;min-width:180px;height:36px;">' +
                        '<button type="submit" class="toolbar-btn danger" style="height:36px;"><i class="bx bx-block"></i> Batalkan</button>' +
                    '</form>' +
                '</div>';
            }

            document.getElementById('modalJadwalDetailBody').innerHTML = html;
            document.getElementById('modalJadwalDetail').classList.add('open');
        }

        document.querySelectorAll('[data-open-jadwal-modal]').forEach(function (el) {
            el.addEventListener('click', function () {
                var card = el.closest('.mobile-card');
                if (card) bukaModalJadwalMobile(card, el.hasAttribute('data-batalkan-trigger'));
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