@extends('layouts.app')
@section('title', 'Tambah Peralatan')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Tambah Peralatan</h1></div>
        <a href="{{ route('admin.peralatan.index') }}" class="toolbar-btn neutral">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    @if($errors->any())
    <div style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;color:#c0392b;">
        <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.peralatan.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-card">
            <h3><i class="bx bx-info-circle"></i> Informasi Peralatan</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Kode Barang <small>(opsional)</small></label>
                    <input type="text" name="kode_barang" class="form-input {{ $errors->has('kode_barang')?'error':'' }}"
                        value="{{ old('kode_barang') }}" placeholder="cth: GU/LAP/2024/001">
                    <span class="form-hint">Kode inventaris fisik. Harus unik jika diisi.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Peralatan <span class="req">*</span></label>
                    <input type="text" name="nama_peralatan" class="form-input {{ $errors->has('nama_peralatan')?'error':'' }}"
                        value="{{ old('nama_peralatan') }}" placeholder="cth: Laptop Zoom Host" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Gedung <span class="req">*</span></label>
                    <input type="text" name="gedung" class="form-input {{ $errors->has('gedung')?'error':'' }}"
                        value="{{ old('gedung') }}" list="gedung-list" placeholder="cth: Gedung A" required>
                    <datalist id="gedung-list">
                        @foreach($gedungList as $g)<option value="{{ $g }}">@endforeach
                    </datalist>
                    <span class="form-hint">Harus cocok persis dengan gedung inventaris yang bertugas.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Lokasi Detail <small>(opsional)</small></label>
                    <input type="text" name="lokasi_detail" class="form-input"
                        value="{{ old('lokasi_detail') }}" placeholder="cth: Rak 3, Lt.2">
                </div>
                <div class="form-group">
                    <label class="form-label">Stok Total <span class="req">*</span></label>
                    <input type="number" name="stok" class="form-input {{ $errors->has('stok')?'error':'' }}"
                        value="{{ old('stok', 0) }}" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan <small>(opsional)</small></label>
                    <input type="text" name="keterangan" class="form-input"
                        value="{{ old('keterangan') }}" placeholder="Catatan tambahan">
                </div>
                <div class="form-group span-2">
                    <label class="form-label">Foto <small>(opsional, max 2MB)</small></label>
                    <input type="file" name="foto" class="form-input" accept="image/jpg,image/jpeg,image/png,image/webp"
                        id="foto-input" style="height:auto;padding:8px 12px;">
                    <div id="foto-preview" style="margin-top:10px;display:none;">
                        <img id="foto-img" src="" alt="Preview" style="height:120px;border-radius:8px;object-fit:cover;border:1px solid var(--grey);">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.peralatan.index') }}" class="btn-cancel">Batal</a>
            <button type="submit" class="btn-submit"><i class="bx bx-save"></i> Simpan</button>
        </div>
    </form>
</main>
@endsection

@push('scripts')
<script>
document.getElementById('foto-input').addEventListener('change', function () {
    var file    = this.files[0];
    var preview = document.getElementById('foto-preview');
    var img     = document.getElementById('foto-img');
    if (file) {
        img.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});
</script>
@endpush
