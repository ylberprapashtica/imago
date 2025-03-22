import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
            protocol: 'ws'
        },
        port: 5173,
        watch: {
            usePolling: true,
            interval: 1000
        }
    }
});
