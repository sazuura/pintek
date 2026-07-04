@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Dashboard</h1></div>
    </div>

    {{-- Layout asimetris: stat card + chart + peralatan lebih lebar di kiri, kalender + aktivitas ditumpuk di kanan --}}
    <div class="chart-grid-asym">
        <div class="dashboard-main-stack">
            {{-- Stat cards --}}
            <div class="stat-cards">
                <div class="stat-card">
                    <span class="stat-trend {{ $trenRapat['arah'] }}" title="Dibanding jumlah rapat 30 hari sebelumnya">
                        @if($trenRapat['arah'] === 'up') <i class="bx bx-up-arrow-alt"></i>
                        @elseif($trenRapat['arah'] === 'down') <i class="bx bx-down-arrow-alt"></i>
                        @else <i class="bx bx-minus"></i>
                        @endif
                        {{ $trenRapat['label'] }}
                    </span>
                    <div class="stat-card-icon purple"><i class="bx bxs-calendar-event"></i></div>
                    <div class="stat-card-info">
                        <h3>{{ $jumlahRapatMendatang }}</h3>
                        <p>Rapat Mendatang</p>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-trend neutral" title="Jumlah akun operator berstatus aktif dari total akun operator terdaftar">dari {{ $totalOperatorAkun }} akun</span>
                    <div class="stat-card-icon blue"><i class="bx bxs-group"></i></div>
                    <div class="stat-card-info">
                        <h3>{{ $jumlahOperator }}</h3>
                        <p>Operator Aktif</p>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-trend {{ $trenPeralatan['arah'] }}" title="Dibanding peminjaman baru 7 hari sebelumnya">
                        @if($trenPeralatan['arah'] === 'up') <i class="bx bx-up-arrow-alt"></i>
                        @elseif($trenPeralatan['arah'] === 'down') <i class="bx bx-down-arrow-alt"></i>
                        @else <i class="bx bx-minus"></i>
                        @endif
                        {{ $trenPeralatan['label'] }}
                    </span>
                    <div class="stat-card-icon orange"><i class="bx bxs-wrench"></i></div>
                    <div class="stat-card-info">
                        <h3>{{ $jumlahPeralatanDipinjam }}</h3>
                        <p>Peralatan Dipinjam</p>
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-trend {{ $trenJadwal['arah'] }}" title="Dibanding jumlah rapat 7 hari sebelumnya">
                        @if($trenJadwal['arah'] === 'up') <i class="bx bx-up-arrow-alt"></i>
                        @elseif($trenJadwal['arah'] === 'down') <i class="bx bx-down-arrow-alt"></i>
                        @else <i class="bx bx-minus"></i>
                        @endif
                        {{ $trenJadwal['label'] }}
                    </span>
                    <div class="stat-card-icon green"><i class="bx bxs-calendar-check"></i></div>
                    <div class="stat-card-info">
                        <h3>{{ $jumlahJadwal }}</h3>
                        <p>Total Jadwal</p>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <h3><i class="bx bx-line-chart" style="color:var(--blue);"></i> Jadwal per Operator</h3>
                    <div class="chart-type-toggle" role="group" aria-label="Tipe grafik" data-active="line">
                        <span class="chart-type-thumb"></span>
                        <button type="button" class="chart-type-btn" data-chart-type="bar" title="Tampilan batang">
                            <i class="bx bx-bar-chart-alt-2"></i>
                        </button>
                        <button type="button" class="chart-type-btn active" data-chart-type="line" title="Tampilan garis">
                            <i class="bx bx-trending-up"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-canvas-wrap chart-canvas-wrap-tall">
                    <canvas id="chartOperator"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3><i class="bx bx-wrench" style="color:var(--blue);"></i> Peralatan Paling Sering Dipinjam</h3>
                @if($topPeralatan->isEmpty())
                    <div class="activity-empty">
                        <i class="bx bx-package"></i>
                        <p>Belum ada peralatan yang dipinjam</p>
                    </div>
                @else
                    <div class="chart-canvas-wrap">
                        <canvas id="chartPeralatan"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <div class="dashboard-sidebar-stack">
            <div class="chart-card calendar-card">
                <div class="calendar-title-row">
                    <div>
                        <h3><i class="bx bx-calendar-heart" style="color:var(--blue);"></i> Kepadatan Jadwal Rapat</h3>
                        <small class="calendar-subtitle">Klik tanggal untuk lihat jadwal rapatnya</small>
                    </div>
                </div>
                <div class="calendar-legend">
                    <span class="legend-item"><span class="legend-swatch today"></span> Hari ini</span>
                    <span class="legend-item"><span class="legend-swatch upcoming"></span> Akan datang</span>
                    <span class="legend-item"><span class="legend-swatch past"></span> Sudah lewat</span>
                </div>

                @php
                    $namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                @endphp
                <div class="calendar-header">
                    <a href="{{ route('admin.dashboard', ['bulan' => $kalender['bulanSebelumnya']]) }}" class="calendar-nav"><i class="bx bx-chevron-left"></i></a>

                    <div class="calendar-picker-wrap">
                        <button type="button" class="calendar-title-btn" id="calendarTitleBtn">
                            <span class="calendar-month">{{ $kalender['labelBulan'] }}</span>
                            <span class="calendar-year">{{ $kalender['tahun'] }}</span>
                            <i class="bx bx-chevron-down"></i>
                        </button>
                        <div class="calendar-picker" id="calendarPicker">
                            <select id="pilihBulan" class="form-select">
                                @foreach($namaBulan as $i => $nama)
                                    <option value="{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}" {{ $kalender['bulanAngka'] == $i + 1 ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                            <select id="pilihTahun" class="form-select" size="5">
                                @for($tahun = $kalender['tahunAngka'] - 6; $tahun <= $kalender['tahunAngka'] + 5; $tahun++)
                                    <option value="{{ $tahun }}" {{ $tahun == $kalender['tahunAngka'] ? 'selected' : '' }}>{{ $tahun }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <a href="{{ route('admin.dashboard', ['bulan' => $kalender['bulanBerikutnya']]) }}" class="calendar-nav"><i class="bx bx-chevron-right"></i></a>
                </div>

                <div class="calendar-scroll">
                    <table class="calendar-table">
                        <thead>
                            <tr>
                                @foreach($kalender['hariHeader'] as $hari)
                                    <th>{{ $hari }}</th>
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
                                        @endphp
                                        <td>
                                            <div class="calendar-cell {{ $hari['isHariIni'] ? 'today' : '' }} {{ !$hari['inBulan'] ? 'faded' : '' }} {{ $hari['inBulan'] ? 'clickable' : '' }} {{ $warnaKelas }}"
                                                 @if($hari['inBulan'])
                                                     data-tanggal="{{ $hari['tanggalKey'] }}"
                                                     data-label="{{ \Carbon\Carbon::parse($hari['tanggalKey'])->translatedFormat('l, d F Y') }}"
                                                 @endif
                                                 @if($adaJadwal) data-tooltip="{{ $hari['jumlah'] }} jadwal rapat - klik untuk detail" @endif>
                                                <span class="calendar-date">{{ $hari['tanggal'] }}</span>
                                                @if($adaJadwal)
                                                    <span class="calendar-badge">{{ $hari['jumlah'] }}</span>
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

            <div class="chart-card">
                <h3><i class="bx bx-bell" style="color:var(--blue);"></i> Aktivitas Terbaru</h3>
                @if($activities->isEmpty())
                    <div class="activity-empty">
                        <i class="bx bx-moon"></i>
                        <p>Belum ada aktivitas dalam 14 hari terakhir</p>
                    </div>
                @else
                    <ul class="activity-feed">
                        @foreach($activities as $a)
                            <li class="activity-item">
                                <span class="activity-icon"><i class="bx {{ $a['icon'] }}"></i></span>
                                <div class="activity-body">
                                    <p>{{ $a['text'] }}</p>
                                    <small>{{ \Carbon\Carbon::parse($a['time'])->diffForHumans() }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal: daftar jadwal pada tanggal yang diklik di kalender --}}
    <div id="modalJadwalTanggal" class="detail-modal-overlay">
        <div class="detail-modal">
            <div class="detail-modal-header">
                <h3 id="modalJadwalTanggalLabel">Jadwal Rapat</h3>
                <button type="button" class="detail-modal-close" onclick="tutupModalJadwal()"><i class="bx bx-x"></i></button>
            </div>
            <div id="modalJadwalTanggalBody" class="detail-modal-body"></div>
        </div>
    </div>

    {{-- Tooltip kustom untuk sel kalender --}}
    <div id="calendarTooltip" class="calendar-tooltip"></div>
</main>
@endsection

@push('scripts')
<script>
var isDark = document.body.classList.contains('dark');
var gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
var labelColor = isDark ? '#94A3B8' : '#6B7280';

Chart.defaults.color = labelColor;
Chart.defaults.borderColor = gridColor;

// Line - jadwal per operator. Tiap titik operator diwarnai berbeda, garis penghubung memakai warna biru utama.
var operatorPalette = ['#3C91E6', '#6366f1', '#06b6d4', '#8b5cf6', '#0ea5e9'];
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
            borderColor: '#3C91E6',
            backgroundColor: 'rgba(60,145,230,.12)',
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

var chartTypeToggle = document.querySelector('.chart-type-toggle');
document.querySelectorAll('.chart-type-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.chart-type-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        chartTypeToggle.dataset.active = btn.dataset.chartType;
        renderChartOperator(btn.dataset.chartType);
    });
});

