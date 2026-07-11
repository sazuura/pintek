@props(['id', 'title' => 'Konfirmasi', 'icon' => 'bx-error-circle', 'iconClass' => 'text-warning-text'])
{{--
    Modal konfirmasi generik dipakai buat semua aksi yang butuh persetujuan user
    (batalkan jadwal, batalkan pengajuan, konfirmasi duplikasi, dst) - menggantikan
    window.confirm() bawaan browser & form/toggle inline yang sebelumnya tersebar
    di tiap halaman. Buka/tutup lewat window.bukaModalKonfirmasi(id) /
    window.tutupModalKonfirmasi(id) (didefinisikan di public/js/content.js), tombol
    dengan atribut [data-modal-close], klik di luar kartu (backdrop), atau Escape.
--}}
<div id="{{ $id }}" class="modal-konfirmasi fixed inset-0 bg-black/45 z-[2100] items-center justify-center p-5 [&:not(.open)]:hidden [&.open]:flex">
    <div class="bg-surface dark:bg-surface-dark rounded-[14px] w-full max-w-[420px] max-h-[80vh] flex flex-col shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
        <div class="flex items-center justify-between gap-3 py-[18px] px-5 border-b border-page-bg dark:border-page-bg-dark">
            <h3 class="m-0 text-[15px] font-semibold text-text dark:text-text-dark flex items-center gap-2">
                <i class="bx {{ $icon }} {{ $iconClass }}"></i> {{ $title }}
            </h3>
            <button type="button" data-modal-close
                class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-text-muted text-lg shrink-0 transition-colors duration-200 hover:bg-page-bg dark:hover:bg-page-bg-dark hover:text-text dark:hover:text-text-dark">
                <i class="bx bx-x"></i>
            </button>
        </div>
        <div class="pt-4 px-5 pb-5 overflow-y-auto flex flex-col gap-3">
            {{ $slot }}
        </div>
    </div>
</div>
