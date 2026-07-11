@extends('layouts.app')
@section('title', 'Edit Peralatan')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Edit Peralatan</h1>
            </div>
            <a href="{{ route('inventaris.peralatan.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        <form action="{{ route('inventaris.peralatan.update', $peralatan->id_peralatan) }}" method="POST"
            enctype="multipart/form-data" novalidate>
            @csrf @method('PUT')

            @php
                $inputClass = 'h-10 px-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)] read-only:bg-page-bg dark:read-only:bg-page-bg-dark read-only:cursor-not-allowed';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-info-circle"></i> Informasi Peralatan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[13px] font-medium text-text dark:text-text-dark">ID Peralatan</label>
                        <input type="text" class="{{ $inputClass }}" value="{{ $peralatan->id_peralatan }}" readonly>
                        <span class="text-xs text-text-muted">ID tidak bisa diubah.</span>
                    </div>
                    <x-input name="kode_barang" label="Nomor Seri" placeholder="cth: GU/LAP/2024/001"
                        value="{{ old('kode_barang', $peralatan->kode_barang) }}" />
                    <x-input name="nama_peralatan" label="Nama Peralatan" required
                        value="{{ old('nama_peralatan', $peralatan->nama_peralatan) }}" />
                    <x-input name="gedung" label="Lokasi" value="{{ $peralatan->gedung }}" />
                    <x-input name="lokasi_detail" label="Lokasi Detail" placeholder="cth: Rak 3, Lt.2"
                        value="{{ old('lokasi_detail', $peralatan->lokasi_detail) }}" />
                    <x-input name="keterangan" label="Keterangan"
                        value="{{ old('keterangan', $peralatan->keterangan) }}" />
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-data"></i> Data Stok</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input type="number" name="stok" id="inp-stok" label="Stok Total" required min="0"
                        value="{{ old('stok', $peralatan->stok) }}" />
                    <x-input type="number" name="rusak" id="inp-rusak" label="Unit Rusak" min="0"
                        value="{{ old('rusak', $peralatan->rusak) }}" />
                </div>
                <div class="mt-3 py-2.5 px-3.5 bg-page-bg dark:bg-page-bg-dark rounded-lg text-[13px]">
                    <span class="text-text-muted">Stok tersedia = stok - rusak = </span>
                    <strong class="text-primary" id="stok-preview">{{ $peralatan->stok_tersedia }}</strong>
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-image"></i> Foto</h3>
                @if($peralatan->foto)
                    <div class="mb-3.5">
                        <p class="text-[13px] text-text-muted mb-2">Foto saat ini:</p>
                        <img src="{{ Storage::url($peralatan->foto) }}" alt="Foto"
                            class="h-[120px] rounded-lg object-cover border border-page-bg dark:border-page-bg-dark">
                        <x-checkbox name="hapus_foto">Hapus foto ini</x-checkbox>
                    </div>
                @endif
                <div class="flex flex-col gap-1.5">
                    <label class="text-[13px] font-medium text-text dark:text-text-dark">{{ $peralatan->foto ? 'Upload Foto Baru' : 'Upload Foto' }} <small class="font-normal text-text-muted ml-1">(opsional,
                            max 2MB)</small></label>
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
                <a href="{{ route('inventaris.peralatan.index') }}"
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
        // Live preview stok tersedia
        ['inp-stok', 'inp-rusak'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', function () {
                var s = parseInt(document.getElementById('inp-stok').value) || 0;
                var r = parseInt(document.getElementById('inp-rusak').value) || 0;
                document.getElementById('stok-preview').textContent = Math.max(0, s - r);
            });
        });

        document.getElementById('foto-input').addEventListener('change', function () {
            var file = this.files[0];
            var prev = document.getElementById('foto-preview');
            var img = document.getElementById('foto-img');
            if (file) { img.src = URL.createObjectURL(file); prev.classList.remove('hidden'); }
            else { prev.classList.add('hidden'); }
        });
    </script>
@endpush
