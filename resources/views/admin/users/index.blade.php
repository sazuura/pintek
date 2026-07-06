@extends('layouts.app')
@section('title', 'Data Users')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Data Users</h1>
            </div>
            <a href="{{ route('admin.users.create') }}"
                class="h-9 px-4 rounded-full bg-primary text-surface dark:text-surface-dark flex justify-center items-center gap-2.5 font-medium">
                <i class="bx bx-plus"></i><span class="text">Tambah User</span>
            </a>
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('admin.users.index') }}" class="contents">
                <div class="relative flex-1 min-w-[180px] max-w-[300px]">
                    <i class="bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
                    <input type="text" name="search" data-live-search="#hasil-users" autocomplete="off" placeholder="Cari nama / email..." value="{{ request('search') }}"
                        class="w-full h-9 pl-[34px] pr-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans transition-colors duration-200 focus:border-primary focus:outline-none focus:bg-surface dark:focus:bg-surface-dark">
                </div>
                <select name="role" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                    <option value="inventaris" {{ request('role') == 'inventaris' ? 'selected' : '' }}>Inventaris</option>
                </select>
                <select name="status" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('admin.users.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
        </div>

        @php
            $actionClass = 'w-8 h-8 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 no-underline shrink-0 hover:opacity-80';
        @endphp

        <div id="hasil-users">
        <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card overflow-hidden max-xs:hidden">
            <div class="py-4 px-5 flex items-center justify-between border-b border-page-bg dark:border-page-bg-dark">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark">Daftar User</h3>
                <small class="text-text-muted">Tap baris untuk detail</small>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-8 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap"></th>
                            <th class="w-10 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">#</th>
                            <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Nama <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="max-md:hidden py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Email</th>
                            <th class="group sortable py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap cursor-pointer select-none hover:text-primary">Role <span class="sort-icon ml-1 opacity-40 text-[10px] group-[.sorted]:opacity-100 group-[.sorted]:text-primary">⇅</span></th>
                            <th class="py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Status</th>
                            <th class="w-20 py-3 px-4 text-[11px] uppercase tracking-[0.5px] text-text-muted text-left bg-page-bg dark:bg-page-bg-dark border-b border-page-bg dark:border-page-bg-dark whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                            @php $uid = 'usr-' . $user->id_user; @endphp

                            <tr class="group accordion-row cursor-pointer border-b border-page-bg dark:border-page-bg-dark transition-colors duration-150 hover:bg-page-bg dark:hover:bg-page-bg-dark {{ !$user->isActive() ? 'opacity-60' : '' }}" data-target="{{ $uid }}">
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle text-center"><i class="bx bx-chevron-down transition-transform duration-200 text-text-muted text-base group-[.open]:rotate-180 group-[.open]:text-primary"></i></td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">{{ $users->firstItem() + $index }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="font-medium">{{ $user->nama_user }}</div>
                                    <div class="text-xs text-text-muted mt-0.5">{{ $user->id_user }}</div>
                                </td>
                                <td class="max-md:hidden py-3.5 px-4 text-[13px] text-text dark:text-text-dark align-middle">{{ $user->email }}</td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    @php $roleColor = ['admin' => 'badge-danger', 'operator' => 'badge-info', 'inventaris' => 'badge-purple'][$user->role] ?? ''; @endphp
                                    <x-badge :variant="$roleColor">{{ ucfirst($user->role) }}</x-badge>
                                </td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    @if($user->isActive())
                                        <x-badge variant="badge-active"><i class="bx bx-check-circle"></i> Active</x-badge>
                                    @else
                                        <x-badge variant="badge-inactive"><i class="bx bx-x-circle"></i> Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-sm text-text dark:text-text-dark align-middle">
                                    <div class="flex gap-1.5 items-center">
                                        <a href="{{ route('admin.users.edit', $user->id_user) }}"
                                            class="{{ $actionClass }} bg-warning dark:bg-warning-dark text-warning-text">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <button type="button"
                                            onclick="bukaKonfirmasiStatusUser('{{ route('admin.users.destroy', $user->id_user) }}', {{ $user->isActive() ? 'true' : 'false' }}, '{{ addslashes($user->nama_user) }}')"
                                            class="{{ $actionClass }} {{ $user->isActive() ? 'bg-danger dark:bg-danger-dark text-danger-text' : 'bg-success dark:bg-success-dark text-success-text' }}">
                                            <i class="bx {{ $user->isActive() ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr class="accordion-detail bg-page-bg dark:bg-page-bg-dark [&:not(.open)]:hidden [&.open]:table-row" id="{{ $uid }}">
                                <td colspan="7" class="!p-0">
                                    @php
                                        $waNumber = $user->nohp ? '62' . ltrim(preg_replace('/\D/', '', $user->nohp), '0') : null;
                                    @endphp
                                    <div class="flex flex-wrap items-start gap-x-8 gap-y-2.5 py-[18px] px-4">
                                        <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                            <i class="bx bx-id-card text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">ID User</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $user->id_user }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                            <i class="bx bxl-whatsapp text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">No. HP</label>
                                                @if($waNumber)
                                                    <p class="m-0 font-medium text-[13px] break-words"><a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="text-[#25D366] font-semibold inline-flex items-center gap-1 hover:underline">{{ $user->nohp }} <i class="bx bx-link-external text-[11px]"></i></a></p>
                                                @else
                                                    <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">-</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]">
                                            <i class="bx {{ $user->jenis_kelamin === 'L' ? 'bx-male-sign' : ($user->jenis_kelamin === 'P' ? 'bx-female-sign' : 'bx-question-mark') }} text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Jenis Kelamin</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $user->jenis_kelamin === 'L' ? 'Laki-laki' : ($user->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2.5 min-w-0 flex-[2_1_260px]">
                                            <i class="bx bx-map text-lg text-primary mt-px shrink-0"></i>
                                            <div>
                                                <label class="text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5">Alamat</label>
                                                <p class="text-text dark:text-text-dark m-0 font-medium text-[13px] break-words">{{ $user->alamat ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10 text-text-muted">
                                    <i class="bx bx-user-x text-4xl block mb-2"></i>
                                    Belum ada user
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$users" label="user" />
        </div>

        {{-- Kartu user - hanya tampil di mobile, tabel di atas tetap dipakai untuk tablet & desktop --}}
        <div class="hidden max-xs:flex flex-col gap-3 mb-4">
            @forelse($users as $user)
                @php
                    $roleColor = ['admin' => 'badge-danger', 'operator' => 'badge-info', 'inventaris' => 'badge-purple'][$user->role] ?? '';
                    $waNumber  = $user->nohp ? '62' . ltrim(preg_replace('/\D/', '', $user->nohp), '0') : null;
                @endphp
                <div class="bg-surface dark:bg-surface-dark rounded-xl p-4 shadow-card flex flex-col gap-3.5">
                    <div class="flex items-center gap-3 cursor-pointer" data-open-user-modal
                         data-nama="{{ $user->nama_user }}"
                         data-id="{{ $user->id_user }}"
                         data-nohp="{{ $user->nohp ?? '-' }}"
                         data-wa="{{ $waNumber }}"
                         data-jk="{{ $user->jenis_kelamin === 'L' ? 'Laki-laki' : ($user->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}"
                         data-alamat="{{ $user->alamat ?? '-' }}">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-text dark:text-text-dark">{{ $user->nama_user }}</div>
                            <div class="text-xs text-text-muted whitespace-nowrap overflow-hidden text-ellipsis">{{ $user->email }}</div>
                        </div>
                        <i class="bx bx-chevron-right text-text-muted text-xl shrink-0"></i>
                    </div>
                    <div class="flex flex-col gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                            <span>Peran</span>
                            <x-badge :variant="$roleColor">{{ ucfirst($user->role) }}</x-badge>
                        </div>
                        <div class="flex items-center justify-between text-[13px] text-text-muted">
                            <span>Status</span>
                            @if($user->isActive())
                                <x-badge variant="badge-active"><i class="bx bx-check-circle"></i> Active</x-badge>
                            @else
                                <x-badge variant="badge-inactive"><i class="bx bx-x-circle"></i> Inactive</x-badge>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2 pt-3 border-t border-page-bg dark:border-page-bg-dark">
                        <a href="{{ route('admin.users.edit', $user->id_user) }}"
                            class="{{ $actionClass }} bg-warning dark:bg-warning-dark text-warning-text">
                            <i class="bx bx-edit"></i>
                        </a>
                        <button type="button"
                            onclick="bukaKonfirmasiStatusUser('{{ route('admin.users.destroy', $user->id_user) }}', {{ $user->isActive() ? 'true' : 'false' }}, '{{ addslashes($user->nama_user) }}')"
                            class="{{ $actionClass }} {{ $user->isActive() ? 'bg-danger dark:bg-danger-dark text-danger-text' : 'bg-success dark:bg-success-dark text-success-text' }}">
                            <i class="bx {{ $user->isActive() ? 'bx-user-x' : 'bx-user-check' }}"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-10 text-center text-text-muted">
                    <i class="bx bx-user-x text-4xl block mb-2"></i>
                    Belum ada user
                </div>
            @endforelse
        </div>
        <div class="hidden max-xs:block bg-surface dark:bg-surface-dark rounded-xl shadow-card">
            <x-pagination :paginator="$users" label="user" />
        </div>
        </div>

        {{-- Modal detail user, dipakai kartu mobile --}}
        <div id="modalUserDetail"
            class="fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
            <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
                <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
                    <h3 id="modalUserDetailLabel" class="m-0 text-[15px] font-semibold text-text dark:text-text-dark">Detail User</h3>
                    <button type="button"
                        class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark"
                        onclick="document.getElementById('modalUserDetail').classList.remove('open')"><i class="bx bx-x"></i></button>
                </div>
                <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-wrap items-start gap-x-8 gap-y-2.5" id="modalUserDetailBody"></div>
            </div>
        </div>

        {{-- Modal konfirmasi nonaktifkan/aktifkan user - satu instance dipakai bareng oleh semua baris/kartu --}}
        <x-modal-konfirmasi id="modalKonfirmasiStatusUser" title="Ubah Status User" icon="bx-error-circle" icon-class="text-danger-text">
            <div id="statusUserBanner" class="bg-danger dark:bg-danger-dark rounded-[10px] py-3.5 px-4">
                <div id="statusUserMessage" class="text-[13px] font-semibold text-[#c0392b]"></div>
            </div>
            <form id="formStatusUser" method="POST">
                @csrf @method('DELETE')
                <div class="flex justify-end gap-2.5 mt-3">
                    <button type="button" data-modal-close
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                    <button type="submit" id="btnKonfirmasiStatusUser"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white">
                        <i id="btnStatusUserIcon" class="bx bx-user-x"></i> <span id="btnStatusUserLabel">Nonaktifkan</span>
                    </button>
                </div>
            </form>
        </x-modal-konfirmasi>
    </main>
@endsection

@push('scripts')
<script>
function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

// Modal konfirmasi nonaktifkan/aktifkan user (komponen global modal-konfirmasi) - satu
// instance dipakai bareng oleh semua baris tabel & kartu mobile. Warna & teks tombol
// menyesuaikan arah aksi: merah/danger kalau menonaktifkan, hijau/success kalau mengaktifkan.
function bukaKonfirmasiStatusUser(url, isActive, namaUser) {
    var banner   = document.getElementById('statusUserBanner');
    var message  = document.getElementById('statusUserMessage');
    var btn      = document.getElementById('btnKonfirmasiStatusUser');
    var btnIcon  = document.getElementById('btnStatusUserIcon');
    var btnLabel = document.getElementById('btnStatusUserLabel');

    document.getElementById('formStatusUser').action = url;

    if (isActive) {
        banner.className = 'bg-danger dark:bg-danger-dark rounded-[10px] py-3.5 px-4';
        message.className = 'text-[13px] font-semibold text-[#c0392b]';
        message.innerHTML = '<i class="bx bx-error"></i> Nonaktifkan akun ' + escapeHtml(namaUser) + '? User tidak akan bisa login sampai diaktifkan kembali.';
        btn.className = 'h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white';
        btnIcon.className = 'bx bx-user-x';
        btnLabel.textContent = 'Nonaktifkan';
    } else {
        banner.className = 'bg-success dark:bg-success-dark rounded-[10px] py-3.5 px-4';
        message.className = 'text-[13px] font-semibold text-success-text';
        message.innerHTML = '<i class="bx bx-check-circle"></i> Aktifkan kembali akun ' + escapeHtml(namaUser) + '?';
        btn.className = 'h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-success-text text-white';
        btnIcon.className = 'bx bx-user-check';
        btnLabel.textContent = 'Aktifkan';
    }

    bukaModalKonfirmasi('modalKonfirmasiStatusUser');
}

document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-open-user-modal]');
    if (!el) return;

    document.getElementById('modalUserDetailLabel').textContent = el.dataset.nama;

    var detailRowClass = 'flex items-start gap-2.5 min-w-0 flex-1 basis-[160px]';
    var detailRowFullClass = 'flex items-start gap-2.5 min-w-0 flex-[2_1_260px]';
    var iconClass = 'bx text-lg text-primary mt-px shrink-0';
    var labelClass = 'text-[11px] text-text-muted uppercase tracking-[0.4px] block mb-0.5';
    var pClass = 'text-text dark:text-text-dark m-0 font-medium text-[13px] break-words';

    var waNumber = el.dataset.wa || '';
    var waRow = waNumber && waNumber !== '-'
        ? '<p class="' + pClass + '"><a href="https://wa.me/' + encodeURIComponent(waNumber) + '" target="_blank" rel="noopener" class="text-[#25D366] font-semibold inline-flex items-center gap-1 hover:underline">' + escapeHtml(el.dataset.nohp) + ' <i class="bx bx-link-external text-[11px]"></i></a></p>'
        : '<p class="' + pClass + '">' + escapeHtml(el.dataset.nohp) + '</p>';

    document.getElementById('modalUserDetailBody').innerHTML =
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-id-card"></i><div><label class="' + labelClass + '">ID User</label><p class="' + pClass + '">' + escapeHtml(el.dataset.id) + '</p></div></div>' +
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bxl-whatsapp"></i><div><label class="' + labelClass + '">No. HP</label>' + waRow + '</div></div>' +
        '<div class="' + detailRowClass + '"><i class="' + iconClass + ' bx-male-sign"></i><div><label class="' + labelClass + '">Jenis Kelamin</label><p class="' + pClass + '">' + escapeHtml(el.dataset.jk) + '</p></div></div>' +
        '<div class="' + detailRowFullClass + '"><i class="' + iconClass + ' bx-map"></i><div><label class="' + labelClass + '">Alamat</label><p class="' + pClass + '">' + escapeHtml(el.dataset.alamat) + '</p></div></div>';

    document.getElementById('modalUserDetail').classList.add('open');
});

document.getElementById('modalUserDetail').addEventListener('click', function (e) {
    if (e.target.id === 'modalUserDetail') e.currentTarget.classList.remove('open');
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.getElementById('modalUserDetail').classList.remove('open');
});
</script>
@endpush
