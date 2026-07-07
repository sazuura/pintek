@extends('layouts.app')
@section('title', 'Dashboard Operator')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
<main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
        <div><h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Dashboard</h1></div>
        <a href="{{ route('operator.peminjaman.create') }}"
            class="h-9 px-4 rounded-full bg-primary text-surface dark:text-surface-dark flex justify-center items-center gap-2.5 font-medium">
            <i class="bx bx-send"></i><span class="text">Ajukan Peminjaman</span>
        </a>
    </div>

    {{-- Statistik & chart di bawah ini mengikuti bulan yang lagi dipilih di kalender
         kanan - navigasi bulan/tahun di kalender otomatis memperbarui semuanya. Taruh di
         luar grid 2 kolom (bukan di dalam kolom kiri) supaya kolom kiri & kanan tetap
         sejajar dari atas. --}}
    <p class="text-[13px] text-text-muted mb-3 flex items-center gap-1.5">
        <i class="bx bx-calendar"></i> Menampilkan data:
        <span class="font-semibold text-text dark:text-text-dark">{{ $kalender['labelBulan'] }} {{ $kalender['tahun'] }}</span>
    </p>

    {{-- Layout asimetris: stat card + reminder lebih lebar di kiri, kalender + aktivitas ditumpuk di kanan --}}
    <div class="grid grid-cols-1 min-[1101px]:grid-cols-[14fr_7fr] gap-4 mb-4 items-start">
        <div class="flex flex-col gap-4 min-w-0">
            {{-- Stat cards --}}
            <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] max-md:grid-cols-2 max-xs:!grid-cols-1 gap-4">
                <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                    <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#e8f4fd] text-[#3C91E6]"><i class="bx bxs-calendar"></i></div>
                    <div>
                        <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $jumlahJadwal }}</h3>
                        <p class="text-[13px] text-text-muted m-0">Jadwal Mendatang</p>
                    </div>
                </div>

                <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                    <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-warning dark:bg-warning-dark text-warning-text"><i class="bx bxs-hourglass"></i></div>
                    <div>
                        <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $menungguCount }}</h3>
                        <p class="text-[13px] text-text-muted m-0">Menunggu Persetujuan</p>
                    </div>
                </div>

                <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                    <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-[#e6f9f0] text-[#1abc9c]"><i class="bx bxs-check-circle"></i></div>
                    <div>
                        <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $disetujuiCount }}</h3>
                        <p class="text-[13px] text-text-muted m-0">Disetujui / Dipakai</p>
                    </div>
                </div>

                <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 flex items-center gap-4 shadow-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(0,0,0,0.10)]">
                    <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center text-2xl shrink-0 bg-danger dark:bg-danger-dark text-danger-text"><i class="bx bxs-x-circle"></i></div>
                    <div>
                        <h3 class="text-2xl font-bold text-text dark:text-text-dark leading-none mb-1">{{ $ditolakCount }}</h3>
                        <p class="text-[13px] text-text-muted m-0">Ditolak</p>
                    </div>
                </div>
            </div>

            {{-- Chart: alat yang paling sering dipinjam operator ini sendiri --}}
            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card flex flex-col">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-4"><i class="bx bx-wrench text-primary"></i> Alat yang Paling Sering Saya Pinjam</h3>
                @if($topPeralatan->isEmpty())
                    <div class="text-center py-10 px-2.5 text-text-muted">
                        <i class="bx bx-package text-3xl block mb-2"></i>
                        <p class="m-0 text-[13px]">Belum ada riwayat peminjaman peralatan</p>
                    </div>
                @else
                    <div class="relative h-[240px] shrink-0 max-md:h-[220px] max-xs:h-[200px] [&>canvas]:!max-h-none">
                        <canvas id="chartTopPeralatan"></canvas>
                    </div>
                @endif
            </div>

            {{-- Reminder: alat yang belum dikembalikan & sudah lewat rencana kembali --}}
            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden flex flex-col">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark pt-5 px-5 {{ $perluDikembalikan->isEmpty() ? 'pb-5' : 'mb-4' }}"><i class="bx bxs-wrench text-primary"></i> Peralatan yang Perlu Dikembalikan</h3>
                @if($perluDikembalikan->isEmpty())
                    <div class="text-center pb-10 px-2.5 text-text-muted">
                        <i class="bx bx-check-shield text-3xl block mb-2"></i>
                        <p class="m-0 text-[13px]">Tidak ada peralatan yang sedang dipinjam</p>
                    </div>
                @else
                    <div class="flex flex-col gap-2.5 px-5 pb-5">
                        @foreach($perluDikembalikan as $t)
                            @php
                                $rencana = $t->tanggal_kembali_rencana->copy()->startOfDay();
                                $hariIni = today();
                                if ($rencana->lt($hariIni)) {
                                    $labelBadge = $rencana->diffInDays($hariIni) . ' hari terlambat';
                                    $rowClass   = 'bg-danger dark:bg-danger-dark';
                                    $badgeClass = 'bg-danger-text text-white';
                                } elseif ($rencana->eq($hariIni)) {
                                    $labelBadge = 'Kembali hari ini';
                                    $rowClass   = 'bg-warning dark:bg-warning-dark';
                                    $badgeClass = 'bg-warning-text text-white';
                                } else {
                                    $labelBadge = 'H-' . $hariIni->diffInDays($rencana);
                                    $rowClass   = 'bg-page-bg dark:bg-page-bg-dark';
                                    $badgeClass = 'bg-primary-50 dark:bg-[#0d2a40] text-primary';
                                }
                            @endphp
                            <div class="flex items-start justify-between gap-3 {{ $rowClass }} rounded-[10px] p-3.5">
                                <div class="min-w-0">
                                    <p class="m-0 text-[13px] font-semibold text-text dark:text-text-dark">{{ $t->items->pluck('peralatan.nama_peralatan')->filter()->join(', ') ?: '-' }}</p>
                                    <p class="m-0 mt-1 text-xs text-text-muted">Rencana kembali: {{ $t->tanggal_kembali_rencana->translatedFormat('d M Y') }} &middot; {{ $t->keperluan }}</p>
                                </div>
                                <span class="shrink-0 inline-flex items-center gap-1 py-[3px] px-2.5 rounded-full text-xs font-medium whitespace-nowrap {{ $badgeClass }}">
                                    <i class="bx bx-time-five"></i> {{ $labelBadge }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <x-pagination :paginator="$perluDikembalikan" label="peralatan" />
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4 min-w-0">
            <div class="bg-surface dark:bg-surface-dark rounded-xl p-5 shadow-card flex flex-col">
                <div class="flex items-start flex-wrap gap-3 mb-2.5">
                    <div>
                        <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-0"><i class="bx bx-calendar-heart text-primary"></i> Jadwal Saya</h3>
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
                    <a href="{{ route('operator.dashboard', ['bulan' => $kalender['bulanSebelumnya']]) }}"
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

                    <a href="{{ route('operator.dashboard', ['bulan' => $kalender['bulanBerikutnya']]) }}"
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
                    <ul class="list-none m-0 p-0 flex flex-col gap-1 max-h-[260px] wide:max-h-[280px] overflow-y-auto">
                        @foreach($activities as $a)
                            <li class="flex items-start gap-3 py-2.5 px-1 border-b border-page-bg dark:border-page-bg-dark last:border-b-0">
                                <span class="w-[34px] h-[34px] wide:w-[35px] wide:h-[35px] rounded-[10px] bg-[#e8f4fd] text-[#3C91E6] flex items-center justify-center shrink-0 text-base"><i class="bx {{ $a['icon'] }}"></i></span>
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
Chart.defaults.color = isDark ? '#94A3B8' : '#6B7280';
Chart.defaults.borderColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
Chart.defaults.font.family = "'Poppins', sans-serif";

// Bar horizontal - alat yang paling sering dipinjam operator ini sendiri. Warna primary sekuensial:
// makin gelap = makin sering dipinjam, angka juga dicetak langsung di ujung bar.
var chartTopPeralatanEl = document.getElementById('chartTopPeralatan');
if (chartTopPeralatanEl) {
    var topPeralatanData = @json($topPeralatan->pluck('total_dipinjam'));
    var topPeralatanBlueDark  = [0, 61, 153];    // #003D99 (primary-700)
    var topPeralatanBlueLight = [204, 224, 255]; // #CCE0FF (primary-100)

    var topPeralatanColors = topPeralatanData.map(function (_, i) {
        var t = topPeralatanData.length > 1 ? 1 - (i / (topPeralatanData.length - 1)) : 1;
        var rgb = topPeralatanBlueDark.map(function (c, ch) {
            return Math.round(topPeralatanBlueLight[ch] + (c - topPeralatanBlueLight[ch]) * t);
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
                ctx.fillText(topPeralatanData[i], bar.x + 8, bar.y);
                ctx.restore();
            });
        }
    };

    new Chart(chartTopPeralatanEl, {
        type: 'bar',
        data: {
            labels: @json($topPeralatan->pluck('nama_peralatan')),
            datasets: [{
                label: 'Kali Dipinjam',
                data: topPeralatanData,
                backgroundColor: topPeralatanColors,
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
    window.location.href = '{{ route('operator.dashboard') }}?bulan=' + pilihTahun.value + '-' + pilihBulan.value;
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
