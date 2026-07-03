@extends('layouts.app')
@section('title', 'Kelola Peminjaman')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Kelola Peminjaman</h1>
                <div style="display:flex;align-items:center;gap:6px;margin-top:4px;font-size:13px;color:var(--dark-grey);">
                    <i class="bx bx-building"></i> Semua Lokasi
                </div>
            </div>
        </div>

        <div class="content-toolbar">
            <form method="GET" action="{{ route('inventaris.peminjaman.index') }}" style="display:contents;">
                <select name="status" class="toolbar-select">
                    <option value="">Semua Status</option>
                    <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Menunggu</option>
                    <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="dikembalikan" {{ request('status') == 'dikembalikan' ? 'selected' : '' }}>Dikembalikan
                    </option>
                </select>
                <select name="id_user" class="toolbar-select">
                    <option value="">Semua Operator</option>
                    @foreach($operatorList as $op)
                        <option value="{{ $op->id_user }}" {{ request('id_user') == $op->id_user ? 'selected' : '' }}>
                            {{ $op->nama_user }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
                @if(request('status') || request('id_user'))
                    <a href="{{ route('inventaris.peminjaman.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i>
                        Reset</a>
                @endif
            </form>
        </div>

        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>Daftar Pengajuan</h3>
                <small style="color:var(--dark-grey);">Semua Peralatan</small>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th class="sortable">Pemohon <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile">Keperluan</th>
                            <th class="hide-mobile sortable">Tgl Pinjam <span class="sort-icon">⇅</span></th>
                            <th>Peralatan</th>
                            <th>Status</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peminjaman as $index => $p)
                            @php $uid = 'inv-pm-' . $p->id_peminjaman; @endphp
                            <tr class="accordion-row" data-target="{{ $uid }}">
                                <td>{{ $peminjaman->firstItem() + $index }}</td>
                                <td>
                                    <div style="font-weight:500;">{{ $p->user->nama_user }}</div>
                                    <div style="font-size:12px;color:var(--dark-grey);">{{ $p->user->nohp ?? '-' }}</div>
                                </td>
                                <td class="hide-mobile">{{ $p->keperluan }}</td>
                                <td class="hide-mobile">{{ $p->tanggal_pinjam->format('d/m/Y') }}</td>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        @foreach($p->items->filter(fn($i) => $i->peralatan->gedung) as $item)
                                            <span style="font-size:12px;">{{ $item->peralatan->nama_peralatan }}
                                                x{{ $item->jumlah }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="badge {{ $p->badge['class'] }}">{{ $p->badge['label'] }}</span>
                                        <i class="bx bx-chevron-down accordion-chevron"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-group">
                                        @if($p->isMenunggu())
                                            <form action="{{ route('inventaris.peminjaman.approve', $p->id_peminjaman) }}"
                                                method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn-icon success" title="Setujui"
                                                    onclick="return confirm('Setujui pengajuan ini?')">
                                                    <i class="bx bx-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn-icon delete" title="Tolak"
                                                onclick="toggleTolak('tolak-{{ $p->id_peminjaman }}')">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        @elseif($p->isDisetujui())
                                            <form action="{{ route('inventaris.peminjaman.kembali', $p->id_peminjaman) }}"
                                                method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn-icon view" title="Konfirmasi Kembali"
                                                    onclick="return confirm('Konfirmasi peralatan sudah dikembalikan?')">
                                                    <i class="bx bx-revision"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- Accordion detail --}}
                            <tr class="accordion-detail" id="{{ $uid }}">
                                <td colspan="7">
                                    <div class="accordion-detail-inner">
                                        <div class="detail-item">
                                            <label>Pemohon</label>
                                            <p>{{ $p->user->nama_user }} · {{ $p->user->nohp ?? '-' }}</p>
                                        </div>
                                        <div class="detail-item">
                                            <label>Keperluan</label>
                                            <p>{{ $p->keperluan }}</p>
                                        </div>
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
                                            <label>Catatan</label>
                                            <p>{{ $p->catatan_inventaris ?? '-' }}</p>
                                        </div>
                                        @if($p->penjadwalan)
                                            <div class="detail-item">
                                                <label>Terkait Jadwal</label>
                                                <p>{{ $p->penjadwalan->judul_kegiatan }} ({{ $p->penjadwalan->tanggal->format('d/m/Y') }})</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Form tolak inline --}}
                                    <div id="tolak-{{ $p->id_peminjaman }}"
                                        style="display:none;padding:12px 16px;border-top:1px solid var(--grey);background:var(--grey);">
                                        <form action="{{ route('inventaris.peminjaman.reject', $p->id_peminjaman) }}"
                                            method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                            @csrf
                                            <input type="text" name="catatan_inventaris" class="form-input"
                                                placeholder="Alasan penolakan (wajib)" required
                                                style="flex:1;min-width:200px;height:36px;">
                                            <button type="submit" class="toolbar-btn danger" style="height:36px;">
                                                <i class="bx bx-x"></i> Tolak
                                            </button>
                                            <button type="button" class="toolbar-btn neutral" style="height:36px;"
                                                onclick="toggleTolak('tolak-{{ $p->id_peminjaman }}')">Batal</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--dark-grey);">
                                    <i class="bx bx-cart-alt" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                    Tidak ada pengajuan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$peminjaman">{{ $peminjaman->total() }} total pengajuan</x-pagination>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        function toggleTolak(id) {
            var el = document.getElementById(id);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    </script>
@endpush