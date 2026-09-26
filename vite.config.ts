import lucide from '@iconify-json/lucide/icons.json' with { type: 'json' };
import ui from '@nuxt/ui/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import path from 'path';
import { defineConfig } from 'vite';
import uiThing from './resources/js/theme/ui-thing.ts';

export default defineConfig({
    define: { __LUCIDE_ICON_NAMES__: JSON.stringify(Object.keys(lucide.icons)) },
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
            // Mode warna ditangani composables/useAppearance.ts (localStorage + cookie yang
            // dibaca HandleAppearance untuk merender `.dark` di blade). Tanpa ini Nuxt UI
            // memasang useDark() VueUse dan keduanya sama-sama menulis class `.dark`.
            colorMode: false,
            components: {
                dirs: ['resources/js/components'],
            },
            // Ikon pilihan user dari DB tidak terdeteksi saat build; bundel koleksi agar tak mengambil dari api.iconify.design.
            icon: {
                clientBundle: {
                    icons: [...Object.keys(lucide.icons), ...Object.keys(lucide.aliases ?? {})].map((name) => `i-lucide-${name}`),
                    sizeLimitKb: 1024,
                },
            },
            ui: {
                colors: {
                    primary: 'violet',
                    neutral: 'zinc',
                },
                ...uiThing,
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
