@extends('layouts.app')
@section('title', 'Tambah Jadwal')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Tambah Jadwal</h1>
            </div>
            <a href="{{ route('admin.jadwal.index') }}" class="toolbar-btn neutral">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div
                style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;color:#c0392b;">
                <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.jadwal.store') }}" method="POST" autocomplete="off">
            @csrf

            <div class="form-card">
                <h3><i class="bx bx-info-circle"></i> Informasi Jadwal</h3>
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Judul Rapat <span class="req">*</span></label>
                        <input type="text" name="judul_kegiatan"
                            class="form-input {{ $errors->has('judul_kegiatan') ? 'error' : '' }}"
                            value="{{ old('judul_kegiatan') }}" placeholder="cth: Rapat Koordinasi Bulanan" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="req">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" class="form-input" value="{{ old('tanggal') }}"
                            min="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Platform <span class="req">*</span></label>
                        <select name="platform" id="platform" class="form-select" required>
                            <option value="">-- Pilih Platform --</option>
                            @foreach(['Online (Zoom)', 'Online (Google Meet)', 'Offline', 'Hybrid'] as $p)
                                <option value="{{ $p }}" {{ old('platform') == $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Mulai <span class="req">*</span></label>
                        <input type="time" name="waktu_mulai" class="form-input" value="{{ old('waktu_mulai') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Selesai <span class="req">*</span></label>
                        <input type="time" name="waktu_selesai" class="form-input" value="{{ old('waktu_selesai') }}"
                            required>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label" id="ket-label">Keterangan</label>
                        <input type="text" name="keterangan" id="keterangan" class="form-input"
                            value="{{ old('keterangan') }}" placeholder="Pilih platform terlebih dahulu">
                        <span class="form-hint" id="ket-hint">Pilih platform untuk petunjuk pengisian.</span>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h3><i class="bx bxs-group"></i> Operator Bertugas <span class="req">*</span></h3>
                <p class="form-hint" style="margin-bottom:12px;">
                    Operator yang sudah dipilih di baris lain otomatis tersembunyi.
                    Operator yang sudah punya jadwal di tanggal ini akan di-disable.
                </p>
                <div class="dynamic-list" id="operator-list">
                    <div class="dynamic-item">
                        <select name="operator_ids[]" class="form-select operator-select" required
                            onchange="refreshOperatorOptions()">
                            <option value="" disabled selected>-- Pilih Operator --</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id_user }}" data-nohp="{{ $op->nohp }}"
                                    data-jadwal='@json($op->jadwalDitugaskan->pluck("tanggal")->map(fn($t) => \Carbon\Carbon::parse($t)->format("Y-m-d")))'>
                                    {{ $op->nama_user }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-remove" onclick="removeOperator(this)" disabled>
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add-item" id="add-operator">
                    <i class="bx bx-plus"></i> Tambah Operator
                </button>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.jadwal.index') }}" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-submit">
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
                hint.textContent = 'Masukkan link meeting.';
                inp.placeholder = 'cth: https://zoom.us/j/xxxxxxx';
                inp.type = 'url';
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
                    // Bentrok jadwal → disable tapi tetap tampil dengan keterangan
                    opt.disabled = isBentrok && !isSelectedElsewhere;

                    if (isBentrok && !isSelectedElsewhere) {
                        if (!opt.dataset.origText) opt.dataset.origText = opt.textContent;
                        opt.textContent = opt.dataset.origText + ' (jadwal bentrok)';
                    } else if (opt.dataset.origText) {
                        opt.textContent = opt.dataset.origText;
                    }
                });
            });
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
    </script>
@endpush