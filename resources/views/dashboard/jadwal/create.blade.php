@extends('layouts.app')
@section('title', 'Tambah Jadwal')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Tambah Jadwal</h1>
            </div>
            <a href="{{ route(auth()->user()->role . '.jadwal.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        <form action="{{ route(auth()->user()->role . '.jadwal.store') }}" method="POST" autocomplete="off" novalidate>
            @csrf

            @php
                $inputClass = 'h-10 px-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
                $hintClass = 'text-xs text-text-muted mt-0.5';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-info-circle"></i> Informasi Jadwal</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <x-input name="judul_kegiatan" label="Judul Rapat" required placeholder="cth: Rapat Koordinasi Bulanan" />
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-4">
                        <div class="flex-1 basis-[200px]">
                            <x-input type="date" name="tanggal" label="Tanggal" required min="{{ now()->format('Y-m-d') }}" data-weekdays-only="1" />
                        </div>
                        <div class="flex-1 basis-[200px]">
                            <x-select name="platform" label="Platform" required placeholder="-- Pilih Platform --">
                                @foreach(['Online (Zoom)', 'Offline', 'Hybrid'] as $p)
                                    <option value="{{ $p }}" {{ old('platform') == $p ? 'selected' : '' }}>{{ $p }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <x-input type="time" name="waktu_mulai" label="Waktu Mulai" required />

                    <x-input type="time" name="waktu_selesai" label="Waktu Selesai" required />

                    <div class="md:col-span-2">
                        <x-input name="keterangan" label="Keterangan" placeholder="Pilih platform terlebih dahulu"
                            hint="Pilih platform untuk petunjuk pengisian." hint-id="ket-hint" />
                    </div>
                    <div class="md:col-span-2" id="lokasi-fisik-wrap" style="display:none;">
                        <x-input name="lokasi_fisik" id="lokasi_fisik" label="Lokasi Fisik" placeholder="cth: Gedung A Lt.2 Ruang Rapat 1"
                            hint="Lokasi untuk peserta yang hadir langsung (rapat Hybrid)." />
                    </div>
                    <div class="md:col-span-2" id="link-otomatis-wrap" style="display:none;">
                        <div class="[&>label]:text-text [&>label]:dark:text-text-dark">
                            <x-checkbox name="link_otomatis" id="link_otomatis">Buat link Zoom otomatis</x-checkbox>
                        </div>
                        <div class="mt-2.5" id="zoom-akun-wrap" style="display:none;">
                            <x-select name="zoom_akun_pilihan" id="zoom_akun_pilihan" label="Pilih Akun Zoom">
                                <option value="">Otomatis (pilih akun yang kosong)</option>
                                <option value="akun_1" data-jadwal='@json($zoomJadwal['akun_1'] ?? [])' {{ old('zoom_akun_pilihan') == 'akun_1' ? 'selected' : '' }}>Akun 1</option>
                                <option value="akun_2" data-jadwal='@json($zoomJadwal['akun_2'] ?? [])' {{ old('zoom_akun_pilihan') == 'akun_2' ? 'selected' : '' }}>Akun 2</option>
                            </x-select>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="text-[13px] font-medium text-text dark:text-text-dark">Alat yang Dibutuhkan <small class="font-normal text-text-muted ml-1">(opsional)</small></label>
                        <p class="{{ $hintClass }} mb-3">Catatan acuan saja, bukan pengajuan peminjaman.</p>
                        <div class="dynamic-list flex flex-col gap-2.5" id="peralatan-list">
                            <div class="dynamic-item flex gap-2.5 items-center">
                                <select name="peralatan_ids[]" class="peralatan-select searchable flex-1"
                                    data-placeholder="-- Pilih Alat --"  onchange="refreshPeralatanOptions()">
                                    <option value="" disabled selected>-- Pilih Alat --</option>
                                    @foreach($daftarPeralatan as $alat)
                                        <option value="{{ $alat->id_peralatan }}"
                                            data-subtitle="{{ $alat->gedung }} &middot; Stok: {{ $alat->stok_tersedia }}"
                                            @if($alat->stok_tersedia <= 0) data-badge="Stok Habis" data-badge-variant="danger" @endif>
                                            {{ $alat->nama_peralatan }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" name="peralatan_jumlah[]" class="peralatan-jumlah {{ $inputClass }} flex-[0_0_80px]" min="1"
                                    placeholder="Jml" style="display:none;">
                                <button type="button" onclick="removePeralatan(this)"
                                    class="btn-remove w-9 h-9 rounded-lg border-none bg-danger dark:bg-danger-dark text-danger-text cursor-pointer flex items-center justify-center shrink-0 text-base transition-colors duration-200 hover:bg-danger-text hover:text-white">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" id="add-peralatan"
                            class="h-9 px-3.5 bg-page-bg dark:bg-page-bg-dark text-primary border border-dashed border-primary rounded-lg text-[13px] font-sans font-medium cursor-pointer inline-flex items-center gap-1.5 transition-colors duration-200 mt-1 w-fit hover:bg-primary-50">
                            <i class="bx bx-plus"></i> Tambah Alat
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bxs-group"></i> Operator Bertugas <span class="text-[#e74c3c] ml-0.5">*</span></h3>
                <p class="text-xs text-text-muted mb-1.5">
                    Operator yang sudah punya jadwal di tanggal ini akan di-disable
                </p>
                <div class="dynamic-list flex flex-col gap-2.5" id="operator-list">
                    <div class="dynamic-item flex gap-2.5 items-center">
                        <select name="operator_ids[]" class="operator-select searchable flex-1" required
                            data-placeholder="-- Pilih Operator --" onchange="refreshOperatorOptions()">
                            <option value="" disabled selected>-- Pilih Operator --</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id_user }}" data-subtitle="{{ $op->nohp ?? '-' }}"
                                    data-jadwal='@json($op->jadwalDitugaskan->pluck("tanggal")->map(fn($t) => \Carbon\Carbon::parse($t)->format("Y-m-d")))'>
                                    {{ $op->nama_user }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" onclick="removeOperator(this)" disabled
                            class="btn-remove w-9 h-9 rounded-lg border-none bg-danger dark:bg-danger-dark text-danger-text cursor-pointer flex items-center justify-center shrink-0 text-base transition-colors duration-200 hover:bg-danger-text hover:text-white disabled:opacity-40 disabled:pointer-events-none">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" id="add-operator"
                    class="h-9 px-3.5 bg-page-bg dark:bg-page-bg-dark text-primary border border-dashed border-primary rounded-lg text-[13px] font-sans font-medium cursor-pointer inline-flex items-center gap-1.5 transition-colors duration-200 mt-4 w-fit hover:bg-primary-50">
                    <i class="bx bx-plus"></i> Tambah Operator
                </button>
                @error('operator_ids')
                    <span class="text-xs text-danger-text mt-2 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2.5 mt-6 pt-5 border-t border-page-bg dark:border-page-bg-dark">
                <a href="{{ route(auth()->user()->role . '.jadwal.index') }}"
                    class="h-10 px-5 bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
                <button type="submit"
                    class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
                    <i class="bx bx-send"></i> Simpan & Kirim Notif WA
                </button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    @vite(['resources/js/jadwal-form.js'])
@endpush
