@extends('layouts.app')
@section('title', 'Sistem Settings')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="mb-5">
            <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Sistem Settings</h1>
            <p class="text-sm text-text-muted m-0 flex items-center gap-1.5">
                <i class="bx bx-home-alt"></i> Home <i class="bx bx-chevron-right text-xs"></i> <span class="text-text dark:text-text-dark font-medium">Sistem Settings</span>
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-4 items-start">

            {{-- Kolom kiri: tabel role --}}
            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-5">
                <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
                    <h3 class="text-[15px] font-semibold text-text dark:text-text-dark flex items-center gap-2 m-0">
                        <span class="w-8 h-8 rounded-lg bg-success/15 text-success-text flex items-center justify-center shrink-0"><i class="bx bx-shield-quarter"></i></span>
                        Otorisasi Role Akses
                    </h3>
                    <span class="text-[13px] font-medium px-3 py-1.5 rounded-full bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        Total: <span class="text-primary font-semibold">{{ $roles->count() }}</span> Role
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-[13px]">
                        <thead>
                            <tr class="text-left text-text-muted border-b border-page-bg dark:border-page-bg-dark">
                                <th class="py-2.5 pr-3 font-medium uppercase tracking-[0.5px] text-[11px]">Role</th>
                                <th class="py-2.5 px-2 font-medium uppercase tracking-[0.5px] text-[11px] text-center" title="Lihat">L</th>
                                <th class="py-2.5 px-2 font-medium uppercase tracking-[0.5px] text-[11px] text-center" title="Tambah">T</th>
                                <th class="py-2.5 px-2 font-medium uppercase tracking-[0.5px] text-[11px] text-center" title="Ubah">U</th>
                                <th class="py-2.5 px-2 font-medium uppercase tracking-[0.5px] text-[11px] text-center" title="Hapus">H</th>
                                <th class="py-2.5 px-3 font-medium uppercase tracking-[0.5px] text-[11px] text-center">Status</th>
                                <th class="py-2.5 pl-3 font-medium uppercase tracking-[0.5px] text-[11px] text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $dot = fn($on) => $on
                                    ? '<span class="inline-flex w-5 h-5 rounded-full bg-success/15 text-success-text items-center justify-center"><i class="bx bx-check text-[13px]"></i></span>'
                                    : '<span class="inline-flex w-5 h-5 rounded-full bg-page-bg dark:bg-page-bg-dark text-text-muted items-center justify-center"><i class="bx bx-minus text-[13px]"></i></span>';
                            @endphp
                            @foreach($roles as $role)
                                <tr class="border-b border-page-bg dark:border-page-bg-dark last:border-0">
                                    <td class="py-3 pr-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></span>
                                            <span class="font-semibold text-text dark:text-text-dark">{{ $role->nama_role }}</span>
                                            @if($role->is_terkunci)
                                                <i class="bx bx-lock-alt text-text-muted text-sm" title="Role bawaan sistem, tidak bisa dihapus"></i>
                                            @endif
                                        </div>
                                        <div class="text-xs text-text-muted mt-0.5 pl-3.5">{{ $role->jumlah_menu_terlihat }} menu dapat diakses</div>
                                    </td>
                                    <td class="py-3 px-2 text-center">{!! $dot($role->ringkasan['lihat']) !!}</td>
                                    <td class="py-3 px-2 text-center">{!! $dot($role->ringkasan['tambah']) !!}</td>
                                    <td class="py-3 px-2 text-center">{!! $dot($role->ringkasan['ubah']) !!}</td>
                                    <td class="py-3 px-2 text-center">{!! $dot($role->ringkasan['hapus']) !!}</td>
                                    <td class="py-3 px-3 text-center">
                                        <x-badge :variant="$role->isAktif() ? 'badge-active' : 'badge-inactive'">{{ ucfirst($role->status) }}</x-badge>
                                    </td>
                                    <td class="py-3 pl-3">
                                        <div class="flex gap-1.5 items-center justify-end">
                                            <button type="button"
                                                onclick="bukaModalHakAkses({{ $role->id }}, '{{ addslashes($role->nama_role) }}', this)"
                                                data-akses='@json($role->aksesMenu->keyBy("id_menu"))'
                                                class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 hover:opacity-80 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark"
                                                title="Kelola hak akses menu">
                                                <i class="bx bx-cog"></i>
                                            </button>
                                            <form action="{{ route('admin.pengaturan.role-akses.destroy', $role) }}" method="POST"
                                                onsubmit="return confirm('Hapus role {{ addslashes($role->nama_role) }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 shrink-0 {{ $role->is_terkunci ? 'opacity-30 cursor-not-allowed' : 'hover:opacity-80' }} bg-danger dark:bg-danger-dark text-danger-text"
                                                    {{ $role->is_terkunci ? 'disabled title="Role bawaan sistem"' : 'title="Hapus role"' }}>
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kolom kanan: info + tambah role --}}
            <div class="flex flex-col gap-4">
                <div class="bg-primary/10 rounded-xl p-4 flex gap-3">
                    <span class="w-8 h-8 rounded-lg bg-primary/20 text-primary flex items-center justify-center shrink-0"><i class="bx bx-info-circle"></i></span>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.5px] text-primary m-0 mb-1">Informasi</p>
                        <p class="text-[13px] text-text dark:text-text-dark m-0">Gunakan tombol gear pada tabel Role untuk mengatur akses halaman/menu untuk setiap role.</p>
                    </div>
                </div>

                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-5">
                    <h3 class="text-[15px] font-semibold text-text dark:text-text-dark flex items-center gap-2 m-0 mb-4">
                        <span class="w-8 h-8 rounded-lg bg-success/15 text-success-text flex items-center justify-center shrink-0"><i class="bx bx-plus"></i></span>
                        Tambah Role Akses
                    </h3>
                    <form action="{{ route('admin.pengaturan.role-akses.store') }}" method="POST" class="flex flex-col gap-4">
                        @csrf
                        <x-input name="nama_role" label="Nama Role" placeholder="Misal: Resepsionis" required value="{{ old('nama_role') }}" />
                        <button type="submit"
                            class="h-10 rounded-lg border-none bg-success dark:bg-success-dark text-success-text font-medium text-[13px] cursor-pointer transition-opacity duration-200 hover:opacity-90">
                            Simpan Role
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Hak Akses Halaman (Gambar 2) - satu instance dipakai bareng semua role,
             diisi lewat JS saat tombol gear diklik --}}
        <div id="modalHakAkses" class="modal-konfirmasi fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
            <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[600px] max-h-[85vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
                <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                    <div>
                        <h3 class="m-0 text-[15px] font-semibold text-text dark:text-text-dark flex items-center gap-2">
                            <i class="bx bx-list-ul"></i> Hak Akses Halaman
                        </h3>
                        <p class="text-xs text-text-muted m-0 mt-1">Daftar Menu Dashboard — <span id="modalHakAksesRole" class="font-semibold text-primary"></span></p>
                    </div>
                    <button type="button" data-modal-close
                        class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark">
                        <i class="bx bx-x"></i>
                    </button>
                </div>

                <form id="formHakAkses" method="POST" class="flex flex-col flex-1 min-h-0">
                    @csrf @method('PUT')

                    <div class="px-5 pt-3 pb-2 flex justify-end">
                        <button type="button" onclick="pilihSemuaAkses(true)"
                            class="h-8 px-3 rounded-lg border-none text-[12px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-success/15 text-success-text">
                            <i class="bx bx-check-double"></i> Pilih Semua
                        </button>
                    </div>

                    <div class="px-5 pb-4 overflow-y-auto flex flex-col gap-1">
                        @foreach($menus as $menu)
                            <div class="rounded-lg hover:bg-page-bg dark:hover:bg-page-bg-dark transition-colors duration-150">
                                <div class="flex items-center gap-3 py-2 px-2">
                                    <i class="bx {{ $menu->icon ?? 'bx-file' }} text-primary text-base shrink-0"></i>
                                    <span class="flex-1 text-[13px] font-medium text-text dark:text-text-dark">{{ $menu->nama_menu }}</span>
                                    <div class="flex items-center gap-3 shrink-0">
                                        @foreach(['bisa_lihat' => 'L', 'bisa_tambah' => 'T', 'bisa_ubah' => 'U', 'bisa_hapus' => 'H'] as $kolom => $label)
                                            <label class="flex items-center gap-1 text-[11px] text-text-muted cursor-pointer select-none" title="{{ ['bisa_lihat'=>'Lihat','bisa_tambah'=>'Tambah','bisa_ubah'=>'Ubah','bisa_hapus'=>'Hapus'][$kolom] }}">
                                                <input type="checkbox" class="akses-checkbox w-4 h-4 accent-primary cursor-pointer" data-kolom="{{ $kolom }}"
                                                    name="akses[{{ $menu->id }}][{{ $kolom }}]" value="1">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                @foreach($menu->children as $anak)
                                    <div class="flex items-center gap-3 py-2 pl-9 pr-2">
                                        <span class="w-1 h-1 rounded-full bg-text-muted shrink-0"></span>
                                        <span class="flex-1 text-[13px] text-text dark:text-text-dark">{{ $anak->nama_menu }}</span>
                                        <div class="flex items-center gap-3 shrink-0">
                                            @foreach(['bisa_lihat' => 'L', 'bisa_tambah' => 'T', 'bisa_ubah' => 'U', 'bisa_hapus' => 'H'] as $kolom => $label)
                                                <label class="flex items-center gap-1 text-[11px] text-text-muted cursor-pointer select-none">
                                                    <input type="checkbox" class="akses-checkbox w-4 h-4 accent-primary cursor-pointer" data-kolom="{{ $kolom }}"
                                                        name="akses[{{ $anak->id }}][{{ $kolom }}]" value="1">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-2.5 py-4 px-5 border-t border-page-bg dark:border-page-bg-dark">
                        <button type="button" data-modal-close
                            class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                        <button type="submit"
                            class="h-9 px-4 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-success dark:bg-success-dark text-success-text">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<script>
function bukaModalHakAkses(idRole, namaRole, btn) {
    document.getElementById('modalHakAksesRole').textContent = namaRole;
    document.getElementById('formHakAkses').action = '{{ url("admin/pengaturan/role-akses") }}/' + idRole + '/akses';

    var akses = btn.dataset.akses ? JSON.parse(btn.dataset.akses) : {};

    document.querySelectorAll('#formHakAkses .akses-checkbox').forEach(function (cb) {
        var match = cb.name.match(/^akses\[(\d+)\]\[(\w+)\]$/);
        if (!match) return;
        var idMenu = match[1];
        var kolom  = match[2];
        cb.checked = !!(akses[idMenu] && akses[idMenu][kolom]);
    });

    bukaModalKonfirmasi('modalHakAkses');
}

function pilihSemuaAkses(state) {
    document.querySelectorAll('#formHakAkses .akses-checkbox').forEach(function (cb) {
        cb.checked = state;
    });
}
</script>
@endpush