// Bar horizontal - peralatan paling sering dipinjam. Warna biru sekuensial: makin gelap = makin
// sering dipinjam, jadi warnanya sendiri ikut menyampaikan info peringkat (bukan sekadar dekorasi).
// Angka juga dicetak langsung di ujung bar biar tidak perlu menerka-nerka lewat sumbu.
var chartPeralatanEl = document.getElementById('chartPeralatan');
if (chartPeralatanEl) {
    var peralatanData = @json($topPeralatan->pluck('total_dipinjam'));
    var peralatanBlueDark  = [21, 76, 138];   // #154C8A
    var peralatanBlueLight = [186, 219, 249]; // #BADBF9

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
        body.innerHTML = '<div class="detail-modal-empty"><i class="bx bx-calendar-x"></i><p>Tidak ada jadwal rapat di tanggal ini</p></div>';
    } else {
        body.innerHTML = daftar.map(function (j) {
            return '' +
                '<div class="detail-modal-item">' +
                    '<div class="detail-modal-item-top">' +
                        '<strong>' + escapeHtml(j.judul) + '</strong>' +
                    '</div>' +
                    '<div class="detail-modal-item-meta">' +
                        '<span><i class="bx bx-time-five"></i> ' + escapeHtml(j.waktu) + ' WIB</span>' +
                        '<span><i class="bx bx-desktop"></i> ' + escapeHtml(j.platform) + '</span>' +
                    '</div>' +
                    '<div class="detail-modal-item-meta">' +
                        '<span><i class="bx bx-group"></i> ' + escapeHtml(j.operators) + '</span>' +
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
    var cell = e.target.closest('.calendar-cell.clickable[data-tanggal]');
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
    calendarPicker.classList.toggle('open');
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

document.querySelectorAll('.calendar-cell[data-tooltip]').forEach(function (cell) {
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
