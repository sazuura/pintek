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
                    <x-input name="gedung" label="Gedung / Tempat" required
                        value="{{ old('gedung', $alat->gedung) }}" />
                    <x-input name="lokasi_detail" label="Lokasi Detail" placeholder="cth: Ruang Rapat Lt.2"
                        value="{{ old('lokasi_detail', $alat->lokasi_detail) }}" />
                    <x-input type="date" name="tanggal_pasang" label="Tanggal Pasang" required
                        value="{{ old('tanggal_pasang', $alat->tanggal_pasang->format('Y-m-d')) }}" />
                    <x-select name="kondisi" label="Kondisi" required>
                        <option value="baik" {{ old('kondisi', $alat->kondisi) == 'baik' ? 'selected' : '' }}>Baik</option>
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
                    class="h-10 px-5 bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
                <button type="submit"
                    class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
                    <i class="bx bx-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
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
