@extends('layouts.app')
@section('title', 'Detail Jadwal')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
        <div><h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Detail Jadwal</h1></div>
        <a href="{{ route('admin.jadwal.index') }}"
            class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
        <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
            <i class="bx bx-info-circle"></i> {{ $jadwal->judul_kegiatan }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-[13px] font-medium text-text dark:text-text-dark">Tanggal</label>
                <p class="text-text dark:text-text-dark m-0">{{ $jadwal->tanggal->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-[13px] font-medium text-text dark:text-text-dark">Waktu</label>
                <p class="text-text dark:text-text-dark m-0">{{ \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') }} WIB</p>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-[13px] font-medium text-text dark:text-text-dark">Platform</label>
                <p class="text-text dark:text-text-dark m-0">{{ $jadwal->platform }}</p>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-[13px] font-medium text-text dark:text-text-dark">Keterangan</label>
                <p class="text-text dark:text-text-dark m-0">{{ $jadwal->keterangan ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-4">
        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
            <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                <i class="bx bxs-group"></i> Operator Bertugas</h3>
            @forelse($jadwal->operators as $op)
            <div class="flex items-center justify-between py-2.5 border-b border-page-bg dark:border-page-bg-dark">
                <div>
                    <div class="font-medium text-text dark:text-text-dark">{{ $op->nama_user }}</div>
                    <div class="text-xs text-text-muted">{{ $op->nohp ?? '-' }}</div>
                </div>
            </div>
            @empty
            <p class="text-text-muted text-center py-5">Tidak ada operator</p>
            @endforelse
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
            <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                <i class="bx bx-wrench"></i> Alat yang Dibutuhkan</h3>
            <p class="text-xs text-text-muted mt-0.5 mb-3">Acuan buat operator - bukan peminjaman yang sudah pasti terjadi.</p>
            @forelse($jadwal->peralatanReferensi as $alat)
            <div class="flex items-center justify-between py-2.5 border-b border-page-bg dark:border-page-bg-dark">
                <div>
                    <div class="font-medium text-text dark:text-text-dark">{{ $alat->nama_peralatan }}</div>
                    <div class="text-xs text-text-muted">{{ $alat->gedung }}</div>
                </div>
                <span class="inline-flex items-center gap-1 py-[3px] px-2.5 rounded-full text-xs font-medium whitespace-nowrap bg-primary-50 dark:bg-primary-950 text-primary">{{ $alat->pivot->jumlah }} unit</span>
            </div>
            @empty
            <p class="text-text-muted text-center py-5">Belum ada catatan alat</p>
            @endforelse
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
            <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                <i class="bx bx-briefcase"></i> Peminjaman Terkait</h3>
            <p class="text-xs text-text-muted mt-0.5 mb-3">Pengajuan peminjaman peralatan yang dikaitkan operator ke rapat ini.</p>
            @forelse($jadwal->peminjaman as $p)
            <div class="flex items-center justify-between py-2.5 border-b border-page-bg dark:border-page-bg-dark">
                <div>
                    <div class="font-medium text-text dark:text-text-dark">{{ $p->keperluan }}</div>
                    <div class="text-xs text-text-muted">{{ $p->user->nama_user ?? '-' }}</div>
                </div>
                <x-badge :variant="$p->badge['class']">{{ $p->badge['label'] }}</x-badge>
            </div>
            @empty
            <p class="text-text-muted text-center py-5">Belum ada peminjaman terkait</p>
            @endforelse
        </div>
    </div>
</main>
@endsection
