@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
        <div><h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Dashboard</h1></div>
    </div>

    @php
        $trendClass = 'absolute top-3.5 right-3.5 inline-flex items-center gap-0.5 text-[11px] font-bold py-[3px] px-2 rounded-full whitespace-nowrap cursor-default';
        $trendVariant = [
            'up'   => 'text-[#1abc9c] bg-[#e6f9f0] dark:bg-[#1abc9c]/15',
            'down' => 'text-[#e74c3c] bg-[#fdecea] dark:bg-[#e74c3c]/15',
            'flat' => 'text-text-muted bg-page-bg dark:bg-page-bg-dark',
        ];
    @endphp

    {{-- Statistik & chart di bawah ini mengikuti bulan yang lagi dipilih di kalender
         kanan - navigasi bulan/tahun di kalender otomatis memperbarui semuanya. Taruh di
         luar grid 2 kolom (bukan di dalam kolom kiri) supaya kolom kiri & kanan tetap
         sejajar dari atas. --}}
    <p class="text-[13px] text-text-muted mb-3 flex items-center gap-1.5">
        <i class="bx bx-calendar"></i> Menampilkan data:
        <span class="font-semibold text-text dark:text-text-dark">{{ $kalender['labelBulan'] }} {{ $kalender['tahun'] }}</span>
    </p>

    {{-- Layout asimetris: stat card + chart + peralatan lebih lebar di kiri, kalender + aktivitas ditumpuk di kanan --}}
    <div class="grid grid-cols-1 min-[1101px]:grid-cols-[14fr_7fr] gap-4 mb-4 items-start">
        <div class="flex flex-col gap-4 min-w-0">
            {{-- Stat cards --}}
            <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] max-md:grid-cols-2 max-xs:!grid-cols-1 gap-4">
                <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                    <span class="{{ $trendClass }} {{ $trendVariant[$trenRapat['arah']] ?? $trendVariant['flat'] }}" title="Dibanding bulan sebelumnya">
                        @if($trenRapat['arah'] === 'up') <i class="bx bx-up-arrow-alt text-[13px]"></i>
                        @elseif($trenRapat['arah'] === 'down') <i class="bx bx-down-arrow-alt text-[13px]"></i>
                        @else <i class="bx bx-minus text-[13px]"></i>
                        @endif
                        {{ $trenRapat['label'] }}
                    </span>
                    <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#f3e8ff] dark:bg-[#8b5cf6]/15 text-[#8b5cf6]"><i class="bx bxs-calendar-event"></i></div>
                    <div>
                        <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $jumlahRapatMendatang }}</h3>
                        <p class="text-[13px] text-text-muted m-0">Rapat Mendatang</p>
                    </div>
                </div>

                <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                    <span class="{{ $trendClass }} {{ $trendVariant['flat'] }} font-medium" title="Jumlah akun operator berstatus aktif dari total akun operator terdaftar">dari {{ $totalOperatorAkun }} akun</span>
                    <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#e8f4fd] dark:bg-[#3C91E6]/15 text-[#3C91E6]"><i class="bx bxs-group"></i></div>
                    <div>
                        <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $jumlahOperator }}</h3>
                        <p class="text-[13px] text-text-muted m-0">Operator Aktif</p>
                    </div>
                </div>

                <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                    <span class="{{ $trendClass }} {{ $trendVariant[$trenPeralatan['arah']] ?? $trendVariant['flat'] }}" title="Dibanding bulan sebelumnya">
                        @if($trenPeralatan['arah'] === 'up') <i class="bx bx-up-arrow-alt text-[13px]"></i>
                        @elseif($trenPeralatan['arah'] === 'down') <i class="bx bx-down-arrow-alt text-[13px]"></i>
                        @else <i class="bx bx-minus text-[13px]"></i>
                        @endif
                        {{ $trenPeralatan['label'] }}
                    </span>
                    <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#FFF0E5] dark:bg-[#FD7238]/15 text-[#FD7238]"><i class="bx bxs-wrench"></i></div>
                    <div>
                        <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $jumlahPeralatanDipinjam }}</h3>
                        <p class="text-[13px] text-text-muted m-0">Peralatan Dipinjam</p>
                    </div>
                </div>

                <div class="relative bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                    <span class="{{ $trendClass }} {{ $trendVariant[$trenJadwal['arah']] ?? $trendVariant['flat'] }}" title="Dibanding bulan sebelumnya">
                        @if($trenJadwal['arah'] === 'up') <i class="bx bx-up-arrow-alt text-[13px]"></i>
                        @elseif($trenJadwal['arah'] === 'down') <i class="bx bx-down-arrow-alt text-[13px]"></i>
                        @else <i class="bx bx-minus text-[13px]"></i>
                        @endif
                        {{ $trenJadwal['label'] }}
                    </span>
                    <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#e6f9f0] dark:bg-[#1abc9c]/15 text-[#1abc9c]"><i class="bx bxs-calendar-check"></i></div>
                    <div>
                        <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $jumlahJadwal }}</h3>
                        <p class="text-[13px] text-text-muted m-0">Total Jadwal</p>
                    </div>
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card flex flex-col">
                <div class="flex items-start justify-between flex-wrap gap-3">
                    <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-4"><i class="bx bx-line-chart text-primary"></i> Jadwal per Operator</h3>
                    <div class="group relative inline-flex gap-1 bg-page-bg dark:bg-page-bg-dark rounded-[11px] p-[3px] shrink-0" role="group" aria-label="Tipe grafik" data-active="line">
                        <span class="absolute top-[3px] left-[3px] w-8 h-[30px] bg-surface dark:bg-surface-dark rounded-lg shadow-[0_2px_6px_rgba(0,0,0,0.15)] transition-transform duration-[250ms] z-0 group-data-[active=line]:translate-x-9"></span>
                        <button type="button" class="relative z-[1] w-8 h-[30px] flex items-center justify-center border-none outline-none rounded-lg bg-transparent text-text-muted text-base cursor-pointer transition-colors duration-200 hover:text-text dark:hover:text-text-dark [&.active]:text-primary" data-chart-type="bar" title="Tampilan batang">
                            <i class="bx bx-bar-chart-alt-2"></i>
                        </button>
                        <button type="button" class="relative z-[1] w-8 h-[30px] flex items-center justify-center border-none outline-none rounded-lg bg-transparent text-text-muted text-base cursor-pointer transition-colors duration-200 hover:text-text dark:hover:text-text-dark [&.active]:text-primary active" data-chart-type="line" title="Tampilan garis">
                            <i class="bx bx-trending-up"></i>
                        </button>
                    </div>
                </div>
                <div class="relative h-[340px] shrink-0 max-wide:h-[300px] max-md:h-[280px] max-xs:h-[260px] [&>canvas]:!max-h-none">
                    <canvas id="chartOperator"></canvas>
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card flex flex-col">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-4"><i class="bx bx-wrench text-primary"></i> Peralatan Paling Sering Dipinjam</h3>
                @if($topPeralatan->isEmpty())
                    <div class="text-center py-10 px-2.5 text-text-muted">
                        <i class="bx bx-package text-3xl block mb-2"></i>
                        <p class="m-0 text-[13px]">Belum ada peralatan yang dipinjam</p>
                    </div>
                @else
                    <div class="relative h-[240px] shrink-0 max-md:h-[220px] max-xs:h-[200px] [&>canvas]:!max-h-none">
                        <canvas id="chartPeralatan"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4 min-w-0">
            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card flex flex-col">
                <div class="flex items-start flex-wrap gap-3 mb-2.5">
                    <div>
                        <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-0"><i class="bx bx-calendar-heart text-primary"></i> Kepadatan Jadwal Rapat</h3>
                        <small class="block text-text-muted text-xs mt-0.5">Klik tanggal untuk lihat jadwal rapatnya</small>
                    </div>
                </div>
                <div class="flex items-center gap-3.5 flex-wrap mb-2">
                    <span class="inline-flex items-center gap-1.5 text-xs text-text-muted whitespace-nowrap"><span class="w-3 h-3 rounded shrink-0 bg-blue-600"></span> Hari ini</span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-text-muted whitespace-nowrap"><span class="w-3 h-3 rounded shrink-0 bg-blue-200 dark:bg-blue-800"></span> Akan datang</span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-text-muted whitespace-nowrap"><span class="w-3 h-3 rounded shrink-0 bg-gray-200 dark:bg-gray-700"></span> Sudah lewat</span>
                </div>

                @php
                    $namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                @endphp
                <div class="flex items-center justify-center gap-[18px] mb-3.5 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                    <a href="{{ route('admin.dashboard', ['bulan' => $kalender['bulanSebelumnya']]) }}"
                        class="w-8 h-8 rounded-full bg-page-bg dark:bg-page-bg-dark flex items-center justify-center text-text-muted text-lg transition-[background-color,color,transform] duration-200 shrink-0 hover:bg-primary hover:text-white hover:scale-[1.06]"><i class="bx bx-chevron-left"></i></a>

                    <div class="relative">
                        <button type="button" class="bg-transparent border-none m-0 py-1 px-1.5 text-[15px] font-sans flex items-baseline gap-1.5 cursor-pointer rounded-md transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark" id="calendarTitleBtn">
                            <span class="text-text dark:text-text-dark font-bold">{{ $kalender['labelBulan'] }}</span>
                            <span class="text-primary font-bold">{{ $kalender['tahun'] }}</span>
                            <i class="bx bx-chevron-down self-center text-base text-text-muted"></i>
                        </button>
                        <div class="[&:not(.open)]:hidden [&.open]:flex items-start absolute top-[calc(100%+6px)] left-1/2 -translate-x-1/2 bg-surface dark:bg-surface-dark rounded-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.18)] p-3 gap-2 z-50 min-w-[220px]" id="calendarPicker">
                            <select id="pilihBulan" size="5" class="flex-1 min-w-0 h-auto text-[13px] p-1 overflow-y-auto border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark font-sans [&>option]:py-1.5 [&>option]:px-2 [&>option]:rounded-md">
                                @foreach($namaBulan as $i => $nama)
                                    <option value="{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}" {{ $kalender['bulanAngka'] == $i + 1 ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                            <select id="pilihTahun" size="5" class="w-[84px] shrink-0 h-auto text-[13px] p-1 overflow-y-auto border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark font-sans [&>option]:py-1.5 [&>option]:px-2 [&>option]:rounded-md">
                                @for($tahun = $kalender['tahunAngka'] - 6; $tahun <= $kalender['tahunAngka'] + 5; $tahun++)
                                    <option value="{{ $tahun }}" {{ $tahun == $kalender['tahunAngka'] ? 'selected' : '' }}>{{ $tahun }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <a href="{{ route('admin.dashboard', ['bulan' => $kalender['bulanBerikutnya']]) }}"
                        class="w-8 h-8 rounded-full bg-page-bg dark:bg-page-bg-dark flex items-center justify-center text-text-muted text-lg transition-[background-color,color,transform] duration-200 shrink-0 hover:bg-primary hover:text-white hover:scale-[1.06]"><i class="bx bx-chevron-right"></i></a>
                </div>

                <div class="overflow-x-hidden">
                    <table class="w-full border-separate border-spacing-1.5 max-xs:border-spacing-1 table-fixed">
                        <thead>
                            <tr>
                                @foreach($kalender['hariHeader'] as $hari)
                                    <th class="text-xs font-semibold text-text-muted text-center pb-2.5">{{ $hari }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kalender['minggu'] as $pekan)
                                <tr>
                                    @foreach($pekan as $hari)
                                        @php
                                            $adaJadwal = $hari['jumlah'] > 0;
                                            $warnaKelas = '';
                                            if ($adaJadwal && $hari['inBulan'] && !$hari['isHariIni']) {
                                                $warnaKelas = $hari['sudahLewat'] ? 'past' : 'upcoming';
                                            }

                                            $cellClass = 'relative w-full aspect-square max-h-[42px] wide:max-h-[43px] max-xs:max-h-[34px] rounded-xl max-xs:rounded-lg flex items-center justify-center text-[13px] max-xs:text-[11px] font-semibold text-text dark:text-text-dark mx-auto transition-transform duration-150';
                                            if ($hari['inBulan']) $cellClass .= ' cursor-pointer hover:scale-[1.08]';
                                            if (!$hari['inBulan']) $cellClass .= ' text-text-muted opacity-50';
                                            if ($warnaKelas === 'upcoming') $cellClass .= ' bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-200';
                                            if ($warnaKelas === 'past') $cellClass .= ' bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
                                            if ($hari['isHariIni']) $cellClass .= ' bg-blue-600 text-white shadow-[0_3px_10px_rgba(0,102,255,0.45)]';

                                            $badgeClass = 'absolute -bottom-1 -right-1 max-xs:-bottom-[3px] max-xs:-right-[3px] min-w-[16px] max-xs:min-w-[13px] h-4 max-xs:h-[13px] px-[3px] rounded-lg text-[9px] max-xs:text-[8px] font-bold flex items-center justify-center leading-none bg-blue-600 text-white shadow-[0_0_0_2px_var(--color-surface)] dark:shadow-[0_0_0_2px_var(--color-surface-dark)]';
                                        @endphp
                                        <td>
                                            <div class="{{ $cellClass }}"
                                                 @if($hari['inBulan'])
                                                     data-tanggal="{{ $hari['tanggalKey'] }}"
                                                     data-label="{{ \Carbon\Carbon::parse($hari['tanggalKey'])->translatedFormat('l, d F Y') }}"
                                                 @endif
                                                 @if($adaJadwal) data-tooltip="{{ $hari['jumlah'] }} jadwal rapat - klik untuk detail" @endif>
                                                <span>{{ $hari['tanggal'] }}</span>
                                                @if($adaJadwal)
                                                    <span class="{{ $badgeClass }}">{{ $hari['jumlah'] }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card flex flex-col">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-4"><i class="bx bx-bell text-primary"></i> Aktivitas Terbaru</h3>
                @if($activities->isEmpty())
                    <div class="text-center py-10 px-2.5 text-text-muted">
                        <i class="bx bx-moon text-3xl block mb-2"></i>
                        <p class="m-0 text-[13px]">Belum ada aktivitas dalam 14 hari terakhir</p>
                    </div>
                @else
                    <ul class="list-none m-0 p-0 flex flex-col gap-1 max-h-[260px] wide:max-h-[280px] overflow-y-auto custom-scrollbar">
                        @foreach($activities as $a)
                            <li class="flex items-start gap-3 py-2.5 px-1 border-b border-page-bg dark:border-page-bg-dark last:border-b-0">
                                <span class="w-[34px] h-[34px] wide:w-[35px] wide:h-[35px] rounded-[10px] bg-[#e8f4fd] dark:bg-[#3C91E6]/15 text-[#3C91E6] flex items-center justify-center shrink-0 text-base"><i class="bx {{ $a['icon'] }}"></i></span>
                                <div>
                                    <p class="m-0 mb-0.5 text-[13px] text-text dark:text-text-dark leading-[1.4]">{{ $a['text'] }}</p>
                                    <small class="text-[11px] text-text-muted">{{ \Carbon\Carbon::parse($a['time'])->diffForHumans() }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal: daftar jadwal pada tanggal yang diklik di kalender --}}
    <div id="modalJadwalTanggal" class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
        <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
            <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                <h3 id="modalJadwalTanggalLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">Jadwal Rapat</h3>
                <button type="button"
                    class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                    onclick="tutupModalJadwal()"><i class="bx bx-x"></i></button>
            </div>
            <div id="modalJadwalTanggalBody" class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3"></div>
        </div>
    </div>

    {{-- Tooltip kustom untuk sel kalender --}}
    <div id="calendarTooltip" class="fixed bg-text dark:bg-text-dark text-white dark:text-text py-1.5 px-2.5 rounded-md text-xs font-medium whitespace-nowrap shadow-[0_4px_14px_rgba(0,0,0,0.25)] z-[3000] pointer-events-none [&:not(.show)]:hidden [&.show]:block"></div>
</main>
@endsection

@push('scripts')
<script>
var isDark = document.documentElement.classList.contains('dark');
var gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
var labelColor = isDark ? '#94A3B8' : '#6B7280';

Chart.defaults.color = labelColor;
Chart.defaults.borderColor = gridColor;
Chart.defaults.font.family = "'Poppins', sans-serif";

// Line - jadwal per operator. Tiap titik operator diwarnai berbeda, garis penghubung memakai warna primary.
var operatorPalette = ['#0066FF', '#6366f1', '#06b6d4', '#8b5cf6', '#0ea5e9'];
var operatorLabels = @json($operatorChart->pluck('nama_user')).map(function (nama) {
    return nama.replace(/\s*\(Operator\)\s*$/i, '');
});
var operatorColors = operatorLabels.map(function (_, i) { return operatorPalette[i % operatorPalette.length]; });

// Toggle line <-> bar untuk chart Jadwal per Operator. Chart dibuat ulang (destroy + create) tiap
// ganti tipe, bukan cuma diganti config.type-nya - supaya Chart.js selalu pakai default ukuran
// bar/line yang benar untuk tipe itu (kalau cuma di-mutate, lebar bar suka jadi tidak konsisten).
var chartOperatorData = @json($operatorChart->pluck('jadwal_ditugaskan_count'));
var chartOperatorInstance = null;

function renderChartOperator(type) {
    if (chartOperatorInstance) chartOperatorInstance.destroy();

    var dataset = type === 'bar'
        ? {
            label: 'Jumlah Jadwal',
            data: chartOperatorData,
            backgroundColor: operatorColors,
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.55,
            categoryPercentage: 0.6
        }
        : {
            label: 'Jumlah Jadwal',
            data: chartOperatorData,
            borderColor: '#0066FF',
            backgroundColor: 'rgba(0,102,255,.12)',
            pointBackgroundColor: operatorColors,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
            tension: 0.35,
            fill: true
        };

    chartOperatorInstance = new Chart(document.getElementById('chartOperator'), {
        type: type,
        data: { labels: operatorLabels, datasets: [dataset] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { ticks: { autoSkip: false, maxRotation: 30, minRotation: 0 } }
            }
        }
    });
}

renderChartOperator('line');

var chartTypeToggle = document.querySelector('[role="group"][aria-label="Tipe grafik"]');
document.querySelectorAll('[data-chart-type]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('[data-chart-type]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        chartTypeToggle.dataset.active = btn.dataset.chartType;
        renderChartOperator(btn.dataset.chartType);
    });
});

