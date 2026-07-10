@extends('layouts.app')
@section('title', 'Ajukan Peminjaman')
@section('sidebar-menu') <x-sidebar-operator /> @endsection

@section('content')
    <main class="w-full pt-9 px-6 pb-9 font-sans max-h-[calc(100vh-56px)] overflow-y-auto overflow-x-hidden">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h1 class="text-4xl font-semibold mb-2.5 text-text dark:text-text-dark">Ajukan Peminjaman Peralatan</h1>
            </div>
            <a href="{{ route('operator.peminjaman.index') }}"
                class="h-9 px-3.5 rounded-lg border-none text-[13px] font-sans cursor-pointer inline-flex items-center gap-1.5 font-medium transition-opacity duration-200 no-underline hover:opacity-85 bg-page-bg dark:bg-page-bg-dark text-text dark:text-text-dark">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>

        <form action="{{ route('operator.peminjaman.store') }}" method="POST" id="form-peminjaman" novalidate>
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
                            hint="Pilih apabila peminjaman ini terkait salah satu rapat yang Anda tugaskan. Keperluan dan Tanggal Pinjam akan terisi otomatis (dapat diubah). Konfirmasi akan ditampilkan apabila peralatan yang sama telah diajukan oleh operator lain untuk mencegah duplikasi.">
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
                        <div id="referensi-hint" class="hidden bg-primary-50 dark:bg-[#0d2a40] rounded-lg py-2.5 px-3.5 mt-1">
                            <p class="text-[13px] text-primary font-medium m-0"><i class="bx bx-info-circle"></i> Alat yang direkomendasikan admin untuk jadwal ini sudah otomatis ditambahkan di bawah (tetap bisa diubah/dihapus):</p>
                            <p id="referensi-hint-list" class="text-[13px] text-primary m-0 mt-1"></p>
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
                <p class="{{ $hintClass }} mb-3.5">
                    Peralatan dari gedung berbeda akan mengirim notifikasi ke masing-masing inventaris secara otomatis.
                    Peralatan yang sudah dipilih di baris lain tersembunyi otomatis.
                </p>

                <div class="dynamic-list flex flex-col gap-2.5" id="peralatan-list">
                    <div class="dynamic-item flex gap-2.5 items-center">
                        <select name="peralatan_ids[]" class="{{ $inputClass }} peralatan-select searchable"
                            data-placeholder="Cari alat..." onchange="refreshPeralatanOptions()" required>
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
                    class="h-9 px-3.5 bg-page-bg dark:bg-page-bg-dark text-primary border border-dashed border-primary rounded-lg text-[13px] font-sans font-medium cursor-pointer inline-flex items-center gap-1.5 transition-colors duration-200 mt-1 w-fit hover:bg-primary-50">
                    <i class="bx bx-plus"></i> Tambah Peralatan
                </button>
                @error('peralatan_ids')
                    <span class="text-xs text-danger-text mt-2 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2.5 mt-6 pt-5 border-t border-page-bg dark:border-page-bg-dark">
                <a href="{{ route('operator.peminjaman.index') }}"
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

        {{-- Modal peringatan pengajuan berulang (spam) - dicek lewat AJAX ke cek-spam
             sesaat sebelum submit, supaya operator sadar kalau alat yang sama sudah
             berkali-kali diajukan untuk tanggal pinjam yang sama. --}}
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
    <script>
        // ── Kaitkan ke Jadwal: auto-isi Keperluan & Tanggal Pinjam, cek peralatan yang
        //    sudah diajukan operator lain (utk peringatan+konfirmasi, bukan blokir), dan
        //    auto-isi baris peralatan dari rekomendasi admin (peralatanReferensi) ──
        var jadwalSudahDiajukan = [];
        var jadwalReferensi     = [];
        var jadwalGantiTerakhir = null; // cegah auto-isi ulang kalau jadwal yang sama dipilih lagi

        function updateJadwalDuplikasiState(jadwalBerubah) {
            var select = document.getElementById('id_penjadwalan');
            var opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) {
                jadwalSudahDiajukan = [];
                jadwalReferensi = [];
            } else {
                try { jadwalSudahDiajukan = JSON.parse(opt.dataset.sudahDiajukan || '[]'); } catch (e) { jadwalSudahDiajukan = []; }
                try { jadwalReferensi = JSON.parse(opt.dataset.referensi || '[]'); } catch (e) { jadwalReferensi = []; }
            }
            updateReferensiHint();
            if (jadwalBerubah) autoIsiDariReferensi();
            refreshPeralatanOptions();
        }

        // Isi ulang daftar peralatan pakai rekomendasi admin utk jadwal ini (alat yang
        // sudah diajukan operator lain dilewati - tetap disebut di keterangan). Kalau
        // jadwal tidak punya rekomendasi sama sekali, baris yang sudah ada tidak diubah.
        function autoIsiDariReferensi() {
            if (jadwalReferensi.length === 0) return;

            var bisaDiisi = jadwalReferensi.filter(function (r) { return jadwalSudahDiajukan.indexOf(r.nama) === -1; });
            if (bisaDiisi.length === 0) return;

            var list = document.getElementById('peralatan-list');
            // Sisakan baris pertama sebagai "template" buat baris berikutnya, buang sisanya.
            while (list.children.length > 1) list.removeChild(list.lastElementChild);

            var firstRow  = list.querySelector('.dynamic-item');
            var firstItem = bisaDiisi[0];
            var select    = firstRow.querySelector('select');
            select.value  = firstItem.id;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            firstRow.querySelector('input[type=number]').value = firstItem.jumlah;

            bisaDiisi.slice(1).forEach(function (r) {
                addPeralatanRow(r.id, r.jumlah);
            });
            updateRemoveButtons();
            window.SearchableSelect && window.SearchableSelect.refreshAll();
        }

        function updateReferensiHint() {
            var box  = document.getElementById('referensi-hint');
            var list = document.getElementById('referensi-hint-list');
            if (jadwalReferensi.length === 0) {
                box.classList.add('hidden');
                return;
            }

            var teks = jadwalReferensi.map(function (r) {
                var sudah = jadwalSudahDiajukan.indexOf(r.nama) !== -1;
                return r.nama + ' (x' + r.jumlah + ')' + (sudah ? ' - sudah diajukan, tidak diisi otomatis' : '');
            }).join(', ');

            list.textContent = teks;
            box.classList.remove('hidden');
        }

        document.getElementById('id_penjadwalan').addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            if (opt.value) {
                document.getElementById('keperluan').value = opt.dataset.judul;
                document.getElementById('tanggal_pinjam').value = opt.dataset.tanggal;
            }
            var berubah = jadwalGantiTerakhir !== opt.value;
            jadwalGantiTerakhir = opt.value;
            updateJadwalDuplikasiState(berubah);
        });

        function getSelectedPeralatan() {
            return Array.from(document.querySelectorAll('.peralatan-select'))
                .map(function (s) { return s.value; }).filter(function (v) { return v !== ''; });
        }

        function refreshPeralatanOptions() {
            var selected = getSelectedPeralatan();
            document.querySelectorAll('.peralatan-select').forEach(function (select) {
                var currentVal = select.value;
                Array.from(select.options).forEach(function (opt) {
                    if (!opt.value) return;
                    opt.hidden = selected.includes(opt.value) && opt.value !== currentVal;

                    // Dicocokkan lewat nama alat (bukan id_peralatan) supaya alat yang sama
                    // tapi baris stoknya beda di gedung lain (mis. Kabel HDMI 15 Meter yang
                    // ada di Gedung A & Gedung B) tetap kena tanda "Sudah Diajukan".
                    var sudahDiajukan = jadwalSudahDiajukan.indexOf(opt.dataset.nama) !== -1;

                    // Alat yang sudah diajukan operator lain tetap bisa dipilih (tidak diblokir),
                    // cuma diberi tanda peringatan - konfirmasi tetap muncul sebelum submit.
                    if (sudahDiajukan) {
                        opt.dataset.badge = 'Sudah Diajukan';
                        opt.dataset.badgeVariant = 'warning';
                    } else {
                        delete opt.dataset.badge;
                        delete opt.dataset.badgeVariant;
                    }
                });
            });
            window.SearchableSelect && window.SearchableSelect.refreshAll();
        }

        function removeItem(btn) {
            var list = document.getElementById('peralatan-list');
            if (list.children.length > 1) {
                btn.closest('.dynamic-item').remove();
                refreshPeralatanOptions();
            }
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            var items = document.querySelectorAll('#peralatan-list .dynamic-item');
            items.forEach(function (item) {
                item.querySelector('.btn-remove').disabled = items.length <= 1;
            });
        }

        document.getElementById('add-peralatan').addEventListener('click', function () {
            addPeralatanRow();
            refreshPeralatanOptions();
            updateRemoveButtons();
        });

        // Bikin satu baris baru (dipakai tombol "Tambah Peralatan" & auto-isi dari rekomendasi jadwal).
        // Kalau value/jumlah diisi, langsung di-set & dipicu event change-nya.
        function addPeralatanRow(value, jumlah) {
            var list = document.getElementById('peralatan-list');
            var clone = list.querySelector('.dynamic-item').cloneNode(true);
            clone.querySelectorAll('option').forEach(function (opt) { opt.hidden = false; opt.disabled = false; delete opt.dataset.badge; delete opt.dataset.badgeVariant; });
            clone.querySelector('select').value = '';
            clone.querySelector('input[type=number]').value = '';
            clone.querySelector('select').onchange = refreshPeralatanOptions;
            clone.querySelector('.btn-remove').disabled = false;
            clone.querySelector('.btn-remove').onclick = function () { removeItem(this); };
            list.appendChild(clone);
            window.SearchableSelect && window.SearchableSelect.reinitRow(clone);

            if (value) {
                var select = clone.querySelector('select');
                select.value = value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                window.SearchableSelect && window.SearchableSelect.refreshAll();
            }
            if (jumlah) {
                clone.querySelector('input[type=number]').value = jumlah;
            }
            return clone;
        }
        document.addEventListener('DOMContentLoaded', function () {
            updateJadwalDuplikasiState();
        });

        // ── Konfirmasi sebelum submit kalau operator memilih alat yang sudah
        //    diajukan/dipinjam operator lain untuk jadwal yang sama - pakai modal custom
        //    di tengah layar, bukan window.confirm() bawaan browser ──
        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str == null ? '' : String(str);
            return div.innerHTML;
        }

        // Alur konfirmasi sebelum submit (dua tahap, berurutan):
        //   1. Duplikasi alat vs jadwal (operator lain sudah ajukan alat sama untuk jadwal ini) - sinkron, dari data yang sudah dimuat di awal halaman.
        //   2. Pengajuan berulang/spam (operator ini sendiri sudah berkali-kali ajukan alat sama di tanggal pinjam yang sama) - via AJAX ke server karena datanya baru diketahui setelah tanggal & alat dipilih.
        // Keduanya cuma peringatan (bisa dilanjutkan setelah konfirmasi), bukan blokir keras.
        document.getElementById('form-peminjaman').addEventListener('submit', function (e) {
            e.preventDefault();
            lanjutkanSetelahCekDuplikat();
        });

        function lanjutkanSetelahCekDuplikat() {
            var namaBentrok = [];
            if (jadwalSudahDiajukan.length > 0) {
                document.querySelectorAll('.peralatan-select').forEach(function (select) {
                    var opt = select.options[select.selectedIndex];
                    if (!opt || jadwalSudahDiajukan.indexOf(opt.dataset.nama) === -1) return;
                    namaBentrok.push(opt.dataset.nama || select.value);
                });
            }

            if (namaBentrok.length > 0) {
                document.getElementById('modalKonfirmasiDuplikatList').innerHTML = namaBentrok.map(function (n) {
                    return '<li>' + escapeHtml(n) + '</li>';
                }).join('');
                bukaModalKonfirmasi('modalKonfirmasiDuplikat');
                return;
            }

            cekSpamLaluSubmit();
        }

        function konfirmasiTetapAjukan() {
            tutupModalKonfirmasi('modalKonfirmasiDuplikat');
            cekSpamLaluSubmit();
        }

        function cekSpamLaluSubmit() {
            var form = document.getElementById('form-peminjaman');
            var formData = new FormData(form);

            fetch('{{ route('operator.peminjaman.cekSpam') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData,
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    var peringatan = data.peringatan || [];
                    if (peringatan.length > 0) {
                        document.getElementById('modalPeringatanSpamList').innerHTML = peringatan.map(function (p) {
                            return '<li>' + escapeHtml(p.nama) + ' (sudah diajukan ' + p.jumlah_sebelumnya + 'x untuk tanggal pinjam ini)</li>';
                        }).join('');
                        bukaModalKonfirmasi('modalPeringatanSpam');
                    } else {
                        form.submit();
                    }
                })
                .catch(function () {
                    // Kalau pengecekan gagal (mis. jaringan bermasalah), jangan sampai
                    // memblokir pengajuan asli - langsung submit saja.
                    form.submit();
                });
        }

        function konfirmasiTetapAjukanSpam() {
            tutupModalKonfirmasi('modalPeringatanSpam');
            document.getElementById('form-peminjaman').submit();
        }
    </script>
@endpush
