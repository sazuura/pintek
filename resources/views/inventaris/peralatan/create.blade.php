@extends('layouts.app')
@section('title', 'Tambah Peralatan')
@section('sidebar-menu') <x-sidebar-inventaris /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Tambah Peralatan</h1>
            </div>
            <a href="{{ route('inventaris.peralatan.index') }}" class="toolbar-btn neutral">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div
                style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;color:#c0392b;">
                <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('inventaris.peralatan.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-card">
                <h3><i class="bx bx-info-circle"></i> Informasi Peralatan</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nomor Seri <span class="req">*</span></label>
                        <input type="text" name="kode_barang"
                            class="form-input {{ $errors->has('kode_barang') ? 'error' : '' }}"
                            value="{{ old('kode_barang') }}" placeholder="cth: GU/LAP/2024/001" required>
                        <span class="form-hint">Nomor seri fisik barang. Harus unik jika diisi.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Peralatan <span class="req">*</span></label>
                        <input type="text" name="nama_peralatan"
                            class="form-input {{ $errors->has('nama_peralatan') ? 'error' : '' }}"
                            value="{{ old('nama_peralatan') }}" placeholder="cth: Laptop Zoom Host" required>
                    </div>
                    {{-- Gedung otomatis dari akun inventaris yang login --}}
                    <div class="form-group">
                        <label class="form-label">Lokasi<span class="req">*</span></label>
                        <input type="text" name="gedung" class="form-input" value="{{ old('gedung')}}"
                            placeholder="Gedung A" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Lokasi Detail</label>
                        <input type="text" name="lokasi_detail" class="form-input" value="{{ old('lokasi_detail') }}"
                            placeholder="cth: Rak 3, Lt.2">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stok Total <span class="req">*</span></label>
                        <input type="number" name="stok" class="form-input {{ $errors->has('stok') ? 'error' : '' }}"
                            value="{{ old('stok', 0) }}" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan <small>(opsional)</small></label>
                        <input type="text" name="keterangan" class="form-input" value="{{ old('keterangan') }}"
                            placeholder="Catatan tambahan">
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Foto <small>(opsional, max 2MB)</small></label>
                        <input type="file" name="foto" class="form-input" id="foto-input"
                            accept="image/jpg,image/jpeg,image/png,image/webp" style="height:auto;padding:8px 12px;">
                        <div id="foto-preview" style="margin-top:10px;display:none;">
                            <img id="foto-img" src="" alt="Preview"
                                style="height:120px;border-radius:8px;object-fit:cover;border:1px solid var(--grey);">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('inventaris.peralatan.index') }}" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-submit"><i class="bx bx-save"></i> Simpan</button>
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
            if (file) { img.src = URL.createObjectURL(file); prev.style.display = 'block'; }
            else { prev.style.display = 'none'; }
        });
    </script>
@endpush