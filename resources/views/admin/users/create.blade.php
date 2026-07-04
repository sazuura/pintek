@extends('layouts.app')
@section('title', 'Tambah User')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Tambah User</h1>
            </div>
            <a href="{{ route('admin.users.index') }}" class="toolbar-btn neutral">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div
                style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;color:#c0392b;">
                <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="form-card">
                <h3><i class="bx bxs-user-plus"></i> Data User Baru</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                        <input type="text" name="nama_user" class="form-input {{ $errors->has('nama_user') ? 'error' : '' }}"
                            value="{{ old('nama_user') }}" placeholder="cth: Budi Santoso" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. HP <span class="req">*</span> <small>(untuk WhatsApp)</small></label>
                        <input type="text" name="nohp" class="form-input {{ $errors->has('nohp') ? 'error' : '' }}"
                            value="{{ old('nohp') }}" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="req">*</span></label>
                        <input type="email" name="email" class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                            value="{{ old('email') }}" placeholder="nama@diskominfotik.go.id" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin <span class="req">*</span></label>
                        <select name="jenis_kelamin" class="form-select {{ $errors->has('jenis_kelamin') ? 'error' : '' }}" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Alamat <span class="req">*</span></label>
                        <textarea name="alamat" class="form-textarea {{ $errors->has('alamat') ? 'error' : '' }}"
                            placeholder="Alamat lengkap" required>{{ old('alamat') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="req">*</span></label>
                        <input type="password" name="password" class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                            placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="req">*</span></label>
                        <select name="role" id="role-select" class="form-select {{ $errors->has('role') ? 'error' : '' }}"
                            onchange="toggleGedung(this.value)" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="operator" {{ old('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                            <option value="inventaris" {{ old('role') == 'inventaris' ? 'selected' : '' }}>Inventaris</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('admin.users.index') }}" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-submit"><i class="bx bx-save"></i> Simpan</button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        function toggleGedung(role) {
            var field = document.getElementById('gedung-field');
            field.style.display = role === 'inventaris' ? 'flex' : 'none';
            field.querySelector('input').required = role === 'inventaris';
        }
        toggleGedung('{{ old("role", "") }}');
    </script>
@endpush