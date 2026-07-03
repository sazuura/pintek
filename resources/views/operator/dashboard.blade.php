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
        </div>

        <div class="chart-grid">
            {{-- Jadwal terdekat --}}
            <div class="chart-card" style="grid-column:span 3;">
                <h3><i class="bx bx-calendar-event" style="color:var(--blue);"></i> Jadwal Terdekat</h3>
                <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Judul Rapat</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwalTerakhir as $j)
                            <tr>
                                <td style="font-weight:500;">{{ $j->judul_kegiatan }}</td>
                                <td>{{ $j->tanggal->translatedFormat('D, d M Y') }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($j->waktu_mulai)->format('H:i') }}
                                    -
                                    {{ \Carbon\Carbon::parse($j->waktu_selesai)->format('H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center;padding:20px;color:var(--dark-grey);">
                                    Belum ada jadwal
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
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