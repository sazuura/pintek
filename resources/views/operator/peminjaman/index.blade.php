@extends('layouts.app')
@section('title', 'Peminjaman Saya')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Peminjaman Saya</h1>
            </div>
            <a href="{{ route('operator.peminjaman.create') }}" class="btn-download">
                <i class="bx bx-plus"></i><span class="text">Ajukan Peminjaman</span>
            </a>
        </div>

        <div class="content-toolbar">
            <form method="GET" action="{{ route('operator.peminjaman.index') }}" style="display:contents;">
                <select name="status" class="toolbar-select">
                    <option value="">Semua Status</option>
                    <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Menunggu</option>
                    <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="dikembalikan" {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>Dikembalikan
                    </option>
                    <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
                @if(request('status'))
                    <a href="{{ route('operator.peminjaman.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i>
                        Reset</a>
                @endif
            </form>
        </div>

        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>Riwayat Pengajuan</h3>
                <small style="color:var(--dark-grey);">Tap baris untuk detail</small>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th class="sortable">Keperluan <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile sortable">Tgl Pinjam <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile">Tgl Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peminjaman as $index => $p)
                            @php $uid = 'pm-' . $p->id_peminjaman; @endphp
                            <tr class="accordion-row {{ $p->isDibatalkan() ? 'row-inactive' : '' }}" data-target="{{ $uid }}">
                                <td>{{ $peminjaman->firstItem() + $index }}</td>
                                <td>
                                    <div style="font-weight:500;">{{ $p->keperluan }}</div>
                                    <div style="font-size:12px;color:var(--dark-grey);margin-top:2px;">
                                        {{ $p->items->count() }} item peralatan
                                    </div>
                                </td>
                                <td class="hide-mobile">{{ $p->tanggal_pinjam->format('d/m/Y') }}</td>
                                <td class="hide-mobile">{{ $p->tanggal_kembali_rencana->format('d/m/Y') }}</td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="badge {{ $p->badge['class'] }}">{{ $p->badge['label'] }}</span>
                                        <i class="bx bx-chevron-down accordion-chevron"></i>
                                    </div>
                                </td>
                            </tr>
                            <tr class="accordion-detail" id="{{ $uid }}">
                                <td colspan="5">
                                    <div class="accordion-detail-inner">
                                        <div class="detail-item">
                                            <label>Tanggal Pinjam</label>
                                            <p>{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}</p>
                                        </div>
                                        <div class="detail-item">
                                            <label>Rencana Kembali</label>
                                            <p>{{ $p->tanggal_kembali_rencana->translatedFormat('l, d F Y') }}</p>
                                        </div>
                                        @if($p->tanggal_kembali_aktual)
                                            <div class="detail-item">
                                                <label>Dikembalikan</label>
                                                <p>{{ $p->tanggal_kembali_aktual->translatedFormat('l, d F Y') }}</p>
                                            </div>
                                        @endif
                                        <div class="detail-item">
                                            <label>Peralatan</label>
                                            <p>{{ $p->items->map(fn($i) => $i->peralatan->nama_peralatan . ' (x' . $i->jumlah . ')')->join(', ') }}
                                            </p>
                                        </div>
                                        <div class="detail-item">
                                            <label>Catatan Inventaris</label>
                                            <p>{{ $p->catatan_inventaris ?? '-' }}</p>
                                        </div>
                                        @if($p->isDibatalkan())
                                            <div class="detail-item" style="grid-column:span 2;">
                                                <label>Alasan Pembatalan</label>
                                                <p style="color:#e74c3c;">{{ $p->alasan_batal }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Tombol batalkan — hanya jika masih diajukan --}}
                                    @if($p->isMenunggu())
                                        <div style="padding:12px 16px;border-top:1px solid var(--grey);">
                                            <button type="button" class="toolbar-btn danger" style="height:32px;font-size:12px;"
                                                onclick="toggleBatal('batal-pm-{{ $p->id_peminjaman }}')">
                                                <i class="bx bx-block"></i> Batalkan Pengajuan
                                            </button>
                                        </div>
                                        <div id="batal-pm-{{ $p->id_peminjaman }}"
                                            style="display:none;padding:12px 16px;background:#fdecea;border-top:1px solid var(--grey);">
                                            <div style="font-size:13px;font-weight:600;color:#c0392b;margin-bottom:10px;">
                                                <i class="bx bx-error"></i> Batalkan pengajuan — notif WA akan dikirim ke inventaris
                                            </div>
                                            <form action="{{ route('operator.peminjaman.batalkan', $p->id_peminjaman) }}"
                                                method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                                @csrf
                                                <input type="text" name="alasan_batal" class="form-input"
                                                    placeholder="Alasan pembatalan (wajib)" required
                                                    style="flex:1;min-width:200px;height:36px;">
                                                <button type="submit" class="toolbar-btn danger" style="height:36px;">
                                                    <i class="bx bx-block"></i> Konfirmasi Batalkan
                                                </button>
                                                <button type="button" class="toolbar-btn neutral" style="height:36px;"
                                                    onclick="toggleBatal('batal-pm-{{ $p->id_peminjaman }}')">Batal</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;padding:40px;color:var(--dark-grey);">
                                    <i class="bx bx-cart-alt" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                    Belum ada pengajuan peminjaman
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">
                <span>{{ $peminjaman->total() }} total pengajuan</span>
                <div class="pagination-links">
                    @if($peminjaman->onFirstPage())
                        <span class="page-link disabled"><i class="bx bx-chevron-left"></i></span>
                    @else
                        <a href="{{ $peminjaman->previousPageUrl() }}" class="page-link"><i class="bx bx-chevron-left"></i></a>
                    @endif
                    @foreach(range(1, $peminjaman->lastPage()) as $p)
                        <a href="{{ $peminjaman->url($p) }}"
                            class="page-link {{ $peminjaman->currentPage() == $p ? 'active' : '' }}">{{ $p }}</a>
                    @endforeach
                    @if($peminjaman->hasMorePages())
                        <a href="{{ $peminjaman->nextPageUrl() }}" class="page-link"><i class="bx bx-chevron-right"></i></a>
                    @else
                        <span class="page-link disabled"><i class="bx bx-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        function toggleBatal(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    </script>
@endpush