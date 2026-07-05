@extends('layouts.app')
@section('title', 'Tambah Peralatan')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Tambah Peralatan</h1>
            </div>
            <a href="{{ route('inventaris.peralatan.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div class="bg-danger dark:bg-danger-dark border-l-4 border-danger-text py-3 px-4 rounded-lg mb-4 text-sm text-[#c0392b]">
                <ul class="m-0 pl-[18px]">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('inventaris.peralatan.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @php
                $inputClass = 'h-10 px-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
                $labelClass = 'text-[13px] font-medium text-text dark:text-text-dark';
                $hintClass = 'text-xs text-text-muted mt-0.5';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-info-circle"></i> Informasi Peralatan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Nomor Seri <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <input type="text" name="kode_barang"
                            class="{{ $inputClass }} {{ $errors->has('kode_barang') ? '!border-danger-text' : '' }}"
                            value="{{ old('kode_barang') }}" placeholder="cth: GU/LAP/2024/001" required>
                        <span class="{{ $hintClass }}">Nomor seri fisik barang. Harus unik jika diisi.</span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Nama Peralatan <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <input type="text" name="nama_peralatan"
                            class="{{ $inputClass }} {{ $errors->has('nama_peralatan') ? '!border-danger-text' : '' }}"
                            value="{{ old('nama_peralatan') }}" placeholder="cth: Laptop Zoom Host" required>
                    </div>
                    {{-- Gedung otomatis dari akun inventaris yang login --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Lokasi<span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <input type="text" name="gedung" class="{{ $inputClass }}" value="{{ old('gedung')}}"
                            placeholder="Gedung A" required>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Lokasi Detail</label>
                        <input type="text" name="lokasi_detail" class="{{ $inputClass }}" value="{{ old('lokasi_detail') }}"
                            placeholder="cth: Rak 3, Lt.2">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Stok Total <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <input type="number" name="stok" class="{{ $inputClass }} {{ $errors->has('stok') ? '!border-danger-text' : '' }}"
                            value="{{ old('stok', 0) }}" min="0" required>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Keterangan <small class="font-normal text-text-muted ml-1">(opsional)</small></label>
                        <input type="text" name="keterangan" class="{{ $inputClass }}" value="{{ old('keterangan') }}"
                            placeholder="Catatan tambahan">
                    </div>
                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="{{ $labelClass }}">Foto <small class="font-normal text-text-muted ml-1">(opsional, max 2MB)</small></label>
                        <input type="file" name="foto" id="foto-input"
                            accept="image/jpg,image/jpeg,image/png,image/webp"
                            class="{{ $inputClass }} h-auto py-2 px-3">
                        <div id="foto-preview" class="hidden mt-2.5">
                            <img id="foto-img" src="" alt="Preview"
                                class="h-[120px] rounded-lg object-cover border border-page-bg dark:border-page-bg-dark">
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2.5 mt-6 pt-5 border-t border-page-bg dark:border-page-bg-dark">
                <a href="{{ route('inventaris.peralatan.index') }}"
                    class="h-10 px-5 bg-page-bg dark:bg-page-bg-dark hover:bg-[#ddd] text-text dark:text-text-dark border-none rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
                <button type="submit"
                    class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
                    <i class="bx bx-save"></i> Simpan
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
