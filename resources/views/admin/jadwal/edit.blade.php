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
                    <div class="form-group span-2 form-row-wrap">
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
                    <div class="form-group span-2">
                        <label class="form-label">Alat yang Dibutuhkan <small>(opsional)</small></label>
                        <p class="form-hint" style="margin-bottom:12px;">
                            Ini cuma catatan acuan buat operator, bukan pengajuan peminjaman. Operator tetap harus
                            ajukan sendiri lewat menu Peminjaman kalau mau benar-benar memakai alatnya.
                        </p>
                        <div class="dynamic-list" id="peralatan-list">
                            @forelse($selectedPeralatan as $alatTerpilih)
                                <div class="dynamic-item">
                                    <select name="peralatan_ids[]" class="form-select peralatan-select searchable"
                                        data-placeholder="Cari alat..." onchange="refreshPeralatanOptions()">
                                        <option value="">-- Pilih Alat --</option>
                                        @foreach($daftarPeralatan as $alat)
                                            <option value="{{ $alat->id_peralatan }}" {{ $alat->id_peralatan == $alatTerpilih->id_peralatan ? 'selected' : '' }}>{{ $alat->nama_peralatan }} ({{ $alat->gedung }})</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="peralatan_jumlah[]" class="form-input peralatan-jumlah" min="1"
                                        value="{{ $alatTerpilih->pivot->jumlah }}" placeholder="Jml" style="flex:0 0 80px;">
                                    <button type="button" class="btn-remove" onclick="removePeralatan(this)">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            @empty
                                <div class="dynamic-item">
                                    <select name="peralatan_ids[]" class="form-select peralatan-select searchable"
                                        data-placeholder="Cari alat..." onchange="refreshPeralatanOptions()">
                                        <option value="" selected>-- Pilih Alat --</option>
                                        @foreach($daftarPeralatan as $alat)
                                            <option value="{{ $alat->id_peralatan }}">{{ $alat->nama_peralatan }} ({{ $alat->gedung }})</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="peralatan_jumlah[]" class="form-input peralatan-jumlah" min="1"
                                        placeholder="Jml" style="flex:0 0 80px;display:none;">
                                    <button type="button" class="btn-remove" onclick="removePeralatan(this)">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            @endforelse
                        </div>
                        <button type="button" class="btn-add-item" id="add-peralatan">
                            <i class="bx bx-plus"></i> Tambah Alat
                        </button>
                    </div>
                </div>
            </div>

        <div class="form-card">
            <h3><i class="bx bxs-group"></i> Operator Bertugas <span class="req">*</span></h3>
            <p class="form-hint" style="margin-bottom:6px;">
                Operator yang sudah dipilih di baris lain otomatis tersembunyi.
                Operator yang sudah punya jadwal di tanggal ini akan di-disable.
            </p>
            <div class="dynamic-list" id="operator-list">
                @foreach($selectedOperators as $idUser)
                    <div class="dynamic-item">
                        <select name="operator_ids[]" class="form-select operator-select searchable" required
                            data-placeholder="Cari operator..." onchange="refreshOperatorOptions()">
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
            window.SearchableSelect && window.SearchableSelect.refreshAll();
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

        document.addEventListener("DOMContentLoaded", function() {
            refreshOperatorOptions();
            updateRemoveButtons();
            refreshPeralatanOptions();
            updatePeralatanRemoveButtons();
        });
    </script>
@endpush