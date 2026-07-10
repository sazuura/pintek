@extends('layouts.app')
@section('title', 'Tambah Alat Terpasang')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Tambah Alat Terpasang</h1>
            </div>
            <a href="{{ route('inventaris.alat-terpasang.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        <form action="{{ route('inventaris.alat-terpasang.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            @php
                $inputClass = 'h-10 px-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-info-circle"></i> Alat yang Dipasang</h3>
                <p class="text-xs text-text-muted mb-3">
                    Bisa pilih beberapa alat sekaligus dalam satu kali pemasangan. Jumlah tidak boleh melebihi
                    stok yang masih bisa dipasang (stok tersedia dikurangi yang sudah terpasang).
                </p>
                <div class="dynamic-list flex flex-col gap-2.5" id="peralatan-list">
                    <div class="dynamic-item flex gap-2.5 items-center">
                        <select name="id_peralatan[]" class="peralatan-select searchable flex-1"
                            data-placeholder="Cari alat..." onchange="refreshPeralatanOptions()">
                            <option value="" selected>-- Pilih Alat --</option>
                            @foreach($daftarPeralatan as $p)
                                <option value="{{ $p->id_peralatan }}"
                                    data-subtitle="{{ $p->gedung }} &middot; Bisa dipasang: {{ $p->sisa_bisa_dipasang }}"
                                    data-sisa="{{ $p->sisa_bisa_dipasang }}"
                                    @if($p->sisa_bisa_dipasang <= 0) data-badge="Stok Habis" data-badge-variant="danger" @endif>
                                    {{ $p->nama_peralatan }}
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="jumlah[]" class="jumlah-input {{ $inputClass }} flex-[0_0_80px]" min="1"
                            placeholder="Jml" style="display:none;">
                        <button type="button" onclick="removePeralatan(this)"
                            class="btn-remove w-9 h-9 rounded-lg border-none bg-danger dark:bg-danger-dark text-danger-text cursor-pointer flex items-center justify-center shrink-0 text-base transition-colors duration-200 hover:bg-danger-text hover:text-white">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" id="add-peralatan"
                    class="h-9 px-3.5 bg-page-bg dark:bg-page-bg-dark text-primary border border-dashed border-primary rounded-lg text-[13px] font-sans font-medium cursor-pointer inline-flex items-center gap-1.5 transition-colors duration-200 mt-3 w-fit hover:bg-primary-50">
                    <i class="bx bx-plus"></i> Tambah Peralatan
                </button>
                @foreach($errors->keys() as $key)
                    @if($key === 'id_peralatan' || str_starts_with($key, 'id_peralatan.') || str_starts_with($key, 'jumlah.'))
                        <span class="text-xs text-danger-text mt-2 block">{{ $errors->first($key) }}</span>
                    @endif
                @endforeach
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-map"></i> Lokasi & Kondisi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="gedung" label="Gedung / Tempat" required
                        placeholder="Gedung A" value="{{ old('gedung') }}" />
                    <x-input name="lokasi_detail" label="Lokasi Detail"
                        placeholder="cth: Ruang Rapat Lt.2" value="{{ old('lokasi_detail') }}" />
                    <x-input type="date" name="tanggal_pasang" label="Tanggal Pasang" required
                        value="{{ old('tanggal_pasang', now()->format('Y-m-d')) }}" />
                    <x-select name="kondisi" label="Kondisi" required>
                        <option value="baik" {{ old('kondisi', 'baik') == 'baik' ? 'selected' : '' }}>Baik</option>
                        <option value="rusak" {{ old('kondisi') == 'rusak' ? 'selected' : '' }}>Rusak</option>
                    </x-select>
                    <div class="md:col-span-2">
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
                <a href="{{ route('inventaris.alat-terpasang.index') }}"
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

        // ── Alat yang dipasang: cegah alat yang sama dipilih dobel, tampilkan input Jml ──
        function refreshPeralatanOptions() {
            var selected = Array.from(document.querySelectorAll('.peralatan-select'))
                .map(function (s) { return s.value; })
                .filter(function (v) { return v !== ''; });

            document.querySelectorAll('.peralatan-select').forEach(function (select) {
                var currentVal = select.value;
                Array.from(select.options).forEach(function (opt) {
                    if (!opt.value) return;
                    opt.hidden = selected.includes(opt.value) && opt.value !== currentVal;
                });

                var jumlahInput = select.closest('.dynamic-item').querySelector('.jumlah-input');
                var opt = select.options[select.selectedIndex];
                var sisa = opt ? parseInt(opt.dataset.sisa) : NaN;

                if (currentVal) {
                    jumlahInput.style.display = '';
                    if (!jumlahInput.value) jumlahInput.value = 1;
                    if (!isNaN(sisa)) {
                        jumlahInput.max = sisa;
                        jumlahInput.title = 'Bisa dipasang: ' + sisa;
                    }
                } else {
                    jumlahInput.style.display = 'none';
                    jumlahInput.value = '';
                    jumlahInput.removeAttribute('max');
                }
            });
            window.SearchableSelect && window.SearchableSelect.refreshAll();
        }

        function removePeralatan(btn) {
            var list = document.getElementById('peralatan-list');
            var item = btn.closest('.dynamic-item');
            if (list.children.length > 1) {
                item.remove();
            } else {
                item.querySelector('select').value = '';
            }
            refreshPeralatanOptions();
        }

        function addPeralatan() {
            var list = document.getElementById('peralatan-list');
            var first = list.querySelector('.dynamic-item');
            var clone = first.cloneNode(true);
            clone.querySelectorAll('option').forEach(function (opt) { opt.hidden = false; });
            clone.querySelector('select').value = '';
            clone.querySelector('.jumlah-input').value = '';
            clone.querySelector('.jumlah-input').style.display = 'none';
            clone.querySelector('select').onchange = refreshPeralatanOptions;
            window.SearchableSelect && window.SearchableSelect.reinitRow(clone);
            list.appendChild(clone);
            refreshPeralatanOptions();
        }

        document.getElementById('add-peralatan').addEventListener('click', addPeralatan);
    </script>
@endpush
