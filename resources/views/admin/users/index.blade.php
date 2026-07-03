@extends('layouts.app')
@section('title', 'Data Users')
@section('sidebar-menu') <x-sidebar-admin /> @endsection

@section('content')
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Data Users</h1>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn-download">
                <i class="bx bx-plus"></i><span class="text">Tambah User</span>
            </a>
        </div>

        <div class="content-toolbar">
            <form method="GET" action="{{ route('admin.users.index') }}" style="display:contents;">
                <div class="toolbar-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="search" placeholder="Cari nama / email..." value="{{ request('search') }}">
                </div>
                <select name="role" class="toolbar-select">
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                    <option value="inventaris" {{ request('role') == 'inventaris' ? 'selected' : '' }}>Inventaris</option>
                </select>
                <select name="status" class="toolbar-select">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="toolbar-btn primary"><i class="bx bx-filter"></i> Filter</button>
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('admin.users.index') }}" class="toolbar-btn neutral"><i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
        </div>

        <div class="data-table-wrap">
            <div class="data-table-head">
                <h3>Daftar User</h3>
                <small style="color:var(--dark-grey);">Tap baris untuk detail</small>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:32px;"></th>
                            <th style="width:40px;">#</th>
                            <th class="sortable">Nama <span class="sort-icon">⇅</span></th>
                            <th class="hide-mobile">Email</th>
                            <th class="sortable">Role <span class="sort-icon">⇅</span></th>
                            {{-- <th class="hide-mobile">Gedung</th> --}}
                            <th>Status</th>
                            <th style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                            @php $uid = 'usr-' . $user->id_user; @endphp

                            <tr class="accordion-row {{ !$user->isActive() ? 'row-inactive' : '' }}" data-target="{{ $uid }}">
                                <td style="text-align:center;"><i class="bx bx-chevron-down accordion-chevron"></i></td>
                                <td>{{ $users->firstItem() + $index }}</td>
                                <td>
                                    <div style="font-weight:500;">{{ $user->nama_user }}</div>
                                    <div style="font-size:12px;color:var(--dark-grey);">{{ $user->id_user }}</div>
                                </td>
                                <td class="hide-mobile" style="font-size:13px;">{{ $user->email }}</td>
                                <td>
                                    @php $roleColor = ['admin' => 'badge-danger', 'operator' => 'badge-info', 'inventaris' => 'badge-purple'][$user->role] ?? ''; @endphp
                                    <span class="badge {{ $roleColor }}">{{ ucfirst($user->role) }}</span>
                                </td>
                                {{-- <td class="hide-mobile">{{ $user->gedung ?? '-' }}</td> --}}
                                <td>
                                    @if($user->isActive())
                                        <span class="badge badge-active"><i class="bx bx-check-circle"></i> Active</span>
                                    @else
                                        <span class="badge badge-inactive"><i class="bx bx-x-circle"></i> Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="{{ route('admin.users.edit', $user->id_user) }}" class="btn-icon edit">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.users.destroy', $user->id_user) }}" method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirm('{{ $user->isActive() ? 'Nonaktifkan' : 'Aktifkan' }} user ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="btn-icon {{ $user->isActive() ? 'delete' : 'success' }}">
                                                <i class="bx {{ $user->isActive() ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <tr class="accordion-detail" id="{{ $uid }}">
                                <td colspan="7">
                                    @php
                                        $waNumber = $user->nohp ? '62' . ltrim(preg_replace('/\D/', '', $user->nohp), '0') : null;
                                    @endphp
                                    <div class="detail-panel">
                                        <div class="detail-row">
                                            <i class="bx bx-id-card"></i>
                                            <div>
                                                <label>ID User</label>
                                                <p>{{ $user->id_user }}</p>
                                            </div>
                                        </div>
                                        <div class="detail-row">
                                            <i class="bx bxl-whatsapp"></i>
                                            <div>
                                                <label>No. HP</label>
                                                @if($waNumber)
                                                    <p><a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener">{{ $user->nohp }} <i class="bx bx-link-external" style="font-size:11px;"></i></a></p>
                                                @else
                                                    <p>-</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="detail-row">
                                            <i class="bx {{ $user->jenis_kelamin === 'L' ? 'bx-male-sign' : ($user->jenis_kelamin === 'P' ? 'bx-female-sign' : 'bx-question-mark') }}"></i>
                                            <div>
                                                <label>Jenis Kelamin</label>
                                                <p>{{ $user->jenis_kelamin === 'L' ? 'Laki-laki' : ($user->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</p>
                                            </div>
                                        </div>
                                        <div class="detail-row full">
                                            <i class="bx bx-map"></i>
                                            <div>
                                                <label>Alamat</label>
                                                <p>{{ $user->alamat ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--dark-grey);">
                                    <i class="bx bx-user-x" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                                    Belum ada user
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$users" label="user" />
            </div>
        </div>
    </main>
@endsection