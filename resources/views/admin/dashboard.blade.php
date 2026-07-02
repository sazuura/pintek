@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Dashboard</h1></div>
    </div>

    {{-- Stat cards --}}
    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-card-icon blue"><i class="bx bxs-group"></i></div>
            <div class="stat-card-info">
                <h3>{{ $jumlahOperator }}</h3>
                <p>Operator Aktif</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon green"><i class="bx bxs-calendar-check"></i></div>
            <div class="stat-card-info">
                <h3>{{ $jumlahJadwal }}</h3>
                <p>Total Jadwal</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon yellow"><i class="bx bxs-check-circle"></i></div>
            <div class="stat-card-info">
                <h3>{{ $totalHadir }}</h3>
                <p>Total Hadir</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon orange"><i class="bx bxs-wrench"></i></div>
            <div class="stat-card-info">
                <h3>{{ $jumlahPeralatan }}</h3>
                <p>Total Peralatan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon red"><i class="bx bxs-x-circle"></i></div>
            <div class="stat-card-info">
                <h3>{{ $totalAlpha }}</h3>
                <p>Total Alpha</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon purple"><i class="bx bxs-time"></i></div>
            <div class="stat-card-info">
                <h3>{{ $totalIzin + $totalSakit }}</h3>
                <p>Izin & Sakit</p>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="chart-grid">
        <div class="chart-card">
            <h3><i class="bx bx-doughnut-chart" style="color:var(--blue);"></i> Rekap Presensi</h3>
            <canvas id="chartDonut"></canvas>
        </div>
        <div class="chart-card" style="grid-column: span 2;">
            <h3><i class="bx bx-line-chart" style="color:var(--blue);"></i> Tren Kehadiran Harian</h3>
            <canvas id="chartTren"></canvas>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card" style="grid-column: span 3;">
            <h3><i class="bx bx-bar-chart-alt-2" style="color:var(--blue);"></i> Jadwal per Operator</h3>
            <canvas id="chartOperator"></canvas>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
var isDark = document.body.classList.contains('dark');
var gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
var labelColor = isDark ? '#94A3B8' : '#6B7280';

Chart.defaults.color = labelColor;
Chart.defaults.borderColor = gridColor;

// Donut — rekap presensi
new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Hadir','Izin','Sakit','Alpha'],
        datasets: [{
            data: [{{ $totalHadir }}, {{ $totalIzin }}, {{ $totalSakit }}, {{ $totalAlpha }}],
            backgroundColor: ['#1abc9c','#3C91E6','#8b5cf6','#e74c3c'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Line — tren harian
new Chart(document.getElementById('chartTren'), {
    type: 'line',
    data: {
        labels: @json($trenLabels),
        datasets: [
            { label:'Hadir', data:@json($trenHadir), borderColor:'#1abc9c', backgroundColor:'rgba(26,188,156,.1)', fill:true, tension:.3 },
            { label:'Izin',  data:@json($trenIzin),  borderColor:'#3C91E6', backgroundColor:'rgba(60,145,230,.1)',  fill:true, tension:.3 },
            { label:'Sakit', data:@json($trenSakit), borderColor:'#8b5cf6', backgroundColor:'rgba(139,92,246,.1)',  fill:true, tension:.3 },
            { label:'Alpha', data:@json($trenAlpha), borderColor:'#e74c3c', backgroundColor:'rgba(231,76,60,.1)',   fill:true, tension:.3 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero:true, ticks:{ stepSize:1 } } }
    }
});

// Bar — jadwal per operator
new Chart(document.getElementById('chartOperator'), {
    type: 'bar',
    data: {
        labels: @json($operatorChart->pluck('nama_user')),
        datasets: [{
            label: 'Jumlah Jadwal',
            data: @json($operatorChart->pluck('absensi_count')),
            backgroundColor: '#3C91E6',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend:{ display:false } },
        scales: { y:{ beginAtZero:true, ticks:{ stepSize:1 } } }
    }
});
</script>
@endpush
