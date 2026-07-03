@extends('layouts.app')
@section('title', 'Edit Jadwal')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Edit Jadwal</h1>
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

        <form action="{{ route('admin.jadwal.update', $jadwal->id_penjadwalan) }}" method="POST">
            @csrf @method('PUT')

            <div class="form-card">
                <h3><i class="bx bx-info-circle"></i> Informasi Jadwal</h3>
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Judul Rapat <span class="req">*</span></label>
                        <input type="text" name="judul_kegiatan" class="form-input"
                            value="{{ old('judul_kegiatan', $jadwal->judul_kegiatan) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="req">*</span></label>
                        <input type="date" name="tanggal" class="form-input"
                            value="{{ old('tanggal', $jadwal->tanggal->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Platform <span class="req">*</span></label>
                        <select name="platform" class="form-select" required>
                            @foreach(['Online (Zoom)', 'Online (Google Meet)', 'Offline', 'Hybrid'] as $p)
                                <option value="{{ $p }}" {{ old('platform', $jadwal->platform) == $p ? 'selected' : '' }}>{{ $p }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Mulai <span class="req">*</span></label>
                        <input type="time" name="waktu_mulai" class="form-input"
                            value="{{ old('waktu_mulai', substr($jadwal->waktu_mulai, 0, 5)) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Selesai <span class="req">*</span></label>
                        <input type="time" name="waktu_selesai" class="form-input"
                            value="{{ old('waktu_selesai', substr($jadwal->waktu_selesai, 0, 5)) }}" required>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-input"
                            value="{{ old('keterangan', $jadwal->keterangan) }}">
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
                @foreach($selectedOperators as $idUser)
                    <div class="dynamic-item">
                        <select name="operator_ids[]" class="form-select operator-select" required
                            onchange="refreshOperatorOptions()">
                            <option value="" disabled selected>-- Pilih Operator --</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id_user }}"
                                        data-jadwal='@json($op->jadwalDitugaskan->pluck("tanggal")->map(fn($t) => \Carbon\Carbon::parse($t)->format("Y-m-d")))'
                                        {{ $op->id_user == $idUser ? 'selected' : '' }}>
                                    {{ $op->nama_user }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-remove" onclick="removeOperator(this)" disabled>
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn-add-item" id="add-operator">
                <i class="bx bx-plus"></i> Tambah Operator
            </button>
        </div>

            <div class="form-actions">
                <a href="{{ route('admin.jadwal.index') }}" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-submit"><i class="bx bx-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        // ── Operator: hide yang sudah dipilih, disable yang bentrok tanggal ────────
        function getSelectedOperators() {
            return Array.from(document.querySelectorAll('.operator-select'))
                .map(function (s) { return s.value; })
                .filter(function (v) { return v !== ''; });
        }

        function getTanggal() {
            var tglInput = document.getElementById('tanggal');
            return tglInput ? tglInput.value : '';
        }

        function refreshOperatorOptions() {
            var selected = getSelectedOperators();
            var tanggal = getTanggal();

            document.querySelectorAll('.operator-select').forEach(function (select) {
                var currentVal = select.value;
                Array.from(select.options).forEach(function (opt) {
                    if (!opt.value) return; 

                    var jadwalDates = [];
                    try { jadwalDates = JSON.parse(opt.dataset.jadwal || '[]'); } catch (e) { }

                    var isSelectedElsewhere = selected.includes(opt.value) && opt.value !== currentVal;
                    var isBentrok = tanggal && jadwalDates.includes(tanggal) && opt.value !== currentVal;

                    opt.hidden = isSelectedElsewhere;
                    opt.disabled = isBentrok && !isSelectedElsewhere;

                    if (isBentrok && !isSelectedElsewhere) {
                        if (!opt.dataset.origText) opt.dataset.origText = opt.textContent.replace(' (jadwal bentrok)', '');
                        opt.textContent = opt.dataset.origText + ' (jadwal bentrok)';
                    } else {
                        if (opt.dataset.origText) {
                            opt.textContent = opt.dataset.origText;
                        }
                    }
                });
            });
        }

        var tglEl = document.getElementById('tanggal');
        if (tglEl) {
            tglEl.addEventListener('change', refreshOperatorOptions);
        }

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
        
            clone.querySelectorAll('option').forEach(function(opt) {
                if(opt.dataset.origText) {
                    opt.textContent = opt.dataset.origText;
                    delete opt.dataset.origText;
                }
                opt.hidden = false;
                opt.disabled = false;
            });

            var select = clone.querySelector('select');
            select.value = '';
            select.onchange = refreshOperatorOptions;
            
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
        document.addEventListener("DOMContentLoaded", function() {
            refreshOperatorOptions();
            updateRemoveButtons();
        });
    </script>
@endpush