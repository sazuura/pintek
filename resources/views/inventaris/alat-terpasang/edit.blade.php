@extends('layouts.app')
@section('title', 'Kelola Alat Terpasang')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Kelola Alat Terpasang</h1>
            </div>
            <a href="{{ route('inventaris.alat-terpasang.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div class="bg-danger dark:bg-danger-dark border-l-4 border-danger-text py-3 px-4 rounded-lg mb-4 text-sm text-[#c0392b]">
                <ul class="m-0 pl-[18px]">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('inventaris.alat-terpasang.update', $alat->id_alat_terpasang) }}" method="POST"
            enctype="multipart/form-data" novalidate>
            @csrf @method('PUT')

            @php
                $inputClass = 'h-10 px-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)] read-only:bg-page-bg dark:read-only:bg-page-bg-dark read-only:cursor-not-allowed';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-info-circle"></i> Informasi Alat</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[13px] font-medium text-text dark:text-text-dark">ID Alat</label>
                        <input type="text" class="{{ $inputClass }}" value="{{ $alat->id_alat_terpasang }}" readonly>
                        <span class="text-xs text-text-muted">ID tidak bisa diubah.</span>
                    </div>
                    <x-select name="id_peralatan" label="Nama Alat" required placeholder="-- Pilih Peralatan --">
                        @foreach($daftarPeralatan as $p)
                            <option value="{{ $p->id_peralatan }}" data-subtitle="{{ $p->gedung }}" {{ old('id_peralatan', $alat->id_peralatan) == $p->id_peralatan ? 'selected' : '' }}>{{ $p->nama_peralatan }}</option>
                        @endforeach
                    </x-select>
                    <x-input name="gedung" label="Gedung" required
                        value="{{ old('gedung', $alat->gedung) }}" />
                    <x-input name="lokasi_detail" label="Lokasi Detail" placeholder="cth: Ruang Rapat Lt.2"
                        value="{{ old('lokasi_detail', $alat->lokasi_detail) }}" />
                    <x-input type="date" name="tanggal_pasang" label="Tanggal Pasang" required
                        value="{{ old('tanggal_pasang', $alat->tanggal_pasang->format('Y-m-d')) }}" />
                    <x-select name="kondisi" label="Kondisi" required>
                        <option value="baik" {{ old('kondisi', $alat->kondisi) == 'baik' ? 'selected' : '' }}>Baik</option>
                        <option value="perlu_servis" {{ old('kondisi', $alat->kondisi) == 'perlu_servis' ? 'selected' : '' }}>Perlu Servis</option>
                        <option value="rusak" {{ old('kondisi', $alat->kondisi) == 'rusak' ? 'selected' : '' }}>Rusak</option>
                    </x-select>
                    <div class="md:col-span-2">
                        <x-input name="keterangan" label="Keterangan"
                            value="{{ old('keterangan', $alat->keterangan) }}" />
                    </div>
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-image"></i> Foto</h3>
                @if($alat->foto)
                    <div class="mb-3.5">
                        <p class="text-[13px] text-text-muted mb-2">Foto saat ini:</p>
                        <img src="{{ $alat->foto_url }}" alt="Foto"
                            class="h-[120px] rounded-lg object-cover border border-page-bg dark:border-page-bg-dark">
                        <x-checkbox name="hapus_foto">Hapus foto ini</x-checkbox>
                    </div>
                @endif
                <div class="flex flex-col gap-1.5">
                    <label class="text-[13px] font-medium text-text dark:text-text-dark">{{ $alat->foto ? 'Upload Foto Baru' : 'Upload Foto' }} <small class="font-normal text-text-muted ml-1">(opsional, max 2MB)</small></label>
                    <input type="file" name="foto" id="foto-input"
                        accept="image/jpg,image/jpeg,image/png,image/webp"
                        class="{{ $inputClass }} h-auto py-2 px-3">
                    <div id="foto-preview" class="hidden mt-2.5">
                        <img id="foto-img" src="" alt="Preview"
                            class="h-[120px] rounded-lg object-cover border border-page-bg dark:border-page-bg-dark">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2.5 mt-6 pt-5 border-t border-page-bg dark:border-page-bg-dark">
                <a href="{{ route('inventaris.alat-terpasang.index') }}"
                    class="h-10 px-5 bg-page-bg dark:bg-page-bg-dark hover:bg-[#ddd] text-text dark:text-text-dark border-none rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
                <button type="submit"
                    class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
                    <i class="bx bx-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>

        {{-- Riwayat servis/maintenance - terpisah dari form utama, submit sendiri --}}
        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mt-5">
            <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                <i class="bx bx-history"></i> Riwayat Servis / Maintenance</h3>

            <form action="{{ route('inventaris.alat-terpasang.riwayat.store', $alat->id_alat_terpasang) }}" method="POST" novalidate class="mb-5">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-start">
                    <x-input type="date" name="tanggal" label="Tanggal" required value="{{ old('tanggal', now()->format('Y-m-d')) }}" />
                    <x-select name="jenis" label="Jenis" required>
                        <option value="pemeriksaan" {{ old('jenis') == 'pemeriksaan' ? 'selected' : '' }}>Pemeriksaan</option>
                        <option value="servis" {{ old('jenis') == 'servis' ? 'selected' : '' }}>Servis</option>
                        <option value="perbaikan" {{ old('jenis') == 'perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                    </x-select>
                    <div class="md:col-span-2">
                        <x-input name="keterangan" label="Keterangan" required
                            placeholder="cth: Ganti lampu proyektor" value="{{ old('keterangan') }}" />
                    </div>
                </div>
                <button type="submit"
                    class="h-9 px-3.5 mt-1 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-primary text-white">
                    <i class="bx bx-plus"></i> Tambah Riwayat
                </button>
            </form>

            @if($alat->riwayat->isEmpty())
                <p class="text-[13px] text-text-muted m-0">Belum ada riwayat servis/maintenance.</p>
            @else
                <div class="flex flex-col gap-2.5">
                    @foreach($alat->riwayat as $r)
                        <div class="flex items-start justify-between gap-3 py-3 px-3.5 bg-page-bg dark:bg-page-bg-dark rounded-lg">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <i class="bx bx-note text-lg text-primary mt-0.5 shrink-0"></i>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[13px] font-semibold text-text dark:text-text-dark">{{ $r->tanggal->translatedFormat('d M Y') }}</span>
                                        <x-badge :variant="$r->jenis === 'perbaikan' ? 'badge-danger' : ($r->jenis === 'servis' ? 'badge-warning' : 'badge-info')">{{ $r->jenisLabel }}</x-badge>
                                    </div>
                                    <p class="text-[13px] text-text dark:text-text-dark m-0 mt-1 break-words">{{ $r->keterangan }}</p>
                                    <p class="text-xs text-text-muted m-0 mt-0.5">Dicatat oleh {{ $r->user->nama_user ?? '-' }}</p>
                                </div>
                            </div>
                            <form action="{{ route('inventaris.alat-terpasang.riwayat.destroy', [$alat->id_alat_terpasang, $r->id]) }}" method="POST" onsubmit="return confirm('Hapus riwayat ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-sm transition-opacity duration-200 shrink-0 hover:opacity-80 bg-danger dark:bg-danger-dark text-danger-text"
                                    title="Hapus riwayat">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.getElementById('foto-input').addEventListener('change', function () {
            var file = this.files[0];
            var prev = document.getElementById('foto-preview');
            var img = document.getElementById('foto-img');
            if (file) { img.src = URL.createObjectURL(file); prev.classList.remove('hidden'); }
            else { prev.classList.add('hidden'); }
        });
    </script>
@endpush
