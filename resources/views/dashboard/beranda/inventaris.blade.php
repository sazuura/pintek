@extends('layouts.app')
@section('title', 'Dashboard Inventaris')
@section('sidebar-menu') <x-sidebar /> @endsection

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

        <div data-skel class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] max-md:grid-cols-2 max-xs:!grid-cols-1 gap-4 mb-6">
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#e8f4fd] dark:bg-[#3C91E6]/15 text-[#3C91E6]"><i class="bx bxs-data"></i></div>
                <div>
                    <h2 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $totalPeralatan }}</h2>
                    <p class="text-[13px] text-text-muted m-0">Total Peralatan</p>
                </div>
            </div>
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#e6f9f0] dark:bg-[#1abc9c]/15 text-[#1abc9c]"><i class="bx bxs-check-circle"></i></div>
                <div>
                    <h2 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $totalTersedia }}</h2>
                    <p class="text-[13px] text-text-muted m-0">Stok Tersedia</p>
                </div>
            </div>
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#fdecea] dark:bg-[#e74c3c]/15 text-[#e74c3c]"><i class="bx bxs-error"></i></div>
                <div>
                    <h2 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $totalRusak }}</h2>
                    <p class="text-[13px] text-text-muted m-0">Unit Rusak</p>
                </div>
            </div>
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#fff4e5] dark:bg-[#f39c12]/15 text-[#f39c12]"><i class="bx bxs-time"></i></div>
                <div>
                    <h2 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $totalMenunggu }}</h2>
                    <p class="text-[13px] text-text-muted m-0">Menunggu Persetujuan</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-6 lg:h-[640px]">

            <div class="lg:col-span-5 flex flex-col gap-4 lg:h-full lg:min-h-0">

                <div data-skel class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card shrink-0">
                    <h2 class="text-[15px] font-semibold text-text dark:text-text-dark mb-4"><i class="bx bx-doughnut-chart text-primary"></i> Komposisi Stok</h2>
                    <canvas id="chartStok" class="max-h-[260px]"></canvas>
                </div>

                <div data-skel class="bg-surface dark:bg-surface-dark rounded-xl shadow-card flex-1 lg:min-h-0 flex flex-col overflow-hidden">
                    <div class="p-5 pb-3.5 shrink-0 flex items-start justify-between gap-3 flex-wrap">
                        <h2 class="text-[15px] font-semibold text-text dark:text-text-dark m-0"><i class="bx bx-error text-[#f39c12]"></i> Stok Sisa Sedikit
                            <x-badge variant="badge-warning" class="ml-1">Stok ≤ 2</x-badge>
                        </h2>
                        <a href="{{ route('inventaris.peralatan.index') }}" class="text-[13px] font-medium text-primary inline-flex items-center gap-1.5 shrink-0">Lihat semua peralatan <i class="bx bx-right-arrow-alt"></i></a>
                    </div>
                    <div class="flex-1 lg:min-h-0 lg:overflow-y-auto custom-scrollbar px-5 pb-4 flex flex-col gap-2.5 border-t border-gray-300 dark:border-gray-700 pt-3.5">
                        @forelse($peralatanKritis as $p)
                            @php $sisaStok = $p->stok - ($p->rusak ?? 0); @endphp
                            <div class="border border-gray-300 dark:border-gray-700 rounded-xl p-3 flex items-center gap-3 shadow-sm">
                                <x-foto-item :path="$p->foto" :alt="$p->nama_peralatan" icon="bx-package"
                                    img-class="w-11 h-11 rounded-lg object-cover shrink-0"
                                    icon-wrap-class="w-11 h-11 rounded-lg bg-primary-50 dark:bg-[#0d2a40] text-primary flex items-center justify-center text-xl shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-[13px] text-text dark:text-text-dark truncate">{{ $p->nama_peralatan }}</div>
                                    <div class="text-xs text-text-muted truncate">{{ $p->gedung }}</div>
                                    @if($p->lokasi_detail)
                                        <div class="text-xs text-text-muted truncate flex items-center gap-1"><i class="bx bx-map"></i> {{ $p->lokasi_detail }}</div>
                                    @endif
                                </div>
                                <div class="w-px self-stretch bg-gray-300 dark:bg-gray-700 shrink-0"></div>
                                <div class="text-center shrink-0">
                                    <div class="text-[10px] uppercase tracking-[0.4px] text-text-muted mb-1">Sisa Stok</div>
                                    <x-badge :variant="$sisaStok == 0 ? 'badge-danger' : 'badge-warning'" class="text-sm font-bold">
                                        {{ $sisaStok }} unit
                                    </x-badge>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-[90px] text-text-muted">
                                <i class="bx bx-check-shield text-3xl block mb-2 text-[#1abc9c]"></i>
                                All stocks secured
                            </div>
                        @endforelse
                    </div>
                    <div class="shrink-0 h-3 bg-surface dark:bg-surface-dark"></div>
                </div>
            </div>

            <div class="lg:col-span-7 lg:h-full lg:min-h-0">
                <div data-skel class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card lg:h-full flex flex-col">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3.5 shrink-0">
                        <h2 class="m-0 sm:flex-1 sm:min-w-0 text-[15px] font-semibold text-text dark:text-text-dark">
                            <i class="bx bx-briefcase text-primary"></i> Pengajuan Menunggu Persetujuan
                            @if($totalMenunggu > 0)
                                <x-badge variant="badge-warning" class="ml-1.5">{{ $totalMenunggu }}</x-badge>
                            @endif
                        </h2>
                        <a href="{{ route('inventaris.peminjaman.index') }}"
                            class="shrink-0 whitespace-nowrap text-[13px] font-medium text-primary inline-flex items-center gap-1.5">
                            Kelola Semua <i class="bx bx-right-arrow-alt"></i>
                        </a>
                    </div>

                    <div class="flex-1 lg:min-h-0 lg:overflow-y-auto custom-scrollbar pr-1">

                        <div class="max-xs:hidden">
                            @forelse($peminjamanMenunggu as $p)
                                @php
                                    $gedungList = $p->items->map(fn($item) => $item->peralatan->gedung ?? '-')->unique()->join(', ');
                                    $mulai  = $p->tanggal_pinjam;
                                    $selesai = $p->tanggal_kembali_rencana;
                                    $rentangTanggal = ($mulai->month === $selesai->month && $mulai->year === $selesai->year)
                                        ? $mulai->translatedFormat('d') . ' - ' . $selesai->translatedFormat('d F Y')
                                        : $mulai->translatedFormat('d F Y') . ' - ' . $selesai->translatedFormat('d F Y');
                                @endphp
                                <div class="bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 rounded-[14px] p-4 mb-3 flex gap-4 items-stretch shadow-sm">

                                    <div class="min-w-0 basis-[38%] flex flex-col justify-center">
                                        <h3 class="text-[15px] font-bold text-text dark:text-text-dark m-0 mb-1.5 leading-snug">{{ $p->keperluan }}</h3>
                                        <div class="text-xs text-text-muted mb-1.5">
                                            <span class="font-medium text-text dark:text-text-dark">{{ $p->user->nama_user }}</span>
                                        </div>
                                        <div class="text-xs text-text-muted flex flex-col gap-1">
                                            <span class="flex items-center gap-1.5"><i class="bx bx-calendar"></i> {{ $rentangTanggal }}</span>
                                        </div>
                                    </div>

                                    <div class="w-px self-stretch bg-gray-300 dark:bg-gray-700 shrink-0"></div>

                                    <div class="min-w-0 basis-[62%] flex flex-col">
                                        <div class="flex flex-col gap-2 flex-1">
                                            @foreach($p->items as $item)
                                                <div class="flex items-center gap-2.5">
                                                    <x-foto-item :path="$item->peralatan->foto" :alt="$item->peralatan->nama_peralatan" icon="bx-package"
                                                        img-class="w-9 h-9 rounded-lg object-cover shrink-0"
                                                        icon-wrap-class="w-9 h-9 rounded-lg bg-page-bg dark:bg-page-bg-dark text-text-muted flex items-center justify-center text-lg shrink-0" />
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-[13px] font-semibold text-text dark:text-text-dark truncate">{{ $item->peralatan->nama_peralatan }}</div>
                                                        <div class="text-[11px] text-text-muted truncate">{{ $item->peralatan->gedung ?? '-' }}</div>
                                                    </div>
                                                    <span class="text-xs font-semibold text-text-muted shrink-0">{{ $item->jumlah }} unit</span>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="flex justify-end gap-2 mt-3 pt-3 border-t border-gray-300 dark:border-gray-700">
                                            <button type="button"
                                                class="h-8 px-3 rounded-lg border border-success-text/30 text-xs font-sans cursor-pointer inline-flex items-center justify-center gap-1.5 font-semibold transition-opacity duration-200 hover:opacity-85 bg-success dark:bg-success-dark text-success-text"
                                                onclick="bukaKonfirmasiSetujui('{{ route('inventaris.peminjaman.approve', $p->id_peminjaman) }}', '{{ addslashes($p->user->nama_user) }}')">
                                                Setujui
                                            </button>
                                            <button type="button"
                                                class="h-8 px-3 rounded-lg border border-danger-text/30 text-xs font-sans cursor-pointer inline-flex items-center justify-center gap-1.5 font-semibold transition-opacity duration-200 hover:opacity-85 bg-danger dark:bg-danger-dark text-danger-text"
                                                onclick="bukaKonfirmasiTolak('{{ route('inventaris.peminjaman.reject', $p->id_peminjaman) }}')">
                                                Tolak
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-[30px] text-text-muted">
                                    <i class="bx bx-briefcase text-4xl block mb-2"></i>
                                    Tidak ada pengajuan yang menunggu
                                </div>
                            @endforelse
                        </div>

                        <div class="hidden max-xs:flex flex-col gap-3">
                            @forelse($peminjamanMenunggu as $p)
                                @php
                                    $gedungList = $p->items->map(fn($item) => $item->peralatan->gedung ?? '-')->unique()->join(', ');
                                    $mulai  = $p->tanggal_pinjam;
                                    $selesai = $p->tanggal_kembali_rencana;
                                    $rentangTanggal = ($mulai->month === $selesai->month && $mulai->year === $selesai->year)
                                        ? $mulai->translatedFormat('d') . ' - ' . $selesai->translatedFormat('d F Y')
                                        : $mulai->translatedFormat('d F Y') . ' - ' . $selesai->translatedFormat('d F Y');
                                @endphp
                                <div class="bg-page-bg dark:bg-page-bg-dark rounded-xl p-4 flex flex-col gap-3.5"
                                    data-judul="{{ $p->keperluan }}"
                                    data-peminjam="{{ $p->user->nama_user }}"
                                    data-tanggal="{{ $rentangTanggal }}"
                                    data-lokasi="{{ $gedungList }}"
                                    data-items='@json($p->items->map(fn($item) => ["nama" => $item->peralatan->nama_peralatan ?? "-", "gedung" => $item->peralatan->gedung ?? "-", "jumlah" => $item->jumlah]))'>
                                    <div class="flex items-center gap-3 cursor-pointer" data-open-pengajuan-modal>
                                        <div class="w-10 h-10 rounded-lg bg-primary-50 dark:bg-[#0d2a40] text-primary flex items-center justify-center text-lg shrink-0">
                                            <i class="bx bx-briefcase"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-sm text-text dark:text-text-dark truncate">{{ $p->keperluan }}</div>
                                            <div class="text-xs text-text-muted truncate">{{ $p->user->nama_user }}</div>
                                        </div>
                                        <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                                    </div>
                                    <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                                            <span><i class="bx bx-calendar"></i> Tanggal</span>
                                            <span class="text-text dark:text-text-dark font-medium text-right">{{ $rentangTanggal }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-[13px] text-text-muted gap-2">
                                            <span class="shrink-0"><i class="bx bx-map"></i> Lokasi</span>
                                            <span class="text-text dark:text-text-dark font-medium text-right truncate">{{ $gedungList }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                                            <span><i class="bx bx-package"></i> Alat</span>
                                            <span class="text-text dark:text-text-dark font-medium">{{ $p->items->count() }} jenis</span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                                        <button type="button"
                                            class="flex-1 h-9 px-3 rounded-lg border border-success-text/30 text-[13px] font-sans cursor-pointer inline-flex items-center justify-center gap-1.5 font-semibold transition-opacity duration-200 hover:opacity-85 bg-success dark:bg-success-dark text-success-text"
                                            onclick="bukaKonfirmasiSetujui('{{ route('inventaris.peminjaman.approve', $p->id_peminjaman) }}', '{{ addslashes($p->user->nama_user) }}')">
                                            <i class="bx bx-check"></i> Setujui
                                        </button>
                                        <button type="button"
                                            class="flex-1 h-9 px-3 rounded-lg border border-danger-text/30 text-[13px] font-sans cursor-pointer inline-flex items-center justify-center gap-1.5 font-semibold transition-opacity duration-200 hover:opacity-85 bg-danger dark:bg-danger-dark text-danger-text"
                                            onclick="bukaKonfirmasiTolak('{{ route('inventaris.peminjaman.reject', $p->id_peminjaman) }}')">
                                            <i class="bx bx-x"></i> Tolak
                                        </button>
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
                </div>
            </div>

        </div>

        <div id="modalDetailPengajuan"
            class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
            <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
                <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                    <h2 id="modalDetailPengajuanLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">Detail Pengajuan</h2>
                    <button type="button"
                        class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                        onclick="document.getElementById('modalDetailPengajuan').classList.remove('open')"><i class="bx bx-x"></i></button>
                </div>
                <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3" id="modalDetailPengajuanBody"></div>
            </div>
        </div>

        <x-modal-konfirmasi id="modalKonfirmasiSetujui" title="Setujui Pengajuan" icon="">
            <div class="bg-success dark:bg-success-dark rounded-[10px] py-3.5 px-4">
                <div class="text-[13px] font-semibold text-success-text">
                    Setujui pengajuan dari <span id="namaPeminjamSetujui" class="font-bold"></span>?
                </div>
            </div>
            <form id="formKonfirmasiSetujui" method="POST">
                @csrf
                <div class="flex justify-end gap-2.5 mt-3">
                    <button type="button" data-modal-close
                        class="h-9 px-3.5 rounded-lg bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                    <button type="submit"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-success-text text-white">
                        Setujui
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        Chart.defaults.font.family = "'Poppins', sans-serif";
        var isDark = document.documentElement.classList.contains('dark');
        var labelTextColor = isDark ? '#FBFBFB' : '#342E37';

        var doughnutLeaderLabels = {
            id: 'doughnutLeaderLabels',
            afterDraw: function (chart) {
                var dataset = chart.data.datasets[0];
                var ctx = chart.ctx;

                chart.getDatasetMeta(0).data.forEach(function (arc, i) {
                    var value = dataset.data[i];
                    if (!value) return;

                    var midAngle = (arc.startAngle + arc.endAngle) / 2;
                    var sinA = Math.sin(midAngle), cosA = Math.cos(midAngle);
                    var cx = arc.x, cy = arc.y, outerR = arc.outerRadius;

                    var p1 = { x: cx + cosA * (outerR + 4), y: cy + sinA * (outerR + 4) };
                    var p2 = { x: cx + cosA * (outerR + 18), y: cy + sinA * (outerR + 18) };
                    var arahKanan = cosA >= 0;
                    var p3 = { x: p2.x + (arahKanan ? 14 : -14), y: p2.y };

                    ctx.save();
                    ctx.strokeStyle = dataset.backgroundColor[i];
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.moveTo(p1.x, p1.y);
                    ctx.lineTo(p2.x, p2.y);
                    ctx.lineTo(p3.x, p3.y);
                    ctx.stroke();

                    ctx.fillStyle = labelTextColor;
                    ctx.font = '600 11px Poppins, sans-serif';
                    ctx.textBaseline = 'middle';
                    ctx.textAlign = arahKanan ? 'left' : 'right';
                    ctx.fillText(String(value), p3.x + (arahKanan ? 4 : -4), p3.y - 6);
                    ctx.restore();
                });
            }
        };

        var warnaSegmen = isDark ? ['#0f9b82', '#c0392b', '#6b7280'] : ['#1abc9c', '#e74c3c', '#aaaaaa'];

        var chartStok = new Chart(document.getElementById('chartStok'), {
            type: 'doughnut',
            data: {
                labels: ['Tersedia', 'Rusak', 'Tidak Tersedia'],
                datasets: [{
                    data: [{{ $totalTersedia }}, {{ $totalRusak }}, {{ $totalPeralatan - $totalTersedia }}],
                    backgroundColor: warnaSegmen,
                    borderWidth: 0,
                    spacing: 3,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '62%',
                layout: { padding: 36 },
                plugins: {
                    legend: { position: 'bottom', labels: { color: labelTextColor, boxWidth: 10, boxHeight: 10, padding: 24 } }
                }
            },
            plugins: [doughnutLeaderLabels]
        });

        new MutationObserver(function () {
            isDark = document.documentElement.classList.contains('dark');
            labelTextColor = isDark ? '#FBFBFB' : '#342E37';
            warnaSegmen = isDark ? ['#0f9b82', '#c0392b', '#6b7280'] : ['#1abc9c', '#e74c3c', '#aaaaaa'];

            chartStok.data.datasets[0].backgroundColor = warnaSegmen;
            chartStok.options.plugins.legend.labels.color = labelTextColor;
            chartStok.update();
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        function bukaKonfirmasiSetujui(url, nama) {
            document.getElementById('formKonfirmasiSetujui').action = url;
            document.getElementById('namaPeminjamSetujui').textContent = nama;
            bukaModalKonfirmasi('modalKonfirmasiSetujui');
        }

        function bukaKonfirmasiTolak(url) {
            document.getElementById('formKonfirmasiTolak').action = url;
            document.getElementById('inputAlasanTolak').value = '';
            bukaModalKonfirmasi('modalKonfirmasiTolak');
        }

        function escapeHtmlInventaris(str) {
            var div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        function bukaModalDetailPengajuan(card) {
            var d = card.dataset;
            document.getElementById('modalDetailPengajuanLabel').textContent = d.judul;

            var items = [];
            try { items = JSON.parse(d.items || '[]'); } catch (e) {}

            var itemsHtml = items.map(function (item) {
                return '<div class="flex items-center justify-between py-2.5 border-b border-page-bg dark:border-page-bg-dark last:border-b-0">' +
                    '<div class="font-medium text-[13px] text-text dark:text-text-dark">' + escapeHtmlInventaris(item.nama) + '</div>' +
                    '<span class="text-sm font-semibold text-text dark:text-text-dark">x' + escapeHtmlInventaris(item.jumlah) + '</span>' +
                    '</div>';
            }).join('');

            var detailRowClass = 'flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]';
            var iconClass = 'bx text-lg text-primary mt-px shrink-0';
            var labelClass = 'text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5';
            var pClass = 'text-text dark:text-text-dark m-0 font-medium text-[13px] break-words';

            var html = '<div class="flex flex-wrap items-start gap-x-8 gap-y-2.5">' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-user"></i><div><label class="' + labelClass + '">Peminjam</label><p class="' + pClass + '">' + escapeHtmlInventaris(d.peminjam) + '</p></div></div>' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-calendar"></i><div><label class="' + labelClass + '">Tanggal</label><p class="' + pClass + '">' + escapeHtmlInventaris(d.tanggal) + '</p></div></div>' +
                '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-map"></i><div><label class="' + labelClass + '">Lokasi</label><p class="' + pClass + '">' + escapeHtmlInventaris(d.lokasi) + '</p></div></div>' +
                '</div>' +
                '<div class="pt-3.5 mt-1 border-t border-page-bg dark:border-page-bg-dark">' +
                '<div class="text-[13px] font-semibold text-text dark:text-text-dark mb-1.5"><i class="bx bx-package text-primary"></i> Daftar Alat</div>' +
                itemsHtml +
                '</div>';

            document.getElementById('modalDetailPengajuanBody').innerHTML = html;
            document.getElementById('modalDetailPengajuan').classList.add('open');
        }

        document.querySelectorAll('[data-open-pengajuan-modal]').forEach(function (el) {
            el.addEventListener('click', function () {
                bukaModalDetailPengajuan(el.closest('[data-judul]'));
            });
        });

        document.getElementById('modalDetailPengajuan').addEventListener('click', function (e) {
            if (e.target.id === 'modalDetailPengajuan') e.target.classList.remove('open');
        });
    </script>
@endpush
