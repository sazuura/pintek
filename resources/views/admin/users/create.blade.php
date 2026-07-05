@extends('layouts.app')
@section('title', 'Tambah User')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Tambah User</h1>
            </div>
            <a href="{{ route('admin.users.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div class="bg-danger dark:bg-danger-dark border-l-4 border-danger-text py-3 px-4 rounded-lg mb-4 text-sm text-[#c0392b]">
                <ul class="m-0 pl-[18px]">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            @php
                $inputClass = 'h-10 px-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
                $labelClass = 'text-[13px] font-medium text-text dark:text-text-dark';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bxs-user-plus"></i> Data User Baru</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Nama Lengkap <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <input type="text" name="nama_user"
                            class="{{ $inputClass }} {{ $errors->has('nama_user') ? '!border-danger-text' : '' }}"
                            value="{{ old('nama_user') }}" placeholder="cth: Budi Santoso" required>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">No. HP <span class="text-[#e74c3c] ml-0.5">*</span> <small class="font-normal text-text-muted ml-1">(untuk WhatsApp)</small></label>
                        <input type="text" name="nohp"
                            class="{{ $inputClass }} {{ $errors->has('nohp') ? '!border-danger-text' : '' }}"
                            value="{{ old('nohp') }}" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Email <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <input type="email" name="email"
                            class="{{ $inputClass }} {{ $errors->has('email') ? '!border-danger-text' : '' }}"
                            value="{{ old('email') }}" placeholder="nama@diskominfotik.go.id" required>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Jenis Kelamin <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <select name="jenis_kelamin" class="{{ $inputClass }} {{ $errors->has('jenis_kelamin') ? '!border-danger-text' : '' }}" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="{{ $labelClass }}">Alamat <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <textarea name="alamat"
                            class="{{ $inputClass }} {{ $errors->has('alamat') ? '!border-danger-text' : '' }} h-auto py-2.5 resize-y min-h-[80px]"
                            placeholder="Alamat lengkap" required>{{ old('alamat') }}</textarea>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Password <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <input type="password" name="password"
                            class="{{ $inputClass }} {{ $errors->has('password') ? '!border-danger-text' : '' }}"
                            placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="{{ $labelClass }}">Role <span class="text-[#e74c3c] ml-0.5">*</span></label>
                        <select name="role" id="role-select" class="{{ $inputClass }} {{ $errors->has('role') ? '!border-danger-text' : '' }}"
                            onchange="toggleGedung(this.value)" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="operator" {{ old('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                            <option value="inventaris" {{ old('role') == 'inventaris' ? 'selected' : '' }}>Inventaris</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2.5 mt-6 pt-5 border-t border-page-bg dark:border-page-bg-dark">
                <a href="{{ route('admin.users.index') }}"
                    class="h-10 px-5 bg-page-bg dark:bg-page-bg-dark hover:bg-[#ddd] text-text dark:text-text-dark border-none rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
                <button type="submit"
                    class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
                    <i class="bx bx-save"></i> Simpan
                </button>
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
