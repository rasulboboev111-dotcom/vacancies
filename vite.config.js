import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import vuetify from 'vite-plugin-vuetify';

export default defineConfig({
    // Bind the dev server to IPv4 127.0.0.1 (not the default [::1]) so the
    // public/hot URL matches the app host and the browser loads assets reliably
    // during local development. Port falls back if 5173 is taken — public/hot is
    // rewritten to whatever port Vite picks, so the app keeps working.
    server: {
        host: '127.0.0.1',
        port: 5173,
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        vuetify({ autoImport: true }),
    ],
});
