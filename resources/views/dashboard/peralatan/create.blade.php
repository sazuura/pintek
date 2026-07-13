@extends('layouts.app')
@section('title', 'Tambah Peralatan')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Tambah Peralatan</h1>
            </div>
            <a href="{{ route(auth()->user()->role . '.peralatan.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        <form action="{{ route(auth()->user()->role . '.peralatan.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            @php
                $inputClass = 'h-10 px-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-info-circle"></i> Informasi Peralatan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="kode_barang" label="Nomor Seri" required
                        placeholder="cth: GU/LAP/2024/001" value="{{ old('kode_barang') }}"
                        hint="Nomor seri fisik barang. Harus unik jika diisi." />
                    <x-input name="nama_peralatan" label="Nama Peralatan" required
                        placeholder="cth: Laptop Zoom Host" value="{{ old('nama_peralatan') }}" />
                    <div>
                        <x-input name="gedung" id="gedung" label="Lokasi" required list="daftar-gedung"
                            autocomplete="off" placeholder="Ketik atau pilih gedung" value="{{ old('gedung') }}"
                            hint="Pilih gedung yang sudah ada, atau ketik nama gedung/tempat baru." />
                        <datalist id="daftar-gedung">
                            @foreach($gedungList as $g)
                                <option value="{{ $g }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <x-input name="lokasi_detail" id="lokasi_detail" label="Lokasi Detail" list="daftar-lokasi-detail"
                            autocomplete="off" placeholder="cth: Rak 3, Lt.2" value="{{ old('lokasi_detail') }}" />
                        <datalist id="daftar-lokasi-detail"></datalist>
                    </div>
                    <x-input type="number" name="stok" label="Stok Total" required min="0"
                        value="{{ old('stok', 0) }}" />
                    <div>
                        <x-input name="keterangan" placeholder="Catatan tambahan" value="{{ old('keterangan') }}">
                            <x-slot:label>Keterangan <small class="font-normal text-text-muted ml-1">(opsional)</small></x-slot:label>
                        </x-input>
                    </div>
                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="text-[13px] font-medium text-text dark:text-text-dark">Foto <small class="font-normal text-text-muted ml-1">(opsional, max 2MB)</small></label>
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
                <a href="{{ route(auth()->user()->role . '.peralatan.index') }}"
                    class="h-10 px-5 bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
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

        // Lokasi Detail bertingkat: saran datalist-nya mengikuti Gedung yang sedang
        // diketik/dipilih, diambil dari lokasi yang sudah pernah dipakai di gedung itu.
        var lokasiPerGedung = @json($lokasiPerGedung);
        var gedungInput = document.getElementById('gedung');
        var lokasiDatalist = document.getElementById('daftar-lokasi-detail');

        function refreshLokasiDetailOptions() {
            var daftar = lokasiPerGedung[gedungInput.value] || [];
            lokasiDatalist.innerHTML = '';
            daftar.forEach(function (lokasi) {
                var opt = document.createElement('option');
                opt.value = lokasi;
                lokasiDatalist.appendChild(opt);
            });
        }
        gedungInput.addEventListener('input', refreshLokasiDetailOptions);
        refreshLokasiDetailOptions();
    </script>
@endpush