// Bar horizontal - peralatan paling sering dipinjam. Warna primary sekuensial: makin gelap = makin
// sering dipinjam, jadi warnanya sendiri ikut menyampaikan info peringkat (bukan sekadar dekorasi).
// Angka juga dicetak langsung di ujung bar biar tidak perlu menerka-nerka lewat sumbu.
var chartPeralatanEl = document.getElementById('chartPeralatan');
if (chartPeralatanEl) {
    var peralatanData = @json($topPeralatan->pluck('total_dipinjam'));
    // Di dark mode gradiennya dipersempit ke rentang biru yang lebih redup (bukan sampai
    // primary-100 yang nyaris putih) supaya bar-nya tidak menyilaukan di atas background gelap.
    var peralatanBlueDark  = isDark ? [0, 41, 102]  : [0, 61, 153];    // #002966 (primary-800) / #003D99 (primary-700)
    var peralatanBlueLight = isDark ? [51, 133, 255] : [204, 224, 255]; // #3385FF (primary-400) / #CCE0FF (primary-100)

    var peralatanColors = peralatanData.map(function (_, i) {
        var t = peralatanData.length > 1 ? 1 - (i / (peralatanData.length - 1)) : 1;
        var rgb = peralatanBlueDark.map(function (c, ch) {
            return Math.round(peralatanBlueLight[ch] + (c - peralatanBlueLight[ch]) * t);
        });
        return 'rgb(' + rgb.join(',') + ')';
    });

    var valueLabelColor = isDark ? '#FBFBFB' : '#342E37';
    var barValueLabelPlugin = {
        id: 'barValueLabel',
        afterDatasetsDraw: function (chart) {
            var ctx = chart.ctx;
            chart.getDatasetMeta(0).data.forEach(function (bar, i) {
                ctx.save();
                ctx.fillStyle = valueLabelColor;
                ctx.font = '600 11px Poppins, sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(peralatanData[i], bar.x + 8, bar.y);
                ctx.restore();
            });
        }
    };

    new Chart(chartPeralatanEl, {
        type: 'bar',
        data: {
            labels: @json($topPeralatan->pluck('nama_peralatan')),
            datasets: [{
                label: 'Kali Dipinjam',
                data: peralatanData,
                backgroundColor: peralatanColors,
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.6,
                categoryPercentage: 0.7
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { right: 28 } },
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        },
        plugins: [barValueLabelPlugin]
    });
}

// Chart.js kadang tidak ikut resize otomatis saat viewport berubah drastis lewat DevTools device
// toolbar (ResizeObserver bawaannya kadang telat/tidak terpicu di mode emulasi) - paksa semua chart
// menghitung ulang ukurannya tiap window resize, bukan hanya mengandalkan mekanisme internalnya.
var chartResizeQueued = false;
window.addEventListener('resize', function () {
    if (chartResizeQueued) return;
    chartResizeQueued = true;
    requestAnimationFrame(function () {
        Object.values(Chart.instances).forEach(function (c) { c.resize(); });
        chartResizeQueued = false;
    });
});

// Modal jadwal per tanggal - data jadwal sebulan penuh sudah dikirim controller (tidak perlu request tambahan saat klik).
var jadwalPerTanggal = @json($kalender['detailPerTanggal']);

function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

function bukaModalJadwal(tanggalKey, label) {
    var daftar = jadwalPerTanggal[tanggalKey] || [];
    var body = document.getElementById('modalJadwalTanggalBody');
    document.getElementById('modalJadwalTanggalLabel').textContent = label;

    if (daftar.length === 0) {
        body.innerHTML = '<div class="text-center py-[30px] px-2.5 text-text-muted dark:text-text-muted"><i class="bx bx-calendar-x text-3xl block mb-2"></i><p class="m-0 text-[13px]">Tidak ada jadwal rapat di tanggal ini</p></div>';
    } else {
        body.innerHTML = daftar.map(function (j) {
            return '' +
                '<div class="bg-page-bg dark:bg-page-bg-dark rounded-[10px] p-3.5">' +
                    '<div class="flex items-center justify-between gap-2 mb-2">' +
                        '<strong class="text-[13px] text-text dark:text-text-dark">' + escapeHtml(j.judul) + '</strong>' +
                    '</div>' +
                    '<div class="flex flex-wrap gap-3.5 text-xs text-text-muted mt-1">' +
                        '<span><i class="bx bx-time-five mr-1"></i> ' + escapeHtml(j.waktu) + ' WIB</span>' +
                        '<span><i class="bx bx-desktop mr-1"></i> ' + escapeHtml(j.platform) + '</span>' +
                    '</div>' +
                    '<div class="flex flex-wrap gap-3.5 text-xs text-text-muted mt-1">' +
                        '<span><i class="bx bx-group mr-1"></i> ' + escapeHtml(j.operators) + '</span>' +
                    '</div>' +
                '</div>';
        }).join('');
    }

    document.getElementById('modalJadwalTanggal').classList.add('open');
}

function tutupModalJadwal() {
    document.getElementById('modalJadwalTanggal').classList.remove('open');
}

document.addEventListener('click', function (e) {
    var cell = e.target.closest('[data-tanggal]');
    if (cell) {
        bukaModalJadwal(cell.dataset.tanggal, cell.dataset.label);
        return;
    }
    if (e.target.id === 'modalJadwalTanggal') {
        tutupModalJadwal();
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') tutupModalJadwal();
});

// Picker bulan & tahun - klik judul kalender untuk lompat langsung, bukan cuma next/prev satu-satu.
var calendarTitleBtn = document.getElementById('calendarTitleBtn');
var calendarPicker = document.getElementById('calendarPicker');
var pilihBulan = document.getElementById('pilihBulan');
var pilihTahun = document.getElementById('pilihTahun');

calendarTitleBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    var akanTerbuka = !calendarPicker.classList.contains('open');
    calendarPicker.classList.toggle('open');
    if (akanTerbuka) {
        // Scroll bulan & tahun yang sedang aktif ke tengah listbox tiap kali dibuka.
        [pilihBulan, pilihTahun].forEach(function (select) {
            var opt = select.options[select.selectedIndex];
            if (opt) opt.scrollIntoView({ block: 'center' });
        });
    }
});

