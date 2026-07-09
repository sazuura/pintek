@extends('layouts.app')
@section('title', 'Tambah Jadwal')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Tambah Jadwal</h1>
            </div>
            <a href="{{ route('admin.jadwal.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        <form action="{{ route('admin.jadwal.store') }}" method="POST" autocomplete="off" novalidate>
            @csrf

            @php
                $inputClass = 'h-10 px-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
                $hintClass = 'text-xs text-text-muted mt-0.5';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-info-circle"></i> Informasi Jadwal</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <x-input name="judul_kegiatan" label="Judul Rapat" required placeholder="cth: Rapat Koordinasi Bulanan" />
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-4">
                        <div class="flex-1 basis-[200px]">
                            <x-input type="date" name="tanggal" label="Tanggal" required min="{{ now()->format('Y-m-d') }}" />
                        </div>
                        <div class="flex-1 basis-[200px]">
                            <x-select name="platform" label="Platform" required placeholder="-- Pilih Platform --">
                                @foreach(['Online (Zoom)', 'Online (Google Meet)', 'Offline', 'Hybrid'] as $p)
                                    <option value="{{ $p }}" {{ old('platform') == $p ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <x-input type="time" name="waktu_mulai" label="Waktu Mulai" required />

                    <x-input type="time" name="waktu_selesai" label="Waktu Selesai" required />

                    <div class="md:col-span-2">
                        <x-input name="keterangan" label="Keterangan" placeholder="Pilih platform terlebih dahulu"
                            hint="Pilih platform untuk petunjuk pengisian." hint-id="ket-hint" />
                    </div>
                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="text-[13px] font-medium text-text dark:text-text-dark">Alat yang Dibutuhkan <small class="font-normal text-text-muted ml-1">(opsional)</small></label>
                        <p class="{{ $hintClass }} mb-3">
                            Daftar ini hanya menjadi catatan acuan bagi operator dan bukan merupakan pengajuan
                            peminjaman. Operator tetap wajib mengajukan peminjaman secara mandiri melalui menu
                            Peminjaman apabila peralatan tersebut akan benar-benar digunakan.
                        </p>
                        <div class="dynamic-list flex flex-col gap-2.5" id="peralatan-list">
                            <div class="dynamic-item flex gap-2.5 items-center">
                                <select name="peralatan_ids[]" class="peralatan-select searchable flex-1"
                                    data-placeholder="Cari alat..." onchange="refreshPeralatanOptions()">
                                    <option value="" selected>-- Pilih Alat --</option>
                                    @foreach($daftarPeralatan as $alat)
                                        <option value="{{ $alat->id_peralatan }}"
                                            data-subtitle="{{ $alat->gedung }} &middot; Stok: {{ $alat->stok_tersedia }}"
                                            @if($alat->stok_tersedia <= 0) data-badge="Stok Habis" data-badge-variant="danger" @endif>
                                            {{ $alat->nama_peralatan }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" name="peralatan_jumlah[]" class="peralatan-jumlah {{ $inputClass }} flex-[0_0_80px]" min="1"
                                    placeholder="Jml" style="display:none;">
                                <button type="button" onclick="removePeralatan(this)"
                                    class="btn-remove w-9 h-9 rounded-lg border-none bg-danger dark:bg-danger-dark text-danger-text cursor-pointer flex items-center justify-center shrink-0 text-base transition-colors duration-200 hover:bg-danger-text hover:text-white">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" id="add-peralatan"
                            class="h-9 px-3.5 bg-page-bg dark:bg-page-bg-dark text-primary border border-dashed border-primary rounded-lg text-[13px] font-sans font-medium cursor-pointer inline-flex items-center gap-1.5 transition-colors duration-200 mt-1 w-fit hover:bg-primary-50">
                            <i class="bx bx-plus"></i> Tambah Alat
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bxs-group"></i> Operator Bertugas <span class="text-[#e74c3c] ml-0.5">*</span></h3>
                <p class="text-xs text-text-muted mb-1.5">
                    Operator yang sudah dipilih di baris lain otomatis tersembunyi.
                    Operator yang sudah punya jadwal di tanggal ini akan di-disable.
                </p>
                <div class="dynamic-list flex flex-col gap-2.5" id="operator-list">
                    <div class="dynamic-item flex gap-2.5 items-center">
                        <select name="operator_ids[]" class="operator-select searchable flex-1" required
                            data-placeholder="-- Pilih Operator --" onchange="refreshOperatorOptions()">
                            <option value="" disabled selected>-- Pilih Operator --</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id_user }}" data-subtitle="{{ $op->nohp ?? '-' }}"
                                    data-jadwal='@json($op->jadwalDitugaskan->pluck("tanggal")->map(fn($t) => \Carbon\Carbon::parse($t)->format("Y-m-d")))'>
                                    {{ $op->nama_user }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" onclick="removeOperator(this)" disabled
                            class="btn-remove w-9 h-9 rounded-lg border-none bg-danger dark:bg-danger-dark text-danger-text cursor-pointer flex items-center justify-center shrink-0 text-base transition-colors duration-200 hover:bg-danger-text hover:text-white disabled:opacity-40 disabled:pointer-events-none">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" id="add-operator"
                    class="h-9 px-3.5 bg-page-bg dark:bg-page-bg-dark text-primary border border-dashed border-primary rounded-lg text-[13px] font-sans font-medium cursor-pointer inline-flex items-center gap-1.5 transition-colors duration-200 mt-1 w-fit hover:bg-primary-50">
                    <i class="bx bx-plus"></i> Tambah Operator
                </button>
                @error('operator_ids')
                    <span class="text-xs text-danger-text mt-2 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2.5 mt-6 pt-5 border-t border-page-bg dark:border-page-bg-dark">
                <a href="{{ route('admin.jadwal.index') }}"
                    class="h-10 px-5 bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
                <button type="submit"
                    class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
                    <i class="bx bx-send"></i> Simpan & Kirim Notif WA
                </button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        // ── Platform hint ──────────────────────────────────────────────────────────
        document.getElementById('platform').addEventListener('change', function () {
            var v = this.value;
            var hint = document.getElementById('ket-hint');
            var inp = document.getElementById('keterangan');
            if (v.includes('Offline')) {
                hint.textContent = 'Masukkan lokasi rapat (Gedung, Ruangan, Lantai).';
                inp.placeholder = 'cth: Gedung A Lt.2 Ruang Rapat 1';
                inp.type = 'text';
            } else if (v.includes('Online')) {
                hint.textContent = 'Keterangan tambahan (opsional), misalnya link meeting.';
                inp.placeholder = 'cth: https://zoom.us/j/xxxxxxx';
                inp.type = 'text';
            } else if (v === 'Hybrid') {
                hint.textContent = 'Masukkan link meeting dan lokasi fisik.';
                inp.placeholder = 'cth: zoom.us/j/xxx | Gedung A Lt.2';
                inp.type = 'text';
            }
        });

        // ── Operator: hide yang sudah dipilih, disable yang bentrok tanggal ────────
        function getSelectedOperators() {
            return Array.from(document.querySelectorAll('.operator-select'))
                .map(function (s) { return s.value; })
                .filter(function (v) { return v !== ''; });
        }

        function getTanggal() {
            return document.getElementById('tanggal').value;
        }

        function refreshOperatorOptions() {
            var selected = getSelectedOperators();
            var tanggal = getTanggal();

            document.querySelectorAll('.operator-select').forEach(function (select) {
                var currentVal = select.value;
                Array.from(select.options).forEach(function (opt) {
                    if (!opt.value) return; // skip placeholder

                    var jadwalDates = [];
                    try { jadwalDates = JSON.parse(opt.dataset.jadwal || '[]'); } catch (e) { }

                    var isSelectedElsewhere = selected.includes(opt.value) && opt.value !== currentVal;
                    var isBentrok = tanggal && jadwalDates.includes(tanggal) && opt.value !== currentVal;

                    // Sudah dipilih di baris lain → sembunyikan
                    opt.hidden = isSelectedElsewhere;
                    // Bentrok jadwal → disable tapi tetap tampil dengan badge keterangan
                    opt.disabled = isBentrok && !isSelectedElsewhere;

                    if (isBentrok && !isSelectedElsewhere) {
                        opt.dataset.badge = 'Jadwal Bentrok';
                    } else {
                        delete opt.dataset.badge;
                    }
                });
            });
            window.SearchableSelect && window.SearchableSelect.refreshAll();
        }

        // Update saat tanggal berubah
        document.getElementById('tanggal').addEventListener('change', refreshOperatorOptions);

        function removeOperator(btn) {
            var list = document.getElementById('operator-list');
            if (list.children.length > 1) {
                btn.closest('.dynamic-item').remove();
                refreshOperatorOptions();
            }
            updateRemoveButtons();
        }

        function addOperator() {
            var list = document.getElementById('operator-list');
            var first = list.querySelector('.dynamic-item');
            var clone = first.cloneNode(true);
            clone.querySelector('select').value = '';
            clone.querySelector('select').onchange = refreshOperatorOptions;
            window.SearchableSelect && window.SearchableSelect.reinitRow(clone);
            clone.querySelector('.btn-remove').disabled = false;
            clone.querySelector('.btn-remove').onclick = function () { removeOperator(this); };
            list.appendChild(clone);
            refreshOperatorOptions();
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            var items = document.querySelectorAll('#operator-list .dynamic-item');
            items.forEach(function (item, i) {
                item.querySelector('.btn-remove').disabled = items.length <= 1;
            });
        }

        document.getElementById('add-operator').addEventListener('click', addOperator);

        // ── Alat yang dibutuhkan: cuma cegah alat yang sama dipilih dobel ──────────
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

                var jumlahInput = select.closest('.dynamic-item').querySelector('.peralatan-jumlah');
                if (currentVal) {
                    jumlahInput.style.display = '';
                    if (!jumlahInput.value) jumlahInput.value = 1;
                } else {
                    jumlahInput.style.display = 'none';
                    jumlahInput.value = '';
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
                // Baris terakhir: reset ke kosong daripada dihapus, biar selalu ada minimal 1 baris template.
                item.querySelector('select').value = '';
            }
            refreshPeralatanOptions();
            updatePeralatanRemoveButtons();
        }

        function addPeralatan() {
            var list = document.getElementById('peralatan-list');
            var first = list.querySelector('.dynamic-item');
            var clone = first.cloneNode(true);
            clone.querySelectorAll('option').forEach(function (opt) { opt.hidden = false; });
            clone.querySelector('select').value = '';
            clone.querySelector('select').onchange = refreshPeralatanOptions;
            window.SearchableSelect && window.SearchableSelect.reinitRow(clone);
            clone.querySelector('.btn-remove').disabled = false;
            clone.querySelector('.btn-remove').onclick = function () { removePeralatan(this); };
            list.appendChild(clone);
            refreshPeralatanOptions();
            updatePeralatanRemoveButtons();
        }

        function updatePeralatanRemoveButtons() {
            document.querySelectorAll('#peralatan-list .btn-remove').forEach(function (btn) {
                btn.disabled = false;
            });
        }

        document.getElementById('add-peralatan').addEventListener('click', addPeralatan);
    </script>
@endpush
