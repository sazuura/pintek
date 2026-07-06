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

        <form action="{{ route('admin.users.store') }}" method="POST" novalidate>
            @csrf

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bxs-user-plus"></i> Data User Baru</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="nama_user" label="Nama Lengkap" required placeholder="cth: Budi Santoso" />

                    <x-input name="nohp" label="No. HP" required placeholder="08xxxxxxxxxx"
                        hint="Dipakai untuk notifikasi WhatsApp" />

                    <x-input type="email" name="email" label="Email" required placeholder="nama@diskominfotik.go.id" />

                    <x-select name="jenis_kelamin" label="Jenis Kelamin" required placeholder="-- Pilih Jenis Kelamin --">
                        <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </x-select>

                    <div class="md:col-span-2">
                        <x-input type="textarea" name="alamat" label="Alamat" required placeholder="Alamat lengkap" />
                    </div>

                    <x-input name="password" label="Password" type="password" toggleable required
                        placeholder="Min. 6 karakter" />

                    <x-select name="role" label="Role" required placeholder="-- Pilih Role --"
                        onchange="toggleGedung(this.value)">
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="operator" {{ old('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                        <option value="inventaris" {{ old('role') == 'inventaris' ? 'selected' : '' }}>Inventaris</option>
                    </x-select>
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