document.addEventListener('click', function (e) {
    if (calendarPicker.classList.contains('open') && !calendarPicker.contains(e.target) && e.target !== calendarTitleBtn) {
        calendarPicker.classList.remove('open');
    }
});

function navigasiKalender() {
    window.location.href = '{{ route('admin.dashboard') }}?bulan=' + pilihTahun.value + '-' + pilihBulan.value;
}
pilihBulan.addEventListener('change', navigasiKalender);
pilihTahun.addEventListener('change', navigasiKalender);

// Tooltip kustom (position:fixed) untuk sel kalender — tidak pernah terpotong oleh overflow:hidden.
var calendarTooltip = document.getElementById('calendarTooltip');

document.querySelectorAll('[data-tooltip]').forEach(function (cell) {
    cell.addEventListener('mouseenter', function () {
        calendarTooltip.textContent = cell.dataset.tooltip;
        calendarTooltip.classList.add('show');

        var rect = cell.getBoundingClientRect();
        var tw   = calendarTooltip.offsetWidth;
        var th   = calendarTooltip.offsetHeight;

        var left = rect.left + (rect.width / 2) - (tw / 2);
        left = Math.max(8, Math.min(left, window.innerWidth - tw - 8));

        var top = rect.top - th - 8;
        if (top < 8) top = rect.bottom + 8;

        calendarTooltip.style.left = left + 'px';
        calendarTooltip.style.top  = top + 'px';
    });
    cell.addEventListener('mouseleave', function () {
        calendarTooltip.classList.remove('show');
    });
});
</script>
@endpush
