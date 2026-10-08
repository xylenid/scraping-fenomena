import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

// Build SPA statis untuk hosting terpisah (Vercel).
// Tidak menggunakan laravel-vite-plugin agar build Laravel (vite.config.js) tetap utuh.
export default defineConfig({
    root: 'spa',
    base: '/',
    plugins: [tailwindcss(), vue()],
    build: {
        outDir: '../dist',
        emptyOutDir: true,
    },
});
