@extends('layouts.app')
@section('title', 'Dashboard Operator')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Dashboard</h1>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-card-icon blue"><i class="bx bxs-calendar"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $jumlahJadwal }}</h3>
                    <p>Total Jadwal Saya</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon green"><i class="bx bxs-check-circle"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $totalHadir }}</h3>
                    <p>Total Hadir</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon yellow"><i class="bx bxs-time"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $totalIzin + $totalSakit }}</h3>
                    <p>Izin & Sakit</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon red"><i class="bx bxs-x-circle"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $totalAlpha }}</h3>
                    <p>Alpha</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon orange"><i class="bx bxs-wrench"></i></div>
                <div class="stat-card-info">
                    <h3>{{ $jumlahPeralatan }}</h3>
                    <p>Peralatan Ditugaskan</p>
                </div>
            </div>
        </div>

        <div class="chart-grid">
            {{-- Donut rekap presensi --}}
            <div class="chart-card">
                <h3><i class="bx bx-doughnut-chart" style="color:var(--blue);"></i> Rekap Presensi Saya</h3>
                <canvas id="chartDonut"></canvas>
            </div>

            {{-- Jadwal terdekat --}}
            <div class="chart-card" style="grid-column:span 2;">
                <h3><i class="bx bx-calendar-event" style="color:var(--blue);"></i> Jadwal Terakhir</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kegiatan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            {{-- <th>Status</th>- --}}
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwalTerakhir as $j)
                            <tr>
                                <td style="font-weight:500;">{{ $j->penjadwalan->judul_kegiatan }}</td>
                                <td>{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($j->penjadwalan->waktu_mulai)->format('H:i') }}
                                    -
                                    {{ \Carbon\Carbon::parse($j->penjadwalan->waktu_selesai)->format('H:i') }}
                                </td>
                                {{-- <td><span class="badge {{ $j->badge['class'] }}">{{ $j->badge['label'] }}</span></td> --}}
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center;padding:20px;color:var(--dark-grey);">
                                    Belum ada jadwal
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($jumlahJadwal > 5)
                    <div style="padding:12px 0 0;">
                        <a href="{{ route('operator.jadwal.index') }}" style="font-size:13px;color:var(--blue);">
                            Lihat semua jadwal →
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        new Chart(document.getElementById('chartDonut'), {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                datasets: [{
                    data: [{{ $totalHadir }}, {{ $totalIzin }}, {{ $totalSakit }}, {{ $totalAlpha }}],
                    backgroundColor: ['#1abc9c', '#3C91E6', '#8b5cf6', '#e74c3c'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
@endpush