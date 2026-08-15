import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import ui from '@nuxt/ui/vue-plugin';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { initTheme } from './composables/useTheme';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Initial custom theme colors, not from appConfig.ui.colors.
        initTheme();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ui)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: 'var(--ui-primary)',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
