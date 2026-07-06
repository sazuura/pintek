@extends('layouts.app')
@section('title', 'Dashboard Inventaris')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Dashboard</h1>
                <div class="flex items-center gap-1.5 mt-1 text-[13px] text-text-muted">
                    <i class="bx bx-building"></i>
                    <span>Semua Lokasi</span>
                </div>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] max-md:grid-cols-2 max-xs:!grid-cols-1 gap-4 mb-6">
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#e8f4fd] text-[#3C91E6]"><i class="bx bxs-data"></i></div>
                <div>
                    <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $totalPeralatan }}</h3>
                    <p class="text-[13px] text-text-muted m-0">Total Peralatan</p>
                </div>
            </div>
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#e6f9f0] text-[#1abc9c]"><i class="bx bxs-check-circle"></i></div>
                <div>
                    <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $totalTersedia }}</h3>
                    <p class="text-[13px] text-text-muted m-0">Stok Tersedia</p>
                </div>
            </div>
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#fdecea] text-[#e74c3c]"><i class="bx bxs-error"></i></div>
                <div>
                    <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $totalRusak }}</h3>
                    <p class="text-[13px] text-text-muted m-0">Unit Rusak</p>
                </div>
            </div>
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#fff4e5] text-[#f39c12]"><i class="bx bxs-time"></i></div>
                <div>
                    <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $totalMenunggu }}</h3>
                    <p class="text-[13px] text-text-muted m-0">Menunggu Persetujuan</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-4 mb-6">
            {{-- Donut: komposisi stok --}}
            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-4"><i class="bx bx-doughnut-chart text-primary"></i> Komposisi Stok</h3>
                <canvas id="chartStok" class="max-h-[240px]"></canvas>
                <div class="flex flex-wrap gap-2 mt-3 text-xs">
                    <x-badge variant="badge-active">Tersedia: {{ $totalTersedia }}</x-badge>
                    <x-badge variant="badge-danger">Rusak: {{ $totalRusak }}</x-badge>
                    <x-badge variant="badge-inactive">Tidak Tersedia: {{ $totalPeralatan - $totalTersedia }}</x-badge>
                </div>
            </div>

            {{-- Peralatan kritis --}}
            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-4"><i class="bx bx-error text-[#f39c12]"></i> Peralatan Kritis
                    <x-badge variant="badge-warning" class="ml-1">Stok ≤ 2</x-badge>
                </h3>
                @forelse($peralatanKritis as $p)
                    <div class="flex items-center justify-between py-2.5 border-b border-page-bg dark:border-page-bg-dark">
                        <div>
                            <div class="font-medium text-[13px] text-text dark:text-text-dark">{{ $p->nama_peralatan }}</div>
                            {{-- PERBAIKAN: Menampilkan nama gedung/lokasi asal peralatan --}}
                            <div class="text-xs text-text-muted">Gedung: {{ $p->gedung }}
                                {{ $p->lokasi_detail ? '(' . $p->lokasi_detail . ')' : '' }}
                            </div>
                        </div>
                        <x-badge :variant="($p->stok - ($p->rusak ?? 0) - ($p->perbaikan ?? 0)) == 0 ? 'badge-danger' : 'badge-warning'">
                            {{ ($p->stok - ($p->rusak ?? 0) - ($p->perbaikan ?? 0)) }} unit
                        </x-badge>
                    </div>
                @empty
                    <div class="text-center py-[90px] text-text-muted">
                        <i class="bx bx-check-shield text-3xl block mb-2 text-[#1abc9c]"></i>
                        All stocks secured
                    </div>
                @endforelse
                <div class="pt-2.5">
                    <a href="{{ route('inventaris.peralatan.index') }}" class="text-[13px] text-primary">
                        Lihat semua peralatan →
                    </a>
                </div>
            </div>

            {{-- Pengajuan peminjaman menunggu --}}
            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card col-span-3">
                <div class="flex items-center justify-between mb-3.5">
                    <h3 class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">
                        <i class="bx bx-briefcase text-primary"></i> Pengajuan Menunggu Persetujuan
                        @if($totalMenunggu > 0)
                            <x-badge variant="badge-warning" class="ml-1.5">{{ $totalMenunggu }}</x-badge>
                        @endif
                    </h3>
                    <a href="{{ route('inventaris.peminjaman.index') }}"
                        class="h-8 px-3 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-primary text-white">
                        Kelola Semua
                    </a>
                </div>

                @forelse($peminjamanMenunggu as $p)
                    <div class="bg-page-bg dark:bg-page-bg-dark rounded-[10px] p-3.5 mb-2.5">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div class="flex-1">
                                <div class="font-semibold text-text dark:text-text-dark mb-1">
                                    {{ $p->user->nama_user }}
                                </div>
                                <div class="text-[13px] text-text-muted mb-1.5">
                                    {{ $p->keperluan }}
                                </div>
                                <div class="text-xs text-text-muted">
                                    <i class="bx bx-calendar"></i>
                                    {{ $p->tanggal_pinjam->format('d/m/Y') }} →
                                    {{ $p->tanggal_kembali_rencana->format('d/m/Y') }}
                                </div>
                                {{-- Item dari gedung ini saja --}}
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($p->items as $item)
                                        <x-badge variant="badge-info">
                                            {{ $item->peralatan->nama_peralatan }} ({{ $item->peralatan->gedung }})
                                            x{{ $item->jumlah }}
                                        </x-badge>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Aksi cepat --}}
                            <div class="flex gap-2 items-center shrink-0">
                                <form action="{{ route('inventaris.peminjaman.approve', $p->id_peminjaman) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="h-[34px] px-3 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-[#1abc9c] text-white"
                                        onclick="return confirm('Setujui pengajuan dari {{ $p->user->nama_user }}?')">
                                        <i class="bx bx-check"></i> Setujui
                                    </button>
                                </form>
                                <button type="button"
                                    class="h-[34px] px-3 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white"
                                    onclick="toggleTolak('tolak-{{ $p->id_peminjaman }}')">
                                    <i class="bx bx-x"></i> Tolak
                                </button>
                            </div>
                        </div>
                        {{-- Form tolak (tersembunyi) --}}
                        <div id="tolak-{{ $p->id_peminjaman }}"
                            class="hidden mt-3 pt-2.5 border-t border-page-bg dark:border-page-bg-dark">
                            <form action="{{ route('inventaris.peminjaman.reject', $p->id_peminjaman) }}" method="POST"
                                class="flex gap-2 items-center flex-wrap">
                                @csrf
                                <input type="text" name="catatan_inventaris"
                                    class="flex-1 min-w-[200px] h-9 px-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans focus:border-primary focus:outline-none"
                                    placeholder="Alasan penolakan (wajib)" required>
                                <button type="submit"
                                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white">Kirim Penolakan</button>
                                <button type="button"
                                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark"
                                    onclick="toggleTolak('tolak-{{ $p->id_peminjaman }}')">Batal</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-[30px] text-text-muted">
                        <i class="bx bx-briefcase text-4xl block mb-2"></i>
                        Tidak ada pengajuan yang menunggu
                    </div>
                @endforelse
            </div>

        </div>
    </main>
@endsection

@push('scripts')
    <script>
        Chart.defaults.font.family = "'Poppins', sans-serif";
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
            el.classList.toggle('hidden');
        }
    </script>
@endpush
