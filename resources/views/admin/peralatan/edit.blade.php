@extends('layouts.app')
@section('title', 'Edit Peralatan')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
<main>
    <div class="head-title">
        <div class="left"><h1>Edit Peralatan</h1></div>
        <a href="{{ route('admin.peralatan.index') }}" class="toolbar-btn neutral">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    @if($errors->any())
    <div style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;color:#c0392b;">
        <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.peralatan.update', $peralatan->id_peralatan) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="form-card">
            <h3><i class="bx bx-info-circle"></i> Informasi Peralatan</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">ID Peralatan</label>
                    <input type="text" class="form-input" value="{{ $peralatan->id_peralatan }}" readonly>
                    <span class="form-hint">ID tidak bisa diubah.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Kode Barang</label>
                    <input type="text" name="kode_barang" class="form-input {{ $errors->has('kode_barang')?'error':'' }}"
                        value="{{ old('kode_barang', $peralatan->kode_barang) }}" placeholder="cth: GU/LAP/2024/001">
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Peralatan <span class="req">*</span></label>
                    <input type="text" name="nama_peralatan" class="form-input"
                        value="{{ old('nama_peralatan', $peralatan->nama_peralatan) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Gedung <span class="req">*</span></label>
                    <input type="text" name="gedung" class="form-input"
                        value="{{ old('gedung', $peralatan->gedung) }}" list="gedung-list" required>
                    <datalist id="gedung-list">
                        @foreach($gedungList as $g)<option value="{{ $g }}">@endforeach
                    </datalist>
                </div>
                <div class="form-group">
                    <label class="form-label">Lokasi Detail</label>
                    <input type="text" name="lokasi_detail" class="form-input"
                        value="{{ old('lokasi_detail', $peralatan->lokasi_detail) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-input"
                        value="{{ old('keterangan', $peralatan->keterangan) }}">
                </div>
            </div>
        </div>

        <div class="form-card">
            <h3><i class="bx bx-data"></i> Data Stok</h3>
            <div class="form-grid cols-3">
                <div class="form-group">
                    <label class="form-label">Stok Total <span class="req">*</span></label>
                    <input type="number" name="stok" class="form-input {{ $errors->has('stok')?'error':'' }}"
                        value="{{ old('stok', $peralatan->stok) }}" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Rusak</label>
                    <input type="number" name="rusak" class="form-input {{ $errors->has('rusak')?'error':'' }}"
                        value="{{ old('rusak', $peralatan->rusak) }}" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Dalam Perbaikan</label>
                    <input type="number" name="perbaikan" class="form-input"
                        value="{{ old('perbaikan', $peralatan->perbaikan) }}" min="0">
                </div>
            </div>
            <div style="margin-top:12px;padding:10px 14px;background:var(--grey);border-radius:8px;font-size:13px;">
                <span style="color:var(--dark-grey);">Stok tersedia = stok - rusak - perbaikan = </span>
                <strong style="color:var(--blue);" id="stok-preview">{{ $peralatan->stok_tersedia }}</strong>
            </div>
        </div>

        <div class="form-card">
            <h3><i class="bx bx-image"></i> Foto</h3>
            @if($peralatan->foto)
            <div style="margin-bottom:14px;">
                <p style="font-size:13px;color:var(--dark-grey);margin-bottom:8px;">Foto saat ini:</p>
                <img src="{{ Storage::url($peralatan->foto) }}" alt="Foto"
                     style="height:120px;border-radius:8px;object-fit:cover;border:1px solid var(--grey);">
                <label style="display:flex;align-items:center;gap:8px;margin-top:10px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="hapus_foto" value="1"> Hapus foto ini
                </label>
            </div>
            @endif
            <div class="form-group">
                <label class="form-label">{{ $peralatan->foto ? 'Upload Foto Baru' : 'Upload Foto' }} <small>(opsional, max 2MB)</small></label>
                <input type="file" name="foto" class="form-input" accept="image/jpg,image/jpeg,image/png,image/webp"
                    id="foto-input" style="height:auto;padding:8px 12px;">
                <div id="foto-preview" style="margin-top:10px;display:none;">
                    <img id="foto-img" src="" alt="Preview" style="height:120px;border-radius:8px;object-fit:cover;border:1px solid var(--grey);">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.peralatan.index') }}" class="btn-cancel">Batal</a>
            <button type="submit" class="btn-submit"><i class="bx bx-save"></i> Simpan Perubahan</button>
        </div>
    </form>
</main>
@endsection

@push('scripts')
<script>
// Live preview stok tersedia
var inputs = ['stok','rusak','perbaikan'].map(function(n){ return document.querySelector('[name="'+n+'"]'); });
function updatePreview() {
    var s = parseInt(inputs[0].value)||0;
    var r = parseInt(inputs[1].value)||0;
    var p = parseInt(inputs[2].value)||0;
    document.getElementById('stok-preview').textContent = Math.max(0, s-r-p);
}
inputs.forEach(function(el){ if(el) el.addEventListener('input', updatePreview); });

// Foto preview
document.getElementById('foto-input').addEventListener('change', function () {
    var file = this.files[0];
    var prev = document.getElementById('foto-preview');
    var img  = document.getElementById('foto-img');
    if (file) { img.src = URL.createObjectURL(file); prev.style.display='block'; }
    else       { prev.style.display='none'; }
});
</script>
@endpush
