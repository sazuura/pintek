@extends('layouts.app')
@section('title', 'Edit User')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Edit User</h1>
            </div>
            <a href="{{ route(auth()->user()->role . '.users.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        <form action="{{ route(auth()->user()->role . '.users.update', $user->id_user) }}" method="POST" novalidate>
            @csrf @method('PUT')

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bxs-user-detail"></i> Edit: {{ $user->nama_user }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input name="nama_user" label="Nama Lengkap" required value="{{ $user->nama_user }}" autocomplete="name" />

                    <x-input name="nohp" label="No. HP" required value="{{ $user->nohp }}"
                        hint="Dipakai untuk notifikasi WhatsApp" autocomplete="tel" inputmode="numeric"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" />

                    <x-input type="email" name="email" label="Email" required value="{{ $user->email }}" autocomplete="email" />

                    <x-select name="jenis_kelamin" label="Jenis Kelamin" required>
                        <option value="L" {{ old('jenis_kelamin', $user->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('jenis_kelamin', $user->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </x-select>

                    <div class="md:col-span-2">
                        <x-input type="textarea" name="alamat" label="Alamat" required value="{{ $user->alamat }}" autocomplete="street-address" />
                    </div>

                    {{-- autocomplete="new-password" wajib supaya Chrome tidak mengira ini form login
                         dan menawarkan saran kredensial tersimpan di field lain. --}}
                    <x-input name="password" label="Password Baru" type="password" toggleable
                        placeholder="Min. 6 karakter" hint="Kosongkan jika tidak berubah" autocomplete="new-password" />

                    <x-select name="role" label="Role" required>
                        @foreach($roles as $r)
                            <option value="{{ $r->slug }}" {{ old('role', $user->role) == $r->slug ? 'selected' : '' }}>{{ $r->nama_role }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>
            <div class="flex justify-end gap-2.5 mt-6 pt-5 border-t border-page-bg dark:border-page-bg-dark">
                <a href="{{ route(auth()->user()->role . '.users.index') }}"
                    class="h-10 px-5 bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
                <button type="submit"
                    class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
                    <i class="bx bx-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </main>
@endsection
