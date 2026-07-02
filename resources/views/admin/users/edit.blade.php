@extends('layouts.app')
@section('title', 'Edit User')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Edit User</h1>
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

        <form action="{{ route('admin.users.update', $user->id_user) }}" method="POST">
            @csrf @method('PUT')
            <div class="form-card">
                <h3><i class="bx bxs-user-detail"></i> Edit: {{ $user->nama_user }}</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">ID User</label>
                        <input type="text" class="form-input" value="{{ $user->id_user }}" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                        <input type="text" name="nama_user" class="form-input"
                            value="{{ old('nama_user', $user->nama_user) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. HP <span class="req">*</span></label>
                        <input type="text" name="nohp" class="form-input {{ $errors->has('nohp') ? 'error' : '' }}"
                            value="{{ old('nohp', $user->nohp) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="req">*</span></label>
                        <input type="email" name="email" class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                            value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Baru <small>(kosongkan jika tidak berubah)</small></label>
                        <input type="password" name="password" class="form-input" placeholder="Min. 6 karakter">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="req">*</span></label>
                        <select name="role" id="role-select" class="form-select" onchange="toggleGedung(this.value)"
                            required>
                            @foreach(['admin', 'operator', 'inventaris'] as $r)
                                <option value="{{ $r }}" {{ old('role', $user->role) == $r ? 'selected' : '' }}>{{ ucfirst($r) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- <div class="form-group" id="gedung-field"
                        style="display:{{ $user->role=='inventaris'?'flex':'none' }};">
                        <label class="form-label">Gedung <small>(wajib untuk Inventaris)</small></label>
                        <input type="text" name="gedung" class="form-input" value="{{ old('gedung', $user->gedung) }}"
                            placeholder="cth: Gedung A">
                    </div> --}}
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('admin.users.index') }}" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-submit"><i class="bx bx-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        function toggleGedung(role) {
            var field = document.getElementById('gedung-field');
            field.style.display = role === 'inventaris' ? 'flex' : 'none';
        }
    </script>
@endpush