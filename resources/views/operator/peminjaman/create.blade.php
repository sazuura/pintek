@extends('layouts.app')
@section('title', 'Ajukan Peminjaman')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Ajukan Peminjaman Peralatan</h1>
            </div>
            <a href="{{ route('operator.peminjaman.index') }}" class="toolbar-btn neutral">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div
                style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;color:#c0392b;">
                <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('operator.peminjaman.store') }}" method="POST">
            @csrf

            <div class="form-card">
                <h3><i class="bx bx-info-circle"></i> Detail Pengajuan</h3>
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Keperluan <span class="req">*</span></label>
                        <input type="text" name="keperluan"
                            class="form-input {{ $errors->has('keperluan') ? 'error' : '' }}" value="{{ old('keperluan') }}"
                            placeholder="cth: Rapat dinas luar kota bersama Kemendagri" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pinjam <span class="req">*</span></label>
                        <input type="date" name="tanggal_pinjam"
                            class="form-input {{ $errors->has('tanggal_pinjam') ? 'error' : '' }}"
                            value="{{ old('tanggal_pinjam') }}" min="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rencana Kembali <span class="req">*</span></label>
                        <input type="date" name="tanggal_kembali_rencana"
                            class="form-input {{ $errors->has('tanggal_kembali_rencana') ? 'error' : '' }}"
                            value="{{ old('tanggal_kembali_rencana') }}" required>
                        <span class="form-hint">Harus setelah tanggal pinjam.</span>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h3><i class="bx bxs-wrench"></i> Pilih Peralatan <span class="req">*</span></h3>
                <p class="form-hint" style="margin-bottom:14px;">
                    Peralatan dari gedung berbeda akan mengirim notifikasi ke masing-masing inventaris secara otomatis.
                    Peralatan yang sudah dipilih di baris lain tersembunyi otomatis.
                </p>

                <div class="dynamic-list" id="peralatan-list">
                    <div class="dynamic-item">
                        <select name="peralatan_ids[]" class="form-select peralatan-select"
                            onchange="refreshPeralatanOptions()" required>
                            <option value="" disabled selected>-- Pilih Peralatan --</option>
                            @foreach($peralatan as $gedung => $items)
                                <optgroup label="{{ $gedung }}">
                                    @foreach($items as $alat)
                                        <option value="{{ $alat->id_peralatan }}"
                                            data-stok="{{ $alat->stok_tersedia }}"
                                            {{ (isset($selectedPeralatanId) && $selectedPeralatanId == $alat->id_peralatan) ? 'selected' : '' }}>
                                            {{ $alat->nama_peralatan }} — stok: {{ $alat->stok_tersedia }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <input type="number" name="peralatan_jumlah[]" class="form-input" min="1" placeholder="Jml"
                            style="flex:0 0 80px;" required>
                        <button type="button" class="btn-remove" onclick="removeItem(this)" disabled>
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add-item" id="add-peralatan">
                    <i class="bx bx-plus"></i> Tambah Peralatan
                </button>
            </div>

            <div class="form-actions">
                <a href="{{ route('operator.peminjaman.index') }}" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-submit">
                    <i class="bx bx-send"></i> Kirim Pengajuan
                </button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        function getSelectedPeralatan() {
            return Array.from(document.querySelectorAll('.peralatan-select'))
                .map(function (s) { return s.value; }).filter(function (v) { return v !== ''; });
        }

        function refreshPeralatanOptions() {
            var selected = getSelectedPeralatan();
            document.querySelectorAll('.peralatan-select').forEach(function (select) {
                var currentVal = select.value;
                Array.from(select.options).forEach(function (opt) {
                    if (!opt.value) return;
                    opt.hidden = selected.includes(opt.value) && opt.value !== currentVal;
                });
            });
        }

        function removeItem(btn) {
            var list = document.getElementById('peralatan-list');
            if (list.children.length > 1) {
                btn.closest('.dynamic-item').remove();
                refreshPeralatanOptions();
            }
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            var items = document.querySelectorAll('#peralatan-list .dynamic-item');
            items.forEach(function (item) {
                item.querySelector('.btn-remove').disabled = items.length <= 1;
            });
        }

        document.getElementById('add-peralatan').addEventListener('click', function () {
            var list = document.getElementById('peralatan-list');
            var clone = list.querySelector('.dynamic-item').cloneNode(true);
            clone.querySelector('select').value = '';
            clone.querySelector('input[type=number]').value = '';
            clone.querySelector('select').onchange = refreshPeralatanOptions;
            clone.querySelector('.btn-remove').disabled = false;
            clone.querySelector('.btn-remove').onclick = function () { removeItem(this); };
            list.appendChild(clone);
            refreshPeralatanOptions();
            updateRemoveButtons();
        });
        document.addEventListener('DOMContentLoaded', function () {
            refreshPeralatanOptions();
        });
    </script>
@endpush