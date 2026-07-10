import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import vue from '@vitejs/plugin-vue';
import autoprefixer from 'autoprefixer';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import path from 'path';
import tailwindcss from 'tailwindcss';
import Components from 'unplugin-vue-components/vite';
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
        Components({
            resolvers: [PrimeVueResolver()],
        }),
    ],

    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@assets': path.resolve(__dirname, './resources'),
            '@components': path.resolve(__dirname, './resources/js/components'),
            '@pages': path.resolve(__dirname, './resources/js/pages'),
            '@layouts': path.resolve(__dirname, './resources/js/layouts'),
            '@lib': path.resolve(__dirname, './resources/js/lib'),
            '@composables': path.resolve(__dirname, './resources/js/composables'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },

    css: {
        postcss: {
            plugins: [tailwindcss, autoprefixer],
        },
    },
});
