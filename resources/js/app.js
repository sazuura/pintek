// Bundel gabungan untuk semua script yang dipakai di SETIAP halaman lewat layouts/app.blade.php.
// Sebelumnya tiap file ini dimuat sebagai <script src> terpisah (7 request, tidak di-minify) -
// sekarang digabung+diminify jadi satu file lewat Vite. Urutan import di bawah ini penting:
// skeleton.js harus dimuat SEBELUM live-search.js dan page-skeleton.js karena keduanya
// memanggil window.SkeletonUtil yang didefinisikan skeleton.js.
import './adminhub.js';
import './content.js';
import './searchable-select.js';
import './datetime-picker.js';
import './skeleton.js';
import './live-search.js';
import './page-skeleton.js';
