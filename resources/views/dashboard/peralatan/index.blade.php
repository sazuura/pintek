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
                                    <option value="">Semua Lokasi</option>
                                    @foreach($gedungList as $g)
                                        <option value="{{ $g }}" {{ request('gedung') == $g ? 'selected' : '' }}>{{ $g }}</option>
                                    @endforeach
                                </select>
                                <select name="status" onchange="this.form.submit()"
                                    class="h-9 px-2.5 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans cursor-pointer">
                                    <option value="">Semua Status</option>
                                    <option value="tersedia" {{ request('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
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

                        <div id="hasil-peralatan-inv" data-skel>
                        @if($peralatan->count())
                            <div class="grid grid-cols-5 max-tablet:!grid-cols-3 max-xs:!grid-cols-1 gap-4">
                                @foreach($peralatan as $item)
                                    <div class="bg-surface dark:bg-surface-dark rounded-2xl shadow-card transition-[transform,box-shadow] duration-200 flex flex-col hover:-translate-y-[3px] hover:shadow-[0_6px_20px_rgba(0,0,0,0.10)] overflow-hidden">

                                        {{-- Area Gambar --}}
                                        <div class="relative bg-page-bg dark:bg-page-bg-dark">
                                            <x-foto-item :path="$item->foto" :alt="$item->nama_peralatan" icon="bx-package"
                                                img-class="w-full aspect-[4/3] object-cover"
                                                icon-wrap-class="w-full aspect-[4/3] flex items-center justify-center text-text-muted text-5xl" />
                                            @if($item->status_terpasang)
                                                <div class="absolute top-3 right-3 z-10">
                                                    <x-badge variant="{{ $item->status_terpasang === 'terpasang' ? 'badge-info' : 'badge-inactive' }}" class="shadow-sm">
                                                        {{ $item->status_terpasang === 'terpasang' ? 'TERPASANG' : 'TIDAK TERPASANG' }}
                                                    </x-badge>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Info --}}
                                        <div class="p-4 flex flex-col gap-2 flex-1">
                                            <div class="text-[15px] font-bold text-text dark:text-text-dark leading-tight mb-0.5">{{ $item->nama_peralatan }}</div>

                                            @if($item->kode_barang)
                                                <div class="text-[13px] text-text-muted flex items-center gap-2">
                                                    <i class="bx bx-barcode text-[15px] shrink-0"></i>
                                                    <span class="truncate">{{ $item->kode_barang }}</span>
                                                </div>
                                            @endif

                                            <div class="text-[13px] text-text-muted flex items-center gap-2">
                                                <i class="bx bx-buildings text-[15px] shrink-0"></i>
                                                <span class="truncate">{{ $item->gedung }}</span>
                                            </div>

                                            <div class="text-[13px] text-text-muted flex items-center gap-2">
                                                <i class="bx bx-map text-[15px] shrink-0"></i>
                                                <span class="truncate">{{ $item->lokasi_detail }}</span>
                                            </div>

                                            {{-- Ringkasan Stok --}}
                                            <div class="mt-2 border border-page-bg dark:border-page-bg-dark rounded-xl p-3">
                                                <div class="flex items-start justify-between gap-1">
                                                    <div class="flex items-center gap-2">
                                                        <div>
                                                            <div class="text-[10px] text-text-muted leading-none whitespace-nowrap">Total Stok</div>
                                                            <div class="text-[18px] font-bold text-text dark:text-text-dark leading-tight text-center">{{ $item->stok }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <div>
                                                            <div class="text-[10px] text-success-text font-semibold leading-none">Baik</div>
                                                            <div class="text-[18px] font-bold text-success-text leading-tight text-center">{{ $item->stok_tersedia }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <div>
                                                            <div class="text-[10px] text-warning-text font-semibold leading-none">Rusak</div>
                                                            <div class="text-[18px] font-bold text-warning-text leading-tight text-center">{{ $item->rusak }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Tombol Aksi --}}
                                            <div class="px-4 pb-4 flex gap-2">
                                            @if($bisaUbah || $bisaHapus)
                                                    @if($bisaUbah)
                                                        <a href="{{ route($roleAktif . '.peralatan.edit', $item->id_peralatan) }}"
                                                            class="flex-1 justify-center h-10 rounded-xl border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-semibold transition-opacity duration-200 no-underline hover:opacity-85 bg-primary text-white">
                                                            <i class="bx bx-edit"></i> Edit
                                                        </a>
                                                    @endif

                                                    @if($bisaUbah)
                                                        <button type="button"
                                                            class="flex-1 justify-center h-10 rounded-xl text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-semibold transition-colors duration-200 border border-success-text/30 text-success-text bg-surface dark:bg-surface-dark hover:bg-success/75 dark:hover:bg-success-dark/10"
                                                            onclick="bukaModalStatusPeralatan('{{ route($roleAktif . '.peralatan.status', $item->id_peralatan) }}', '{{ $item->status_terpasang ?? 'tidak terpasang' }}')"
                                                            title="Pasang">
                                                            <i class="bx bx-pin"></i> Pasang
                                                        </button>
                                                    @endif

                                                    @if($bisaHapus)
                                                        <button type="button"
                                                             class="flex-1 justify-center h-10 rounded-xl text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-semibold transition-colors duration-200 border border-danger-text/30 text-danger-text bg-surface dark:bg-surface-dark hover:bg-danger/75 dark:hover:bg-danger-dark/10"
                                                            onclick="bukaKonfirmasiHapusPeralatan('{{ route($roleAktif . '.peralatan.destroy', $item->id_peralatan) }}', '{{ addslashes($item->nama_peralatan) }}')"
                                                            title="Hapus">
                                                            <i class="bx bx-trash"></i> Hapus
                                                        </button>
                                                    @endif
                                                @endif
                                                </div>
                                    </div>
                                @endforeach
                            </div>

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

                        <x-modal-konfirmasi id="modalStatusPeralatan" title="Ubah Status Terpasang" icon="bx-transfer-alt" icon-class="text-primary">
                            <form id="formStatusPeralatan" method="POST">
                                @csrf
                                <div class="mt-2">
                                    <label for="status-terpasang" class="text-[13px] font-medium text-text dark:text-text-dark block mb-2">Status Terpasang</label>
                                    <select id="status-terpasang" name="status_terpasang" class="w-full h-9 px-3 border border-page-bg dark:border-page-bg-dark rounded-lg bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark text-[13px] font-sans">
                                        <option>-- Pilih Status --</option>
                                        <option value="terpasang">Terpasang</option>
                                        <option value="tidak terpasang">Tidak Terpasang</option>
                                    </select>
                                </div>
                                <div class="flex justify-end gap-2.5 mt-4">
                                    <button type="button" data-modal-close
                                        class="h-9 px-3.5 rounded-lg bg-surface dark:bg-surface-dark border border-gray-300 dark:border-gray-700 text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark text-text dark:text-text-dark">Batal</button>
                                    <button type="submit"
                                        class="h-9 px-3.5 rounded-lg border-none bg-primary text-white text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 hover:opacity-85">
                                        Simpan
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

                        function bukaModalStatusPeralatan(url, status) {
                            var form = document.getElementById('formStatusPeralatan');
                            form.action = url;
                            document.getElementById('status-terpasang').value = status || 'tidak terpasang';
                            bukaModalKonfirmasi('modalStatusPeralatan');
                        }
                    </script>
                @endpush

