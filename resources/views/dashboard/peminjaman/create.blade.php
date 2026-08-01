@extends('layouts.app')
@section('title', 'Ajukan Peminjaman')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
    @php $roleAktif = auth()->user()->role; @endphp
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Ajukan Peminjaman Peralatan</h1>
            </div>
            <a href="{{ route($roleAktif . '.peminjaman.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        <form action="{{ route($roleAktif . '.peminjaman.store') }}" method="POST" id="form-peminjaman" novalidate
            data-cek-spam-url="{{ route($roleAktif . '.peminjaman.cekSpam') }}">
            @csrf

            @php
                $inputClass = 'h-10 px-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-surface dark:bg-surface-dark text-text dark:text-text-dark text-sm font-sans transition-[border-color,box-shadow] duration-200 w-full box-border focus:border-primary focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,102,255,0.10)]';
                $hintClass = 'text-xs text-text-muted mt-0.5';
            @endphp

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bx-info-circle"></i> Detail Pengajuan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <x-select name="id_penjadwalan" id="id_penjadwalan"
                            hint="Pilih apabila peminjaman ini terkait salah satu rapat yang Anda tugaskan">
                            <x-slot:label>Kaitkan ke Jadwal <small class="font-normal text-text-muted ml-1">(opsional)</small></x-slot:label>
                            <option value="">-- Tidak terkait jadwal tertentu --</option>
                            @foreach($jadwalAktif as $j)
                                <option value="{{ $j->id_penjadwalan }}"
                                    data-judul="{{ $j->judul_kegiatan }}"
                                    data-tanggal="{{ $j->tanggal->format('Y-m-d') }}"
                                    data-sudah-diajukan='@json($j->peralatanSudahDiajukan())'
                                    data-referensi='@json($j->peralatanReferensi->map(fn($p) => ["id" => $p->id_peralatan, "nama" => $p->nama_peralatan, "jumlah" => $p->pivot->jumlah]))'
                                    {{ old('id_penjadwalan') == $j->id_penjadwalan ? 'selected' : '' }}>
                                    {{ $j->judul_kegiatan }} - {{ $j->tanggal->translatedFormat('D, d M Y') }}
                                </option>
                            @endforeach
                        </x-select>
                        <div id="referensi-hint" class="hidden bg-page-bg dark:bg-page-bg-dark rounded-lg py-2.5 px-3.5 mt-1">
                            <p class="text-[13px] text-text dark:text-text-dark font-medium m-0 mb-1.5"><i class="bx bx-info-circle text-primary"></i> Rekomendasi alat untuk jadwal ini:</p>
                            <div id="referensi-hint-list" class="flex flex-col gap-1"></div>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <x-input name="keperluan" label="Keperluan" required
                            placeholder="cth: Rapat dinas luar kota bersama Kemendagri" value="{{ old('keperluan') }}" />
                    </div>
                    <x-input type="date" name="tanggal_pinjam" label="Tanggal Pinjam" required
                        min="{{ now()->format('Y-m-d') }}" value="{{ old('tanggal_pinjam') }}" />
                    <x-input type="date" name="tanggal_kembali_rencana" label="Rencana Kembali" required
                        value="{{ old('tanggal_kembali_rencana') }}" hint="Boleh sama dengan atau setelah tanggal pinjam." />
                </div>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-6 mb-5">
                <h3 class="text-[15px] font-semibold text-text dark:text-text-dark mb-5 pb-3 border-b border-page-bg dark:border-page-bg-dark flex items-center gap-2">
                    <i class="bx bxs-wrench"></i> Pilih Peralatan <span class="text-[#e74c3c] ml-0.5">*</span></h3>

                <div class="dynamic-list flex flex-col gap-2.5" id="peralatan-list">
                    <div class="dynamic-item flex gap-2.5 items-center">
                        <select name="peralatan_ids[]" class="{{ $inputClass }} peralatan-select searchable"
                            data-placeholder="-- Pilih Peralatan --" onchange="refreshPeralatanOptions()" required>
                            <option value="" disabled selected>-- Pilih Peralatan --</option>
                            @foreach($peralatan as $gedung => $items)
                                <optgroup label="{{ $gedung }}">
                                    @foreach($items as $alat)
                                        <option value="{{ $alat->id_peralatan }}"
                                            data-subtitle="{{ $gedung }} &middot; Stok: {{ $alat->stok_tersedia }}"
                                            data-nama="{{ $alat->nama_peralatan }}"
                                            {{ (isset($selectedPeralatanId) && $selectedPeralatanId == $alat->id_peralatan) ? 'selected' : '' }}>
                                            {{ $alat->nama_peralatan }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <input type="number" name="peralatan_jumlah[]" class="{{ $inputClass }} flex-[0_0_80px]" min="1" placeholder="Jml" required>
                        <button type="button" onclick="removeItem(this)" disabled
                            class="btn-remove w-9 h-9 rounded-lg border-none bg-danger dark:bg-danger-dark text-danger-text cursor-pointer flex items-center justify-center shrink-0 text-base transition-colors duration-200 hover:bg-danger-text hover:text-white disabled:opacity-40 disabled:pointer-events-none">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" id="add-peralatan"
                    class="h-9 px-3.5 bg-page-bg dark:bg-page-bg-dark text-primary border border-dashed border-primary rounded-lg text-[13px] font-sans font-medium cursor-pointer inline-flex items-center gap-1.5 transition-colors duration-200 mt-4 w-fit hover:bg-primary-50">
                    <i class="bx bx-plus"></i> Tambah Peralatan
                </button>
                @error('peralatan_ids')
                    <span class="text-xs text-danger-text mt-2 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2.5 mt-6 pt-5 border-t border-page-bg dark:border-page-bg-dark">
                <a href="{{ route($roleAktif . '.peminjaman.index') }}"
                    class="h-10 px-5 bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark rounded-lg text-sm font-sans cursor-pointer no-underline inline-flex items-center gap-2 transition-colors duration-200">Batal</a>
                <button type="submit"
                    class="h-10 px-5 bg-primary hover:bg-primary-600 text-white border-none rounded-lg text-sm font-semibold font-sans cursor-pointer inline-flex items-center gap-2 transition-colors duration-200">
                    <i class="bx bx-send"></i> Kirim Pengajuan
                </button>
            </div>
        </form>

        {{-- Modal konfirmasi duplikasi alat - dipakai saat submit, menggantikan window.confirm() bawaan browser --}}
        <x-modal-konfirmasi id="modalKonfirmasiDuplikat" title="Konfirmasi Duplikasi Alat" icon="bx-error" icon-class="text-warning-text">
            <p class="text-[13px] text-text dark:text-text-dark m-0 mb-2">Alat berikut sudah dipinjam/diajukan operator lain untuk jadwal ini:</p>
            <ul id="modalKonfirmasiDuplikatList" class="text-[13px] text-text dark:text-text-dark m-0 pl-[18px] flex flex-col gap-1"></ul>
            <p class="text-[13px] text-text-muted m-0">Apakah Anda yakin tetap ingin mengajukan peminjaman ini?</p>
            <div class="flex justify-end gap-2.5 mt-1">
                <button type="button" data-modal-close
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                <button type="button" onclick="konfirmasiTetapAjukan()"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-warning-text text-white">
                    <i class="bx bx-check"></i> Ya, Tetap Ajukan
                </button>
            </div>
        </x-modal-konfirmasi>

        {{-- Modal peringatan pengajuan berulang (spam) - dicek lewat AJAX ke cek-spam sesaat sebelum submit, supaya operator sadar kalau alat yang sama sudah berkali-kali diajukan untuk tanggal pinjam yang sama. --}}
        <x-modal-konfirmasi id="modalPeringatanSpam" title="Pengajuan Berulang Terdeteksi" icon="bx-error" icon-class="text-warning-text">
            <p class="text-[13px] text-text dark:text-text-dark m-0 mb-2">Anda sudah beberapa kali mengajukan peralatan berikut untuk tanggal pinjam yang sama:</p>
            <ul id="modalPeringatanSpamList" class="text-[13px] text-text dark:text-text-dark m-0 pl-[18px] flex flex-col gap-1"></ul>
            <p class="text-[13px] text-text-muted m-0">Apakah Anda yakin ingin melanjutkan pengajuan ini?</p>
            <div class="flex justify-end gap-2.5 mt-1">
                <button type="button" data-modal-close
                    class="h-9 px-3.5 rounded-lg bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                <button type="button" onclick="konfirmasiTetapAjukanSpam()"
                    class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-warning-text text-white">
                    <i class="bx bx-check"></i> Ya, Tetap Ajukan
                </button>
            </div>
        </x-modal-konfirmasi>
    </main>
@endsection

@push('scripts')
    @vite(['resources/js/peminjaman-form.js'])
@endpush
