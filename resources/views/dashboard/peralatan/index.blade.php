@extends('layouts.app')
@section('title', 'Data Peralatan')
@section('sidebar-menu') <x-sidebar /> @endsection

@section('content')
    @php
        $roleAktif   = auth()->user()->role;
        $isOperator  = $roleAktif === 'operator';
        $bisaTambah  = auth()->user()->punyaAkses('peralatan', 'tambah');
        $bisaUbah    = auth()->user()->punyaAkses('peralatan', 'ubah');
        $bisaHapus   = auth()->user()->punyaAkses('peralatan', 'hapus');
        $bisaPinjam  = $isOperator && auth()->user()->punyaAkses('peminjaman', 'tambah');
    @endphp
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Data Peralatan</h1>
            </div>
            @if($bisaTambah)
                <a href="{{ route($roleAktif . '.peralatan.create') }}"
                    class="h-9 px-4 rounded-full bg-primary text-surface dark:text-surface-dark flex justify-center items-center gap-2.5 font-medium">
                    <i class="bx bx-plus"></i><span class="text">Tambah Peralatan</span>
                </a>
            @endif
        </div>

        <div class="bg-surface dark:bg-surface-dark rounded-[10px] py-3.5 px-4 mb-4 flex items-center gap-2.5 flex-wrap shadow-[0_2px_8px_rgba(0,0,0,0.04)] max-md:flex-col max-md:items-stretch">
            <form method="GET" action="{{ route($roleAktif . '.peralatan.index') }}" class="contents">
                <div class="relative flex-1 min-w-[180px] max-w-[300px] max-md:max-w-full">
                    <i class="bx bx-search absolute left-2.5 top-1/2 -translate-y-1/2 text-text-muted text-base pointer-events-none"></i>
                    <input type="text" name="search" data-live-search="#hasil-peralatan-inv" autocomplete="off" placeholder="Cari nama / kode barang..." value="{{ request('search') }}"
                        class="w-full h-9 pl-[34px] pr-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans transition-colors duration-200 focus:border-primary focus:outline-none focus:bg-surface dark:focus:bg-surface-dark">
                </div>
                <select name="gedung" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Gedung</option>
                    @foreach($gedungList as $g)
                        <option value="{{ $g }}" {{ request('gedung') == $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="kritis" {{ request('status') == 'kritis' ? 'selected' : '' }}>Hampir Habis</option>
                    <option value="tidak_tersedia" {{ request('status') == 'tidak_tersedia' ? 'selected' : '' }}>Tidak Tersedia</option>
                </select>
                <select name="kondisi" onchange="this.form.submit()"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="">Semua Kondisi</option>
                    <option value="baik" {{ request('kondisi') == 'baik' ? 'selected' : '' }}>Baik</option>
                    <option value="rusak" {{ request('kondisi') == 'rusak' ? 'selected' : '' }}>Rusak</option>
                </select>
                <select name="urutkan" onchange="this.form.submit()" data-placeholder="-- Urutkan --"
                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                    <option value="" disabled {{ request('urutkan') ? '' : 'selected' }}>-- Urutkan --</option>
                    <option value="nama_asc" {{ request('urutkan') == 'nama_asc' ? 'selected' : '' }}>Nama (A-Z)</option>
                    <option value="nama_desc" {{ request('urutkan') == 'nama_desc' ? 'selected' : '' }}>Nama (Z-A)</option>
                    <option value="stok_desc" {{ request('urutkan') == 'stok_desc' ? 'selected' : '' }}>Stok Terbanyak</option>
                    <option value="stok_asc" {{ request('urutkan') == 'stok_asc' ? 'selected' : '' }}>Stok Tersedikit</option>
                </select>
                @if(request()->hasAny(['search', 'gedung', 'status', 'kondisi', 'urutkan']))
                    <a href="{{ route($roleAktif . '.peralatan.index') }}"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                        <i class="bx bx-x"></i> Reset</a>
                @endif
            </form>
            <div class="ml-auto flex gap-2 items-center max-md:ml-0 max-md:w-full">
                <span class="text-[13px] text-text-muted">{{ $peralatan->total() }} peralatan</span>
            </div>
        </div>

        {{-- Grid marketplace --}}
        <div id="hasil-peralatan-inv" data-skel>
        @if($peralatan->count())
            <div class="grid grid-cols-5 max-tablet:!grid-cols-3 max-xs:!grid-cols-1 gap-4">
                @foreach($peralatan as $item)
                    <div class="bg-surface dark:bg-surface-dark rounded-xl overflow-hidden shadow-card transition-[transform,box-shadow] duration-200 flex flex-col hover:-translate-y-[3px] hover:shadow-[0_6px_20px_rgba(0,0,0,0.10)]">
                        {{-- Foto - fallback ke ikon global kalau kolom foto kosong ATAU filenya sudah tidak ada di storage --}}
                        <x-foto-item :path="$item->foto" :alt="$item->nama_peralatan" icon="bx-package"
                            img-class="w-full aspect-[4/3] object-cover bg-page-bg dark:bg-page-bg-dark"
                            icon-wrap-class="w-full aspect-[4/3] bg-page-bg dark:bg-page-bg-dark flex items-center justify-center text-text-muted text-4xl" />

                        <div class="p-3.5 flex flex-col gap-2 flex-1">
                            <div class="text-base font-bold text-text dark:text-text-dark leading-tight">{{ $item->nama_peralatan }}</div>

                            @if($item->kode_barang)
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                    <i class="bx bx-barcode"></i> {{ $item->kode_barang }}
                                </div>
                            @endif

                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                <i class="bx bx-building"></i> {{ $item->lokasi_detail }}
                            </div>

                            <div class="border border-page-bg dark:border-page-bg-dark rounded-lg overflow-hidden mt-1">
                                <div class="text-center py-2 text-[13px] text-text dark:text-text-dark">
                                    Total Stok: <span class="font-bold text-[15px]">{{ $item->stok }}</span>
                                </div>
                                <div class="grid grid-cols-2 border-t border-page-bg dark:border-page-bg-dark">
                                    <div class="flex items-center justify-center gap-1.5 py-2.5 border-r border-page-bg dark:border-page-bg-dark">
                                        <i class="bx bxs-check-circle text-success-text text-base"></i>
                                        <span class="text-[13px] font-semibold text-success-text">{{ $item->stok_tersedia }} Baik</span>
                                    </div>
                                    <div class="flex items-center justify-center gap-1.5 py-2.5">
                                        <i class="bx bxs-error text-warning-text text-base"></i>
                                        <span class="text-[13px] font-semibold text-warning-text">{{ $item->rusak }} Rusak</span>
                                    </div>
                                </div>
                            </div>

                            <x-badge :variant="$item->statusBadgeClass">{{ $item->statusLabel }}</x-badge>
                        </div>

                        @if($isOperator)
                            @if($bisaPinjam)
                                <div class="py-2.5 px-3.5 border-t border-page-bg dark:border-page-bg-dark flex gap-1.5">
                                    @if($item->stok_tersedia > 0)
                                        <a href="{{ route('operator.peminjaman.create', ['id_peralatan' => $item->id_peralatan]) }}"
                                            class="flex-1 justify-center h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-primary text-white">
                                            <i class="bx bx-package"></i> Pinjam
                                        </a>
                                    @else
                                        <span class="flex-1 justify-center h-9 px-3.5 rounded-lg text-[13px] font-sans inline-flex items-center gap-1.5 font-medium cursor-not-allowed opacity-50 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                                            Stok Habis
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @elseif($bisaUbah || $bisaHapus)
                            <div class="py-2.5 px-3.5 border-t border-page-bg dark:border-page-bg-dark flex gap-1.5">
                                @if($bisaUbah)
                                    <a href="{{ route($roleAktif . '.peralatan.edit', $item->id_peralatan) }}"
                                        class="flex-1 justify-center h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                                        <i class="bx bx-edit"></i> Edit
                                    </a>
                                @endif
                                @if($bisaHapus)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg border-none cursor-pointer inline-flex items-center justify-center text-[15px] transition-opacity duration-200 shrink-0 hover:opacity-80 bg-danger dark:bg-danger-dark text-danger-text"
                                        onclick="bukaKonfirmasiHapusPeralatan('{{ route($roleAktif . '.peralatan.destroy', $item->id_peralatan) }}', '{{ addslashes($item->nama_peralatan) }}')"
                                        title="Hapus">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Pagination untuk grid --}}
            <div class="mt-5 bg-surface dark:bg-surface-dark rounded-xl shadow-card">
                <x-pagination :paginator="$peralatan" label="peralatan" />
            </div>

        @else
            <div class="bg-surface dark:bg-surface-dark rounded-xl shadow-card p-[60px] text-center text-text-muted">
                <i class="bx bx-package text-5xl block mb-3"></i>
                <p>Belum ada peralatan</p>
                @if($bisaTambah)
                    <a href="{{ route($roleAktif . '.peralatan.create') }}"
                        class="mt-3 inline-flex h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-primary text-white">
                        <i class="bx bx-plus"></i> Tambah Sekarang
                    </a>
                @endif
            </div>
        @endif
        </div>

        <x-modal-konfirmasi id="modalKonfirmasiHapusPeralatan" title="Hapus Peralatan" icon="bx-trash" icon-class="text-danger-text">
            <div class="bg-danger dark:bg-danger-dark rounded-[10px] py-3.5 px-4">
                <div class="text-[13px] font-semibold text-danger-text">
                    <i class="bx bx-error"></i> Hapus <span id="namaPeralatanHapus" class="font-bold"></span>? Tindakan ini tidak bisa dibatalkan.
                </div>
            </div>
            <form id="formKonfirmasiHapusPeralatan" method="POST">
                @csrf @method('DELETE')
                <div class="flex justify-end gap-2.5 mt-3">
                    <button type="button" data-modal-close
                        class="h-9 px-3.5 rounded-lg bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                    <button type="submit"
                        class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85 bg-danger-text text-white">
                        <i class="bx bx-trash"></i> Hapus
                    </button>
                </div>
            </form>
        </x-modal-konfirmasi>
    </main>
@endsection

@push('scripts')
    <script>
        function bukaKonfirmasiHapusPeralatan(url, nama) {
            document.getElementById('formKonfirmasiHapusPeralatan').action = url;
            document.getElementById('namaPeralatanHapus').textContent = nama;
            bukaModalKonfirmasi('modalKonfirmasiHapusPeralatan');
        }
    </script>
@endpush
