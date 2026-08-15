import { useStorage } from '@vueuse/core';
import { computed, watch } from 'vue';
// Import eksplisit, bukan auto-import: auto-imports.d.ts ber-@ts-nocheck sehingga
// deklarasi global-nya tidak terlihat oleh vue-tsc.
import { useAppConfig } from '@nuxt/ui/runtime/vue/composables/useAppConfig.js';

type ThemeColors = Record<'primary' | 'neutral', string>;

// Urutan asli customizer shadcn klasik
export const PRIMARY_COLORS = [
    'zinc',
    'slate',
    'stone',
    'gray',
    'neutral',
    'pink',
    'rose',
    'orange',
    'green',
    'blue',
    'yellow',
    'violet',
    'emerald',
    'lime',
    'indigo',
    'fuchsia',
] as const;

// shadcn "base color": hanya palet abu-abu
export const NEUTRAL_COLORS = ['neutral', 'gray', 'zinc', 'stone', 'slate', 'mauve', 'olive'] as const;

export const THEME_KEY = 'nuxt-ui-app-theme';

const ALLOWED: Record<keyof ThemeColors, readonly string[]> = {
    primary: PRIMARY_COLORS,
    neutral: NEUTRAL_COLORS,
};

// Warna dari build (vite.config.ts), dibaca sekali saat modul dievaluasi — yaitu
// sebelum nilai simpanan diterapkan, jadi ini benar-benar nilai awal.
const FALLBACK: ThemeColors = (() => {
    const { primary, neutral } = useAppConfig().ui.colors;
    return { primary, neutral };
})();

/**
 * Buang nilai yang bukan nama palet Tailwind, dan lengkapi field yang hilang.
 * localStorage bisa diedit user dan bisa datang dari tab lain lewat event `storage`;
 * nilai asing menghasilkan `var(--color-xxx-500, )` — CSS invalid yang merusak
 * SELURUH palet, bukan cuma satu warna.
 */
const sanitize = (raw: unknown): ThemeColors => {
    const value = (raw ?? {}) as Partial<Record<keyof ThemeColors, unknown>>;
    const pick = (key: keyof ThemeColors) => {
        const candidate = value[key];
        return typeof candidate === 'string' && ALLOWED[key].includes(candidate) ? candidate : FALLBACK[key];
    };
    return { primary: pick('primary'), neutral: pick('neutral') };
};

const theme = useStorage<ThemeColors>(THEME_KEY, FALLBACK, undefined, {
    // Jangan tulis apa pun sebelum user benar-benar memilih warna.
    writeDefaults: false,
    serializer: {
        read: (raw) => {
            try {
                return sanitize(JSON.parse(raw));
            } catch {
                return { ...FALLBACK }; // localStorage rusak
            }
        },
        write: JSON.stringify,
    },
});

/**
 * Satu-satunya penulis `appConfig.ui.colors`. Plugin runtime/plugins/colors.js punya
 * computed() di atas objek itu yang menulis ulang seluruh --ui-color-* lewat useHead,
 * jadi assign di sini langsung repaint tanpa reload.
 *
 * Dipanggil di main.ts sebelum app.use(ui): `immediate` membuat paint pertama sudah
 * pakai warna tersimpan, dan watcher yang sama menerapkan perubahan dari tab lain
 * (useStorage mendengarkan event `storage`).
 */
export function initTheme() {
    watch(theme, (value) => Object.assign(useAppConfig().ui.colors, value), {
        immediate: true,
        flush: 'sync',
    });
}

export function useTheme() {
    const bind = (key: keyof ThemeColors) =>
        computed<string>({
            get: () => theme.value[key],
            // Ganti objeknya, bukan mutasi properti: satu-arah UI → storage → appConfig.
            set: (value) => {
                theme.value = { ...theme.value, [key]: value };
            },
        });

    return { primary: bind('primary'), neutral: bind('neutral') };
}
