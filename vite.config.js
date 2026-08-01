import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/jadwal-form.js',
                'resources/js/peminjaman-form.js',
                'resources/js/login.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
