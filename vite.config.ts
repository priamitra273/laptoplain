import ui from '@nuxt/ui/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import path from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts', 'resources/css/app.css'],
            ssr: 'resources/js/ssr.ts',
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
        ui({
            router: 'inertia',
            components: {
                dirs: ['resources/js/components'],
            },
            ui: {
                colors: {
                    primary: 'indigo',
                    neutral: 'slate',
                },
            },
        }),
    ],

    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, './resources/js'),
            '@assets': path.resolve(import.meta.dirname, './resources'),
            '@components': path.resolve(import.meta.dirname, './resources/js/components'),
            '@pages': path.resolve(import.meta.dirname, './resources/js/pages'),
            '@layouts': path.resolve(import.meta.dirname, './resources/js/layouts'),
            '@lib': path.resolve(import.meta.dirname, './resources/js/lib'),
            '@composables': path.resolve(import.meta.dirname, './resources/js/composables'),
            'ziggy-js': resolve(import.meta.dirname, 'vendor/tightenco/ziggy'),
        },
    },
});
