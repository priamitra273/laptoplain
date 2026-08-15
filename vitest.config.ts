import vue from '@vitejs/plugin-vue';
import { resolve } from 'node:path';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': resolve(import.meta.dirname, './resources/js'),
        },
    },
    test: {
        environment: 'node',
        include: ['resources/js/**/*.{test,spec}.ts'],
        exclude: ['**/node_modules/**', 'resources/js/**/*_v1/**'],
        passWithNoTests: true,
    },
});
