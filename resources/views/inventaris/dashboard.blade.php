@extends('layouts.app')
@section('title', 'Dashboard Inventaris')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Dashboard</h1>
                <div style="display:flex;align-items:center;gap:6px;margin-top:4px;font-size:13px;color:var(--dark-grey);">
                    <i class="bx bx-building"></i>
                    <span>Semua Lokasi</span>
                </div>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="bx bxs-data"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $totalPeralatan }}</h3>
                    <p>Total Peralatan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="bx bxs-check-circle"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $totalTersedia }}</h3>
                    <p>Stok Tersedia</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon red"><i class="bx bxs-error"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $totalRusak }}</h3>
                    <p>Unit Rusak</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon yellow"><i class="bx bxs-time"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $totalMenunggu }}</h3>
                    <p>Menunggu Persetujuan</p>
                </div>
            </div>
        </div>

        <div class="chart-grid">
            {{-- Donut: komposisi stok --}}
            <div class="chart-card">
                <h3><i class="bx bx-doughnut-chart" style="color:var(--blue);"></i> Komposisi Stok</h3>
                <canvas id="chartStok"></canvas>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;font-size:12px;">
                    <span class="badge badge-active">Tersedia: {{ $totalTersedia }}</span>
                    <span class="badge badge-danger">Rusak: {{ $totalRusak }}</span>
                    <span class="badge badge-inactive">Tidak Tersedia: {{ $totalPeralatan - $totalTersedia }}</span>
                </div>
            </div>

            {{-- Peralatan kritis --}}
            <div class="chart-card">
                <h3><i class="bx bx-error" style="color:#f39c12;"></i> Peralatan Kritis
                    <span class="badge badge-warning" style="margin-left:4px;">Stok ≤ 2</span>
                </h3>
                @forelse($peralatanKritis as $p)
                    <div style="display:flex;align-items:center;justify-content:space-between;
                                                padding:10px 0;border-bottom:1px solid var(--grey);">
                        <div>
                            <div style="font-weight:500;font-size:13px;color:var(--dark);">{{ $p->nama_peralatan }}</div>
                            {{-- PERBAIKAN: Menampilkan nama gedung/lokasi asal peralatan --}}
                            <div style="font-size:12px;color:var(--dark-grey);">Gedung: {{ $p->gedung }}
                                {{ $p->lokasi_detail ? '(' . $p->lokasi_detail . ')' : '' }}
                            </div>
                        </div>
                        <span
                            class="badge {{ ($p->stok - ($p->rusak ?? 0) - ($p->perbaikan ?? 0)) == 0 ? 'badge-danger' : 'badge-warning' }}">
                            {{ ($p->stok - ($p->rusak ?? 0) - ($p->perbaikan ?? 0)) }} unit
                        </span>
                    </div>
                @empty
                    <div style="text-align:center;padding:90px 0;color:var(--dark-grey);">
                        <i class="bx bx-check-shield" style="font-size:32px;display:block;margin-bottom:8px;color:#1abc9c;"></i>
                        All stocks secured
                    </div>
                @endforelse
                <div style="padding-top:10px;">
                    <a href="{{ route('inventaris.peralatan.index') }}" style="font-size:13px;color:var(--blue);">
                        Lihat semua peralatan →
                    </a>
                </div>
            </div>

            {{-- Pengajuan peminjaman menunggu --}}
            <div class="chart-card" style="grid-column:span 3;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <h3 style="margin:0;">
                        <i class="bx bx-cart" style="color:var(--blue);"></i> Pengajuan Menunggu Persetujuan
                        @if($totalMenunggu > 0)
                            <span class="badge badge-warning" style="margin-left:6px;">{{ $totalMenunggu }}</span>
                        @endif
                    </h3>
                    <a href="{{ route('inventaris.peminjaman.index') }}" class="toolbar-btn primary"
                        style="height:32px;padding:0 12px;font-size:13px;">
                        Kelola Semua
                    </a>
                </div>

                @forelse($peminjamanMenunggu as $p)
                    <div style="background:var(--grey);border-radius:10px;padding:14px;margin-bottom:10px;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                            <div style="flex:1;">
                                <div style="font-weight:600;color:var(--dark);margin-bottom:4px;">
                                    {{ $p->user->nama_user }}
                                </div>
                                <div style="font-size:13px;color:var(--dark-grey);margin-bottom:6px;">
                                    {{ $p->keperluan }}
                                </div>
                                <div style="font-size:12px;color:var(--dark-grey);">
                                    <i class="bx bx-calendar"></i>
                                    {{ $p->tanggal_pinjam->format('d/m/Y') }} →
                                    {{ $p->tanggal_kembali_rencana->format('d/m/Y') }}
                                </div>
                                {{-- Item dari gedung ini saja --}}
                                <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
                                    @foreach($p->items as $item)
                                        <span class="badge badge-info">
                                            {{ $item->peralatan->nama_peralatan }} ({{ $item->peralatan->gedung }})
                                            x{{ $item->jumlah }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Aksi cepat --}}
                            <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
                                <form action="{{ route('inventaris.peminjaman.approve', $p->id_peminjaman) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="toolbar-btn success" style="height:34px;"
                                        onclick="return confirm('Setujui pengajuan dari {{ $p->user->nama_user }}?')">
                                        <i class="bx bx-check"></i> Setujui
                                    </button>
                                </form>
                                <button type="button" class="toolbar-btn danger" style="height:34px;"
                                    onclick="toggleTolak('tolak-{{ $p->id_peminjaman }}')">
                                    <i class="bx bx-x"></i> Tolak
                                </button>
                            </div>
                        </div>
                        {{-- Form tolak (tersembunyi) --}}
                        <div id="tolak-{{ $p->id_peminjaman }}"
                            style="display:none;margin-top:12px;border-top:1px solid var(--grey);padding-top:10px;">
                            <form action="{{ route('inventaris.peminjaman.reject', $p->id_peminjaman) }}" method="POST"
                                style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                @csrf
                                <input type="text" name="catatan_inventaris" class="form-input"
                                    placeholder="Alasan penolakan (wajib)" required style="flex:1;min-width:200px;height:36px;">
                                <button type="submit" class="toolbar-btn danger" style="height:36px;">Kirim Penolakan</button>
                                <button type="button" class="toolbar-btn neutral" style="height:36px;"
                                    onclick="toggleTolak('tolak-{{ $p->id_peminjaman }}')">Batal</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div style="text-align:center;padding:30px 0;color:var(--dark-grey);">
                        <i class="bx bx-cart-alt" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                        Tidak ada pengajuan yang menunggu
                    </div>
                @endforelse
            </div>

        </div>
    </main>
@endsection

@push('scripts')
    <script>
        new Chart(document.getElementById('chartStok'), {
            type: 'doughnut',
            data: {
                labels: ['Tersedia', 'Rusak', 'Tidak Tersedia'],
                datasets: [{
                    data: [{{ $totalTersedia }}, {{ $totalRusak }}, {{ $totalPeralatan - $totalTersedia - $totalRusak }}],
                    backgroundColor: ['#1abc9c', '#e74c3c', '#aaaaaa'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        function toggleTolak(id) {
            var el = document.getElementById(id);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    </script>
@endpush