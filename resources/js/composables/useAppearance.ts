import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

export function resolveAppearance(value: Appearance): 'light' | 'dark' {
    if (value === 'system') {
        if (typeof window === 'undefined') return 'light';
        const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
        return mediaQueryList.matches ? 'dark' : 'light';
    }
    return value;
}

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }

    if (value === 'system') {
        const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
        const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.toggle('dark', systemTheme === 'dark');
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system
    const savedAppearance = getStoredAppearance();
    updateTheme(savedAppearance || 'system');

    // Set up system theme change listener
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const appearance = ref<'light' | 'dark'>('light');

    onMounted(() => {
        initializeTheme();

        const savedAppearance = localStorage.getItem('appearance') as Appearance | null;

        // Resolve 'system' ke nilai aktual dark/light
        appearance.value = resolveAppearance(savedAppearance || 'system');

        // Listen perubahan system theme secara real-time
        mediaQuery()?.addEventListener('change', () => {
            const currentAppearance = getStoredAppearance();

            // Hanya update jika preference-nya 'system' atau belum di-set
            if (currentAppearance === 'system' || !currentAppearance) {
                appearance.value = resolveAppearance('system');
            }
        });
    });

    function updateAppearance(value: Appearance) {
        // Simpan preferensi asli ke localStorage dan cookie (bisa 'system')
        localStorage.setItem('appearance', value);
        setCookie('appearance', value);

        // Apply theme ke DOM
        updateTheme(value);

        // appearance.value selalu resolved ke 'dark' | 'light'
        appearance.value = resolveAppearance(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
